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
namespace phpformsframework\libs\storage;

use phpformsframework\libs\Error;
use phpformsframework\libs\international\Data;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Log;
use phpformsframework\libs\Request;
use phpformsframework\libs\tpl\Gridsystem;
use phpformsframework\libs\security\Validator;
use Exception;

abstract class DatabaseAdapter
{
    protected const ERROR_BUCKET        = "database";

    protected const TYPE                = null;
    protected const PREFIX              = null;
    protected const KEY_NAME            = null;
    protected const KEY_REL             = "ID_";
    protected const KEY_IS_INT          = false;

    const MAX_NUMROWS                   = 10000;
    const MAX_RESULTS                   = 1000;

    const FTYPE_ARRAY                   = "array";
    const FTYPE_ARRAY_INCREMENTAL       = "arrayIncremental";
    const FTYPE_ARRAY_OF_NUMBER         = "arrayOfNumber";
    const FTYPE_BOOLEAN                 = "boolean";
    const FTYPE_BOOL                    = "bool";
    const FTYPE_DATE                    = "date";
    const FTYPE_NUMBER                  = "number";
    const FTYPE_TIMESTAMP               = "timestamp";
    const FTYPE_PRIMARY                 = "primary";
    const FTYPE_STRING                  = "string";
    const FTYPE_CHAR                    = "char";
    const FTYPE_TEXT                    = "text";

    const FTYPE_ARRAY_JSON              = "json";
    const FTYPE_NUMBER_BIG              = "bigint";
    const FTYPE_NUMBER_FLOAT            = "float";

    const FTYPE_NUMBER_DECIMAN          = "currency";

    const FTYPE_BLOB                    = "blob";

    const FTYPE_TIME                    = "time";
    const FTYPE_DATE_TIME               = "datetime";

    const FTYPE_OBJECT                  = "object";


    private $connection                 = array(
                                            "host"          => null
                                            , "username"    => null
                                            , "secret"      => null
                                            , "name"        => null
                                            , "prefix"		=> null
                                            , "table"       => null
                                            , "key"         => null
                                            , "key_rel"     => null
                                        );

    protected $key_name                 = null;
    protected $key_rel_prefix           = null;
    protected $key_rel_suffix           = null;
    protected $key_primary              = null;
    protected $struct					= null;
    protected $relationship			    = null;
    protected $indexes					= null;
    protected $table                    = null;
    protected $alias                    = null;

    private $select                     = null;
    private $insert                     = null;
    private $set                     	= null;
    private $where                      = null;
    private $sort                      	= null;
    private $limit                     	= null;

    private $exts                       = false;
    protected $rawdata                  = false;

    /**
     * @var DatabaseDriver
     */
    protected $driver                   = null;

    abstract protected function getDriver();
    abstract protected function convertFields($fields, $action);

    /**
     * @param array $query
     * @return array|null
     */
    abstract protected function processRead(array $query) : ?array;

    /**
     * @param array $query
     * @return array|null
     */
    abstract protected function processInsert(array $query) : ?array;

    /**
     * @param array $query
     * @return array|null
     */
    abstract protected function processUpdate(array $query) : ?array;

    /**
     * @param array $query
     * @return array|null
     */
    abstract protected function processDelete(array $query) : ?array;

    /**
     * @param array $query
     * @return array|null
     */
    abstract protected function processWrite(array $query) : ?array;

    /**
     * @todo da tipizzare
     * @param array $query
     * @return mixed
     */
    abstract protected function processCmd(array $query);

    /**
     * @param $mixed
     * @param string|null $type
     * @param bool $enclose
     * @return string|null
     */
    abstract public function toSql($mixed, string $type = null, bool $enclose = true) : ?string;

    public function __construct($connection = null, $table = null, $struct= null, $relationship = null, $indexes = null, $alias = null, $exts = false, $rawdata = false)
    {
        $this->connection               = $connection;
        $this->struct                   = $struct;
        $this->relationship             = $relationship;
        $this->indexes                  = $indexes;
        $this->alias                    = $alias;
        $this->exts                     = $exts;
        $this->rawdata                  = $rawdata;
        $this->setTable($table);

        $this->key_primary              = array_search(self::FTYPE_PRIMARY, $this->struct);
    }

    /**
     * @return bool
     */
    private function loadDriver() : bool
    {
        $connector                                      = $this->getConnector();
        if ($connector) {
            $this->driver                               = $this->getDriver();
            if ($this->driver->connect(
                $connector["name"],
                $connector["host"],
                $connector["username"],
                $connector["secret"]
            )) {
                $this->key_name                         = $connector["key"];
                if (strpos($connector["key_rel"], "_") === 0) {
                    $this->key_rel_prefix               = $connector["key_rel"];
                } else {
                    $this->key_rel_suffix               = $connector["key_rel"];
                }
            }
        }
        return (bool) $this->driver;
    }

    /**
     * @param array $query
     * @return array|null
     */
    protected function processRawQuery(array $query) : ?array
    {
        $res                                            = null;
        $success                                        = $this->driver->query($query);
        if ($success) {
            $res                                        = array(
                                                            "recordset"     => $this->driver->getRecordset(),
                                                            "fields"        => $this->driver->getFieldset(),
                                                            "count"         => $this->driver->numRows()
                                                        );
        }

        return $res;
    }

