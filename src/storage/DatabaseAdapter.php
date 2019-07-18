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

use phpformsframework\libs\Constant;
use phpformsframework\libs\Error;
use phpformsframework\libs\international\Data;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Log;
use phpformsframework\libs\Request;
use phpformsframework\libs\tpl\Gridsystem;
use phpformsframework\libs\security\Validator;

abstract class DatabaseAdapter
{
    const ERROR_BUCKET                  = "database";

    const TYPE                          = null;
    const PREFIX                        = null;
    const KEY                           = null;

    const MAX_NUMROWS                   = 10000;
    const MAX_RESULTS                   = 1000;

    private $connection                 = array(
                                            "host"          => null
                                            , "username"    => null
                                            , "password"    => null
                                            , "name"        => null
                                            , "prefix"		=> null
                                            , "table"       => null
                                            , "key"         => null
                                        );

    protected $key_name                 = null;
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
    protected $driver                                     = null;

    abstract protected function getDriver();
    abstract protected function convertFields($fields, $action);
    abstract protected function processRead($query);
    abstract protected function processInsert($query);
    abstract protected function processUpdate($query);
    abstract protected function processDelete($query);
    abstract protected function processWrite($query);
    abstract protected function processCmd($query);
    abstract public function toSql($cDataValue, $data_type = null, $enclose_field = true, $transform_null = null);

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
    }

    protected function loadDriver()
    {
        $connector                                      = $this->getConnector();
        if ($connector) {
            $this->driver                               = $this->getDriver();
            if ($this->driver->connect(
                $connector["name"],
                $connector["host"],
                $connector["username"],
                $connector["password"]
            )) {
                $this->key_name                         = $connector["key"];
            }
        }
        return (bool) $this->driver;
    }

    protected function processRawQuery($query, $key = null)
    {
        $res                                            = null;
        $success                                        = $this->driver->query($query);
        if ($success) {
            switch ($key) {
                case "recordset":
                    $res                                = $this->driver->getRecordset();
                    break;
                case "fields":
                    $res                                = $this->driver->getFieldset();
                    break;
                case "num_rows":
                    $res                                = $this->driver->numRows();
                    break;
                default:
                    $res                                = array(
                                                            "recordset"     => $this->driver->getRecordset()
                                                            , "fields"      => $this->driver->getFieldset()
                                                            , "num_rows"    => $this->driver->numRows()
                                                        );
            }
        } else {
            $res                                        = $success;
        }

        return $res;
    }

    protected function getConnector($key = null)
    {
        $connection                                                                     = $this->connection;

        $prefix                                                                         = (
            isset($connection["prefix"]) && defined($connection["prefix"] . "NAME") && constant($connection["prefix"] . "NAME")
                                                                                            ? $connection["prefix"]
                                                                                            : static::PREFIX
                                                                                        );

        $connector["host"]                                                              = (
            $connection["host"]
                                                                                            ? $connection["host"]
                                                                                            : (
                                                                                                defined($prefix . "HOST")
                                                                                                ? constant($prefix . "HOST")
                                                                                                : "localhost"
                                                                                            )
                                                                                        );
        $connector["username"]                                                          = (
            $connection["username"]
                                                                                            ? $connection["username"]
                                                                                            : (
                                                                                                defined($prefix . "USER")
                                                                                                ? constant($prefix . "USER")
                                                                                                : null
                                                                                            )
                                                                                        );
        $connector["password"]                                                          = (
            $connection["password"]
                                                                                            ? $connection["password"]
                                                                                            : (
                                                                                                defined($prefix . "PASSWORD")
                                                                                                ? constant($prefix . "PASSWORD")
                                                                                                : null
                                                                                            )
                                                                                        );
        $connector["name"]                                                              = (
            $connection["name"]
                                                                                            ? $connection["name"]
                                                                                            : (
                                                                                                defined($prefix . "NAME")
                                                                                                ? constant($prefix . "NAME")
                                                                                                : null
                                                                                            )
                                                                                        );
        $connector["table"]                                                             = (
            $connection["table"]
                                                                                            ? $connection["table"]
                                                                                            : null
                                                                                        );
        $connector["key"]                                                               = (
            $connection["key"]
                                                                                            ? $connection["key"]
                                                                                            : static::KEY
                                                                                        );

        if (!$this->table && $connector["table"]) {
            $this->setTable($connector["table"]);
        }

        if (!$connector["name"]) {
            Error::register(static::TYPE . "_database_connection_failed", static::ERROR_BUCKET);
            return false;
        }

        return ($key
            ? $connector[$key]
            : $connector
        );
    }

    private function getTable($key = null)
    {
        return ($key
            ? $this->table[$key]
            : $this->table
        );
    }

    private function getQuery($action, $table_name = null)
    {
        $query                                                      = false;
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

            if (!Error::check(static::ERROR_BUCKET)) {
                $query["action"] 								    = $action;
                $query["key"] 										= $this->key_name;
                $query["from"]                                      = $this->getTable("name");

                if ($action == "read") {
                    $query = $query + $this->convertFields($this->select, "select");
                }
                if ($action == "insert" || $action == "write") {
                    $query = $query + $this->convertFields($this->insert, "insert");
                }
                if ($action == "update" || $action == "write") {
                    $query = $query + $this->convertFields($this->set, "update");
                }
                if ($action != "insert") {
                    $query = $query + $this->convertFields($this->where, "where");
                }
                if ($action == "read" && $this->sort) {
                    $query = $query + $this->convertFields($this->sort, "sort");
                }
                if ($action == "read" && $this->limit) {
                    $query["limit"] = $this->limit;
                }
            }
        } else {
            Error::register("Connection failed to database: " . static::TYPE, static::ERROR_BUCKET);
        }
        return $query;
    }


    private function process($query)
    {
        $res                                                        = null;

        if (is_array($query)) {
            switch ($query["action"]) {
                case "read":
                    $db                                              = null;

                    if (1 || !$this->exts) {
                        $res = Database::cache($query);
                    } //todo: da verificare
                    if (!$res) {
                        $db = $this->processRead($query);
                    }

                    if ($db) {
                        $exts                                       = array();

                        if ($this->exts && is_array($db["fields"]) && count($db["fields"])) {
                            foreach ($db["fields"] as $name) {
                                if ($name == $query["key"]) {
                                    $exts[$name]                    = null;
                                } elseif (strpos($name, "ID_") === 0) {
                                    $exts[$name]                    = null;
                                } elseif (isset($this->relationship[$name])) {
                                    $exts[$name]                    = null;
                                } elseif (isset($this->alias[$name]) && isset($this->relationship[$this->alias[$name]])) {
                                    $exts[$name]                    = $this->alias[$name];
                                }
                            }
                        }

                        if ($db["num_rows"] < $this::MAX_NUMROWS) {
                            if ($this->rawdata || $db["num_rows"] > $this::MAX_RESULTS) {
                                $res["rawdata"]                     = $db["recordset"];
                            } else {
                                $key                                = $this->getFieldAlias($query["key"]);
                                foreach ($db["recordset"] as $record) {
                                    $res["keys"][]                  = $record[$key];
                                    if ($exts) {
                                        foreach ($exts as $field_name => $field_alias) {
                                            if ($record[$field_name]) {
                                                $ids = explode(",", $record[$field_name]);
                                                foreach ($ids as $id) {
                                                    $res["exts"][($field_alias ? $field_alias : $field_name)][$id][] = $record[$key];
                                                }
                                            }
                                        }
                                    }


                                    $res["result"][]                = $this->fields2output($record, $this->select);
                                }
                                if (isset($db["count"])) {
                                    $res["count"] = $db["count"];
                                }
                            }
                        }
                    }

                    break;
                case "insert":
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
                    $res                                            = $this->processCmd($query);
            }

            Database::setCache($res, $query);
        }
        return $res;
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
    protected function convertKey($target_key, $fields)
    {
        if (isset($fields[$target_key]) && !isset($fields[$this->key_name])) {
            $fields[$this->key_name]                                = $fields[$target_key];
            unset($fields[$target_key]);
        }
        return $fields;
    }
    protected function extractKeys($recordset, $key)
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
     * @param string|array $query
     * @param string[recordset|fields|num_rows] $key
     * @return null|bool|array
     */
    public function rawQuery($query, $key = null)
    {
        $this->clearResult();

        return $this->processRawQuery($query, $key);
    }

    /**
     * @param $table_name
     * @param null $where
     * @param null $fields
     * @param null $sort
     * @param null $limit
     * @return bool|array
     */
    public function lookup($table_name, $where = null, $fields = null, $sort = null, $limit = null)
    {
        return $this->read($where, $fields, $sort, $limit, $table_name);
    }

    /**
     * @param null|array $fields
     * @param null|array $where
     * @param null|array $sort
     * @param null|array $limit
     * @param null|array $table_name
     * @return bool|array
     */
    public function find($fields = null, $where = null, $sort = null, $limit = null, $table_name = null)
    {
        if (!$where && !$sort && !$limit) {
            $where                                                  = $fields;
            $fields                                                 = null;
        }

        return $this->read($where, $fields, $sort, $limit, $table_name);
    }

    /**
     * @param array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|array $limit
     * @param null|string $table_name
     * @return bool|array
     */
    public function read($where, $fields = null, $sort = null, $limit = null, $table_name = null)
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
     * @param null|string $table_name
     * @return bool
     */
    public function insert($insert, $table_name = null)
    {
        $this->clearResult();

        $this->insert                                               = $insert;

        $query                                                      = $this->getQuery("insert", $table_name);

        return $this->process($query);
    }

    /**
     * @param array $set
     * @param array $where
     * @param null|string $table_name
     * @return bool
     */
    public function update($set, $where, $table_name = null)
    {
        $this->clearResult();

        $this->set                                                  = $set;
        $this->where                                                = $where;

        $query                                                      = $this->getQuery("update", $table_name);

        return $this->process($query);
    }

    /**
     * @param array $insert
     * @param array $update
     * @param null|string $table_name
     * @return bool
     */
    public function write($insert, $update, $table_name = null)
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
     * @param null|string $table_name
     * @return bool
     */
    public function delete($where, $table_name = null)
    {
        $this->clearResult();

        $this->where                                                = $where;

        $query                                                      = $this->getQuery("delete", $table_name);

        return $this->process($query);
    }
    /**
     * @param string $action
     * @param array $what
     * @param null|string $table_name
     * @return bool
     */
    public function cmd($action, $what, $table_name = null)
    {
        $this->clearResult();

        $this->where                                                = $what;

        $query                                                      = $this->getQuery($action, $table_name);

        return $this->process($query);
    }

    /**
     * @param $field
     */
    protected function getFieldAlias($field)
    {
        $res                                                        = false;
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

    private function getStructField($key, $subkey = null)
    {
        if (isset($this->struct[$key])) {
            return ($subkey
                ? $this->struct[$key][$subkey]
                : $this->struct[$key]
            );
        } else {
            Error::register("Field: '" . $key . "' not found in struct on table: " . $this->table["name"], static::ERROR_BUCKET);
        }

        return null;
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

    /*****************************/

    /**
     * @param $record
     * @param null $prototype
     * @return array
     */
    private function fields2output($record, $prototype = null)
    {
        static $hits                                                    = array();

        if (Constant::DEBUG) {
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

        if ($prototype) {
            $res                                                        = array_fill_keys(array_keys(array_filter($prototype)), "");
            if (is_array($prototype)) {
                foreach ($prototype as $name => $value) {
                    $arrValue                                           = null;
                    $field                                              = null;
                    $toField                                            = null;

                    if ($name == "*") {
                        $res[$this->table["alias"]]                     = $record;
                        unset($res["*"]);
                        break;
                    }

                    $key                                                = $name;
                    if (!is_bool($value)) {
                        $arrType                                        = $this->convert($value);
                        if (isset($arrType["to"])) {
                            $toField                                    = $arrType["to"];
                        }
                        if (isset($arrType["field"])) {
                            $field                                      = $arrType["field"];
                            $key                                        = $field;
                        }

                        unset($res[$name]);
                    } elseif (isset($this->alias[$name])) {
                        $key                                            = $this->alias[$name];
                        unset($res[$name]);
                    }

                    if (strpos($field, ".") > 0) {
                        $arrValue                                       = explode(".", $field);
                        if (isset($record[$arrValue[0]])) {
                            if (is_array($record[$arrValue[0]])) {
                                $key                                    = $name;
                                $res[$key]                              = $record[$arrValue[0]][$arrValue[1]];
                            } elseif ($record[$arrValue[0]]) {
                                $subvalue                               = $this->decode($record[$arrValue[0]]);
                                if ($subvalue) {
                                    $res[$key]                          = $subvalue[$arrValue[1]];
                                }
                            }
                        }
                    } elseif (isset($record[$name]) && is_array($record[$name])) {
                        $res[$key]                                      = $record[$name];
                    } else {
                        $res[$key]                                      = $this->decode($record[$name]);
                    }

                    if (!$toField) {
                        $struct                                         = null;
                        if ($arrValue && isset($this->struct[$arrValue[0]]) && is_array($this->struct[$arrValue[0]])) {
                            $struct                                     = (
                                isset($this->struct[$arrValue[0]][$arrValue[1]]) && $this->struct[$arrValue[0]][$arrValue[1]]
                                                                            ? $this->struct[$arrValue[0]][$arrValue[1]]
                                                                            : $this->struct[$arrValue[0]]["default"]
                                                                        );
                        }
                        if (!$struct) {
                            $struct_field                               = $this->getStructField($name);
                            $struct                                     = (
                                is_array($struct_field)
                                                                            ? $struct_field["default"]
                                                                            : $struct_field
                                                                        );
                        }
                        if ($struct) {
                            $toField = $this->convert($struct, "to");
                        }
                    }

                    if ($toField) {
                        $res[$key]                                      = $this->to($res[$key], $toField, $name);
                    }
                }
            } else {
                $res[$prototype]                                        = (
                    isset($record[$prototype])
                                                                            ? $record[$prototype]
                                                                            : null
                                                                        );
            }
        } else {
            $res                                                        = $record;
        }
        return $res;
    }

    private function recordsetCast(&$record)
    {
        if (is_array($record) && count($record)) {
            foreach ($record as $key => $value) {
                switch ($this->getStructField($key)) {
                    case "array":
                        $record[$key]                                   = (
                            is_array($value)
                                                                            ? $value
                                                                            : $this->decode($value)
                                                                        );
                        break;
                    case "number":
                    case "timestamp":
                    case "primary":
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

    private function convert($def, $key = null)
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

        if ($key && !isset($res[$key])) {
            $res[$key] = null;
        }

        return ($key
            ? $res[$key]
            : $res
        );
    }

    /**
     * @param $string
     * @return array|mixed|object
     */
    private function decode($string)
    {
        if (substr($string, 0, 1) == "{") {
            $json                                                       = json_decode($string, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                $string                                                 = $json;
            }
        }

        return $string;
    }

    /**
     * @param null $index
     * @return array|mixed
     */
    private function getColorPalette($index = null)
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
        return ($index === null
            ? $colors
            : $colors[$index]
        );
    }
    /**
     * @param $source
     * @param $convert
     * @param $default
     * @return array|string
     */
    private function to($source, $convert, $default = null)
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
                    $res = str_replace("/", ", ", $res);
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

    private function convertWith($data, $method, $params = null)
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

    private function in($source, $convert)
    {
        $res                                                                = $source;
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

        return $res;
    }

    private function getEncryptParams($password, $algorithm, $cost = 12)
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
    private function encrypt($data, $password, $algorithm = "AES256", $cost = 12)
    {
        $res                                                                = null;
        $params                                                             = $this->getEncryptParams($password, $algorithm, $cost);
        if ($params) {
            $res = base64_encode(openssl_encrypt($data, $params["method"], $params["key"], OPENSSL_RAW_DATA, $params["iv"]));
        }

        return $res;
    }

    private function decrypt($encrypted, $password, $algorithm = "AES256", $cost = 12)
    {
        $res                                                                = null;
        $params                                                             = $this->getEncryptParams($password, $algorithm, $cost);
        if ($params) {
            $res = openssl_decrypt(base64_decode($encrypted), $params["method"], $params["key"], OPENSSL_RAW_DATA, $params["key"]);
        }
        return $res;
    }



    /**
     * @param $name
     * @param mixed $value
     * @return array
     */
    protected function normalizeField($name, $value)
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
                $struct_type                                                = "array";
            } else {
                $arrType                                                    = $this->convert($struct_field);
                $struct_type                                                = $arrType["field"];
                if (isset($arrType["in"])) {
                    $toField                                                = $arrType["in"];
                }
            }
            switch ($struct_type) {
                case "arrayIncremental":
                case "arrayOfNumber":
                case "array":
                    if (is_array($value) || is_object($value)) {
                        if ($struct_type == "arrayOfNumber") {
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
                    } elseif (is_numeric($value) || $struct_type == "arrayOfNumber" || $struct_type == "arrayIncremental") {
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
                case "boolean":
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
                case "date":
                    $fields[$name] = $value;
                    break;
                case "number":
                case "timestamp":
                case "primary":
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
                        $fields[$name]                                      = (double) str_replace(".", ",", $value);
                    } elseif ($value == "empty") {
                        $fields[$name]                                      = 0;
                    } else {
                        $fields[$name]                                      = (int)$value;
                        if ($fields[$name] >= pow(2, 31)) {
                            Error::register($name . "is too long", static::ERROR_BUCKET);
                        }
                    }
                    break;
                case "string":
                case "char":
                case "text":
                default:
                    if (is_array($value)) {
                        if ($this->isAssocArray($value)) {
                            $fields[$name]                                  = json_encode($value);
                        } else {
                            $fields[$name]                                  = implode(",", array_unique($value));
                        }
                    } elseif(is_object($value)) {
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
                                                                                "value"     => $this->in($fields[$name], $toField)
                                                                                , "name"    => $name
                                                                                , "op"      => $op
                                                                                , "type"    => $struct_type
                                                                            );
        }

        return $res;
    }
}