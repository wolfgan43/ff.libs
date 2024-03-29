<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs\storage\drivers;

use ff\libs\cache\Cashable;
use ff\libs\storage\DatabaseDriver;
use ff\libs\storage\DatabaseQuery;
use ff\libs\Exception;
use mysqli_result;

/**
 * classe preposta alla gestione della connessione con database di tipo SQL
 *
 * @package FormsFramework
 * @subpackage utils
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright &copy; 2000-2007, Samuele Diella
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @link http://www.formsphpframework.com
 */
class MySqli extends DatabaseDriver
{
    use Cashable;

    public const ENGINE             = "sql";
    protected const PREFIX          = "MYSQL_DATABASE_";
    private const WAIT_TIMEOUT      = 28000; //28800

    public $charset			        = "utf8";

    public $persistent				= false;

    /**
     * @var \mysqli|null
     */
    protected $link_id              = null;
    private $link_lifetime          = null;
    /**
     * @var mysqli_result|null
     */
    protected $query_id             = null;

    public $avoid_real_connect      = true;
    public $buffered_affected_rows  = null;

    private $use_found_rows         = false;

    /**
     * Kill All instance of DB
     */
    public static function freeAll() : void
    {
        foreach (self::$links as $link) {
            @mysqli_kill($link, mysqli_thread_id($link));
            @mysqli_close($link);
        }
        self::$links = [];
    }

    /**
     * LIBERA LA CONNESSIONE E LA QUERY
     */
    private function cleanup()
    {
        $this->freeResult();
        if (is_object($this->link_id) && !$this->persistent) {
            @mysqli_kill($this->link_id, mysqli_thread_id($this->link_id));
            @mysqli_close($this->link_id);

            if (isset(self::$links[$this->dbKey])) {
                unset(self::$links[$this->dbKey]);
            }
        }
        $this->link_id      = null;
        $this->link_lifetime= null;
        $this->errno        = 0;
        $this->error        = "";
    }
    /**
     * LIBERA LA RISORSA DELLA QUERY SENZA CHIUDERE LA CONNESSIONE
     */
    private function freeMemory()
    {
        if (is_object($this->query_id)) {
            @mysqli_free_result($this->query_id);
        }
        $this->query_id		            = null;
    }

    private function freeResult()
    {
        $this->freeMemory();
        $this->row			            = -1;
        $this->record		            = false;
        $this->num_rows		            = null;
        $this->fields		            = array();
        $this->buffered_affected_rows   = null;
        $this->buffered_insert_id       = null;
        $this->use_found_rows           = false;
    }