    /**
     * @return array
     */
    protected function getConnector() : array
    {
        $connector                                      = array();

        try {
            $env                                        = Kernel::$Environment;
            $prefix                                     = $env . '::' . (
                isset($this->connection["prefix"]) && defined($env . '::' . $this->connection["prefix"] . "NAME")
                ? $this->connection["prefix"]
                : static::PREFIX
            );

            $connector["host"]                          = constant($prefix . "HOST");
            $connector["username"]                      = constant($prefix . "USER");
            $connector["secret"]                        = constant($prefix . "SECRET");
            $connector["name"]                          = constant($prefix . "NAME");

            $connector["table"]                         = (
                empty($this->connection["table"])
                ? null
                : $this->connection["table"]
            );
            $connector["key"]                           = (
                empty($this->connection["key"])
                ? static::KEY_NAME
                : $this->connection["key"]
            );

            $key_rel                                    = (
                empty($this->connection["key_rel"])
                ? static::KEY_REL
                : $this->connection["key_rel"]
            );
            $connector["key_rel"]                       = $key_rel;

            if (!$this->table && $connector["table"]) {
                $this->setTable($connector["table"]);
            }

            if (empty($connector["name"])) {
                Error::register(static::TYPE . " database connection failed", static::ERROR_BUCKET);
            }
        } catch (Exception $e) {
            Error::register("Connection Params Missing: " . $e->getMessage(), static::ERROR_BUCKET);
        }

        return $connector;
    }

    /**
     * @param string $key
     * @return string|null
     */
    private function getTable(string $key) : ?string
    {
        return (isset($this->table[$key])
            ? $this->table[$key]
            : null
        );
    }

    /**
     * @param string $action
     * @param string|null $table_name
     * @return array|null
     */
    private function getQuery(string $action, string $table_name = null) : ?array
    {
        $query                                                      = null;
        if ($this->loadDriver()) {
            $this->setTable($table_name, "name");

            if (!$this->key_name) {
                Error::register(static::TYPE . " key missing", static::ERROR_BUCKET);
            }
            if (!$this->getTable("name")) {
                Error::register(static::TYPE . " table missing", static::ERROR_BUCKET);
            }
            if (!is_array($this->insert)
                && ($action == "insert" || $action == "write")) {
                Error::register("insert is empty", static::ERROR_BUCKET);
            }
            if (!is_array($this->set) && !is_array($this->where)
                && ($action == "update" || $action == "write")) {
                Error::register("set or where is empty", static::ERROR_BUCKET);
            }
            if (!is_array($this->where)
                && ($action == "delete")) {
                Error::register("where is empty", static::ERROR_BUCKET);
            }

            $query["action"] 								        = $action;
            $query["key"] 										    = $this->key_name;
            $query["from"]                                          = $this->getTable("name");

            if ($action == "read") {
                $query                                              = $query + $this->convertFields($this->select, "select");
            }
            if ($action == "insert" || $action == "write") {
                $query                                              = $query + $this->convertFields($this->insert, "insert");
            }
            if ($action == "update" || $action == "write") {
                $query                                              = $query + $this->convertFields($this->set, "update");
            }
            if ($action != "insert") {
                $query                                              = $query + $this->convertFields($this->where, "where");
            }
            if ($action == "read" && $this->sort) {
                $query                                              = $query + $this->convertFields($this->sort, "sort");
            }
            if ($action == "read" && $this->limit) {
                $query["limit"]                                     = $this->limit;
            }
        } else {
            Error::register("Connection failed to database: " . static::TYPE, static::ERROR_BUCKET);
        }

        return $query;
    }

