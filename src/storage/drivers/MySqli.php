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

use phpformsframework\libs\Debug;
use phpformsframework\libs\Hook;
use phpformsframework\libs\Error;
use mysqli_result;
use phpformsframework\libs\storage\DatabaseDriver;

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

    public $field_primary           = null;

    public $avoid_real_connect      = true;
    public $buffered_affected_rows  = null;

    public $reconnect               = true;
    public $reconnect_tryed         = false;

    private $use_found_rows         = false;

    /**
     * This method istantiate a ffDb_Sql instance. When using this
     * function, the resulting object will deeply use Forms Framework.
     *
     * @return MySqli
     */
    public static function factory()
    {
        $tmp = new static();

        Hook::handle("mysqli_on_factory_done", $tmp);

        return $tmp;
    }

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
    private function freeResult()
    {
        if (is_object($this->query_id)) {
            @mysqli_free_result($this->query_id);
        }
        $this->query_id		            = null;
        $this->row			            = -1;
        $this->record		            = false;
        $this->num_rows		            = null;
        $this->fields		            = array();
        $this->fields_names	            = array();
        $this->field_primary            = null;
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
                    return false;
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
                return false;
            }

            if ($this->charset !== null) {
                @mysqli_set_charset($this->link_id, $this->charset);
            }

            static::$_dbs[$dbkey]       = $this->link_id;
            $this->link_id              =& static::$_dbs[$dbkey];
        }

        return is_object($this->link_id);
    }

    /**
     * @param array $query
     * @param string|null $table
     * @return bool
     */
    public function insert(array $query, string $table = null) : bool
    {
        $Query_String                   = "INSERT INTO " .  $query["from"] . "
                                            (
                                                " . $query["insert"]["head"] . "
                                            ) VALUES (
                                                " . $query["insert"]["body"] . "
                                            )";

        return $this->execute($Query_String);
    }

    /**
     * @param array $query
     * @param string|null $table
     * @return bool
     */
    public function update(array $query, string $table = null) : bool
    {
        $Query_String                   = "UPDATE " . $query["from"] . " SET 
                                                " . $query["update"] . "
                                            WHERE " . $query["where"];

        return $this->execute($Query_String);
    }

    /**
     * @param array $query
     * @param string|null $table
     * @return bool
     */
    public function delete(array $query, string $table = null) : bool
    {
        $Query_String                   = "DELETE FROM " .  $query["from"] . "  
                                            WHERE " . $query["where"];

        return $this->execute($Query_String);
    }

    /**
     * Esegue una query senza restituire un recordset
     * @param string $Query_String
     * @return bool
     */
    private function execute(string $Query_String) : bool
    {
        if ($Query_String == "") {
            $this->errorHandler("Execute invoked With blank Query String");
            return false;
        }
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        Debug::dumpCaller($Query_String);

        $this->freeResult();

        $this->query_id = @mysqli_query($this->link_id, $Query_String);
        if ($this->checkError()) {
            $this->errorHandler("Invalid SQL: " . $Query_String);
            return false;
        }

        $this->buffered_affected_rows = @mysqli_affected_rows($this->link_id);
        $this->buffered_insert_id = @mysqli_insert_id($this->link_id);

        return true;
    }

    /**
     * @param object|null $obj
     * @return array|object|null
     */
    public function getRecordset(object $obj = null)
    {
        $res = null;
        if (!$this->query_id) {
            $this->errorHandler("eachAll called with no query pending");
            return null;
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

        mysqli_free_result($this->query_id);

        if ($res === null && $this->checkError()) {
            $this->errorHandler("fetch_assoc_error");
            return null;
        }
        return $res;
    }

    /**
     * @return array
     */
    public function getFieldset() : array
    {
        return $this->fields_names;
    }

    /**
     * @param array $query
     * @return string
     */
    private function querySelect(array $query) : string
    {
        return "SELECT " . (
            !empty($query["calc_found_rows"])
                ? " SQL_CALC_FOUND_ROWS "
                : ""
            ) . $query["select"];
    }

    /**
     * @param array $query
     * @return string|null
     */
    private function queryFrom(array $query) : ?string
    {
        return (isset($query["from"])
            ? " FROM " .  $query["from"]
            : null
        );
    }

    /**
     * @param array $query
     * @return string|null
     */
    private function queryWhere(array $query) : ?string
    {
        return (isset($query["where"])
            ? " WHERE " . $query["where"]
            : null
        );
    }

    /**
     * @param array $query
     * @return string|null
     */
    private function querySort(array $query) : ?string
    {
        return (isset($query["sort"])
            ? " ORDER BY " . $query["sort"]
            : null
        );
    }

    /**
     * @param array $query
     * @return string|null
     */
    private function queryLimit(array $query) : ?string
    {
        return (
                isset($query["limit"])
                ? " LIMIT " . $query["limit"]
                : null
            ) . (
                isset($query["offset"])
                ? " OFFSET " . $query["offset"]
                : null
            );
    }

    /**
     * Esegue una query
     * @param array $query
     * @return bool
     */
    public function query(array $query) : bool
    {
        $Query_String                                       = $this->querySelect($query)    .
                                                            $this->queryFrom($query)        .
                                                            $this->queryWhere($query)       .
                                                            $this->querySort($query)        .
                                                            $this->queryLimit($query);

        $this->use_found_rows                               = strpos($Query_String, " SQL_CALC_FOUND_ROWS ") !== false;
        if ($Query_String == "") {
            $this->errorHandler("Query invoked With blank Query String");
            return false;
        }
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        Debug::dumpCaller($Query_String);

        $this->freeResult();

        $this->query_id = @mysqli_query($this->link_id, $Query_String);
        if (!$this->query_id || $this->checkError()) {
            $this->errorHandler("Invalid SQL: " . $Query_String);
            return false;
        } else {
            $finfo = mysqli_fetch_fields($this->query_id);
            foreach ($finfo as $meta) {
                $this->fields[$meta->name] = $meta;
                $this->fields_names[] = $meta->name;
                if ($meta->flags & MYSQLI_PRI_KEY_FLAG) {
                    $this->field_primary = $meta->name;
                }
            }
        }

        return is_object($this->query_id);
    }

    /**
     * @param string $name
     * @param array $query
     * @return mixed
     */
    public function cmd(string $name = "count", array $query = null)
    {
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        $res = null;
        switch ($name) {
            case "count":
                $query["select"] = "COUNT(ID) AS count";
                $this->query($query);
                if ($this->nextRecord()) {
                    $res = $this->record["count"];
                }
                break;
            case "calc_found_rows":
                $query["select"] = "FOUND_ROWS() AS count";
                $this->query($query);
                if ($this->nextRecord()) {
                    $res = $this->record["count"];
                }
                break;
            case "processlist":
                $query["select"] = "SHOW FULL PROCESSLIST";
                $this->query($query);
                $res = $this->getRecordset();
                break;
            default:
                $this->errorHandler("Command not supported");
                return false;
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

        $Query_String = implode("; ", $Query);

        Debug::dumpCaller($Query_String);
        $this->freeResult();

        mysqli_multi_query($this->link_id, $Query_String);
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
     * @param string $tabella
     * @param string|null $chiave
     * @param string|null $valorechiave
     * @param string|null $defaultvalue
     * @param string|null $nomecampo
     * @param string|null $tiporestituito
     * @param bool $bReturnPlain
     * @return mixed
     */
    public function lookup(string $tabella, string $chiave = null, string $valorechiave = null, string $defaultvalue = null, string $nomecampo = null, string $tiporestituito = null, bool $bReturnPlain = false)
    {
        if (!$this->link_id && !$this->connect()) {
            return null;
        }

        if ($tiporestituito === null) {
            $tiporestituito = "Text";
        }

        if (strpos(strtolower(trim($tabella)), "select") !== 0) {
            $listacampi = "";

            if (is_array($nomecampo)) {
                if (!count($nomecampo)) {
                    $this->errorHandler("lookup: Nuessun campo specificato da recuperare");
                    return null;
                }
                foreach ($nomecampo as $key => $value) {
                    if (strlen($listacampi)) {
                        $listacampi .= ", ";
                    }
                    $listacampi .= "`" . $key . "`";
                }
                reset($nomecampo);
            } elseif ($nomecampo !== null) {
                $listacampi = "`" . $nomecampo . "`";
            } else {
                $listacampi = "*";
            }
            $sSql = "SELECT " . $listacampi . " FROM " . $tabella . " WHERE 1 ";
        } else {
            $sSql = $tabella;
        }
        if (is_array($chiave)) {
            if (!count($chiave)) {
                $this->errorHandler("lookup: Nuessuna chiave specificata per il lookup");
                return null;
            }
            foreach ($chiave as $key => $value) {
                if (is_object($value) && get_class($value) != "Data") {
                    $this->errorHandler("lookup: Il valore delle chiavi dev'essere di tipo Data od un plain value");
                    return null;
                }
                $sSql .= " AND `" . $key . "` = " . $this->toSql($value);
            }
            reset($chiave);
        } elseif ($chiave != null) {
            if (is_object($valorechiave) && get_class($valorechiave) != "Data") {
                $this->errorHandler("lookup: Il valore della chiave dev'essere un oggetto Data od un plain value");
                return null;
            }
            $sSql .= " AND `" . $chiave . "` = " . $this->toSql($valorechiave);
        }

        $this->query($sSql);
        if ($this->nextRecord()) {
            if (is_array($nomecampo)) {
                $valori = array();
                if (!count($nomecampo)) {
                    $this->errorHandler("lookup: Nuessun campo specificato da recuperare");
                    return null;
                }
                foreach ($nomecampo as $key => $value) {
                    $valori[$key] = $this->getField($key, $value, $bReturnPlain);
                }
                reset($nomecampo);

                return $valori;
            } elseif ($nomecampo !== null) {
                return $this->getField($nomecampo, $tiporestituito, $bReturnPlain);
            } else {
                return $this->getField($this->fields_names[0], $tiporestituito, $bReturnPlain);
            }
        } else {
            return $defaultvalue;
        }
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
            return false;
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
            return false;
        }
    }

    /**
     * Conta il numero di righe
     * @return int|null
     */
    public function numRows() : ?int
    {
        if (!$this->query_id) {
            $this->errorHandler("numRows() called with no query pending");
            return null;
        }

        if ($this->num_rows === null) {
            if ($this->use_found_rows) {
                $db = new MySqli();
                $this->num_rows = $db->cmd("calc_found_rows");
            } else {
                $this->num_rows = @mysqli_num_rows($this->query_id);
            }
        }
        return $this->num_rows;
    }

    /**
     * @return string|null
     */
    public function getInsertID() : ?string
    {
        if (!$this->link_id) {
            $this->errorHandler("insert_id() called with no DB connection");
            return null;
        }

        return $this->buffered_insert_id;
    }

    /**
     * @param string $DataValue
     * @return string
     */
    protected function toSqlEscape(string $DataValue) : string
    {
        return mysqli_real_escape_string($this->link_id, $DataValue);
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

    /**
     * @param string $keys
     * @return string
     */
    protected function id2object($keys)
    {
        return $keys;
    }

    /**
     * @param string $value
     * @param string|null $type
     * @param bool $enclose
     * @return string|null
     */
    public function toSqlString(string $value, string $type = null, bool $enclose = true): ?string
    {
        return parent::toSqlString($this->toSqlEscape($value), $type, $enclose);
    }
}
