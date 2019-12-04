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

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\Driver\Command;
use MongoDB\BSON\ObjectID;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Exception\InvalidArgumentException;
use IteratorIterator;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Error;
use phpformsframework\libs\Hook;
use phpformsframework\libs\storage\DatabaseDriver;

/**
 * ffDB_MongoDB è la classe preposta alla gestione della connessione con database di tipo SQL
 *
 * @package FormsFramework
 * @subpackage utils
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright &copy; 2000-2007, Samuele Diella
 * @license http://opensource.org/licenses/gpl-3.0.html
 * @link http://www.formsphpframework.com
 */
class MongoDB extends DatabaseDriver
{
    private $replica                 = null;

    /**
     * @var Manager|null
     */
    protected $link_id              = null;
    /**
     * @var IteratorIterator|null
     */
    protected $query_id             = null;

    private $query_params           = array();
    private $key_name				= "_id";

    /**
     * This method istantiate a ffDB_MongoDB instance. When using this
     * function, the resulting object will deeply use Forms Framework.
     *
     * @return MongoDB
     */
    public static function factory()
    {
        $tmp = new static();

        Hook::handle("mongodb_on_factory_done", $tmp);

        return $tmp;
    }

    public static function freeAll()
    {
        static::$_dbs = array();
    }

    /**
     * LIBERA LA CONNESSIONE E LA QUERY
     */
    private function cleanup()
    {
        $this->freeResult();
        $dbkey = $this->host . "|" . $this->user . "|" . $this->replica;
        if (isset(static::$_dbs[$dbkey])) {
            unset(static::$_dbs[$dbkey]);
        }

        $this->link_id                  = null;
        $this->errno                    = 0;
        $this->error                    = "";
    }

    /**
     * LIBERA LA RISORSA DELLA QUERY SENZA CHIUDERE LA CONNESSIONE
     */
    private function freeResult()
    {
        $this->query_id                 = null;
        $this->query_params             = array();
        $this->row                      = -1;
        $this->record                   = false;
        $this->num_rows                 = null;
        $this->fields                   = array();
        $this->buffered_insert_id       = null;
    }

    // -----------------------------------------------
    //  FUNZIONI PER LA GESTIONE DELLA CONNESSIONE/DB

    // GESTIONE DELLA CONNESSIONE

    /**
     * Gestisce la connessione al DB
     * @param string|null $Database
     * @param string|null $Host
     * @param string|null $User
     * @param string|null $Secret
     * @param bool $replica
     * @return bool
     */
    public function connect(string $Database = null, string $Host = null, string $User = null, string $Secret = null, $replica = false) : bool
    {
        if ($Host && $Database) {
            $this->cleanup();

            $this->database                         = $Database;
            $this->host                             = $Host;
            $this->user                             = $User;
            $this->secret                           = $Secret;
            $this->replica                          = $replica;
        }

        $dbkey = $this->host . "|" . $this->user . "|" . $this->replica;
        if (isset(static::$_dbs[$dbkey])) {
            $this->link_id =& static::$_dbs[$dbkey];
        }

        $auth                                       = (
            $this->user && $this->secret
                                                        ? $this->user . ":" . $this->secret . "@"
                                                        : ""
                                                    );

        if (!$this->link_id) {
            if (class_exists("\MongoDB\Driver\Manager")) {
                $this->link_id = new Manager(
                    "mongodb://"
                    . $auth
                    . $this->host
                    . "/"
                    . $this->database
                    . (
                        $this->replica
                        ? "?replicaSet=" . $this->replica
                        : ""
                    )
                );

                if (!$this->link_id) {
                    $this->cleanup();
                    $this->errorHandler("Connection failed to host " . $this->host);
                    return false;
                }
            } else {
                $this->errorHandler("Class not found: MongoDB\Driver\Manager");
                return false;
            }

            static::$_dbs[$dbkey] = $this->link_id;
            $this->link_id =& static::$_dbs[$dbkey];
        }

        return is_object($this->link_id);
    }

    /**
     * Esegue una query senza restituire un recordset
     * @param array $query
     * @param null|string $table
     * @return boolean
     */
    public function insert(array $query, string $table = null) : bool
    {
        return $this->getQuery($query, $table, "insert");
    }

    /**
     * @param array $query
     * @param string|null $table
     * @return bool
     */
    public function update(array $query, string $table = null) : bool
    {
        return $this->getQuery($query, $table, "update");
    }