    /**
     * @param array $query
     * @return array|null
     */
    private function process(array $query) : ?array
    {
        $res                                                        = null;
        if (is_array($query)) {
            switch ($query["action"]) {
                case "read":
                    $db                                             = null;

                    if (1 || !$this->exts) {
                        $res                                        = Database::cache($query);
                    }

                    if (!$res) {
                        $db                                         = $this->processRead($query);
                    }

                    if ($db) {
                        $extsData                                   = array();

                        if ($this->exts && is_array($db["fields"]) && count($db["fields"])) {
                            foreach ($db["fields"] as $name) {
                                if ($name == $query["key"]) {
                                    $extsData[$name]                = null;
                                } elseif (strpos($name, $this->key_rel_prefix) === 0) {
                                    $extsData[$name]                = null;
                                } elseif (strlen($name) - strlen($this->key_rel_suffix) == strrpos($name, $this->key_rel_suffix)) {
                                    $extsData[$name]                = null;
                                } elseif (isset($this->relationship[$name])) {
                                    $extsData[$name]                = null;
                                } elseif (isset($this->alias[$name]) && isset($this->relationship[$this->alias[$name]])) {
                                    $extsData[$name]                = $this->alias[$name];
                                }
                            }
                        }

                        $count_recordset                            = count($db["recordset"]);
                        if (!empty($query["limit"]) || $count_recordset < $this::MAX_NUMROWS) {
                            if ($this->rawdata || $count_recordset > $this::MAX_RESULTS) {
                                $res["rawdata"]                     = $db["recordset"];
                                $res["count"]                       = $db["count"];
                            } else {
                                $key                                = $query["key"];

                                foreach ($db["recordset"] as $record) {
                                    if (count($extsData)) {
                                        $res["keys"][]                  = $this->recordKey($record, $key);
                                        foreach ($extsData as $field_name => $field_alias) {
                                            if ($record[$field_name]) {
                                                $ids = explode(",", $record[$field_name]);
                                                foreach ($ids as $id) {
                                                    $res["exts"][($field_alias ? $field_alias : $field_name)][$id][] = $this->recordKey($record, $key);
                                                }
                                            }
                                        }
                                    }
                                    $res["result"][]                = $this->fields2output($record, $this->select);
                                }
                                $res["count"]                       = $db["count"];
                            }
                        }
                    }

                    break;
                case "insert":
                    //@todo da alterare la cache in funzione dei dati inseriti
                    $res                                            = $this->processInsert($query);
                    break;
                case "update":
                    $res                                            = $this->processUpdate($query);
                    break;
                case "delete":
                    $res                                            = $this->processDelete($query);
                    break;
                case "write":
                    $res                                            = $this->processWrite($query);
                    break;
                default:
                    Error::register("Method not Managed", static::ERROR_BUCKET);
            }

            Database::setCache($res, $query);
        }

        return $res;
    }

    /**
     * @param array $record
     * @param string $key
     * @return string|null
     */
    private function recordKey(array $record, string $key) : ?string
    {
        return (isset($record[$key])
            ? $record[$key]
            : null
        );
    }

    private function setTable($table = null, $key = null)
    {
        if ($table) {
            if ($key) {
                $this->table[$key]                                  = $table;
            } else {
                $this->table                                        = (
                    is_array($table)
                                                                        ? $table
                                                                        : array(
                                                                            "name"                  => $table
                                                                            , "alias"               => $table
                                                                            , "engine"              => "InnoDB"
                                                                            , "crypt"               => false
                                                                            , "pairing"             => false
                                                                            , "transfert"           => false
                                                                            , "charset"             => "utf8"
                                                                        )
                                                                    );
            }
        }
    }

    /**
     * @param array $arr
     * @return bool
     */
    protected function isAssocArray(array $arr)
    {
        return Database::isAssocArray($arr);
    }

    /**
     * @param array $recordset
     * @param string $key
     * @return array|null
     */
    protected function extractKeys(array $recordset, string $key) : ?array
    {
        $res = null;
        if (is_array($recordset) && count($recordset)) {
            foreach ($recordset as $record) {
                if (isset($record[$key])) {
                    $res[] = $record[$key];
                }
            }
        }

        return $res;
    }

    /**
     * todo: da tipizzare
     * @param array $query
     * @param string[recordset|fields|num_rows] $key
     * @return mixed
     */
    public function rawQuery($query, $key = null)
    {
        $this->clearResult();

        $res                                            = $this->processRawQuery($query);
        if (!$key) {
            return $res;
        }
        return (isset($res[$key])
            ? $res[$key]
            : null
        );
    }

    public function lookup($table_name, $where = null, $fields = null, $sort = null, $limit = null)
    {
        return $this->read($where, $fields, $sort, $limit, $table_name);
    }

    public function find($fields = null, $where = null, $sort = null, $limit = null, string $table_name = null)
    {
        if (!$where && !$sort && !$limit) {
            $where                                                  = $fields;
            $fields                                                 = null;
        }

        return $this->read($where, $fields, $sort, $limit, $table_name);
    }

    /**
     * @param array $where
     * @param array|null $fields
     * @param array|null $sort
     * @param array|null $limit
     * @param string|null $table_name
     * @return array|null
     */
    public function read(array $where, array $fields = null, array $sort = null, array $limit = null, string $table_name = null)
    {
        $this->clearResult();

        $this->where                                                = $where;
        $this->sort                	                                = $sort;
        $this->limit                                                = $limit;
        $this->select                                               = $fields;

        $query                                                      = $this->getQuery("read", $table_name);

        return $this->process($query);
    }

    /**
     * @param array $insert
     * @param string|null $table_name
     * @return array|null
     */
    public function insert(array $insert, string $table_name = null) : ?array
    {
        $this->clearResult();

        $this->insert                                               = $insert;

        $query                                                      = $this->getQuery("insert", $table_name);

        return $this->process($query);
    }

    /**
     * @param array $set
     * @param array $where
     * @param string|null $table_name
     * @return bool|null
     */
    public function update(array $set, array $where, string $table_name = null) : ?bool
    {
        $this->clearResult();

        $this->set                                                  = $set;
        $this->where                                                = $where;

        $query                                                      = $this->getQuery("update", $table_name);

        return (bool) $this->process($query);
    }

