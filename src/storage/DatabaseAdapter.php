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
use Exception;
use phpformsframework\libs\util\Normalize;

/**
 * Class DatabaseAdapter
 * @package phpformsframework\libs\storage
 */
abstract class DatabaseAdapter implements Constant
{
    public const MAX_NUMROWS            = 10000;

    private const OPERATOR_COMPARISON   = [
                                            '$gt'       => self::FTYPE_NUMBER,
                                            '$gte'      => self::FTYPE_NUMBER,
                                            '$lt'       => self::FTYPE_NUMBER,
                                            '$lte'      => self::FTYPE_NUMBER,
                                            '$eq'       => false,
                                            '$regex'    => self::FTYPE_STRING,
                                            '$in'       => self::FTYPE_ARRAY,
                                            '$nin'      => self::FTYPE_ARRAY,
                                            '$ne'       => self::FTYPE_STRING,
                                            '$inset'    => self::FTYPE_ARRAY,
                                            '$inc'      => false,
                                            '$inc-'     => false,
                                            '$addToSet' => false,
                                            '$set'      => false
                                        ];

    protected const OR                  = '$or';
    protected const AND                 = '$and';
    protected const OP_ARRAY_DEFAULT    = '$in';
    protected const OP_DEFAULT          = '$eq';
    protected const OP_INC_DEC          = '$inc';
    protected const OP_ADD_TO_SET       = '$addToSet';
    protected const OP_SET              = '$set';

    private const ERROR_INSERT_IS_EMPTY = "insert is empty";
    private const ERROR_UPDATE_IS_EMPTY = "set and/or where are empty";
    private const ERROR_WRITE_IS_EMPTY  = "insert and/or set and/or where are empty";
    private const ERROR_DELETE_IS_EMPTY = "you cant truncate table. where is empty";
    private const ERROR_CMD_IS_EMPTY    = "where and/or command is empty";

    protected const TYPE                = null;
    protected const PREFIX              = null;
    protected const KEY_NAME            = null;
    protected const KEY_REL             = "ID_";
    protected const KEY_IS_INT          = false;

    protected const RESULT              = Database::RESULT;
    protected const INDEX               = Database::INDEX;

    private const COUNT                 = Database::COUNT;
    private const INDEX_PRIMARY         = Database::INDEX_PRIMARY;

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

    protected $main_table    			= null;
    protected $struct					= null;
    protected $relationship			    = null;
    protected $indexes					= null;
    protected $table                    = null;

    private $index2query                = array();
    private $table_name                 = null;

    /**
     * @var DatabaseDriver
     */
    protected $driver                   = null;

    private $prototype                  = array();
    private $to                         = array();
    private $in                         = array();


    /**
     * @return mixed
     */
    abstract protected function getDriver();

    /**
     * @param array|null $casts
     * @param string|null $field_db
     * @param string|null $field_output
     */
    private function converterCallback(array $casts = null, string $field_db = null, string $field_output  = null) : void
    {
        if ($casts) {
            foreach ($casts as $cast) {
                $params                                 = array();
                $op                                     = strtolower(substr($cast, 0, 2));
                if (strpos($cast, "(") !== false) {
                    $func = explode("(", $cast, 2);
                    $cast = $func[0];
                    $params = explode(",", rtrim($func[1], ")"));
                }




                if ($op === "to" && $field_output) {
                    $this->to[$field_output][substr($cast, 2)]         = $params;
                } elseif ($op === "in" && $field_db) {
                    $this->in[$field_db][substr($cast, 2)]             = $params;
                } else {
                    Error::registerWarning($cast . " is not a valid function", static::ERROR_BUCKET);
                }
            }
        }
    }

    /**
     * @param string $field_output
     * @param string|null $field_db
     * @return string
     * @throws Exception
     */
    private function converter(string &$field_output, string $field_db = null) : string
    {
        $casts                                      = $this->converterCasts($field_output);
        if (!$field_db) {
            $field_db                               = $field_output;
        }

        $this->converterCallback($casts, $field_db, $field_output);

        return $this->converterStruct($field_db, $field_output);
    }

