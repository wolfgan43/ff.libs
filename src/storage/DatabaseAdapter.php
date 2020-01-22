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
use phpformsframework\libs\tpl\Gridsystem;
use phpformsframework\libs\security\Validator;
use Exception;

/**
 * Class DatabaseAdapter
 * @package phpformsframework\libs\storage
 */
abstract class DatabaseAdapter
{
    private const OPERATOR_COMPARISON   = [
                                            '$gt'       => self::FTYPE_NUMBER,
                                            '$gte'      => self::FTYPE_NUMBER,
                                            '$lt'       => self::FTYPE_NUMBER,
                                            '$lte'      => self::FTYPE_NUMBER,
                                            '$eq'       => false,
                                            '$regex'    => self::FTYPE_STRING,
                                            '$in'       => self::FTYPE_ARRAY,
                                            '$nin'      => self::FTYPE_ARRAY,
                                            '$ne'       => false,
                                            '$inset'    => self::FTYPE_ARRAY,
                                            '$inc'      => false,
                                            '$inc-'     => false,
                                            '$addToSet' => false,
                                            '$set'      => false
                                        ];

    protected const OR                  = '$or';
    protected const AND                 = '$and';
    private const OP_ARRAY_DEFAULT      = '$in';
    private const OP_DEFAULT            = '$eq';
    protected const OP_INC_DEC          = '$inc';
    protected const OP_ADD_TO_SET       = '$addToSet';
    protected const OP_SET              = '$set';

    private const ACTION_READ           = DatabaseDriver::ACTION_READ;
    private const ACTION_DELETE         = DatabaseDriver::ACTION_DELETE;
    private const ACTION_INSERT         = DatabaseDriver::ACTION_INSERT;
    private const ACTION_UPDATE         = DatabaseDriver::ACTION_UPDATE;
    private const ACTION_WRITE          = "write";
    private const ACTION_CMD            = "cmd";

    public const CMD_COUNT              = DatabaseDriver::CMD_COUNT;
    public const CMD_PROCESS_LIST       = DatabaseDriver::CMD_PROCESS_LIST;

    protected const ERROR_BUCKET        = Database::ERROR_BUCKET;

    protected const TYPE                = null;
    protected const PREFIX              = null;
    protected const KEY_NAME            = null;
    protected const KEY_REL             = "ID_";
    protected const KEY_IS_INT          = false;

    protected const RESULT              = Database::RESULT;
    protected const INDEX               = Database::INDEX;
    protected const INDEX_PRIMARY       = Database::INDEX_PRIMARY;
    protected const COUNT               = Database::COUNT;
    protected const CALC_FOUND_ROWS     = "calc_found_rows"; //todo presente in mysqli da togliere

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

    protected $main_table    			= null;
    protected $struct					= null;
    protected $relationship			    = null;
    protected $indexes					= null;
    protected $table                    = null;

    private $select                     = null;
    private $insert                     = null;
    private $set                     	= null;
    private $where                      = null;
    private $sort                      	= null;
    private $limit                     	= null;
    private $offset                     = null;

    private $index2query                = array();

    /**
     * @var DatabaseDriver
     */
    protected $driver                   = null;
    protected $query                    = null;

    private $prototype                  = array();
    private $to                         = array();
    private $in                         = array();


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
     * @return string
     */
    abstract protected function convertFieldSort(string $value) : string;