    /**
     * @param array $insert
     * @param array $update
     * @param string|null $table_name
     * @return array|null
     */
    public function write(array $insert, array $update, string $table_name = null) : ?array
    {
        $this->clearResult();

        $this->insert                                               = $insert;
        $this->set                                                  = $update["set"];
        $this->where                                                = $update["where"];

        $query                                                      = $this->getQuery("write", $table_name);

        return $this->process($query);
    }

    /**
     * @param array $where
     * @param string|null $table_name
     * @return bool
     */
    public function delete(array $where, string $table_name = null) : bool
    {
        $this->clearResult();

        $this->where                                                = $where;

        $query                                                      = $this->getQuery("delete", $table_name);

        return (bool) $this->process($query);
    }

    public function cmd(string $action, array $what, string $table_name = null)
    {
        $this->clearResult();

        $this->where                                                = $what;

        $query                                                      = $this->getQuery($action, $table_name);

        return $this->processCmd($query);
    }


    /**
     * @param string $field
     * @return string|null
     */
    protected function getFieldAlias(string $field) : ?string
    {
        $res                                                        = null;
        if (is_array($this->alias) && count($this->alias)) {
            $alias_rev                                              = array_flip($this->alias);

            if (isset($alias_rev[$field])) {
                $res                                                = $alias_rev[$field];
            }
        } elseif ($this->struct === null || isset($this->struct[$field])) {
            $res                                                    = $field;
        }

        return $res;
    }

    /**
     * @param string $key
     * @return array|string|null
     */
    private function getStructField(string $key)
    {
        if (!isset($this->struct[$key])) {
            Error::register("Field: '" . $key . "' not found in struct on table: " . $this->table["name"], static::ERROR_BUCKET);
            return null;
        }

        return $this->struct[$key];
    }

    private function clearResult()
    {
        $this->select                                               = null;
        $this->insert                                               = null;
        $this->set                                                  = null;
        $this->where                                                = null;
        $this->sort                                                 = null;
        $this->limit                                                = null;
    }


    /**
     * @param array $record
     * @param array|null $prototype
     * @return array
     */
    private function fields2output(array $record, array $prototype = null) : array
    {
        static $hits                                                    = array();

        if (Kernel::$Environment::DEBUG) {
            $hash                                                       = md5(serialize($this->where));

            $hits["count"]                                              = (
                isset($hits["count"])
                                                                            ? $hits["count"] + 1
                                                                            : 1
                                                                        );

            $hits[$hash]["records"]                                     = (
                isset($hits[$hash])
                                                                            ? $hits[$hash]["records"] + 1
                                                                            : 1
                                                                        );

            $hits[$hash]["where"]                                       = $this->where;
            if ($hits["count"] > $this::MAX_RESULTS) {
                Log::debugging(array(
                    "URL" =>  Request::url()
                    , "Too Many Caller" => $hits
                ));
                $hits                                                   = array();
            }
        }

        $this->recordsetCast($record);

        if (is_array($prototype)) {
            $res                                                        = array_fill_keys(array_keys(array_filter($prototype)), "");
            foreach ($prototype as $name => $value) {
                $arrValue                                               = null;
                $field                                                  = null;
                $toField                                                = null;

                if ($name == "*") {
                    $res[$this->table["alias"]]                         = $record;
                    unset($res["*"]);
                    break;
                }

                $key                                                    = $name;
                if (!is_bool($value)) {
                    $arrType                                            = $this->convert($value);
                    if (isset($arrType["to"])) {
                        $toField                                        = $arrType["to"];
                    }
                    if (isset($arrType["field"])) {
                        $field                                          = $arrType["field"];
                        $key                                            = $field;
                    }

                    unset($res[$name]);
                } elseif (isset($this->alias[$name])) {
                    $key                                                = $this->alias[$name];
                    unset($res[$name]);
                }

                if (strpos($field, ".") > 0) {
                    $arrValue                                           = explode(".", $field);
                    if (isset($record[$arrValue[0]])) {
                        if (is_array($record[$arrValue[0]])) {
                            $key                                        = $name;
                            $res[$key]                                  = $record[$arrValue[0]][$arrValue[1]];
                        } elseif ($record[$arrValue[0]]) {
                            $subvalue                                   = $this->decode($record[$arrValue[0]]);
                            if ($subvalue) {
                                $res[$key]                              = $subvalue[$arrValue[1]];
                            }
                        }
                    }
                } elseif (isset($record[$name])) {
                    if ($this->struct[$name] == self::FTYPE_ARRAY_JSON) {
                        $res[$key]                                      = $this->decode($record[$name]);
                    } else {
                        $res[$key]                                      = $record[$name];
                    }
                }

                if (!$toField) {
                    $structCurrent                                      = null;
                    if ($arrValue && isset($this->struct[$arrValue[0]]) && is_array($this->struct[$arrValue[0]])) {
                        $structCurrent                                  = (
                            isset($this->struct[$arrValue[0]][$arrValue[1]]) && $this->struct[$arrValue[0]][$arrValue[1]]
                                                                            ? $this->struct[$arrValue[0]][$arrValue[1]]
                                                                            : $this->struct[$arrValue[0]]["default"]
                                                                        );
                    }
                    if (!$structCurrent) {
                        $struct_field                                   = $this->getStructField($name);
                        $structCurrent                                  = (
                            is_array($struct_field)
                                                                            ? $struct_field["default"]
                                                                            : $struct_field
                                                                        );
                    }
                    if ($structCurrent) {
                        $toField                                        = $this->convertTo($structCurrent);
                    }
                }

                if ($toField) {
                    $res[$key]                                          = $this->to($res[$key], $toField, $name);
                }
            }
        } else {
            $res                                                        = $record;
        }
        return $res;
    }