    /**
     * @param array $query
     * @param string|null $table
     * @return bool
     */
    public function delete(array $query, string $table = null) : bool
    {
        return $this->getQuery($query, $table, "delete");
    }

    /**
     * @param array $query
     * @param string $table
     * @param string $action
     * @return bool
     */
    private function getQuery(array $query, string $table, string $action) : bool
    {
        if ($action == 'update') {
            $mongoDB            = $query;
        } else {
            $mongoDB[$action]   = $query;
        }

        $mongoDB["action"]      = $action;
        $mongoDB["table"]       = $table;

        return $this->execute($mongoDB);
    }

    /**
     * @param array $query
     * @return bool
     */
    private function execute(array $query) : bool
    {
        if (empty($query)) {
            $this->errorHandler("Execute invoked With blank Query String");
            return false;
        }
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        $mongoDB = $query;
        if (!isset($mongoDB["action"])) {
            if (!empty($mongoDB["insert"])) {
                $mongoDB["action"] = "insert";
            } elseif (!empty($mongoDB["set"])) {
                $mongoDB["action"] = "update";
            } elseif (!empty($mongoDB["delete"])) {
                $mongoDB["action"] = "delete";
            } elseif (!empty($mongoDB["from"])) {
                $mongoDB["action"] = "select";
            }
        }

        Debug::dumpCaller($mongoDB);

        $this->freeResult();

        if (isset($mongoDB["where"][$this->key_name])) {
            $mongoDB["where"][$this->key_name] = $this->id2object($mongoDB["where"][$this->key_name]);
        }
        switch ($mongoDB["action"]) {
            case "insert":
                if (!isset($mongoDB["table"])) {
                    $mongoDB["table"] = $mongoDB["into"];
                }
                if (isset($mongoDB["table"]) && isset($mongoDB["insert"])) {
                    if (!isset($mongoDB["insert"][$this->key_name])) {
                        $mongoDB["insert"][$this->key_name] = $this->createObjectID();
                    }
                    $bulk = new BulkWrite();
                    $bulk->insert($mongoDB["insert"]);
                    try {
                        if (!$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk)) {
                            $this->errorHandler("MongoDB Insert: " . $this->error);
                            return false;
                        }
                    } catch (Exception $e) {
                        $this->errorHandler("MongoDB Insert: " . $e->getMessage());
                    }
                    $this->buffered_insert_id = $mongoDB["insert"][$this->key_name];
                }
                break;
            case "update":
                if (!isset($mongoDB["table"])) {
                    $mongoDB["table"] = $mongoDB["update"];
                }
                if (isset($mongoDB["table"]) && isset($mongoDB["set"])) {
                    $set = array();
                    foreach ($mongoDB["set"] as $key => $value) {
                        if (strpos($key, '$') === 0) {
                            $set[$key] = $value;
                        } else {
                            $set['$set'][$key] = $value;
                        }
                    }

                    if (!is_array($mongoDB["where"])) {
                        $mongoDB["where"] = array();
                    }

                    $mongoDB["options"]["multi"] = true;

                    $bulk = new BulkWrite();
                    $bulk->update($mongoDB["where"], $set, $mongoDB["options"]);
                    try {
                        if (!$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk)) {
                            $this->errorHandler("MongoDB Update: " . $this->error);
                            return false;
                        }
                    } catch (Exception $e) {
                        $this->errorHandler("MongoDB Update: " . $e->getMessage());
                        return false;
                    }
                }
                break;
            case "delete":
                if (!isset($mongoDB["table"])) {
                    $mongoDB["table"] = $mongoDB["delete"];
                }
                if (isset($mongoDB["table"]) && isset($mongoDB["where"])) {
                    if (!is_array($mongoDB["where"])) {
                        $mongoDB["where"] = array();
                    }
                    if (!is_array($mongoDB["options"])) {
                        $mongoDB["options"] = array();
                    }

                    $bulk = new BulkWrite();
                    $bulk->delete($mongoDB["where"], $mongoDB["options"]);
                    try {
                        if (!$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk)) {
                            $this->errorHandler("MongoDB Delete: " . $this->error);
                            return false;
                        }
                    } catch (Exception $e) {
                        $this->errorHandler("MongoDB Delete: " . $e->getMessage());
                        return false;
                    }
                }
                break;
            case "select":
            case "":
                $this->query($mongoDB);
                break;
            default:
        }

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
            return null;
        }

        if (class_exists("\MongoDB\Driver\Query")) {
            try {
                $cursor = $this->link_id->executeQuery($this->database . "." . $this->query_params["table"], new Query($this->query_params["where"], $this->query_params["options"]));
            } catch (Exception $e) {
                $this->errorHandler("Query failed: " . $e->getMessage());
                return null;
            }
            if (!$cursor) {
                $this->errorHandler("fetch_assoc_error");
                return null;
            } else {
                $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

                $res = $cursor->toArray();
                if ($this->query_params["key_primary"]) {
                    foreach ($res as $key => $value) {
                        $res[$key][$this->query_params["key_primary"]] = $this->objectID2string($res[$key][$this->key_name]);
                        unset($res[$key][$this->key_name]);
                    }
                }
            }
        } else {
            $this->errorHandler("Class not found: MongoDB\Driver\Query");
            return null;
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
     * @param array $query
     * @return bool
     */
    public function query(array $query) : bool
    {
        if (empty($query)) {
            $this->errorHandler("Query invoked With blank Query String");
            return false;
        }
        if (!$this->link_id && !$this->connect()) {
            return false;
        }
        Debug::dumpCaller($query);

        $this->freeResult();

        $mongoDB = $query;
        if (!isset($mongoDB["action"])) {
            if (!empty($mongoDB["from"])) {
                $mongoDB["action"] = "select";
            }

            if (!empty($mongoDB["insert"])) {
                $mongoDB["action"] = "insert";
            } elseif (!empty($mongoDB["set"])) {
                $mongoDB["action"] = "update";
            } elseif (!empty($mongoDB["delete"])) {
                $mongoDB["action"] = "delete";
            }
        }

        if (isset($mongoDB["where"][$this->key_name])) {
            $mongoDB["where"][$this->key_name] = $this->id2object($mongoDB["where"][$this->key_name]);
        }

        if (!isset($mongoDB['options'])) {
            $mongoDB['options'] = null;
        }

        switch ($mongoDB["action"]) {
            case "insert":
            case "update":
            case "delete":
                $this->execute($mongoDB);
                break;
            case "select":
            case "":
                if (!isset($mongoDB["table"])) {
                    $mongoDB["table"] = $mongoDB["from"];
                }
                if (isset($mongoDB["table"])) {
                    $this->query_params = array(
                        "table" => $mongoDB["table"]
                        , "where" => (
                            $mongoDB["where"]
                            ? $mongoDB["where"]
                            : array()
                        )
                        , "options" => (
                            $mongoDB["options"]
                            ? $mongoDB["options"]
                            : array()
                        )
                    );

                    $this->query_params["key_primary"] = (
                        empty($mongoDB["key_primary"])
                        ? null
                        : $mongoDB["key_primary"]
                    );

                    if (isset($mongoDB["select"])) {
                        $this->query_params["options"]["projection"] = $mongoDB["select"];
                    }
                    if (isset($mongoDB["sort"])) {
                        $this->query_params["options"]["sort"] = $mongoDB["sort"];
                    }
                    if (isset($mongoDB["limit"])) {
                        $this->query_params["options"]["limit"] = $mongoDB["limit"];
                    }
                    if (isset($mongoDB["offset"])) {
                        $this->query_params["options"]["skip"] = $mongoDB["offset"];
                    }

                    if (class_exists("\MongoDB\Driver\Query")) {
                        try {
                            $cursor = $this->link_id->executeQuery($this->database . "." . $this->query_params["table"], new Query($this->query_params["where"], $this->query_params["options"]));
                        } catch (Exception $e) {
                            $this->errorHandler("Query failed: " . $e->getMessage());
                            return false;
                        }
                        if (!$cursor) {
                            $this->errorHandler("Invalid SQL: " . print_r($query, true));
                            return false;
                        } else {
                            $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

                            $this->query_id = new IteratorIterator($cursor);
                            $this->query_id->rewind(); // Very important
                        }
                    } else {
                        $this->errorHandler("Class not found: MongoDB\Driver\Query");
                        return false;
                    }
                }
                break;
            default:
        }

        return is_object($this->query_id);
    }

    /**
     * @param string $name
     * @param array $query
     * @return array|null
     */
    public function cmd(string $name = "count", array $query = null) : ?array
    {
        $res = null;
        if (empty($query)) {
            $this->errorHandler("Query invoked With blank Query String");
        }
        if ($this->link_id || $this->connect()) {
            Debug::dumpCaller($query);

            $this->freeResult();

            $mongoDB = $query;

            if (isset($mongoDB["where"][$this->key_name])) {
                $mongoDB["where"][$this->key_name] = $this->id2object($mongoDB["where"][$this->key_name]);
            }
            if (!isset($mongoDB["table"])) {
                $mongoDB["table"] = $mongoDB["from"];
            }
            if (isset($mongoDB["table"])) {
                switch ($name) {
                    case "count":
                        $res["count"] = $this->numRows();
                        break;
                    case "processlist":
                        //@todo: da implementare
                        $res = null;
                        break;
                    default:
                        $this->errorHandler("Command not supported");
                }
            }
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
        if (!$this->link_id && !$this->connect()) {
            return null;
        }

        $queryId = array();
        if (is_array($queries) && count($queries)) {
            Debug::dumpCaller($queries);
            $this->freeResult();

            foreach ($queries as $query) {
                if (empty($query)) {
                    $this->errorHandler("Query invoked With blank Query String");
                    return null;
                }

                if (is_array($query)) {
                    $mongoDB = $query;
                } else {
                    $mongoDB = $this->sql2mongoDB($query);
                }

                switch ($mongoDB["action"]) {
                    case "insert":
                        $bulk = new BulkWrite();
                        $bulk->insert($query);
                        if (!$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk)) {
                            $this->errorHandler("MongoDB Insert: " . $this->error);
                            return null;
                        }
                        break;
                    case "update":
                        $bulk = new BulkWrite();
                        $bulk->update($query["where"], $query["set"]);
                        if (!$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk)) {
                            $this->errorHandler("MongoDB Update: " . $this->error);
                            return null;
                        }
                        break;
                    case "delete":
                        $bulk = new BulkWrite();
                        $bulk->delete($query);
                        if (!$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk)) {
                            $this->errorHandler("MongoDB Delete: " . $this->error);
                            return null;
                        }
                        break;
                    case "select":
                    case "":
                        if (class_exists("\MongoDB\Driver\Query")) {
                            $cursor = $this->link_id->executeQuery($this->database . "." . $mongoDB["table"], new Query($mongoDB["sql"]));
                            if (!$cursor) {
                                $this->errorHandler("Invalid SQL: " . print_r($query, true));
                                return null;
                            } else {
                                $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

                                $this->query_id = new IteratorIterator($cursor);
                                $this->query_id->rewind(); // Very important
                                $this->num_rows = iterator_count(new IteratorIterator($this->link_id->executeQuery($this->database . "." . $mongoDB["table"], new Query($mongoDB["select"]))));
                            }
                        } else {
                            $this->errorHandler("Class not found: MongoDB\Driver\Query");
                            return null;
                        }
                        break;
                    default:
                }

                $queryId[] = $this->query_id;
            }
        }

        return $queryId;
    }

    /**
     * Sposta il puntatore al DB al record successivo (va chiamato almeno una volta)
     * @return boolean
     */
    private function getRecord()
    {
        $this->record                       = $this->query_id->current();

        return (bool) $this->record;
    }

    /**
     * @param object|null $obj
     * @return bool
     */
    public function nextRecord(object &$obj = null) : bool
    {
        if (!$this->query_id) {
            $this->errorHandler("nextRecord called with no query pending");
            return false;
        }

        if ($this->getRecord()) {
            $this->row += 1;
            $this->query_id->next();
            return true;
        } else {
            return false;
        }
    }



    /**
     * Conta il numero di righe
     * @return int
     */
    public function numRows() : int
    {
        if ($this->num_rows === null) {
            try {
                $Command = new Command(array(
                    "count" => $this->query_params["table"]
                , "query" => $this->query_params["where"]
                ));
                $cursor = $this->link_id->executeCommand($this->database, $Command);
                $this->num_rows = $cursor->toArray()[0]->n;
            } catch (Exception $e) {
                $this->errorHandler("Class not found: MongoDB\Driver\Command");
                return null;
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

        return $this->objectID2string($this->buffered_insert_id);
    }

    /**
     * @param ObjectID|string $var
     * @return string
     */
    private function objectID2string($var)
    {
        return ($var instanceof ObjectID
            ? $var->__toString()
            : (string) $var
        );
    }

    /**
     * @param string $DataValue
     * @return string
     */
    protected function toSqlEscape(string $DataValue) : string
    {
        return $DataValue;
    }

    /**
     * @param string $msg
     */
    protected function errorHandler(string $msg) : void
    {
        Error::register("MongoDB(" . $this->database . ") - " . $msg . " #" . $this->errno . ": " . $this->error, static::ERROR_BUCKET);
    }

    /**
     * @return ObjectID
     */
    private function createObjectID()
    {
        return new ObjectID();
    }

    /**
     * @param ObjectID|string $value
     * @return ObjectID|null
     */
    private function getObjectID($value) : ?ObjectID
    {
        if ($value instanceof ObjectID) {
            $res = $value;
        } else {
            try {
                $res = new ObjectID($value);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }
        return $res;
    }

    /**
     * @param array|ObjectID|string $keys
     * @return array|ObjectID|null
     */
    protected function id2object($keys)
    {
        $res = null;
        if (is_array($keys)) {
            foreach ($keys as $subkey => $subvalue) {
                if (is_array($subvalue)) {
                    foreach ($subvalue as $key) {
                        $ID = $this->getObjectID($key);
                        if ($ID) {
                            $res[$subkey][] = $ID;
                        }
                    }
                } else {
                    $ID = $this->getObjectID($subvalue);
                    if ($ID) {
                        $res[] = $ID;
                    }
                }
            }
        } else {
            $ID = $this->getObjectID($keys);
            if ($ID) {
                $res = $ID;
            }
        }

        return $res;
    }

    /**
     * Handle where
     * @param string $exp
     * @return string|null
     */
    private function equation2mg(string $exp) : ?string
    {
        # split operator
        $exp = trim($exp);
        if (!$exp) {
            return null;
        }

        $operator = null;
        $mg_equation = null;
        ### check for normal
        if (preg_match('/(\w+).*?([<>=!]+)(.*)/i', $exp, $matches)) {
            $operator = $matches[2];

        ### check for is null and such
        } elseif (preg_match('/([^ ]+) +?(is +?null|is +?not +?null|like) *?(.*)/i', $exp, $matches)) {
            $operator = strtolower($matches[2]);
        }

        $matches[3] = trim($matches[3]);
        if ($operator == '=') {
            $mg_equation = "{ " . $matches[1] . " : " . $matches[3] . " }";
        } elseif ($operator == '<') {
            $mg_equation = "{  " . $matches[1] . " : { '\$lt' : $matches[3] } }";
        } elseif ($operator == '>') {
            $mg_equation = "{  " . $matches[1] . " : { '\$gt' : $matches[3] } }";
        } elseif ($operator == '<=') {
            $mg_equation = "{  " . $matches[1] . " : { '\$lte' : $matches[3] } }";
        } elseif ($operator == '>=') {
            $mg_equation = "{  " . $matches[1] . " : { '\$gte' : $matches[3] } }";
        } elseif ($operator == 'is null') {
            $mg_equation = "{  " . $matches[1] . " : null }";
        } elseif ($operator == '!=') {
            $mg_equation = "{  " . $matches[1] . " : { '\$ne' : $matches[3] } }";
        } elseif ($operator == 'is not null') {
            $mg_equation = "{  " . $matches[1] . " : { '\$ne' : null } }";
        } elseif ($operator == 'like') {
            $a = $matches[1];
            $b = $matches[3];
            if (!preg_match('/[%_]/', $b)) {
                $mg_equation = "{ $a : $b }";
            } else {
                $b = trim($b);
                $b = preg_replace("/(^['\"]|['\"]$)/", "", $b); # 'text' -> text  or "text" -> text
                if (!preg_match("/^%/", $b)) {
                    $b = "^" . $b;
                }  # handles like 'text%' -> /^text/
                if (!preg_match("/%$/", $b)) {
                    $b .= "$";
                }  # handles like '%text' -> /^text$/
                $b = preg_replace("/%/", ".*", $b);
                $b = preg_replace("/_/", ".", $b);
                $mg_equation = "{ $a : /$b/}";
            }
        } else {
            $this->errorHandler("Unknown operator '$operator' :  $exp");
            return null;
        }

        return $mg_equation;
    }

    /**
     * @param string $str
     * @return string|null
     */
    private function where2mg(string $str) : ?string
    {
        # Make infix stuff to polishstuff
        $arr = preg_split('/ *?(\() *?| *?(\)) *?| +(and) +| +(or) +| +(not) +|(not)/i', $str, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $stack = array();
        $polish = array();

        foreach ($arr as $val) {
            $val = trim($val);
            switch (strtolower($val)) {

                case 'not':
                    $stack[]= strtolower($val);
                    break;
                case 'or':
                    while ((sizeof($stack) > 0) && ($stack[sizeof($stack) - 1] == 'and')) {
                        $e = array_pop($stack);
                        $polish[] = $e;
                    }
                    $stack[]= strtolower($val);
                    break;

                case 'and':
                    $stack[]= strtolower($val);
                    break;

                case '(':
                    $stack[]= $val;
                    break;

                case ')':
                    while ((sizeof($stack) > 0) && ($stack[sizeof($stack) - 1] != '(')) {
                        $e = array_pop($stack);
                        $polish[] = $e;
                    }
                    array_pop($stack);

                    break;
                default:
                    ### handle not
                    $polish[] = $val;

                    while ((sizeof($stack) > 0) && $stack[sizeof($stack) - 1] == 'not') {
                        $e = array_pop($stack);
                        $polish[] = $e;
                    }

                    break;

            }
        }

        # empty stack
        while (sizeof($stack) > 0) {
            $e = array_pop($stack);
            $polish[] = $e;
        }


        #### Polish stuff to mongo
        $cnt=0;
        $popcount = 2;
        $tmparr=array();
        $rs = null;
        $mgoper = null;
        foreach ($polish as $val) {
            $cnt++;
            switch (strtolower($val)) {
                case 'and':
                case 'not':
                case 'or':

                    if ($val == 'or') {
                        $mgoper = '$or';
                    }
                    if ($val == 'and') {
                        $mgoper = '$and';
                    }
                    if ($val == 'not') {
                        $mgoper = '$not';
                        $popcount = 1;
                    }

                    if ($val == $polish[$cnt] && $popcount > 1) {
                        $popcount++;
                        continue;
                    }

                    $tmparr2 = array();
                    for ($i = 1; $i<=$popcount; $i++) {
                        $tmparr2[]=array_pop($tmparr);
                    }
                    $operstring = join(", ", array_reverse($tmparr2));

                    if ($popcount==1) {
                        $e = $operstring;
                        if ($val == 'not') {
                            $e = preg_replace('/{ (\w+) : ([\w\'"]+) }/', '{ $1 : { $ne : $2 } }', $e);

                            if ($e == $operstring) {
                                $e = preg_replace('/{ (\w+) : { \$ne : ([\w\'"]+) } }/', '{ $1 : $2 }', $e);
                            }
                            echo $e;
                        } else {
                            $e = " { $mgoper : $operstring } ..";
                        }
                        $tmparr[] = $e;
                        $popcount=2;
                        continue;
                    } else {
                        $e = " { $mgoper : [  $operstring ] } ";
                    }

                    $popcount=2;

                    if (isset($polish[$cnt])) { # if stuff is still left
                        # push it back
                        $tmparr[] = $e;
                    } else {
                        # only called once
                        $rs .= $e;
                    }

                break;

                default:
                    $tmparr[] = $this->equation2mg($val);
                    break;
            }
        }
        foreach ($tmparr as $val) {
            $rs .= $val;
        }

        return $rs;
    }

    /**
     * @param string $sql
     * @return array|null
     */
    private function sql2mongoDB(string $sql) : ?array
    {
        $mg_collection = null;
        $mg_fields = null;
        $mg_skip = null;
        $mg_sort = null;
        $mg_limit = null;
        # make as oneline
        $sql = stripslashes(trim($sql));
        $sql = preg_replace('/\r\n?/', ' ', ($sql));
        # remove ending ;'s
        $sql = preg_replace('/;+$/', '', ($sql));



        preg_match('/^(\w+) /i', $sql, $querytype);
        $query['querytype'] = strtolower($querytype[1]);

        ### If select
        if ($query['querytype'] == "insert") {
            $query['action'] = "insert";
            $this->errorHandler("insert not supported yet");
            return null;
        } elseif ($query['querytype'] == "update") {
            $query['action'] = "update";
            $this->errorHandler("update not supported yet");
            return null;
        } elseif ($query['querytype'] == "delete") {
            $query['action'] = "delete";
            $this->errorHandler("delete not supported yet");
            return null;
        } elseif ($query['querytype'] == "select") {
            $query['action'] = "select";
            $findcommand = "find";

            preg_match('/select *?(.*?)from/i', $sql, $fields);
            preg_match('/select.*?from(.*?)($|where|group.*?by|order.*?by|limit|$)/i', $sql, $tables);
            preg_match('/select.*?from.*?where(.*?)(group.*?by|order.*?by|limit|$)/i', $sql, $where);
            preg_match('/select.*?from.*?(where|group.*?by|order.*?by|.*?).*?limit (.*?)$/i', $sql, $limit);
            preg_match('/select.*?from.*?order.*?by(.*?)(limit|$)/i', $sql, $orderby);


            $query['fields'] = explode(',', $fields[1]);
            $query['tables'] = explode(',', $tables[1]);
            if ($where[1]) {
                $query['where'][] = $where[1];
            }
            if ($limit[2]) {
                $query['limit'] = $limit[2];
            }
            if ($orderby[1]) {
                $query['orderby'] = $orderby[1];
            }

            ### Handle fields
            # remove spaces
            $mg_count = null;
            $mg_where = null;
            $mg_distinct = null;
            $tmpfields = null;
            foreach ($query['fields'] as $key => $value) {
                if (preg_match('/count\((.*?)\)/i', $value, $countmatch)) {
                    # Special fields
                    $mg_count .= ".count()";
                    $countfield = $countmatch[1];
                    if ($countfield != "*") {
                        $mg_where .= " { $countfield : { '\$exists' : true } } ";
                    }
                } elseif (preg_match('/distinct (.*?) *$/i', $value, $distinctmatch)) {
                    $distinctfield = $distinctmatch[1];
                    $mg_distinct .= ".distinct('$distinctfield') (not working)";
                } else {
                    # normal fields
                    $query['fields'][$key] = trim($value);
                    $tmpfields[]= trim($value);
                }
            }
            $query['fields'] = $tmpfields;
            if (sizeof($query['fields']) > 1 && $query['fields'][0] != '*' && is_array($query['fields']) && sizeof($query['fields']) > 1) {
                $mg_fields = ',{' . join(':1,', $query['fields']) . ':1}';
            }

            ### Handle table

            if (sizeof($query['tables']) > 1) {
                $this->errorHandler("only one table for now");
                return null;
            } else {
                $mg_collection = trim($query['tables'][0]);
            }


            ### Handle where
            if (is_array($query['where'])) {
                foreach ($query['where'] as $key) {
                    # split operator
                    $mg_where .= $this->where2mg($key);
                }
            }

            ### Handle order by

            if ($query['orderby']) {
                $orderby = trim($query['orderby']);
                $arr = preg_split("/ +/", $orderby);
                $orderfield = $arr[0];
                $ordersort = strtolower($arr[1]);
                $mg_sort = '.sort( { ' . $orderfield . ' : ';

                if ($ordersort == 'asc') {
                    $mg_sort .= "1";
                } elseif ($ordersort == 'desc') {
                    $mg_sort .= "-1";
                } else {
                    $this->errorHandler("desc or asc missing $mg_sort $orderby");
                    return null;
                }
                $mg_sort .= " } )";
            }


            ### Handle limit
            $skipvalue = null;
            if ($query['limit']) {
                $limits = explode(',', $query['limit']);
                $limitcnt=0;
                $rowstofind = 0;
                foreach ($limits as $value) {
                    $value = trim($value);
                    $limitcnt++;
                    if ($limitcnt == 2) {
                        $mg_skip = ".skip($value)";
                        $mg_limit = ".limit($skipvalue)";
                        $rowstofind = $skipvalue;
                    } else {
                        $mg_limit = ".limit($value)";
                        $skipvalue = $value;
                        $rowstofind = $skipvalue;
                    }
                }
                if ($rowstofind==1) {
                    $findcommand = "findOne";
                } else {
                    $findcommand = "find";
                }
            }
            $query["table"] = $mg_collection;
            $query["json"] = "db.$mg_collection.$findcommand( $mg_where$mg_fields )$mg_distinct$mg_count$mg_skip$mg_sort$mg_limit";
        } else {
            $query['action'] = $query['querytype'];
            $this->errorHandler("unsupported querytype for the time being: " . $query['querytype']);
            return null;
        }

        return $query;
    }
}
