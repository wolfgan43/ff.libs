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
namespace ff\libs\storage;

use ArrayObject;
use ff\libs\ClassDetector;
use ff\libs\dto\DataResponse;
use ff\libs\dto\DataTableResponse;
use ff\libs\dto\Mapping;
use ff\libs\security\Validator;
use ff\libs\storage\dto\OrmResults;
use ff\libs\util\Normalize;
use ff\libs\Exception;
use stdClass;

/**
 * Class OrmItem
 * @package ff\libs\storage
 */
abstract class OrmItem
{
    use ClassDetector;
    use Mapping;
    use OrmUtil;
    use Hoockable;

    private const ERROR_RECORDSET_EMPTY                                         = "recordset empty";

    private static $buffer                                                      = null;

    private const LOGICAL_OPERATORS                                             = [
        '$or'           => '$or',
        '$and'          => '$and'
    ];

    private const SEARCH_OPERATORS                                              = [
        'gt'            => '$gt',
        'gte'           => '$gte',
        'lt'            => '$lt',
        'lte'           => '$lte',
        'ne'            => '$ne',
        'nin'           => '$nin',
        'in'            => '$in',
        'regex'         => '$regex'
    ];

    protected const COLLECTION                                                  = null;
    protected const TABLE                                                       = null;
    protected const TABLE_SEARCH                                                = null;
    protected const TABLE_READ                                                  = null;

    protected const PRIMARY_KEY                                                 = null;
    protected const JOINS                                                       = [];
    protected const DELETE_JOINS                                                = [];
    protected const DELETE_LOGICAL_FIELD                                        = null;
    protected const ENABLE_UPDATE_NESTED                                        = false;

    protected const REQUIRED                                                    = [];
    protected const REQUIRED_SEARCH                                             = [];

    /**
     * you can specify for each field witch validator you want:
     *  - Associative array field_name => validator
     *  - Validator can be:
     *      - Array of value
     *      - Callback with the value as args. The return must be boolean.
     *        if true the value is valid.
     *      - String you can use the validation in class Validator.
     * @example
     * ->dbValidator[
     *      "field_a" => ["myOptionA", "myOptionB", "myOptionC"],
     *      "field_b" => "Mycallback",
     *      "field_c" => "password"
     * ];
     * @var array
     *
     */
    protected const VALIDATOR                                                   = [];
    protected const CONVERSION                                                  = [];

    protected const DATARESPONSE                                                = [];
    protected const SEARCH                                                      = [];

    /**
     * @var OrmModel[]|null
     */
    private $models                                                             = [];
    private $model                                                              = null;

    private $indexes                                                            = [];
    private $storedData                                                         = [];

    private $oneToOne                                                           = [];
    private $oneToMany                                                          = [];
    /**
     * @var stdClass|null
     */
    private $informationSchema                                                  = null;

    /**
     * @param array|null $where
     * @param array|null $fill
     * @return array|null
     */
    abstract protected function onLoad(array &$where = null, array &$fill = null)   : ?array;

    /**
     * @param Model $db
     */
    abstract protected function onCreate($db)                                       : void;

    /**
     * @param Model $db
     * @param string $recordKey
     */
    abstract protected function onReadAfter($db, string $recordKey)                 : void;

    /**
     * @param array $record
     * @param array $db_record
     */
    abstract protected function onRead(array &$record, array $db_record)            : void;

    /**
     * @param array $record
     * @param array $db_record
     */
    abstract protected function onSearch(array &$record, array $db_record)          : void;

    /**
     * @param array $fields
     */
    abstract protected function onFill(array &$fields)                              : void;

    /**
     * @param Model $db
     * @param string $recordKey
     */
    abstract protected function onChange($db, string $recordKey)                    : void;

    /**
     * @param Model $db
     * @param string|null $recordKey
     */
    abstract protected function onApply($db, string $recordKey = null)              : void;

    /**
     * @param array $record
     */
    abstract protected function onInsert(array &$record)                            : void;

    /**
     * @param array $record
     * @param string $recordKey
     */
    abstract protected function onUpdate(array &$record, string $recordKey)         : void;

    /**
     * @param Model $db
     * @param string $recordKey
     */
    abstract protected function onDelete($db, string $recordKey)                    : void;