    /**
     * @param array $record
     */
    private function recordsetCast(array &$record) : void
    {
        if (is_array($record) && count($record)) {
            foreach ($record as $key => $value) {
                switch ($this->getStructField($key)) {
                    case self::FTYPE_PRIMARY:
                        if (static::KEY_IS_INT) {
                            $record[$key]                               = (int)$value;
                        }
                        break;
                    case self::FTYPE_ARRAY:
                        $record[$key]                                   = (
                            is_array($value)
                                                                            ? $value
                                                                            : $this->decode($value)
                                                                        );
                        break;
                    case self::FTYPE_NUMBER:
                    case self::FTYPE_TIMESTAMP:
                        if (!$value) {
                            $record[$key]                               = 0;
                        } elseif (strpos($value, ".") !== false || strpos($value, ",") !== false) {
                            $record[$key]                               = (double) str_replace(".", ",", $value);
                        } else {
                            $record[$key]                               = (int)$value;
                        }
                        break;
                    default:
                }
            }
        }
    }

    /**
     * @param string $def
     * @return string|null
     */
    private function convertTo(string $def) : ?string
    {
        $convert = self::convert($def);
        return (isset($convert["to"])
            ? $convert["to"]
            : null
        );
    }

    /**
     * @param string $def
     * @return array
     */
    private function convert(string $def) : array
    {
        $arrStruct                                                      = explode(":", $def);
        $res["field"]                                                   = $arrStruct[0];
        unset($arrStruct[0]);
        if (count($arrStruct)) {
            foreach ($arrStruct as $value) {
                $func                                                   = substr($value, 2);
                $op                                                     = substr($value, 0, 2);

                if (strpos("", "(") !== false) {
                    $arrFunc = explode("(", $func);
                    $func                                               = array(
                                                                            "name"      => strtoupper($arrFunc[0])
                                                                            , "params"  => explode(",", rtrim($arrFunc[1], ")"))
                                                                        );
                } else {
                    $func                                               = array(
                                                                            "name"      => strtoupper($func)
                                                                            , "params"  => array()
                                                                        );
                }
                $res[$op]                                               = $func;
            }
        }

        return $res;
    }

    /**
     * @param $string|null
     * @return array|null
     */
    private function decode(string $string = null) : ?array
    {
        return Validator::json2Array($string);
    }

