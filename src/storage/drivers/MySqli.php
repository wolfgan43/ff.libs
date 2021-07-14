<?php
/**
 * VGallery: CMS based on FormsFramework
 * Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage core
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/gpl-3.0.html
 *  @link https://github.com/wolfgan43/vgallery
 */
namespace phpformsframework\libs\storage\drivers;

use phpformsframework\libs\cache\Cashable;
use phpformsframework\libs\Error;
use phpformsframework\libs\storage\DatabaseDriver;
use phpformsframework\libs\storage\DatabaseQuery;
use mysqli_result;

/**
 * classe preposta alla gestione della connessione con database di tipo SQL
 *
 * @package FormsFramework
 * @subpackage utils
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright &copy; 2000-2007, Samuele Diella
 * @license http://opensource.org/licenses/gpl-3.0.html
 * @link http://www.formsphpframework.com
 */
class MySqli extends DatabaseDriver
{
    use Cashable;

    public $charset			        = "utf8";
    public $charset_names		    = "utf8";
    public $charset_collation	    = "utf8_unicode_ci";

    public $persistent				= false;

    /**
     * @var \mysqli|null
     */
    protected $link_id              = null;
    /**
     * @var mysqli_result|null
     */
    protected $query_id             = null;

    public $avoid_real_connect      = true;
    public $buffered_affected_rows  = null;

    public $reconnect               = true;
    public $reconnect_tryed         = false;

    private $use_found_rows         = false;

    /**
     * Kill All instance of DB
     */
    public static function freeAll()
    {
        foreach (static::$_dbs as $link) {
            @mysqli_kill($link, mysqli_thread_id($link));
            @mysqli_close($link);
        }
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
            $dbkey = $this->host . "|" . $this->user . "|" . $this->secret;
            if (isset(static::$_dbs[$dbkey])) {
                unset(static::$_dbs[$dbkey]);
            }
        }
        $this->link_id      = null;
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

        $dbkey = $this->host . "|" . $this->user . "|" . $this->database;
        if (isset(static::$_dbs[$dbkey])) {
            $this->link_id =& static::$_dbs[$dbkey];
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

            static::$_dbs[$dbkey]       = $this->link_id;
            $this->link_id              =& static::$_dbs[$dbkey];

            mysqli_options($this->link_id, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
        }

        return is_object($this->link_id);
    }
    /**
     * @param DatabaseQuery $query
     * @return bool
     */
    public function read(DatabaseQuery $query) : bool
    {
        $count_num_rows                                 = $query->countRecords();
        $query_string                                   = "SELECT " .
            (
                $count_num_rows
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
     */
    public function insert(DatabaseQuery $query) : bool
    {
        $query_string                   = "INSERT INTO " .  $query->from . "
                                            (
                                                " . $query->insert["head"] . "
                                            ) VALUES (
                                                " . $query->insert["body"] . "
                                            )";

        return $this->execute($query_string);
    }

    /**
     * @param DatabaseQuery $query
     * @return bool
     */
    public function update(DatabaseQuery $query) : bool
    {
        $query_string                   = "UPDATE " . $query->from . " SET 
                                                " . $query->update . "
                                            WHERE " . $query->where;

        return $this->execute($query_string);
    }

    /**
     * @param DatabaseQuery $query
     * @return bool
     */
    public function delete(DatabaseQuery $query) : bool
    {
        $query_string                   = "DELETE FROM " .  $query->from . "  
                                            WHERE " . $query->where;

        return $this->execute($query_string);
    }

    /**
     * Esegue una query senza restituire un recordset
     * @param string $query_string
     * @return bool
     */
    private function execute(string $query_string) : bool
    {
        if (!$query_string) {
            $this->errorHandler("Execute invoked With blank Query String");
        }
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        $this->cacheSetProcess($query_string);

        $this->freeResult();

        $this->query_id = @mysqli_query($this->link_id, $query_string);
        if ($this->checkError()) {
            $this->errorHandler("Invalid SQL: " . $query_string);
        }

        $this->buffered_affected_rows = @mysqli_affected_rows($this->link_id);
        $this->buffered_insert_id = @mysqli_insert_id($this->link_id);

        return true;
    }

    /**
     * @param object|null $obj
     * @return array|null
     */
    public function getRecordset(object &$obj = null) : ?array
    {
        $res = null;
        if (!$this->query_id) {
            $this->errorHandler("eachAll called with no query pending");
        }
        if ($obj != null) {
            $res = @mysqli_fetch_object($this->query_id, $obj);
        } else {
            $res = mysqli_fetch_all($this->query_id, MYSQLI_ASSOC);
        }
        if ($this->use_found_rows) {
            $this->numRows();
        } elseif (is_array($res)) {
            $this->num_rows = count($res);
        }
        if (isset($res[0])) {
            $this->fields                   = array_keys($res[0]);
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
     * @param string $query_string
     * @return bool
     */
    private function query($query_string) : bool
    {
        if (!$query_string) {
            $this->errorHandler("Query invoked With blank Query String");
        }
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        $this->cacheSetProcess($query_string);

        $this->freeResult();

        $this->use_found_rows                               = strpos($query_string, " SQL_CALC_FOUND_ROWS ") !== false;
        $this->query_id = @mysqli_query($this->link_id, $query_string);
        if (!$this->query_id || $this->checkError()) {
            $this->errorHandler("Invalid SQL: " . $query_string);
        }

        return is_object($this->query_id);
    }

    /**
     * @param DatabaseQuery $query
     * @param string $action
     * @return array|null
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
                    $res = $this->getRecordset()[0];
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
     * @param array $Query
     * @return array|null
     */
    public function multiQuery(array $Query) : ?array
    {
        if (!$this->link_id && !$this->connect()) {
            return null;
        }

        $query_string = implode("; ", $Query);

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
     * @return int|null
     */
    public function numRows() : ?int
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
        return $this->num_rows;
    }

    /**
     * @param array $keys
     * @return array|null
     */
    public function getUpdatedIDs(array $keys) : ?array
    {
        return $keys;
    }
    /**
     * @return string|null
     */
    public function getInsertID() : ?string
    {
        if (!$this->link_id) {
            $this->errorHandler("insert_id() called with no DB connection");
        }

        return $this->buffered_insert_id;
    }

    /**
     * @param string $DataValue|null
     * @return string|null
     */
    protected function toSqlEscape(string $DataValue = null) : ?string
    {
        return mysqli_real_escape_string($this->link_id, str_replace("`", "", $DataValue));
    }

    /**
     * @param string $value
     * @return string
     */
    protected function convertID(string $value) : string
    {
        return $value;
    }

    /**
     * @param string $type
     * @param string|null $value
     * @return string|null
     */
    protected function toSqlString(string $type, string $value = null): ?string
    {
        return $this->tpSqlEscaper(parent::toSqlString($type, $value));
    }

    /**
     * @param string $type
     * @param array $Array
     * @return string|null
     */
    protected function toSqlArray(string $type, array $Array): ?string
    {
        $value = parent::toSqlArray($type, $Array);
        if (is_array($value)) {
            $value = implode("','", $value);
        }

        return $this->tpSqlEscaper($value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function tpSqlEscaper(string $value = null) : string
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
     */
    protected function errorHandler(string $msg) : void
    {
        $this->checkError(); // this is needed due to params order

        Error::register("MySQL(" . $this->database . ") - " . $msg . " #" . $this->errno . ": " . $this->error, static::ERROR_BUCKET);
    }
}