    /**
     * @return stdClass
     */
    private static function dtd() : stdClass
    {
        return (object) [
                "collection"        => static::COLLECTION,
                "table"             => static::TABLE,
                "table_read"        => static::TABLE_READ,
                "dataResponse"      => static::DATARESPONSE,
                "required"          => static::REQUIRED,
                "primaryKey"        => static::PRIMARY_KEY
            ];
    }

    /**
     * @param array|null $query
     * @param array|null $order
     * @param int|null $limit
     * @param int|null $offset
     * @param int|null $draw
     * @param null $model_name
     * @return DataTableResponse
     * @throws Exception
     * @todo da tipizzare
     */
    public static function search(array $query = null, array $order = null, int $limit = null, int $offset = null, int $draw = null, $model_name = null)
    {
        $item                                                                   = new static();
        $toDataResponse                                                         = $item::SEARCH;

        $where                                                                  = self::where($query);
        $sort                                                                   = self::sort($order, $toDataResponse);

        if (!empty($model_name)) {
            foreach (Model::columns($model_name) as $dbField => $keyField) {
                $toDataResponse[$dbField] = $keyField;
                if (isset($where[$keyField])) {
                    $where[$dbField] = $where[$keyField];
                    unset($where[$keyField]);
                }
                if (isset($sort[$keyField])) {
                    $sort[$dbField] = $sort[$keyField];
                    unset($sort[$keyField]);
                }
            }
        }

        $item->hook()->register("onRead", function(array &$record, array $db_record) use ($item) {
            $item->onSearch($record, $db_record);
        });

        $where_primary = array_intersect_key($where ?? [], array_fill_keys(static::REQUIRED_SEARCH, true));
        if(!empty(static::REQUIRED_SEARCH) && empty($where_primary)) {
            throw new Exception("Fields (" . implode(", ", static::REQUIRED_SEARCH) . ") are required. in table " . (static::TABLE_SEARCH ?? static::TABLE) . " for collection " . static::COLLECTION, 400);
        }

        return $item->db
            ->table($item::TABLE_SEARCH ?? $item::TABLE, $toDataResponse)
            ->setHook($item->hook)
            ->setWherePrimary($where_primary)
            ->read($where, $sort, $limit, $offset)
            ->toDataTable(function (OrmResults $results, DataTableResponse $dataTableResponse) use ($draw) {
                $dataTableResponse->draw = $draw + 1;
            });
    }

    /**
     * @param array|null $query
     * @return DataResponse
     * @throws Exception
     * @todo da tipizzare
     */
    public static function count(array $query = null) : DataResponse
    {
        $item                                                                   = new static();
        $where                                                                  = self::where($query);

        $recordsTotal                                                           = $item->db
            ->table($item::TABLE_SEARCH ?? $item::TABLE)
            ->setHook($item->hook)
            ->setWherePrimary(array_intersect_key($where ?? [], array_fill_keys(static::REQUIRED_SEARCH, true)))
            ->count();

        return new DataResponse([
            "recordsTotal"      => $recordsTotal,
            "recordsFiltered"   => (
                empty($query)
                ? $recordsTotal
                : $item->db
                    ->cmd("count", $where)
            )
        ]);
    }

    private static function where(array $query = null) : ?array
    {
        $where                                                                      = null;
        if (is_array($query)) {
            foreach ($query as $key => $value) {
                if (isset(self::LOGICAL_OPERATORS[$key])) {
                    $where[$key]                                                    = $value;
                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $op => $subvalue) {
                        if (substr($op, 0, 1) == '$') {
                            continue;
                        }

                        if (isset(self::SEARCH_OPERATORS[$op])) {
                            $value[self::SEARCH_OPERATORS[$op]]                     = (
                            (self::SEARCH_OPERATORS[$op] == '$in' || self::SEARCH_OPERATORS[$op] == '$nin') && !is_array($subvalue)
                                ?  explode(",", $subvalue)
                                : $subvalue
                            );
                        } else {
                            $value['$in'][]                                         = $subvalue;
                            unset($value[$op]);
                        }
                    }
                } elseif (strpos($value, "*") !== false) {
                    $value                                                          = ['$regex' => $value];
                }
                $where[$key]                                                        = $value;
            }
        }