    /**
     * @param int $index
     * @return string|null
     */
    private function getColorPalette(int $index) : ?string
    {
        $colors = array(
            "EF5350"
            , "EC407A"
            , "AB47BC"
            , "7E57C2"
            , "5C6BC0"
            , "42A5F5"
            , "29B6F6"
            , "26C6DA"
            , "26A69A"
            , "66BB6A"
            , "9CCC65"
            , "D4E157"
            , "FFEE58"
            , "FFCA28"
            , "FFA726"
        );
        return (isset($colors[$index])
            ? $colors[$index]
            : null
        );
    }
    /**
     * @param string $source
     * @param array $convert
     * @param string $default
     * @return string
     */
    private function to(string $source, array $convert, string $default = null) : ?string
    {
        $res                                                                = null;
        $method                                                             = $convert["name"];
        $params                                                             = $convert["params"];
        switch ($method) {
            case "IMAGE":
                if ($source === true) {
                    $res                                                    = '<i></i>';
                } elseif (strpos($source, "/") === 0) {
                    $res                                                    = '<img src="' . Media::getUrl($source) . '" />';
                } elseif (strpos($source, "<") === 0) {
                    $res                                                    = $source;
                } elseif (strpos($source, "#") !== false) {
                    $arrSource                                              = explode("#", $source);
                    $hex                                                    = (
                        $arrSource[1]
                                                                                ? $arrSource[1]
                                                                                : $this->getColorPalette(rand(0, 14))
                                                                            );
                    $res                                                    = '<span style="background-color: #' . $hex . ';">' . $arrSource[0] . '</span>';
                } elseif ($source) {
                    $res                                                    = Gridsystem::getInstance()->get($source, "icon-tag");
                }
                break;
            case "TIMEELAPSED":
                $time                                                       = time() - $source; // to get the time since that moment
                $time                                                       = ($time < 1) ? 1 : $time;
                $day                                                        = 86400;
                $min                                                        = 60;
                if ($time < 2 * $day) {
                    if ($time < $min) {
                        $res                                                = Translator::get_word_by_code("about") . " " . Translator::get_word_by_code("a") . " " . Translator::get_word_by_code("minute") . " " . Translator::get_word_by_code("ago");
                    } elseif ($time > $day) {
                        $res                                                = Translator::get_word_by_code("yesterday") . " " . Translator::get_word_by_code("at") . " " . date("G:i", $source);
                    } else {
                        $tokens                                             = array(
                                                                                31536000 	=> 'year',
                                                                                2592000 	=> 'month',
                                                                                604800 		=> 'week',
                                                                                86400 		=> 'day',
                                                                                3600 		=> 'hour',
                                                                                60 			=> 'minute',
                                                                                1 			=> 'second'
                                                                            );

                        foreach ($tokens as $unit => $text) {
                            if ($time < $unit) {
                                continue;
                            }
                            $res                                            = floor($time / $unit);
                            $res                                            .= ' ' . Translator::get_word_by_code($text . (($res > 1) ? 's' : '')) . " " . Translator::get_word_by_code("ago");
                            break;
                        }
                    }
                }
                break;
            case "DATETIME":
                $lang                                                       = Locale::getLang("code");
                $oData                                                      = new Data($source, "Timestamp");
                $res                                                        = $oData->getValue("Date", $lang);

                if ($lang == "ENG") {
                    $prefix                                                 = "+";
                    $res                                                    = "+" . $res;
                } else {
                    $prefix                                                 = "/";
                }

                $conv                                                       = array(
                                                                                $prefix . "01/" => " " . Translator::get_word_by_code("Januaunable to updatery") . " "
                                                                                , $prefix . "02/" => " " . Translator::get_word_by_code("February") . " "
                                                                                , $prefix . "03/" => " " . Translator::get_word_by_code("March") . " "
                                                                                , $prefix . "04/" => " " . Translator::get_word_by_code("April") . " "
                                                                                , $prefix . "05/" => " " . Translator::get_word_by_code("May") . " "
                                                                                , $prefix . "06/" => " " . Translator::get_word_by_code("June") . " "
                                                                                , $prefix . "07/" => " " . Translator::get_word_by_code("July") . " "
                                                                                , $prefix . "08/" => " " . Translator::get_word_by_code("August") . " "
                                                                                , $prefix . "09/" => " " . Translator::get_word_by_code("September") . " "
                                                                                , $prefix . "10/" => " " . Translator::get_word_by_code("October") . " "
                                                                                , $prefix . "11/" => " " . Translator::get_word_by_code("November") . " "
                                                                                , $prefix . "12/" => " " . Translator::get_word_by_code("December") . " "
                                                                            );
                $res                                                        = str_replace(array_keys($conv), array_values($conv), $res);
                if ($prefix) {
                    $res                                                    = str_replace("/", ", ", $res);
                }
                $res                                                        .= " " . Translator::get_word_by_code("at") . " " . Translator::get_word_by_code("hours") . " " . $oData->getValue("Time", Locale::getLang("code"));

                break;
            case "DATE":
                $oData                                                      = new Data($source, "Timestamp");
                $res                                                        = $oData->getValue("Date", Locale::getLang("code"));
                break;
            case "TIME":
                $oData                                                      = new Data($source, "Timestamp");
                $res                                                        = $oData->getValue("Time", Locale::getLang("code"));
                break;
            case "STRING":
                if ($source) {
                    if (is_string($source)) {
                        $res                                                = $source;
                    } else {
                        $res                                                = $default;
                    }
                } else {
                    $res                                                    = "";
                }
                break;
            case "DESCRYPT":
                $res                                                        = $this->decrypt($source, $params[0], $params[1]);
                break;
            case "AES128":
            case "AES192":
            case "AES256":
            case "BF":
            case "CAST":
            case "IDEA":
                $res                                                        = $this->decrypt($source, $params[0], $method);
                break;
            default:
                $res                                                        = $default;
        }

        return $res;
    }

    /**
     * @param string $data
     * @param string $method
     * @param array|null $params
     * @return string
     */
    private function convertWith(string $data, string $method, array $params = null) : string
    {
        switch ($method) {
            case "ASCII":
                $res                                                        = ord($data);
                break;
            case "CHAR_LENGTH":
            case "CHARACTER_LENGTH":
            case "LENGTH":
                $res                                                        = strlen($data);
                break;
            case "LCASE":
            case "LOWER":
                $res                                                        = strtolower($data);
                break;
            case "LTRIM":
                $res                                                        = ltrim($data, $params[0]);
                break;
            case "RTRIM":
                $res                                                        = rtrim($data, $params[0]);
                break;
            case "TRIM":
                $res                                                        = trim($data, $params[0]);
                break;
            case "UCASE":
            case "UPPER":
                $res                                                        = strtoupper($data);
                break;
            case "REVERSE":
                $res                                                        = strrev($data);
                break;
            case "MD5":
                $res                                                        = md5($data);
                break;
            case "OLDPASSWORD":
                $res                                                        = "*" . strtoupper(sha1(sha1($data, true)));
                break;
            case "PASSWORD":
                $res                                                        = "*" . strtoupper(sha1(sha1($data, true)));
                //todo: da usare Password_Verify (password_hash($data, PASSWORD_DEFAULT))
                break;
            case "BCRYPT":
                $res                                                        = password_hash($data, PASSWORD_BCRYPT);
                break;
            case "ARGON2I":
                $res                                                        = password_hash($data, PASSWORD_ARGON2I);
                break;
            case "REPLACE":
                $res                                                        = str_replace($params[0], $params[1], $data);
                break;
            case "CONCAT":
                $res                                                        = $data . " " . implode(" ", $params);
                break;
            case "ENCRYPT":
                $res                                                        = $this->encrypt($data, $params[0], $params[1]);
                break;
            case "AES128":
            case "AES192":
            case "AES256":
            case "BF":
            case "CAST":
            case "IDEA":
                $res                                                        = $this->encrypt($data, $params[0], $method);
                break;
            case "SLUG":
                $res                                                        = Validator::urlRewrite($data);
                break;
            default:
                $res                                                        = $data;
        }

        return $res;
    }