    /**
     * @param string $subject
     * @return array|null
     */
    private function converterCasts(string &$subject) : ?array
    {
        $casts                                      = null;
        if (strpos($subject, ":") !== false) {
            $casts                                  = explode(":", $subject);
            $subject                                = array_shift($casts);
        }

        return $casts;
    }

    /**
     * @param string $field_db
     * @param string|null $field_output
     * @return string
     * @throws Exception
     */
    private function converterStruct(string $field_db, string $field_output = null) : string
    {
        $struct_type                                = $this->getStructField($field_db);
        $casts                                      = $this->converterCasts($struct_type);

        $this->converterCallback($casts, $field_db, $field_output);

        return $struct_type;
    }

    /**
     * @param string $value
     * @return string|int
     * @todo da tipizzare
     */
    abstract protected function convertFieldSort(string $value);

    /**
     * @param array $res
     * @param string $name
     * @param string|null $or
     */
    abstract protected function convertFieldWhere(array &$res, string $name, string $or = null) : void;

    /**
     * @param string $key_primary
     * @return string
     */
    abstract protected function convertKeyPrimary(string $key_primary) : string;

    /**
     * @todo da tipizzare
     * @param array $res
     * @param $value
     * @param string $struct_type
     * @param string|null $name
     * @param string|null $or
     */
    private function fieldWhere(array &$res, $value, string $struct_type, string $name = null, string $or = null) : void
    {
        if ($this->key_name != $this->key_primary && $name == $this->key_primary) {
            $name = $this->convertKeyPrimary($name);
        }
        if (is_array($value)) {
            $res[$name] = $this->fieldOperations($value, $struct_type, $name);
        } else {
            $res[$name][self::OP_DEFAULT] = $this->fieldOperation($this->fieldIn($name, $value), $struct_type, $name, self::OP_DEFAULT);
        }

        $this->convertFieldWhere($res, $name, $or);
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @param mixed|null $value
     * @return string
     */
    private function fieldIn(string $name, $value = null)
    {
        if ($value && isset($this->in[$name])) {
            foreach ($this->in[$name] as $func => $params) {
                $value = $this->convertWith($value, strtoupper($func), $params);
            }
        }

        return $value;
    }

    /**
     * @todo da tipizzare
     * @param $value
     * @param string $struct_type
     * @param string|null $name
     * @param string|null $op
     * @return mixed
     */
    abstract protected function fieldOperation($value, string $struct_type, string $name = null, string $op = null);

    /**
     * @param string $struct_type
     * @param string $name
     * @param string|null $op
     * @return mixed
     * @todo da tipizzare
     */
    abstract protected function fieldOperationNULL(string $struct_type, string $name, string $op = null);

    /**
     * @param array $values
     * @param string $struct_type
     * @param string $name
     * @return array|null
     */
    private function fieldOperations(array $values, string $struct_type, string $name) : ?array
    {
        $res                                = null;

        foreach ($values as $op => $value) {
            if (!empty(self::OPERATOR_COMPARISON[$op])) {
                $res[$op]                   = $this->fieldOperation($this->fieldIn($name, $value), $struct_type, $name, $op);
            }
        }
        if (!$res) {
            $res[self::OP_ARRAY_DEFAULT]    = $this->fieldOperation($this->fieldIn($name, $values), $struct_type, $name, self::OP_ARRAY_DEFAULT);
        }

        return $res;
    }

    /**
     * @param array $fields
     * @param bool $use_control
     * @return array|string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function querySelect(array $fields, bool $use_control = true)
    {
        $res                                = array();
        if ($use_control) {
            $this->setIndex2Query();
        }

        foreach ($fields as $name => $value) {
            if (!isset($this->struct[$name])) {
                continue;
            }

            if (is_bool($value) || $name == $value) {
                $value                  = $name;
                $this->converter($name);
            } else {
                $this->converter($value, $name);
            }

            if ($this->key_name != $this->key_primary && $name == $this->key_primary) {
                $name = $this->convertKeyPrimary($name);
            }
            $res[$name]                 = $value;
        }

        ksort($res);
        $this->prototype                = $res;

        return $res + $this->index2query;
    }

    /**
     * @param array|null $fields
     * @return array|string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function querySort(array $fields = null)
    {
        $res                            = array();
        if ($fields) {
            foreach ($fields as $name => $value) {
                $this->converter($name);

                $res[$name]             = $this->convertFieldSort($value);
            }
        }
        return $res;
    }

    /**
     * @param array|null $fields
     * @return array|string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function queryWhere(array $fields = null)
    {
        $res                            = array();
        if (isset($fields[self::OR])) {
            //@todo da finire
            $or                         = $this->queryWhere($fields[self::OR]);
            unset($fields[self::OR]);
        }

        if ($fields) {
            foreach ($fields as $name => $value) {
                $struct_type            = $this->converter($name);
                if (is_array($value)) {
                    if (isset($value[self::AND])) {
                        if (count($value) > 1) {
                            Error::register('if you define $and you cant use values outside of $and or $or', static::ERROR_BUCKET);
                        }
                        $value          = $value[self::AND];
                    }

                    if (isset($value[self::OR])) {
                        $this->fieldWhere($res, $value[self::OR], $struct_type, $name, static::OR);
                        unset($value[self::OR]);
                    }
                }

                $this->fieldWhere($res, $value, $struct_type, $name);

                unset($res[static::OR]);
            }
        }
        return $res;
    }

    /**
     * @param array|null $fields
     * @return array
     * @throws Exception
     */
    protected function queryInsert(array $fields = null) : array
    {
        $res                            = array();
        if ($fields) {
            foreach ($fields as $name => $value) {
                if (!isset($this->struct[$name])) {
                    continue;
                }

                $struct_type            = $this->converter($name);

                $res[$name]             = (
                    $value === null
                    ? $this->fieldOperationNULL($struct_type, $name)
                    : $this->driver->toSql($this->fieldIn($name, $value), $struct_type)
                );
            }
        }
        ksort($res);

        return $res;
    }

    /**
     * @param array|null $fields
     * @return array|string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function queryUpdate(array $fields = null)
    {
        $res                                                = array();
        if ($fields) {
            foreach ($fields as $name => $value) {
                if (!isset($this->struct[$name])) {
                    continue;
                }

                $struct_type                                = $this->converter($name);
                if ($value === "++") {
                    $res[self::OP_INC_DEC][$name]           = $this->fieldOperation($this->fieldIn($name, $value), $struct_type, $name, self::OP_INC_DEC);
                } elseif ($value === "--") {
                    $res[self::OP_INC_DEC][$name]           = $this->fieldOperation($this->fieldIn($name, $value), $struct_type, $name, self::OP_INC_DEC . '-');
                } elseif ($value === "+") {
                    $res[self::OP_ADD_TO_SET][$name]        = $this->fieldOperation($this->fieldIn($name, $value), $struct_type, $name, self::OP_ADD_TO_SET);
                } else {
                    $res[self::OP_SET][$name]               = $this->fieldOperation($this->fieldIn($name, $value), $struct_type, $name, self::OP_SET);
                }
            }
        }
        return $res;
    }

    /**
     * @param DatabaseQuery $query
     * @return array|object|null
     * todo da tipizzare
     */
    private function processRead(DatabaseQuery $query)
    {
        return ($this->driver->read($query)
            ? $this->driver->getRecordset()
            : null
        );
    }

    /**
     * @param DatabaseQuery $query
     * @return array|null
     */
    private function processInsert(DatabaseQuery $query) : ?array
    {
        $res                                            = null;
        if ($this->driver->insert($query)) {
            $res                                        = array(
                                                            self::INDEX_PRIMARY => array(
                                                                $this->key_primary => $this->driver->getInsertID()
                                                            )
                                                        );
        }

        return $res;
    }

    /**
     * @param DatabaseQuery $query
     * @return array|null
     */
    private function processUpdate(DatabaseQuery $query) : ?array
    {
        $res                                            = null;
        if ($this->driver->update($query)) {
            $res                                        = array(
                                                            self::INDEX_PRIMARY => array(
                                                                $this->key_primary => null
                                                            )
                                                        );
        }

        return $res;
    }

    /**
     * @param DatabaseQuery $query
     * @return array|null
     */
    private function processDelete(DatabaseQuery $query) : ?array
    {
        $res                                            = null;
        if ($this->driver->delete($query)) {
            $res                                        = array(
                                                            self::INDEX_PRIMARY => array(
                                                                $this->key_primary => null
                                                            )
                                                        );
        }

        return $res;
    }

    /**
     * @param DatabaseQuery $query
     * @return array|null
     * @throws Exception
     */
    private function processWrite(DatabaseQuery $query) : ?array
    {
        //todo: da valutare se usare REPLACE INTO. Necessario test benckmark
        $res                                            = null;
        $keys                                           = array();
        $query_read                                     = clone $query;
        $query_read->select                             = $this->querySelect([$this->key_primary => true], false);
        if ($this->driver->read($query_read)) {
            $keys                                       = array_column((array) $this->driver->getRecordset(), $this->key_name);
        }

        if (count($keys)) {
            $query_update                               = clone $query;

            if ($this->driver->update($query_update)) {
                $res                                = array(
                                                        self::INDEX_PRIMARY => array(
                                                            $this->key_primary => $this->driver->getUpdatedIDs($keys)
                                                        ),
                                                        "action"    => self::ACTION_UPDATE
                                                    );
            }
        } elseif (!empty($query->insert)) {
            $query_insert                           = clone $query;
            if ($this->driver->insert($query_insert)) {
                $res                                = array(
                                                        self::INDEX_PRIMARY => array(
                                                            $this->key_primary => $this->driver->getInsertID()
                                                        ),
                                                        "action"    => self::ACTION_INSERT
                                                    );
            }
        }

        return $res;
    }

    /**
     * @param DatabaseQuery $query
     * @param string $action
     * @return array|null
     */
    private function processCmd(DatabaseQuery $query, string $action) : ?array
    {
        return $this->driver->cmd($query, $action);
    }
    /**
     * DatabaseAdapter constructor.
     * @param string|null $main_table
     * @param array|null $table
     * @param array|null $struct
     * @param array|null $relationship
     * @param array|null $indexes
     * @param string|null $key_primary
     */
    public function __construct(string $main_table = null, array $table = null, array $struct = null, array $indexes = null, array $relationship = null, string $key_primary = null)
    {
        $this->main_table                               = $main_table;
        $this->table                                    = $table;
        $this->struct                                   = $struct;
        $this->indexes                                  = $indexes;
        $this->relationship                             = $relationship;
        $this->key_primary                              = $key_primary;
    }

    /**
     * @return bool
     * @throws Exception
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
     * @todo da tipizzare
     * @param $query
     * @return array|null
     */
    protected function processRawQuery($query) : ?array
    {
        return ($this->driver->rawQuery($query)
            ? $this->driver->getRecordset()
            : null
        );
    }

    /**
     * @return array
     * @throws Exception
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
     *
     */
    private function setIndex2Query() : void
    {
        if (!empty($this->indexes)) {
            $indexes = array_keys($this->indexes);
            $this->index2query = array_combine($indexes, $indexes);
        }

        if ($this->key_primary) {
            $this->index2query[$this->key_primary]  = $this->key_primary;
        }
    }

    /**
     * @param string $action
     * @param string|null $table_name
     * @param bool $calc_found_rows
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQuery(string $action, string $table_name = null, bool $calc_found_rows = false) : DatabaseQuery
    {
        $this->clearResult();

        if ($this->loadDriver()) {
            $this->table_name = (
                $table_name
                ? $table_name
                : $this->getTable("name")
            );

            if (!$this->key_name) {
                Error::register(static::TYPE . " key missing", static::ERROR_BUCKET);
            }
            if (!$this->table_name) {
                Error::register(static::TYPE . " table missing", static::ERROR_BUCKET);
            }
        } else {
            Error::register("Connection failed to database: " . static::TYPE, static::ERROR_BUCKET);
        }

        return new DatabaseQuery($action, $this->table_name, $this->key_primary, $calc_found_rows);
    }

    /**
     * @param array $select
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $table_name
     * @param bool $calc_found_rows
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryRead(array $select, array $where = null, array $sort = null, int $limit = null, int $offset = null, bool $calc_found_rows = false, string $table_name = null) : DatabaseQuery
    {
        $query                  = $this->getQuery(self::ACTION_READ, $table_name, $calc_found_rows);

        $query->select          = $this->querySelect($select, empty($this->table["skip_control"]));
        $query->sort            = $this->querySort($sort);
        $query->where           = $this->queryWhere($where);
        $query->limit           = $limit    < 0 ? null : $limit;
        $query->offset          = $offset   < 0 ? null : $offset;

        return $query;
    }

    /**
     * @param array $insert
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryInsert(array $insert, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_INSERT, $table_name);

        $query->insert          = $this->queryInsert($insert);

        return $query;
    }

    /**
     * @param array $set
     * @param array $where
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryUpdate(array $set, array $where, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_UPDATE, $table_name);

        $query->update          = $this->queryUpdate($set);
        $query->where           = $this->queryWhere($where);

        return $query;
    }

    /**
     * @param array $insert
     * @param array $set
     * @param array $where
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryWrite(array $insert, array $set, array $where, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_WRITE, $table_name);

        $query->insert          = $this->queryInsert($insert);
        $query->update          = $this->queryUpdate($set);
        $query->where           = $this->queryWhere($where);

        return $query;
    }

    /**
     * @param array $where
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryDelete(array $where, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_DELETE, $table_name);

        $query->where           = $this->queryWhere($where);

        return $query;
    }

    /**
     * @param array $where
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryCmd(array $where, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_CMD, $table_name);

        $query->where           = $this->queryWhere($where);

        return $query;
    }

    /**
     * @param string|null $table
     * @param null $key
     */
    private function setTable(string $table = null, $key = null) : void
    {
        if ($table) {
            if ($key) {
                $this->table[$key]                                  = $table;
            } else {
                $this->table                                        = array(
                                                                        "name"      => $table,
                                                                        "alias"     => $table,
                                                                        "engine"    => "InnoDB",
                                                                        "crypt"     => false,
                                                                        "pairing"   => false,
                                                                        "transfert" => false,
                                                                        "charset"   => "utf8"
                                                                    );
            }
        }
    }

    /**
     * @todo da tipizzare
     * @param $query
     * @return array|null
     */
    public function rawQuery($query) : ?array
    {
        $this->clearResult();

        return $this->processRawQuery($query);
    }

    /**
     * @param array $fields
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $calc_found_rows
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function read(array $fields, array $where = null, array $sort = null, int $limit = null, int $offset = null, bool $calc_found_rows = false, string $table_name = null) : ?array
    {
        $res                                                    = null;
        $query                                                  = $this->getQueryRead($fields, $where, $sort, $limit, $offset, $calc_found_rows, $table_name);

        $db                                                     = $this->processRead($query);
        if ($db) {
            $res[self::RESULT]                                  = $db;
            $res[self::COUNT]                                   = $this->driver->numRows();

            $count_recordset                                    = (is_array($res[self::RESULT]) ? count($res[self::RESULT]) : null);
            if ($count_recordset && (!empty($query->limit) || $count_recordset < static::MAX_NUMROWS)) {
                $this->convertRecordset($res, array_flip($this->index2query));
            }
        }

        return $res;
    }

    /**
     * @param array $db
     * @param array|null $indexes
     * @throws Exception
     */
    protected function convertRecordset(array &$db, array $indexes = null) : void
    {
        $use_control                                    = !empty($indexes);
        if ($use_control || count($this->to)) {
            foreach ($db[self::RESULT] as &$record) {
                if ($use_control) {
                    $index                              = array_intersect_key($record, $indexes);

                    if (isset($record[$this->key_name])) {
                        $index[$this->key_primary]      = $record[$this->key_name];
                    }

                    $db[self::INDEX][]                  = $index;
                }

                $record                                 = $this->fields2output($record);
            }
        }
    }

    /**
     * @param array $insert
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function insert(array $insert, string $table_name = null) : ?array
    {
        if (empty($insert)) {
            Error::register(self::ERROR_INSERT_IS_EMPTY);
        }

        return $this->processInsert($this->getQueryInsert($insert, $table_name));
    }

    /**
     * @param array $set
     * @param array $where
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function update(array $set, array $where, string $table_name = null) : ?array
    {
        if (empty($set) || empty($where)) {
            Error::register(self::ERROR_UPDATE_IS_EMPTY);
        }

        return $this->processUpdate($this->getQueryUpdate($set, $where, $table_name));
    }

    /**
     * @param array $insert
     * @param array $set
     * @param array $where
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function write(array $insert, array $set, array $where, string $table_name = null) : ?array
    {
        if (empty($insert) || empty($set) || empty($where)) {
            Error::register(self::ERROR_WRITE_IS_EMPTY);
        }

        return $this->processWrite($this->getQueryWrite($insert, $set, $where, $table_name));
    }

    /**
     * @param array $where
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function delete(array $where, string $table_name = null) : ?array
    {
        if (empty($where)) {
            Error::register(self::ERROR_DELETE_IS_EMPTY);
        }

        return $this->processDelete($this->getQueryDelete($where, $table_name));
    }

    /**
     * @param array $where
     * @param string $action
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function cmd(array $where, string $action = self::CMD_COUNT, string $table_name = null) : ?array
    {
        if (empty($where)) {
            Error::register(self::ERROR_CMD_IS_EMPTY);
        }

        return $this->processCmd($this->getQueryCmd($where, $table_name), $action);
    }


    /**
     * @param string $key
     * @return string
     * @throws Exception
     */
    private function getStructField(string $key) : string
    {
        if (!isset($this->struct[$key])) {
            Error::register("Field: '" . $key . "' not found in struct on table: " . $this->table["name"], static::ERROR_BUCKET);
        }

        return $this->struct[$key];
    }

    /**
     *
     */
    private function clearResult()
    {
        $this->table_name                                           = null;
        $this->index2query                                          = array();
        $this->prototype                                            = array();
        $this->to                                                   = array();
        $this->in                                                   = array();
    }

    /**
     * @param array $record
     * @return array
     * @throws Exception
     */
    protected function fields2output(array $record) : array
    {
        $keys                                                       = array_intersect_key($this->prototype, $record);
        $values                                                     = array_intersect_key($record, $this->prototype);

        $res                                                        = array_combine($keys, $values);
        if (!empty($this->to)) {
            foreach ($this->to as $field => $funcs) {
                foreach ($funcs as $func => $params) {
                    $res[$field]                                    = $this->to($res[$field], $func, $params);
                }
            }
        }
        return $res;
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
     * @param string $func
     * @param array|null $params
     * @return string
     * @throws Exception
     */
    private function to(string $source, string $func, array $params = null) : ?string
    {
        $res                                                                = null;
        switch (strtoupper($func)) {
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
                }
                break;
            case "TIMEELAPSED":
                $time                                                       = time() - $source; // to get the time since that moment
                $time                                                       = ($time < 1) ? 1 : $time;
                $day                                                        = 86400;
                $min                                                        = 60;
                if ($time < 2 * $day) {
                    if ($time < $min) {
                        $res                                                = Translator::getWordByCode("about") . " " . Translator::getWordByCode("a") . " " . Translator::getWordByCode("minute") . " " . Translator::getWordByCode("ago");
                    } elseif ($time > $day) {
                        $res                                                = Translator::getWordByCode("yesterday") . " " . Translator::getWordByCode("at") . " " . date("G:i", $source);
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
                            $res                                            .= ' ' . Translator::getWordByCode($text . (($res > 1) ? 's' : '')) . " " . Translator::getWordByCode("ago");
                            break;
                        }
                    }
                }
                break;
            case "DATETIME":
                $lang                                                       = Locale::getCodeLang();
                $oData                                                      = new Data($source, "Timestamp");
                $res                                                        = $oData->getValue("Date", $lang);

                if ($lang == "en") {
                    $prefix                                                 = "+";
                    $res                                                    = "+" . $res;
                } else {
                    $prefix                                                 = "/";
                }

                $conv                                                       = array(
                                                                                $prefix . "01/" => " " . Translator::getWordByCode("Januaunable to updatery") . " "
                                                                                , $prefix . "02/" => " " . Translator::getWordByCode("February") . " "
                                                                                , $prefix . "03/" => " " . Translator::getWordByCode("March") . " "
                                                                                , $prefix . "04/" => " " . Translator::getWordByCode("April") . " "
                                                                                , $prefix . "05/" => " " . Translator::getWordByCode("May") . " "
                                                                                , $prefix . "06/" => " " . Translator::getWordByCode("June") . " "
                                                                                , $prefix . "07/" => " " . Translator::getWordByCode("July") . " "
                                                                                , $prefix . "08/" => " " . Translator::getWordByCode("August") . " "
                                                                                , $prefix . "09/" => " " . Translator::getWordByCode("September") . " "
                                                                                , $prefix . "10/" => " " . Translator::getWordByCode("October") . " "
                                                                                , $prefix . "11/" => " " . Translator::getWordByCode("November") . " "
                                                                                , $prefix . "12/" => " " . Translator::getWordByCode("December") . " "
                                                                            );
                $res                                                        = str_replace(array_keys($conv), array_values($conv), $res);
                if ($prefix) {
                    $res                                                    = str_replace("/", ", ", $res);
                }
                $res                                                        .= " " . Translator::getWordByCode("at") . " " . Translator::getWordByCode("hours") . " " . $oData->getValue("Time", Locale::getCodeLang());

                break;
            case "DATE":
                $oData                                                      = new Data($source, "Timestamp");
                $res                                                        = $oData->getValue("Date", Locale::getCodeLang());
                break;
            case "TIME":
                $oData                                                      = new Data($source, "Timestamp");
                $res                                                        = $oData->getValue("Time", Locale::getCodeLang());
                break;
            case "STRING":
                $res                                                        = $source;
                break;
            case "SLUG":
                $res                                                        = Normalize::urlRewrite($source);
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
                $res                                                        = $this->decrypt($source, $params[0], $func);
                break;
            default:
                if (!$params && is_callable($func)) {
                    $res                                                    = $func($source);
                } else {
                    Error::register("ConversionTo not Managed: " . $func . " for " . $source, static::ERROR_BUCKET);
                }
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
                $res                                                        = Normalize::urlRewrite($data);
                break;
            default:
                $res                                                        = (
                    $params
                    ? $data
                    : $method($data)
                );
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
}