        return $where;
    }

    private static function sort(array $order = null, array $toDataResponse = null) : ?array
    {
        $sort                                                                       = null;
        if (is_array($order)) {
            foreach ($order as $key => $value) {
                if (isset($value["column"])) {
                    if (is_array($value["column"])) {
                        throw new Exception("Multi array not supported in order: " . $key, 500);
                    }
                    if (!isset($toDataResponse[$value["column"]]) && is_numeric($value["column"])) {
                        throw new Exception("Order Column value not found: " . $key, 500);
                    }

                    $sort[$toDataResponse[$value["column"]] ?? $value["column"]]    = $value["dir"] ?? "asc";
                } elseif (!is_array($value)) {
                    $sort[$key]                                                     = $value;
                }
            }
        }

        return $sort;
    }
    /**
     * @param array $where
     * @param bool $forse_physical
     * @return OrmResults
     * @throws Exception
     */
    public static function deleteAll(array $where, bool $forse_physical = false) : OrmResults
    {
        $item                                                                   = new static();
        $db                                                                     = $item
                                                                                    ->db
                                                                                    ->table(static::TABLE);
        return ($item::DELETE_LOGICAL_FIELD && !$forse_physical
            ? $db->update([$item::DELETE_LOGICAL_FIELD => true], $where)
            : $db->delete($where)
        );
    }

    /**
     * @param array $set
     * @param array|null $where
     * @return OrmResults
     * @throws Exception
     */
    public static function updateAll(array $set, array $where = null) : OrmResults
    {
        $item                                                                   = new static();
        return $item->db
            ->table($item::TABLE)
            ->update($set, $where);
    }

    /**
     * @param array $data
     * @param string|null $record_key
     * @return static
     * @throws Exception
     */
    public static function load(array $data, string $record_key = null) : self
    {
        return new static(null, $data, $record_key);
    }

    /**
     * OrmItem constructor.
     * @param array|null $where
     * @param array|null $fill
     * @param string|null $record_key
     * @throws Exception
     */
    public function __construct(array $where = null, array $fill = null, string $record_key = null)
    {
        $logical_fields = $this->onLoad($where, $fill);
        if (!empty(static::DELETE_LOGICAL_FIELD) && !isset($logical_fields[static::DELETE_LOGICAL_FIELD])) {
            $logical_fields[static::DELETE_LOGICAL_FIELD] = false;
        }

        $this->loadCollection($logical_fields);
        $this->read($where);
        $this->setRecordKey($record_key);
        if (!empty($fill)) {
            $this->fillByArray($fill);
            $this->storedData = array_replace($this->storedData, $fill);
        }

        if (!$this->isStored()) {
            $this->onCreate($this->db);
        } elseif ($this->isChanged()) {
            $this->onChange($this->db, $this->recordKey);
        } else {
            $this->onReadAfter($this->db, $this->recordKey);
        }
    }

    /**
     * @param string|null $model_name
     * @param array|null $fill
     * @return OrmModel|null
     * @throws Exception
     */
    public function &extend(string $model_name = null, array $fill = null) : ?OrmModel
    {
        $this->model                                                            = $model_name;
        $this->models[$this->model]                                             = (
            $this->model
            ? new OrmModel($this->model, $this->primaryIndexes, $fill)
            : null
        );

        return $this->models[$this->model];
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function onBeforeParseRow(callable $callback) : self
    {
        $this->hook()->register("onBeforeParseRow", $callback);

        return $this;
    }

    /**
     * @param array|null $logical_fields
     * @throws Exception
     */
    private function loadCollection(array $logical_fields = null)
    {
        $collection_name                                                        = static::COLLECTION ?? $this->getClassName();

        $this->db                                                               = new Model();
        $this->db->loadCollection($collection_name, $logical_fields);
        $this->db->table(static::TABLE);
        $this->db->setHook($this->hook);

        $this->informationSchema                                                = $this->db->informationSchema();
        if (!$this->informationSchema) {
            throw new Exception("Table: " . static::TABLE . " not found in Colletion: " . static::COLLECTION, 501);
        }
        if (!empty(static::JOINS)) {
            if (!isset(self::$buffer["current"])) {
                self::$buffer["current"]                                        = static::class;
                self::$buffer["db"]                                             =& $this->db;
            }
            self::$buffer["items"][static::class]                               = true;
            foreach (static::JOINS as $join => $fields) {
                if (is_numeric($join)) {
                    $join                                                       = $fields;
                    $fields                                                     = null;
                }

                if (!isset(self::$buffer["items"][$join]) && is_callable([$join, 'dtd'])) {
                    $table                                                      = null;
                    /**
                     * @var OrmItem $join
                     */
                    $dtd                                                        = $join::dtd();
                    $informationSchemaJoin                                      = $this->db->informationSchema($dtd->table, $dtd->collection);
                    if (isset($informationSchemaJoin->relationship[static::TABLE])) {
                        /**
                         * OneToOne
                         */
                        $table                                                  = $informationSchemaJoin->schema["alias"];
                        $this->oneToOne[$table]                                 = $this->setRelationship($join, $dtd, $informationSchemaJoin, (object) $informationSchemaJoin->relationship[static::TABLE]);
                    } elseif (isset($this->informationSchema->relationship[$dtd->table]) && empty($this->informationSchema->relationship[$dtd->table]["one2one"])) {
                        /**
                         * OneToMany
                         */
                        $table                                                  = $dtd->table;
                        $this->oneToMany[$table]                                = $this->setRelationship($join, $dtd, $informationSchemaJoin, (object) $this->informationSchema->relationship[$table]);
                    }

                    $property                                                   = $dtd->table_read ?? $dtd->table;
                    if ($table) {
                        if (!property_exists($this, $property)) {
                            throw new Exception("Missing property " . $property . " on " . $this->getClassName(), 500);
                        } elseif (!is_array($this->{$property})) {
                            $this->{$property}                                     = [];
                        }
                    }

                    self::$buffer["db"]->join($dtd->collection, $property, $fields ?? $dtd->dataResponse);
                }
            }

            if (self::$buffer["current"] == static::class) {
                self::$buffer                                                   = null;
            }
        }
    }

    private function setRelationship(string $mapClass, stdClass $dtd, stdClass $informationSchema, stdClass $relationship) : stdClass
    {
        $rel                                                    = new stdClass();
        $rel->mapClass                                          = $mapClass;
        $rel->dbExternal                                        = $relationship->external;
        $rel->dbPrimary                                         = $relationship->primary;
        $rel->primaryKey                                        = $dtd->primaryKey ?? $rel->dbPrimary;
        $rel->informationSchema                                 = $informationSchema;
        $rel->dataResponse                                      = (
            empty($dtd->dataResponse)
                                                                    ? array_intersect_key(get_class_vars($mapClass), $informationSchema->dtd)
                                                                    : array_fill_keys($dtd->dataResponse, true)
        );
        $rel->indexes                                           = null;
        $rel->indexes_primary                                   = null;

        return $rel;
    }

    /**
     * @param object $obj
     * @return void
     * @throws Exception
     */
    private function fillByObject(object $obj) : void
    {
        $this->fillByArray(
            $obj instanceof ArrayObject
            ? $obj->getArrayCopy()
            : (array) $obj
        );
    }

    /**
     * @param array $array
     * @return void
     * @throws Exception
     */
    private function fillByArray(array $array) : void
    {
        $this->onFill($array);

        $this->fillOneToOne($array);
        $this->fillOneToMany($array);

        $this->autoMapping($array);
    }

    /**
     * @param array|object|null $fields
     * @return OrmItem
     * @throws Exception
     */
    public function fill(array|object $fields = null) : self
    {
        if (!empty($fields)) {
            if (is_array($fields)) {
                $this->fillByArray($fields);
            } else {
                $this->fillByObject($fields);
            }
        }

        return $this;
    }

    /**
     * @param array $fields
     * @throws Exception
     */
    private function fillOneToOne(array &$fields) : void
    {
        foreach (array_intersect_key($this->oneToOne, $fields) as $table => $oneToOne) {
            if (!is_array($fields[$table]) || isset($fields[$table][0])) {
                $this->error([$table . " must be object"]);
            }

            $this->{$table}                                                     = array_replace($this->{$table} ?? [], $fields[$table]);
            unset($fields[$table]);
        }
    }

    /**
     * @param array $fields
     * @throws Exception
     */
    private function fillOneToMany(array &$fields) : void
    {
        foreach (array_intersect_key($this->oneToMany, $fields) as $table => $oneToMany) {
            if (!is_array($fields[$table]) || !isset($fields[$table][0])) {
                $this->error([$table . " must be array of object"]);
            }

            if (empty($this->{$table})) {
                $this->{$table}                                                 = $fields[$table];
            } else {
                $data                                                           = [];
                $count_update                                                   = 0;

                foreach ($fields[$table] as $vars) {
                    if (!empty($vars[$oneToMany->primaryKey]) && isset($oneToMany->indexes[$vars[$oneToMany->primaryKey]])) {
                        $index                                                  = $oneToMany->indexes[$vars[$oneToMany->primaryKey]];
                        /**
                         * Update
                         */
                        $data[$index]                                           = array_replace($this->{$table}[$index], $vars);
                        $count_update++;
                    } else {
                        /**
                         * Insert
                         */
                        $index                                                  = count($this->{$table}) + count($data) - $count_update;
                        $data[$index]                                           = $vars;
                    }
                }

                $this->{$table}                                                 = array_replace($this->{$table}, $data);
            }

            unset($fields[$table]);
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function apply() : string
    {
        $this->db->table(static::TABLE);

        $this->onApply($this->db, $this->recordKey);

        $vars                                                                   = array_diff_key($this->fieldSetPurged(), $this->oneToMany);

        $this->applyOneToOne($vars);

        //$vars = $this->fieldConvert($vars); //@todo da finire
        $this->verifyRequire($vars);
        $this->verifyValidator($vars);

        if ($this->recordKey) {
            /**
             * phpBug 62115 Issue with method array_diff_assoc
             * @todo da controllare array_diff_assoc($vars, $this->storedData <==> array_diff_assoc(get_object_vars($this), $this->storedData
             */
            if (!empty(array_intersect_key(@array_diff_assoc($vars, $this->storedData), $this->informationSchema->dtd))) {
                $this->onUpdate($vars, $this->recordKey);

                $this->db->update(
                    array_intersect_key(@array_diff_assoc(get_object_vars($this), $this->storedData), $this->informationSchema->dtd),
                    [static::TABLE . "." . $this->primaryKey => $this->recordKey]
                );
            }
        } else {
            $this->onInsert($vars);

            $item                                                               = $this->db->insert(array_merge($vars, $this->primaryIndexes));
            $this->recordKey                                                    = $item->key(0);
            $this->primaryKey                                                   = $item->getPrimaryKey();
            if ($this->recordKey === null) {
                throw new Exception($this->db->getName() .  "::db->insert Missing primary " . $this->primaryKey, 500);
            }
        }

        $this->applyOneToMany();

        return $this->recordKey;
    }

    /**
     * @param array $vars
     * @throws Exception
     */
    private function applyOneToOne(array &$vars) : void
    {
        /**
         * @var OrmItem $obj
         */
        foreach ($this->oneToOne as $table => $oneToOne) {
            $relDataDB                                                          = $this->storedData[$table] ?? [];
            $relData                                                            = array_intersect_key($this->{$table}, $oneToOne->informationSchema->dtd);

            if (!empty($this->primaryIndexes[$oneToOne->dbExternal])) {
                /**
                 * Update
                 */
                if (empty($relDataDB[$oneToOne->primaryKey])) {
                    /**
                     * 2+ nesting level
                     */
                    $obj = new $oneToOne->mapClass([$oneToOne->dbPrimary => $this->primaryIndexes[$oneToOne->dbExternal]]);
                    if (!$obj->isStored()) {
                        throw new Exception($oneToOne->dbPrimary . " '" . $this->primaryIndexes[$oneToOne->dbExternal] . "' not found in table '" . $table . "'", 404);
                    }

                } else {
                    /**
                     * first nesting level
                     */
                    $obj = $oneToOne->mapClass::load($relDataDB, $this->primaryIndexes[$oneToOne->dbExternal])
                        ->setIndexes($oneToOne->indexes);
                }

                /**
                 * get object with new primaryKey
                 */
                if (isset($this->{$table}[$oneToOne->primaryKey]) && $this->{$table}[$oneToOne->primaryKey] != $obj->{$oneToOne->primaryKey}) {
                    $obj = new $oneToOne->mapClass([$oneToOne->primaryKey => $this->{$table}[$oneToOne->primaryKey]]);
                    if (!$obj->isStored()) {
                        throw new Exception($oneToOne->primaryKey . " '" . $this->{$table}[$oneToOne->primaryKey] . "' not found in table '" . $table . "'", 404);
                    }
                }

                $obj->fill(
                    $obj::ENABLE_UPDATE_NESTED
                        ? $this->{$table}
                        : array_diff_key($this->{$table}, $oneToOne->informationSchema->dtd)
                );
                $id                                                         = $obj->apply();

                /**
                 * Update relation with new primaryKey
                 */
                if ($this->primaryIndexes[$oneToOne->dbExternal] != $id) {
                    $vars[$oneToOne->dbExternal]                                = $id;
                }
                $this->{$table}                                                 = $obj->toArray();
            } elseif(empty($relData) && empty($relDataDB)) {
                $this->{$table}                                                 = null;
            } else {
                if (!empty($this->{$table}[$oneToOne->primaryKey])) {
                    /**
                     * update by primary key
                     */
                    $obj = new $oneToOne->mapClass([$oneToOne->primaryKey => $this->{$table}[$oneToOne->primaryKey]]);
                    if (!$obj->isStored()) {
                        throw new Exception($oneToOne->primaryKey . " '" . $this->{$table}[$oneToOne->primaryKey] . "' not found in table '" . $table . "'", 404);
                    }

                    $obj->fill(
                        $obj::ENABLE_UPDATE_NESTED
                            ? $this->{$table}
                            : array_diff_key($this->{$table}, $oneToOne->informationSchema->dtd)
                    );
                } else {
                    /**
                     * Insert
                     */
                    $obj                                                        = $oneToOne->mapClass::load($this->{$table} /*$relData*/);
                }

                $vars[$oneToOne->dbExternal]                                    = $obj->apply();
                $this->{$table}                                                 = $obj->toArray();
            }

            unset($vars[$table]);
        }
    }

    /**
     * @throws Exception
     */
    private function applyOneToMany() : void
    {
        /**
         * @var OrmItem $obj
         */
        foreach ($this->oneToMany as $table => $oneToMany) {
            if (!isset($this->{$table})) {
                continue;
            }
            $relDataDBs                                                         = $this->storedData[$table] ?? [];
            $relDatas                                                           = $this->{$table};

            foreach ($relDatas as $index => $var) {
                if (!is_array($var)) {
                    throw new Exception("Relationship is oneToMany (" . static::TABLE . " => " . $table . "). Property '" . $table . "' must be an array of items in parent class.", 500);
                }

                $relData                                                        = array_intersect_key($var, $oneToMany->informationSchema->dtd);
                if (($primaryValue = ($relData[$oneToMany->primaryKey] ?? null)) && ($indexes = ($oneToMany->indexes_primary[$oneToMany->indexes[$primaryValue]] ?? null))) {
                    $key                                                        = $indexes[$oneToMany->dbPrimary];

                    /**
                     * Skip update if DataStored = DataProperty
                     */
                    $relDataDB                                                  = $relDataDBs[$oneToMany->indexes[$primaryValue]] ?? null;
                    if ($relDataDB == $relData) {
                        if (!empty($oneToMany->dataResponse)) {
                            $this->{$table}[$index]                             = array_intersect_key($this->{$table}[$index], $oneToMany->dataResponse) ?: null;
                        }
                        continue;
                    }

                    /**
                     * Update
                     */
                    $obj                                                        = $oneToMany->mapClass::load($relDataDB, $key)
                                                                                    ->setIndexes($indexes);
                    $obj->fill($relData);
                    $obj->apply();

                    $this->{$table}[$index]                                     = $obj->toArray();
                } elseif (!isset($relDataDBs[$index])) {
                    /**
                     * Insert
                     */
                    $indexes                                                    = array_intersect_key($relData, $oneToMany->informationSchema->indexes);
                    $indexes[$oneToMany->dbExternal]                            = $this->{static::PRIMARY_KEY} ?? $this->recordKey;

                    $obj                                                        = $oneToMany->mapClass::load($relData)
                                                                                    ->setIndexes($indexes);
                    $obj->apply();
                    $this->{$table}[$index]                                     = $obj->toArray();
                } else {
                    $this->{$table}[$index]                                     = array_intersect_key($this->{$table}[$index], $oneToMany->dataResponse);
                }
            }
        }
    }

    /**
     * @param array|null $where
     * @throws Exception
     */
    private function read(array $where = null) : void
    {
        if (!empty($where)) {
            $this->hook()->register("onRead", function(array &$record, array $db_record) {
                $this->onRead($record, $db_record);
            });

            $item                                                               = $this->db
                ->table(static::TABLE_READ ?? static::TABLE)
                ->readOne($where);

            if ($item->countRecordset()) {
                $this->recordKey                                                = $item->key(0);
                $this->primaryKey                                               = $item->getPrimaryKey();
                $this->primaryIndexes                                           = $item->getPrimaryIndexes(0);
                $this->indexes                                                  = $item->indexes();

                if ($record = $item->getArray(0)) {
                    $this->storedData                                           = json_decode(json_encode($record), true);

                    $this->autoMapping($record);

                    foreach ($this->oneToOne as $table => $oneToOne) {
                        if (isset($this->indexes[$oneToOne->informationSchema->table][0])) {
                            $this->oneToOne[$table]->indexes                    = $this->indexes[$oneToOne->informationSchema->table][0];
                        }
                    }
                    foreach ($this->oneToMany as $table => $oneToMany) {
                        $this->oneToMany[$table]->indexes                       = array_flip(array_column($this->indexes[$table] ?? [], $oneToMany->primaryKey));
                        $this->oneToMany[$table]->indexes_primary               =& $this->indexes[$table];
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    private function getPublicVars() : array
    {
        $me = new class {
            /**
             * @param $object
             * @return array
             */
            public function getPublicVars($object) : array
            {
                return get_object_vars($object);
            }
        };
        return $me->getPublicVars($this);
    }

    /**
     * @return DataResponse
     * @throws Exception
     * @todo da tipizzare
     */
    public function toDataResponse(string $model_name = null, array $rawdata = null)
    {
        $response = new DataResponse($this->toArray());
        if (!$this->recordKey) {
            $response->error(404, $this->db->getName() . " not stored");
        }

        if ($model_name && ($model = $this->extend($model_name, $rawdata)) && $rawdata) {
            $model->apply();
        }

        if ($this->model) {
            $response->set($this->models[$this->model]->schema()->table, $this->models[$this->model]->toArray());
        }
        return $response;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->fieldSetPurged(array_fill_keys(static::DATARESPONSE, true));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function delete() : void
    {
        $this->db->table(static::TABLE);

        //@todo da raffinare facendolo scendere nelle relazioni nested. (come l'update)
        if ($this->recordKey) {
            $this->onDelete($this->db, $this->recordKey);

            if (!empty(static::DELETE_JOINS)) {
                foreach (static::DELETE_JOINS as $join) {
                    if (is_callable([$join, 'dtd'])) {
                        /**
                         * @var OrmItem $join
                         */
                        $dtd                                                        = $join::dtd();

                        $informationSchemaJoin                                      = $this->db->informationSchema($dtd->table);
                        if (isset($informationSchemaJoin->relationship[static::TABLE])) {
                            /**
                             * OneToOne
                             */
                            $relationship                                           = (object) $informationSchemaJoin->relationship[static::TABLE];

                            $join::deleteAll([static::TABLE . "." . $relationship->primary => $this->primaryIndexes[$relationship->external]]);
                        } elseif (isset($this->informationSchema->relationship[$dtd->table])) {
                            /**
                             * OneToMany
                             */
                            $relationship                                           = (object) $this->informationSchema->relationship[$dtd->table];

                            $join::deleteAll([$dtd->table . "." . $relationship->external => $this->recordKey]);
                        }
                    }
                }
            }
            if (static::DELETE_LOGICAL_FIELD) {
                $this->db->update([static::DELETE_LOGICAL_FIELD => true], [static::TABLE . "." . $this->primaryKey => $this->recordKey]);
            } else {
                $this->db->delete([static::TABLE . "." . $this->primaryKey => $this->recordKey]);
            }
        }
    }

    /**
     * @return bool
     */
    public function isStored() : bool
    {
        return !empty($this->recordKey);
    }
    /**
     * @return bool
     */
    public function isChanged() : bool
    {
        $vars = $this->fieldSetPurged();
        return array_intersect_key($this->storedData, $vars) != $vars;
    }
    /**
     * @return string|null
     */
    public function getID() : ?string
    {
        return $this->recordKey;
    }

    /**
     * @param string $message
     * @param int|null $code
     * @return $this
     * @throws Exception
     */
    public function exceptionNotFound(string $message, int $code = null) : self
    {
        if (!$this->isStored()) {
            throw new Exception($message, $code);
        }

        return $this;
    }

    /**
     * @param array|null $fields
     * @return array
     * @todo da verificare se serve veramente purgare tutte le info quando il dataresponse o il search non è valorizzato
     * @todo migliorare l'hooksystem. al momento chiama sempre funzioni non valorizzate
     */
    private function fieldSetPurged(array $fields = null) : array
    {
        return (!empty($fields)
            ? array_intersect_key(get_object_vars($this), $fields)
            : array_diff_key(get_object_vars($this), get_class_vars(__CLASS__))
        );
    }

    protected function setRecordKey(string $key = null) : self
    {
        if (!empty($key)) {
            $this->recordKey    = $key;
            $this->primaryKey   = $this->db->informationSchema()->key;
        }

        return $this;
    }

    /**
     * @param array|null $indexes
     * @return $this
     */
    private function setIndexes(array $indexes = null) : self
    {
        $this->primaryIndexes   = $indexes ?? [];

        return $this;
    }

    /**
     * @param array $vars
     * @throws Exception
     */
    private function verifyRequire(array $vars) : void
    {
        $required   = array_diff_key(array_fill_keys(static::REQUIRED, true), array_filter($vars, function ($var) {
            return $var !== null;
        }));

        if (!empty($required)) {
            $this->error(array_keys($required), " are required");
        }
    }

    /**
     * @param array $vars
     * @throws Exception
     */
    private function verifyValidator(array $vars) : void
    {
        $errors                                                                 = null;
        $validators                                                             = array_intersect_key($vars, static::VALIDATOR);
        $dtd                                                                    = $this->db->dtd();
        foreach ($validators as $field => $value) {
            if (is_array(static::VALIDATOR[$field])) {
                if (isset($dtd->$field) && ($dtd->$field == Database::FTYPE_ARRAY || $dtd->$field == Database::FTYPE_ARRAY_OF_NUMBER)) {
                    $arrField                                                   = Normalize::string2array($value);
                    if (count(array_diff($arrField, static::VALIDATOR[$field]))) {
                        $errors[]                                               = $field . " must be: [" . implode(", ", static::VALIDATOR[$field]) . "]";
                    }
                } elseif (!in_array($value, static::VALIDATOR[$field])) {
                    $errors[]                                                   = $field . " must be: [" . implode(", ", static::VALIDATOR[$field]) . "]";
                }
            } elseif (is_callable([$this, static::VALIDATOR[$field]])) {
                if (!$this->{static::VALIDATOR[$field]}($value)) {
                    $errors[]                                                   = $field . " not valid";
                }
            } else {
                $validator                                                      = Validator::is($value, $field, static::VALIDATOR[$field]);
                if ($validator->isError()) {
                    $errors[]                                                   = $validator->error;
                }
            }
        }

        if ($errors) {
            $this->error($errors);
        }
    }

    /**
     * @param array $vars
     * @return array
     */
    private function fieldConvert(array $vars) : array
    {
        //@todo da finire con funzioni vere probabilmente
        foreach (static::CONVERSION as $field => $convert) {
            if (array_key_exists($field, $vars)) {
                $vars[$convert] = $vars[$field];
                unset($vars[$field]);
            }
        }
        return $vars;
    }
}