    /**
     * @param string|null $Database
     * @param string|null $Host
     * @param string|null $User
     * @param string|null $Secret
     * @return bool
     * @throws Exception
     */
    public function connect(string $Database = null, string $Host = null, string $User = null, string $Secret = null) : bool
    {
        if ($Host && $Database) {
            $this->cleanup();

            $this->database                         = $Database;
            $this->host                             = $Host;
            $this->user                             = $User;
            $this->secret                           = $Secret;
        }

        $this->dbKey                                = $this->host . "|" . $this->user . "|" . $this->database;
        if (isset(self::$links[$this->dbKey])) {
            $this->link_id =& self::$links[$this->dbKey];
        }

        if (!$this->link_id) {
            if (!$this->avoid_real_connect) {
                $this->link_id = @mysqli_init();
                if (!is_object($this->link_id) || $this->checkError()) {
                    $this->cleanup();
                    $this->errorHandler("mysqli::init failed");
                }

                if ($this->persistent) {
                    $rc = @mysqli_real_connect($this->link_id, "p:" . $this->host, $this->user, $this->secret, $this->database, null, null, MYSQLI_CLIENT_FOUND_ROWS);
                } else {
                    $rc = @mysqli_real_connect($this->link_id, $this->host, $this->user, $this->secret, $this->database, null, null, MYSQLI_CLIENT_FOUND_ROWS);
                }
            } else {
                if ($this->persistent) {
                    $this->link_id = @mysqli_connect("p:" . $this->host, $this->user, $this->secret, $this->database);
                } else {
                    $this->link_id = @mysqli_connect($this->host, $this->user, $this->secret, $this->database);
                }
                $rc = is_object($this->link_id);
            }

            if (!$rc || mysqli_connect_errno()) {
                $this->cleanup();
                $this->errorHandler("Connection failed to host " . $this->host);
            }

            if ($this->charset !== null) {
                @mysqli_set_charset($this->link_id, $this->charset);
            }

            self::$links[$this->dbKey]  = $this->link_id;
            $this->link_id              =& self::$links[$this->dbKey];

            if (defined("MYSQLI_OPT_INT_AND_FLOAT_NATIVE")) {
                mysqli_options($this->link_id, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
            }
            $this->link_lifetime = time();
        }

        return is_object($this->link_id);
    }

    /**
     * @param DatabaseQuery $query
     * @return bool
     * @throws Exception
     */
    public function read(DatabaseQuery $query) : bool
    {
        if (!$query->limit && $query->offset) {
            $query->limit                               = static::MAX_NUMROWS;
        }

        $query_string                                   = "SELECT " .
            (
                $query->calcFoundRows()
                ? " SQL_CALC_FOUND_ROWS "
                : ""
            ) . $query->select .
            " FROM " .  $query->from .
            (
                $query->where
                ? " WHERE " . $query->where
                : null
            ) .
            (
                $query->sort
                ? " ORDER BY " . $query->sort
                : null
            ) .
            (
                $query->limit
                ? " LIMIT " . $query->limit
                : null
            ) . (
                $query->offset
                ? " OFFSET " . $query->offset
                : null
            );
        return $this->query($query_string);
    }

    /**
     * @param DatabaseQuery $query
     * @return bool
     * @throws Exception
     */
    public function insert(DatabaseQuery $query) : bool
    {
        $this->execute("INSERT INTO " .  $query->from . "
            (
                " . $query->insert["head"] . "
            ) VALUES (
                " . $query->insert["body"] . "
            )
        ");

        return (bool) ($this->buffered_insert_id = @mysqli_insert_id($this->link_id));
    }

    /**
     * @param DatabaseQuery $query
     * @return bool
     * @throws Exception
     */
    public function update(DatabaseQuery $query) : bool
    {
        $this->execute(
            "UPDATE " . $query->from . " SET 
            " . $query->update .
            (
                $query->where
                ? " WHERE " . $query->where
                : null
            )
        );

        return (bool) ($this->buffered_affected_rows = @mysqli_affected_rows($this->link_id));
    }

    /**
     * @param DatabaseQuery $query
     * @return bool
     * @throws Exception
     */
    public function delete(DatabaseQuery $query) : bool
    {
        if (empty($query->where)) {
            $this->errorHandler("where not set");
        }

        $this->execute(
            "DELETE FROM " .  $query->from . "  
            WHERE " . $query->where
        );

        return (bool) ($this->buffered_affected_rows = @mysqli_affected_rows($this->link_id));
    }

    private function checkLinkWaitTimeout() : void
    {
        if ($this->link_lifetime !== null && time() - $this->link_lifetime >= self::WAIT_TIMEOUT) {
            $this->cleanup();
        }
    }

    /**
     * Esegue una query senza restituire un recordset
     * @param string $query
     * @return void
     * @throws Exception
     */
    private function execute(string $query) : void
    {
        $this->checkLinkWaitTimeout();
        if (!$this->link_id && !$this->connect()) {
            return;
        }

        $this->cacheSetProcess($query);

        $this->freeResult();

        $this->stopWatch(self::ERROR_BUCKET);
        try {
            $this->query_id = @mysqli_query($this->link_id, $query);
        } catch (\Exception $e) {
        }
        $this->stopWatch(self::ERROR_BUCKET);

        if ($this->checkError()) {
            $this->errorHandler("Invalid SQL: " . $query);
        }
    }

    /**
     * @return array|null
     */
    public function getRecord() : ?array
    {
        return $this->record;
    }

    /**
     * @return array|null
     * @throws Exception
     */
    public function getRecordset() : ?array
    {
        if (!$this->query_id) {
            $this->errorHandler("eachAll called with no query pending");
        }

        $res = mysqli_fetch_all($this->query_id, MYSQLI_ASSOC);
        if (isset($res[0])) {
            $this->fields                   = array_keys($res[0]);
        }

        if ($this->use_found_rows) {
            $this->numRows();
        } elseif (!empty($res)) {
            $this->num_rows                     = count($res);
        } else {
            $this->num_rows                     = 0;
        }

        $this->freeMemory();

        if ($res === null && $this->checkError()) {
            $this->errorHandler("fetch_assoc_error");
        }
        return $res;
    }

    /**
     * @return array
     */
    public function getFieldset() : array
    {
        if (!count($this->fields) && $this->record) {
            $this->fields                   = array_keys($this->record);
        }

        return $this->fields;
    }

    /**
     * Esegue una query
     * @param string $query
     * @return bool
     * @throws Exception
     */
    public function query($query) : bool
    {
        $this->checkLinkWaitTimeout();
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        $this->cacheSetProcess($query);

        $this->freeResult();

        $this->use_found_rows = strpos($query, " SQL_CALC_FOUND_ROWS ") !== false;

        $this->stopWatch(self::ERROR_BUCKET);
        try {
            $this->query_id = @mysqli_query($this->link_id, $query);
        } catch (\Exception $e) {
        }
        $this->stopWatch(self::ERROR_BUCKET);

        if (!$this->query_id || $this->checkError()) {
            $this->errorHandler("Invalid SQL: " . $query);
        }

        return is_object($this->query_id);
    }

    /**
     * @param DatabaseQuery $query
     * @param string $action
     * @return array|null
     * @throws Exception
     */
    public function cmd(DatabaseQuery $query, string $action = self::CMD_COUNT) : ?array
    {
        $res = null;
        switch ($action) {
            case self::CMD_COUNT:
                $query_string = "SELECT COUNT(`" . $query->key_primary . "`) AS `count`" .
                    " FROM " .  $query->from .
                    (
                        $query->where
                        ? " WHERE " . $query->where
                        : null
                    );
                if ($this->query($query_string)) {
                    $res = $this->getRecordset();
                }
                break;
            case self::CMD_PROCESS_LIST:
                $query_string = "SHOW FULL PROCESSLIST";
                if ($this->query($query_string)) {
                    $res = $this->getRecordset();
                }
                break;
            default:
                $this->errorHandler("Command not supported");
        }

        return $res;
    }

    /**
     * @param array $queries
     * @return array|null
     * @throws Exception
     */
    public function multiQuery(array $queries) : ?array
    {
        $this->checkLinkWaitTimeout();
        if (!$this->link_id && !$this->connect()) {
            return null;
        }

        $query_string = implode("; ", $queries);

        $this->cacheSetProcess($query_string);

        $this->freeResult();

        mysqli_multi_query($this->link_id, $query_string);
        $i = 0;
        $rc = null;
        do {
            $i++;
            $extraResult = mysqli_use_result($this->link_id);
            $rc |= $this->checkError();

            if ($extraResult instanceof mysqli_result) {
                $extraResult->free();
            }
        } while (mysqli_more_results($this->link_id) && (true | mysqli_next_result($this->link_id)));

        return array("rc" => !$rc, "ord" => $i);
    }

    /**
     * Sposta il puntatore al DB al record successivo (va chiamato almeno una volta)
     * @param object|null $obj
     * @return bool
     * @throws Exception
     */
    public function nextRecord(object &$obj = null) : bool
    {
        if (!$this->query_id) {
            $this->errorHandler("nextRecord called with no query pending");
        }

        // fetch assoc bug workaround...
        if ($this->row == ($this->numRows() - 1)) {
            return false;
        }
        if ($obj) {
            $this->record = @mysqli_fetch_object($this->query_id, $obj);
            $this->row += 1;
        } else {
            $this->record = @mysqli_fetch_assoc($this->query_id);
        }

        if ($this->record) {
            $this->row += 1;
            return true;
        } else {
            $this->freeMemory();
            return false;
        }
    }

    /**
     * Conta il numero di righe
     * @return int
     * @throws Exception
     */
    public function numRows() : int
    {
        if ($this->num_rows === null) {
            if ($this->use_found_rows) {
                $query_string = "SELECT FOUND_ROWS() AS count";
                $this->query($query_string);
                if ($this->nextRecord()) {
                    $this->num_rows = $this->record["count"];
                }
            } else {
                if (!$this->query_id) {
                    $this->errorHandler("numRows() called with no query pending");
                }

                $this->num_rows = @mysqli_num_rows($this->query_id);
            }
        }
        return $this->num_rows ?? 0;
    }

    /**
     * @param array|null $keys
     * @return array
     */
    public function getUpdatedIDs(array $keys = null) : array
    {
        return $keys ?? [];
    }

    /**
     * @param array|null $keys
     * @return array
     */
    public function getDeletedIDs(array $keys = null) : array
    {
        return $keys ?? [];
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function getInsertID() : ?string
    {
        if (!$this->link_id) {
            $this->errorHandler("insert_id() called with no DB connection");
        }

        return $this->buffered_insert_id;
    }

    /**
     * @param bool|float|int|string|array $DataValue
     * @return string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function toSqlEscape($DataValue) : string
    {
        if (!$this->link_id) {
            $this->errorHandler("toSqlEscape() called with no DB connection");
        }

        return mysqli_real_escape_string($this->link_id, str_replace("`", "", is_array($DataValue) ? implode(",", $DataValue) : $DataValue));
    }

    /**
     * @param string|null $value
     * @return string
     * @throws Exception
     */
    protected function convertID(string $value = null) : string
    {
        return $this->toSqlString($value);
    }

    /**
     * @param bool|float|int|string $value
     * @return string
     * @throws Exception
     */
    protected function toSqlString($value): string
    {
        return $this->tpSqlEscaper(parent::toSqlString($value));
    }

    /**
     * @param string $type
     * @param bool|float|int|string $value
     * @return bool|float|int|string
     * @throws Exception
     */
    protected function toSqlStringCast(string $type, $value)
    {
        if ($type == self::FTYPE_BOOLEAN || $type == self::FTYPE_BOOL) {
            return (int) (bool) $value;
        }

        return parent::toSqlStringCast($type, $value);
    }

    /**
     * @param string $type
     * @param array $Array
     * @param bool $castResult
     * @return string
     */
    protected function toSqlArray(string $type, array $Array, bool $castResult = false): string
    {
        $value = parent::toSqlArray($type, $Array, $castResult);
        if (is_array($value)) {
            return $this->toSqlArray2String($value);
        }

        return $this->tpSqlEscaper(
            $castResult || $type == self::FTYPE_ARRAY_JSON
                ? $value
                : str_replace(self::SEP, "'" . self::SEP . "'", $value)
        );
    }

    /**
     * @param array $values
     * @return string
     */
    private function toSqlArray2String(array $values) : string
    {
        $res = [];
        foreach ($values as $value) {
            if (is_null($value)) {
                continue;
            }

            $res[] = (
                is_bool($value) || is_int($value)
                ? $value
                : $this->tpSqlEscaper($value)
            );
        }
        return implode(self::SEP, $res);
    }

    /**
     * @param string $value
     * @return string
     */
    private function tpSqlEscaper(string $value) : string
    {
        return "'" . $value . "'";
    }

    /**
     * GESTIONE ERRORI
     * @return bool
     */
    private function checkError() : bool
    {
        if (is_object($this->link_id)) {
            $this->error = @mysqli_error($this->link_id);
            $this->errno = @mysqli_errno($this->link_id);

            return $this->errno !== 0;
        } else {
            return true;
        }
    }

    /**
     * @param string $msg
     * @throws Exception
     */
    protected function errorHandler(string $msg) : void
    {
        $this->checkError();

        throw new Exception("MySQL(" . $this->database . ") " . "#" . $this->errno . ": " . $this->error . " - " . $msg, 500);
    }
}