    private function in($source, array $convert = null)
    {
        $res                                                                = $source;
        if ($convert) {
            $method                                                             = $convert["name"];
            $params                                                             = $convert["params"];

            if (is_array($source)) {
                if (count($source)) {
                    foreach ($source as $i => $v) {
                        $res[$i]                                                = $this->convertWith($v, $method, $params);
                    }
                }
            } elseif ($source) {
                $res                                                            = $this->convertWith($source, $method, $params);
            }
        }

        return $res;
    }

    /**
     * @param string $password
     * @param string $algorithm
     * @param int $cost
     * @return array|null
     */
    private function getEncryptParams(string $password, string $algorithm, int $cost = 12) : ?array
    {
        $res                                                                = null;
        if ($password && $algorithm) {
            switch ($algorithm) {
                case "AES128":
                    $method                                                 = "aes-128-cbc";
                    break;
                case "AES192":
                    $method                                                 = "aes-192-cbc";
                    break;
                case "AES256":
                    $method                                                 = "aes-256-cbc";
                    break;
                case "BF":
                    $method                                                 = "bf-cbc";
                    break;
                case "CAST":
                    $method                                                 = "cast5-cbc";
                    break;
                case "IDEA":
                    $method                                                 = "idea-cbc";
                    break;
                default:
                    $method                                                 = null;
            }


            if ($method) {
                $res = array(
                    "key"       => password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost])
                    , "method"  => $method
                    , "iv"      => chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0)
                );
            }
        }

        return $res;
    }

    /**
     * @param string $data
     * @param string $password
     * @param string $algorithm
     * @param int $cost
     * @return string|null
     */
    private function encrypt(string $data, string $password, string $algorithm = "AES256", int $cost = 12) : ?string
    {
        $res                                                                = null;
        $params                                                             = $this->getEncryptParams($password, $algorithm, $cost);
        if ($params) {
            $res = base64_encode(openssl_encrypt($data, $params["method"], $params["key"], OPENSSL_RAW_DATA, $params["iv"]));
        }

        return $res;
    }

    /**
     * @param string $encrypted
     * @param string $password
     * @param string $algorithm
     * @param int $cost
     * @return string|null
     */
    private function decrypt(string $encrypted, string $password, string $algorithm = "AES256", int $cost = 12) : ?string
    {
        $res                                                                = null;
        $params                                                             = $this->getEncryptParams($password, $algorithm, $cost);
        if ($params) {
            $res = openssl_decrypt(base64_decode($encrypted), $params["method"], $params["key"], OPENSSL_RAW_DATA, $params["key"]);
        }
        return $res;
    }



    /**
     * @todo da tipizzare
     * @param $name
     * @param mixed $value
     * @return array|string
     */
    protected function normalizeField(string $name, $value)
    {
        $res                                                                = false;
        if (is_array($value)
            && (
                isset($value['$gt'])
                || isset($value['$gte'])
                || isset($value['$lt'])
                || isset($value['$lte'])
                || isset($value['$eq'])
                || isset($value['$regex'])
                || isset($value['$in'])
                || isset($value['$nin'])
                || isset($value['$ne'])
                || isset($value['$inset'])
            )
        ) {
            $res                                                            = "special";
        }

        if (!$res) {
            $op                                                             = null;
            $toField                                                        = null;
            $fields                                                         = array();

            $struct_field                                                   = $this->getStructField($name);
            if (is_array($struct_field)) {
                $struct_type                                                = self::FTYPE_ARRAY;
            } else {
                $arrType                                                    = $this->convert($struct_field);
                $struct_type                                                = $arrType["field"];
                if (isset($arrType["in"])) {
                    $toField                                                = $arrType["in"];
                }
            }

            switch ($struct_type) {
                case self::FTYPE_ARRAY_INCREMENTAL:
                case self::FTYPE_ARRAY_OF_NUMBER:
                case self::FTYPE_ARRAY:
                    if (is_array($value) || is_object($value)) {
                        if ($struct_type == self::FTYPE_ARRAY_OF_NUMBER) {
                            $fields[$name]                                  = array_map('intval', $value);
                        } else {
                            $fields[$name]                                  = $value;
                        }
                    } elseif (strrpos($value, "++") === strlen($value) -2) {
                        $fields[$name]                                      = array();
                    } elseif (strrpos($value, "--") === strlen($value) -2) {
                        $fields[$name]                                      = array();
                    } elseif (strpos($value, "+") === 0) {
                        $op                                                 = "+";
                        $fields[$name]                                      = substr($value, 1);
                    } elseif (is_bool($value)) {
                        $fields[$name] = array((int)$value);
                    } elseif (is_numeric($value) || $struct_type == self::FTYPE_ARRAY_OF_NUMBER || $struct_type == self::FTYPE_ARRAY_INCREMENTAL) {
                        if (strpos($value, ".") !== false || strpos($value, ",") !== false) {
                            $fields[$name]                                  = array((double)$value);
                        } else {
                            $fields[$name]                                  = array((int)$value);
                        }
                    } elseif (strtotime($value)) {
                        $fields[$name]                                      = array($value);
                    } elseif ($value == "empty" || !$value) {
                        $fields[$name]                                      = array();
                    } else {
                        $fields[$name]                                      = array((string)$value);
                    }
                    break;
                case self::FTYPE_BOOLEAN:
                case self::FTYPE_BOOL:
                    if (is_array($value) || is_object($value)) {
                        $fields[$name]                                      = false;
                    } elseif (strrpos($value, "++") === strlen($value) -2) {
                        $fields[$name]                                      = false;
                    } elseif (strrpos($value, "--") === strlen($value) -2) {
                        $fields[$name]                                      = false;
                    } elseif (strpos($value, "+") === 0) {
                        $fields[$name]                                      = false;
                    } elseif (is_bool($value)) {
                        $fields[$name]                                      = $value;
                    } elseif (is_numeric($value)) {
                        $fields[$name]                                      = (bool)$value;
                    } elseif ($value == "empty") {
                        $fields[$name]                                      = false;
                    } else {
                        $fields[$name]                                      = (bool)$value;
                    }
                    break;
                case self::FTYPE_DATE:
                    $fields[$name] = $value;
                    break;
                case self::FTYPE_NUMBER:
                case self::FTYPE_TIMESTAMP:
                    if (is_array($value) || is_object($value)) {
                        $fields[$name]                                      = 0;
                    } elseif (strrpos($value, "++") === strlen($value) -2) {
                        $op                                                 = "++";
                        $fields[$name]                                      = substr($value, -2);
                    } elseif (strrpos($value, "--") === strlen($value) -2) {
                        $op                                                 = "--";
                        $fields[$name]                                      = substr($value, -2);
                    } elseif (strpos($value, "+") === 0) {
                        $op                                                 = "+";
                        $fields[$name]                                      = substr($value, 1);
                    } elseif (is_bool($value)) {
                        $fields[$name]                                      = (int)$value;
                    } elseif (!is_numeric($value) && strtotime($value)) {
                        $fields[$name]                                      = strtotime($value);
                    } elseif (strpos($value, ".") !== false || strpos($value, ",") !== false) {
                        $fields[$name]                                      = (double) str_replace(",", ".", $value);
                    } elseif ($value == "empty") {
                        $fields[$name]                                      = 0;
                    } else {
                        $fields[$name]                                      = (int)$value;
                        if ($fields[$name] >= pow(2, 31)) {
                            Error::register($name . " is too long", static::ERROR_BUCKET);
                        }
                    }
                    break;
                case self::FTYPE_STRING:
                case self::FTYPE_CHAR:
                case self::FTYPE_TEXT:
                case self::FTYPE_PRIMARY:
                default:
                    if (is_array($value)) {
                        if ($this->isAssocArray($value)) {
                            $fields[$name]                                  = json_encode($value);
                        } else {
                            $fields[$name]                                  = implode(",", array_unique($value));
                        }
                    } elseif (is_object($value)) {
                        $fields[$name]                                      = json_encode(get_object_vars($value));
                    } elseif (strrpos($value, "++") === strlen($value) -2) {
                        $op                                                 = "++";
                        $fields[$name]                                      = substr($value, -2);
                    } elseif (strrpos($value, "--") === strlen($value) -2) {
                        $op                                                 = "--";
                        $fields[$name]                                      = substr($value, -2);
                    } elseif (strpos($value, "+") === 0) {
                        $op                                                 = "+";
                        $fields[$name]                                      = substr($value, 1);
                    } elseif (is_bool($value)) {
                        $fields[$name]                                      = (string)($value ? "1" : "0");
                    } elseif (is_numeric($value)) {
                        $fields[$name]                                      = (string)$value;
                    } elseif (is_null($value)) {
                        $fields[$name]                                      = $value;
                    } elseif ($value == "empty" || empty($value)) {
                        $fields[$name]                                      = "";
                    } elseif (substr($name, 0, 1) == "_") {
                        $fields[$name]                                      = $value;
                    } else {
                        $fields[$name]                                      = (string)$value;
                    }
            }

            $res                                                            = array(
                                                                                "value"     => $this->in($fields[$name], $toField),
                                                                                "name"      => $name,
                                                                                "op"        => $op,
                                                                                "type"      => $struct_type
                                                                            );
        }

        return $res;
    }
}
