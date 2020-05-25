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
use IteratorIterator;
use phpformsframework\libs\cache\Cashable;
use phpformsframework\libs\Error;
use phpformsframework\libs\storage\DatabaseDriver;
use phpformsframework\libs\storage\DatabaseQuery;

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
    use Cashable;

    private $replica                 = null;

    /**
     * @var Manager|null
     */
    protected $link_id              = null;
    /**
     * @var IteratorIterator|null
     */
    protected $query_id             = null;

    /**
     * @var DatabaseQuery|null
     */
    private $query_params           = null;
    private $key_name				= "_id";

    private $use_found_rows         = false;

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
        $this->query_params             = null;
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
                }
            } else {
                $this->errorHandler("Class not found: MongoDB\Driver\Manager");
            }

            static::$_dbs[$dbkey] = $this->link_id;
            $this->link_id =& static::$_dbs[$dbkey];
        }

        return is_object($this->link_id);
    }

    /**
     * @param DatabaseQuery $query
     * @return boolean
     */
    public function read(DatabaseQuery $query) : bool
    {
        $query->action = self::ACTION_READ;
        return $this->query($query);
    }

    /**
     * @param DatabaseQuery $query
     * @return boolean
     */
    public function insert(DatabaseQuery $query) : bool
    {
        $query->action = self::ACTION_INSERT;
        return $this->query($query);
    }

    /**
     * @param DatabaseQuery $query
     * @return bool
     */
    public function update(DatabaseQuery $query) : bool
    {
        $query->action = self::ACTION_UPDATE;
        return $this->query($query);
    }

    /**
     * @param DatabaseQuery $query
     * @return bool
     */
    public function delete(DatabaseQuery $query) : bool
    {
        $query->action = self::ACTION_DELETE;
        return $this->query($query);
    }

    /**
     * @return array|null
     */
    public function getRecordset() :?array
    {
        $res = null;
        if (!$this->query_id) {
            $this->errorHandler("eachAll called with no query pending");
        }

        if (class_exists("\MongoDB\Driver\Query")) {
            $cursor = null;
            try {
                $cursor = $this->link_id->executeQuery($this->database . "." . $this->query_params->from, new Query($this->query_params->where, $this->query_params->options));
            } catch (Exception $e) {
                $this->errorHandler("Query failed: " . $e->getMessage());
            }
            if (!$cursor) {
                $this->errorHandler("fetch_assoc_error");
            } else {
                $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

                $res = $cursor->toArray();
                if (!$this->use_found_rows) {
                    $this->num_rows = count($res);
                }
            }
        } else {
            $this->errorHandler("Class not found: MongoDB\Driver\Query");
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
     * @param DatabaseQuery $query
     * @return bool
     */
    private function processQueryParams(DatabaseQuery $query) : bool
    {
        if (!$this->query_params) {
            if (empty($query->from)) {
                $this->errorHandler("table not set");
            }

            switch ($query->action) {
                case self::ACTION_READ:
                    if (!empty($query->select)) {
                        $query->options["projection"]                       = $query->select;
                    }
                    if (!empty($query->sort)) {
                        $query->options["sort"]                             = $query->sort;
                    }
                    if (!empty($query->limit)) {
                        $query->options["limit"]                            = $query->limit;
                        $this->use_found_rows                               = true;
                    }
                    if (isset($query->offset)) {
                        $query->options["skip"]                             = $query->offset;
                        $this->use_found_rows                               = true;
                    }
                    break;
                case self::ACTION_INSERT:
                    if (empty($query->insert)) {
                        $this->errorHandler("insert not set");
                    }
                    if (!isset($query->insert[$this->key_name])) {
                        $query->insert[$this->key_name]                     = $this->createObjectID();
                    }
                    break;
                case self::ACTION_UPDATE:
                    if (empty($query->update)) {
                        $this->errorHandler("update not set");
                    }
                    if (empty($query->where)) {
                        $this->errorHandler("where not set");
                    }

                    $query->options["multi"]                                = true;
                    break;
                case self::ACTION_DELETE:
                case self::ACTION_CMD:
                    if (empty($query->where)) {
                        $this->errorHandler("where not set");
                    }
                    break;
                default:
                    $this->errorHandler("Action not supported");
            }

            $this->query_params                                             = $query;
        }

        return true;
    }

    /**
     * Esegue una query
     * @param DatabaseQuery $query
     * @return bool
     */
    public function query($query) : bool
    {
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        $this->cacheSetProcess($query->toJson());

        $this->freeResult();

        $this->processQueryParams($query);
        switch ($this->query_params->action) {
            case self::ACTION_READ:
                if (class_exists("\MongoDB\Driver\Query")) {
                    $cursor = null;
                    try {
                        $cursor = $this->link_id->executeQuery($this->database . "." . $this->query_params->from, new Query($this->query_params->where, $this->query_params->options));
                    } catch (Exception $e) {
                        $this->errorHandler("Query failed: " . $e->getMessage());
                    }
                    if (!$cursor) {
                        $this->errorHandler("Invalid SQL: " . print_r($query->toArray(), true));
                    } else {
                        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

                        $this->query_id = new IteratorIterator($cursor);
                        $this->query_id->rewind(); // Very important
                    }
                } else {
                    $this->errorHandler("Class not found: MongoDB\Driver\Query");
                }
                break;
            case self::ACTION_INSERT:
                if (class_exists("MongoDB\Driver\BulkWrite")) {
                    $bulk = new BulkWrite();
                    $bulk->insert($this->query_params->insert);
                    if (!$this->link_id->executeBulkWrite($this->database . "." . $this->query_params->from, $bulk)) {
                        $this->errorHandler("MongoDB Insert: " . $this->error);
                    }
                    $this->buffered_insert_id = $this->query_params->insert[$this->key_name];
                } else {
                    $this->errorHandler("Class not found: MongoDB\Driver\BulkWrite");
                }
                break;
            case self::ACTION_UPDATE:
                if (class_exists("MongoDB\Driver\BulkWrite")) {
                    $bulk = new BulkWrite();
                    $bulk->update($this->query_params->where, $this->query_params->update, $this->query_params->options);
                    if (!$this->link_id->executeBulkWrite($this->database . "." . $this->query_params->from, $bulk)) {
                        $this->errorHandler("MongoDB Update: " . $this->error);
                    }
                } else {
                    $this->errorHandler("Class not found: MongoDB\Driver\BulkWrite");
                }
                break;
            case self::ACTION_DELETE:
                if (class_exists("MongoDB\Driver\BulkWrite")) {
                    $bulk = new BulkWrite();
                    $bulk->delete($this->query_params->where, $this->query_params->options);
                    if (!$this->link_id->executeBulkWrite($this->database . "." . $this->query_params->from, $bulk)) {
                        $this->errorHandler("MongoDB Delete: " . $this->error);
                    }
                } else {
                    $this->errorHandler("Class not found: MongoDB\Driver\BulkWrite");
                }
                break;
            default:
                $this->errorHandler("Action not supported");
        }

        return true;
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
                if ($this->processQueryParams($query)) {
                    $res[][self::CMD_COUNT] = $this->numRows();
                }
                break;
            case self::CMD_PROCESS_LIST:
                //@todo: da implementare
                $res = null;
                break;
            default:
                $this->errorHandler("Command not supported");
        }

        return $res;
    }

    /**
     * @param DatabaseQuery[] $queries
     * @return array|null
     */
    public function multiQuery(array $queries) : ?array
    {
        if (!$this->link_id && !$this->connect()) {
            return null;
        }

        $queryId = array();
        if (!empty($queries)) {
            foreach ($queries as $query) {
                if ($this->query($query)) {
                    $queryId[] = $this->getResult();
                }


            }
        }

        return $queryId;
    }

    /**
     * @return array
     */
    private function getResult() : array
    {
        switch ($this->query_params->action) {
            case self::ACTION_READ:
                $res = $this->getRecordset();
                break;
            case self::ACTION_INSERT:
                $res = $this->buffered_insert_id;
                break;
            default:
                $res = null;
        }

        return [$this->query_params->action => $res];
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
            if (class_exists("\MongoDB\Driver\Command")) {
                try {
                    $Command = new Command(array(
                        "count" => $this->query_params->from,
                        "query" => $this->query_params->where
                    ));
                    $cursor = $this->link_id->executeCommand($this->database, $Command);
                    $this->num_rows = $cursor->toArray()[0]->n;
                } catch (Exception $e) {
                    $this->errorHandler("Command failed: " . $e->getMessage());
                }
            } else {
                $this->errorHandler("Class not found: MongoDB\Driver\Command");
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
        return array_map(function ($value) {
            return (string) $value;
        }, $keys);
    }

    /**
     * @return string|null
     */
    public function getInsertID() : ?string
    {
        if (!$this->link_id) {
            $this->errorHandler("insert_id() called with no DB connection");
        }
        
        return (string) $this->buffered_insert_id;
    }

    /**
     * @param string $DataValue|null
     * @return string|null
     */
    protected function toSqlEscape(string $DataValue = null) : ?string
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
     * @param string $value
     * @return ObjectID
     */
    protected function convertID(string $value) : ObjectID
    {
        return $this->getObjectID($value);
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
     * @return ObjectID
     */
    private function getObjectID($value) : ObjectID
    {
        return ($value instanceof ObjectID
            ? $value
            : new ObjectID($value)
        );
    }
}