    /**
     * @param array $res
     * @param string $name
     * @param string|null $or
     */
    abstract protected function convertFieldWhere(array &$res, string $name, string $or = null) : void;

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
        if (!empty($value)) {
            if (is_array($value)) {
                $res[$name] = $this->fieldOperations($value, $struct_type, $name);
            } else {
                $res[$name][self::OP_DEFAULT] = $this->fieldOperation($this->fieldIn($value, $name), $struct_type, $name, self::OP_DEFAULT);
            }

            $this->convertFieldWhere($res, $name, $or);
        }
    }

    /**
     * @todo da tipizzare
     * @param $value
     * @param string $name
     * @return string
     */
    private function fieldIn($value, string $name)
    {
        if (isset($this->in[$name])) {
            foreach ($this->in[$name] as $func => $params) {
                $value = $this->convertWith($value, strtoupper($func), $params);
            }
        }

        return $value;
    }

    /**
     * @param $value
     * @param string $struct_type
     * @param string|null $name
     * @param string|null $op
     * @return string
     */
    abstract protected function fieldOperation($value, string $struct_type, string $name = null, string $op = null) : string;

    /**
     * @param array $values
     * @param string $struct_type
     * @param string $name
     * @return array|null
     */
    private function fieldOperations(array $values, string $struct_type, string $name) : ?array
    {
        $res                                                = null;

        foreach ($values as $op => $value) {
            if (!empty(self::OPERATOR_COMPARISON[$op])) {
                $res[$op] = $this->fieldOperation($this->fieldIn($value, $name), $struct_type, $name, $op);
            }
        }
        if (!$res) {
            $res[self::OP_ARRAY_DEFAULT] = $this->fieldOperation($this->fieldIn($values, $name), $struct_type, $name, self::OP_ARRAY_DEFAULT);
        }
        return $res;
    }

    /**
     * @todo da tipizzare
     * @param array|null $fields
     * @return array|string
     */
    protected function querySelect(array $fields = null)
    {
        $res                            = $this->setIndex2Query();
        if (!$fields) {
            $fields                     = array_fill_keys(array_keys(array_diff_key($this->struct, $this->indexes)), true);
        }

        foreach ($fields as $name => $value) {
            if (is_bool($value) || $name == $value) {
                $value                  = $name;
                $this->converter($name);
            } else {
                $this->converter($value, $name);
            }

            $res[$name]                 = $value;
            $this->prototype[$name]     = $value;
        }

        return $res;
    }

    /**
     * @todo da tipizzare
     * @param array|null $fields
     * @return array|string
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
     * @todo da tipizzare
     * @param array|null $fields
     * @return array|string
     */
    protected function queryWhere(array $fields = null)
    {
        $res                            = array();
        if (isset($this->where[self::OR])) {
            //@todo da finire
            $or                         = $this->queryWhere($this->where[self::OR]);
            unset($this->where[self::OR]);
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
     */
    protected function queryInsert(array $fields = null) : array
    {
        $res                            = array();
        if ($fields) {
            foreach ($fields as $name => $value) {
                $struct_type            = $this->converter($name);

                $res[$name]             = $this->driver->toSql($this->fieldIn($value, $name), $struct_type);
            }
        }
        return $res;
    }

    /**
     * @todo da tipizzare
     * @param array|null $fields
     * @return array|string
     */
    protected function queryUpdate(array $fields = null)
    {
        //aggiungere ++ -- ecc
        $res                                                = array();
        if ($fields) {
            foreach ($fields as $name => $value) {
                $struct_type                                = $this->converter($name);
                switch ($value) {
                    case "++":
                        $res[self::OP_INC_DEC][$name]       = $this->fieldOperation($this->fieldIn($value, $name), $struct_type, $name, self::OP_INC_DEC);
                        break;
                    case "--":
                        $res[self::OP_INC_DEC][$name]       = $this->fieldOperation($this->fieldIn($value, $name), $struct_type, $name, self::OP_INC_DEC . '-');
                        break;
                    case "+":
                        $res[self::OP_ADD_TO_SET][$name]    = $this->fieldOperation($this->fieldIn($value, $name), $struct_type, $name, self::OP_ADD_TO_SET);
                        break;
                    default:
                        $res[self::OP_SET][$name]           = $this->fieldOperation($this->fieldIn($value, $name), $struct_type, $name, self::OP_SET);
                }
            }
        }
        return $res;
    }

    /**
     * @param DatabaseQuery $query
     * @return array|null
     */
    private function processRead(DatabaseQuery $query) : ?array
    {
        $res                                            = null;

        if ($this->driver->read($query)) {
            $res[static::RESULT]                        = $this->driver->getRecordset();
            $res[static::COUNT]                         = $this->driver->numRows();
        }

        return $res;
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
                                                            static::INDEX_PRIMARY => array(
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
                                                            static::INDEX_PRIMARY => array(
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
                                                            static::INDEX_PRIMARY => array(
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
    private function processWrite(DatabaseQuery $query) : ?array
    {
        //todo: da valutare se usare REPLACE INTO. Necessario test benckmark
        $res                                            = null;
        $keys                                           = null;
        $query_read                                     = clone $query;
        $query_read->select                             = $this->querySelect([$this->key_primary => true]);
        if ($this->driver->read($query_read)) {
            $keys                                       = array_column($this->driver->getRecordset(), $query_read->key_primary);
        }

        if (is_array($keys)) {
            $query_update                               = clone $query;
            $query_update->where                        = $this->queryWhere([$this->key_primary => $keys]);
            if ($this->driver->update($query_update)) {
                $res                                = array(
                                                        array(
                                                            static::INDEX_PRIMARY => array(
                                                                $this->key_primary => $keys
                                                            )
                                                        ),
                                                        "action"    => self::ACTION_UPDATE
                                                    );
            }
        } elseif (!empty($query->insert)) {
            if ($this->driver->insert($query)) {
                $res                                = array(
                                                        array(
                                                            static::INDEX_PRIMARY => array(
                                                                $this->key_primary => $this->driver->getInsertID()
                                                            )
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
     * @return array
     */
    private function setIndex2Query() : array
    {
        if (is_array($this->indexes) && count($this->indexes)) {
            $indexes = array_keys($this->indexes);
            $this->index2query = array_combine($indexes, $indexes);
        }
        if ($this->key_primary) {
            $this->index2query[$this->key_primary]  = $this->key_primary;
        }

        return array_diff_key($this->index2query, (array) $this->select);
    }

    /**
     * @param string $action
     * @param string|null $table_name
     * @return array|null
     */
    private function getQuery(string $action, string $table_name = null) : ?DatabaseQuery
    {
        $query                              = null;
        if ($this->loadDriver()) {
            if (!$table_name) {
                $table_name                 = $this->getTable("name");
            }
            if (!$this->key_name) {
                Error::register(static::TYPE . " key missing", static::ERROR_BUCKET);
            }
            if (!$table_name) {
                Error::register(static::TYPE . " table missing", static::ERROR_BUCKET);
            }
            if (!is_array($this->insert)
                && ($action == self::ACTION_INSERT || $action == self::ACTION_WRITE)) {
                Error::register("insert is empty", static::ERROR_BUCKET);
            }
            if (!is_array($this->set) && !is_array($this->where)
                && ($action == self::ACTION_UPDATE || $action == self::ACTION_WRITE)) {
                Error::register("set or where is empty", static::ERROR_BUCKET);
            }
            if (!is_array($this->where)
                && ($action == self::ACTION_DELETE)) {
                Error::register("where is empty", static::ERROR_BUCKET);
            }

            $query = new DatabaseQuery($action, $table_name, $this->key_primary);

            switch ($action) {
                case self::ACTION_READ:
                    $query->select          = $this->querySelect($this->select);
                    $query->sort            = $this->querySort($this->sort);
                    $query->where           = $this->queryWhere($this->where);
                    $query->limit           = $this->limit;
                    $query->offset          = $this->offset;
                    break;
                case self::ACTION_INSERT:
                    $query->insert          = $this->queryInsert($this->insert);
                    break;
                case self::ACTION_UPDATE:
                    $query->update          = $this->queryUpdate($this->set);
                    $query->where           = $this->queryWhere($this->where);
                    break;
                case self::ACTION_WRITE:
                    $query->insert          = $this->queryInsert($this->insert);
                    $query->update          = $this->queryUpdate($this->set);
                    break;
                case self::ACTION_DELETE:
                    $query->where           = $this->queryWhere($this->where);
                    break;
                default:
                    $query->select          = $this->querySelect($this->select);
                    $query->where           = $this->queryWhere($this->where);
            }
        } else {
            Error::register("Connection failed to database: " . static::TYPE, static::ERROR_BUCKET);
        }

        return $query;
    }


    /**
     * @param DatabaseQuery $query
     * @return array|null
     */
    private function process(DatabaseQuery $query) : ?array
    {
        $res                                                        = null;
        switch ($query->action) {
            case self::ACTION_READ:
                $db                                             = $this->processRead($query);
                if ($db) {
                    $count_recordset                            = count($db[static::RESULT]);
                    if (!empty($query->limit) || $count_recordset < static::MAX_NUMROWS) {
                        $this->index2query = array(); //@todo da correggere con .*
                        if ($count_recordset && (count($this->index2query) || count($this->to)) && $count_recordset <= static::MAX_RESULTS) {
                            foreach ($db[static::RESULT] as $record) {
                                $res[static::RESULT][]          = $this->fields2output($record);
                                $res[static::INDEX][]           = array_intersect_key($record, $this->index2query);
                            }
                            $res[static::COUNT]                 = $db[static::COUNT];
                        } else {
                            $res                                =& $db;
                        }
                    }
                }

                break;
            case self::ACTION_INSERT:
                $res                                            = $this->processInsert($query);
                break;
            case self::ACTION_UPDATE:
                $res                                            = $this->processUpdate($query);
                break;
            case self::ACTION_DELETE:
                $res                                            = $this->processDelete($query);
                break;
            case self::ACTION_WRITE:
                $res                                            = $this->processWrite($query);
                break;
            default:
                Error::register("Action not Managed", static::ERROR_BUCKET);
        }

        return $res;
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
     * @param array $where
     * @param array|null $fields
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $table_name
     * @return array|null
     */
    public function read(array $where, array $fields = null, array $sort = null, int $limit = null, int $offset = null, string $table_name = null) : ?array
    {
        $this->clearResult();

        $this->where                                                = $where;
        $this->sort                	                                = $sort;
        $this->limit                                                = $limit;
        $this->offset                                               = $offset;
        $this->select                                               = (array) $fields;

        $query                                                      = $this->getQuery(self::ACTION_READ, $table_name);

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

        $query                                                      = $this->getQuery(self::ACTION_INSERT, $table_name);

        return $this->process($query);
    }

    /**
     * @param array $set
     * @param array $where
     * @param string|null $table_name
     * @return array|null
     */
    public function update(array $set, array $where, string $table_name = null) : ?array
    {
        $this->clearResult();

        $this->set                                                  = $set;
        $this->where                                                = $where;

        $query                                                      = $this->getQuery(self::ACTION_UPDATE, $table_name);

        return $this->process($query);
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

        $query                                                      = $this->getQuery(self::ACTION_WRITE, $table_name);

        return $this->process($query);
    }

    /**
     * @param array $where
     * @param string|null $table_name
     * @return array|null
     */
    public function delete(array $where, string $table_name = null) : ?array
    {
        $this->clearResult();

        $this->where                                                = $where;

        $query                                                      = $this->getQuery(self::ACTION_DELETE, $table_name);

        return $this->process($query);
    }

    /**
     * @param array $where
     * @param string $action
     * @param string|null $table_name
     * @return array|null
     */
    public function cmd(array $where, string $action = self::CMD_COUNT, string $table_name = null) : ?array
    {
        $this->clearResult();

        $this->where                                                = $where;

        $query                                                      = $this->getQuery(self::ACTION_CMD, $table_name);

        return $this->processCmd($query, $action);
    }


    /**
     * @param string $key
     * @return string
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
        $this->select                                               = null;
        $this->insert                                               = null;
        $this->set                                                  = null;
        $this->where                                                = null;
        $this->sort                                                 = null;
        $this->limit                                                = null;

        $this->index2query                                          = array();
        $this->prototype                                            = array();
        $this->to                                                   = array();
        $this->in                                                   = array();
    }

    /**
     * @param array $record
     * @return array
     */
    private function fields2output(array $record) : array
    {
        $res = array_combine(array_values($this->prototype), array_intersect_key($record, $this->prototype));
        if (is_array($this->to) && count($this->to)) {
            foreach ($this->to as $field => $funcs) {
                foreach ($funcs as $func => $params) {
                    $res[$field] = $this->to($res[$field], $func, $params);
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
                $res                                                        = $source;
                break;
            case "SLUG":
                $res                                                        = Validator::urlRewrite($source);
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
                if (!$params) {
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
                $res                                                        = Validator::urlRewrite($data);
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
