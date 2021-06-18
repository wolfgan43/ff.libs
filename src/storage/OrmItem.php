<?php
namespace phpformsframework\libs\storage;

use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\dto\DataTableResponse;
use phpformsframework\libs\dto\Mapping;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\dto\OrmResults;
use Exception;
use phpformsframework\libs\util\Normalize;
use stdClass;

/**
 * Class OrmItem
 * @package phpformsframework\libs\storage
 */
abstract class OrmItem
{
    use ClassDetector;
    use Mapping;
    use OrmUtil;

    private const ERROR_RECORDSET_EMPTY                                         = "recordset empty";

    private static $buffer                                                      = null;

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
    protected const PRIMARY_KEY                                                 = null;
    protected const JOINS                                                       = [];
    protected const DELETE_JOINS                                                = [];
    protected const DELETE_LOGICAL_FIELD                                        = null;

    protected const REQUIRED                                                    = [];

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

    /**
     * @var OrmModel[]|null
     */
    private $models                                                             = [];
    private $model                                                              = null;

    private $tables                                                             = [];
    private $indexes                                                            = [];
    private $storedData                                                         = [];

    private $oneToOne                                                           = [];
    private $oneToMany                                                          = [];
    /**
     * @var stdClass|null
     */
    private $informationSchema                                                  = null;

    /**
     * @param Model $db
     * @param string|null $recordKey
     */
    abstract protected function onLoad($db, string $recordKey = null)           : void;

    /**
     * @param Model $db
     */
    abstract protected function onCreate($db)                                   : void;

    /**
     * @param Model $db
     * @param string $recordKey
     */
    abstract protected function onRead($db, string $recordKey)                  : void;

    /**
     * @param Model $db
     * @param string $recordKey
     */
    abstract protected function onChange($db, string $recordKey)                : void;

    /**
     * @param Model $db
     * @param string|null $recordKey
     */
    abstract protected function onApply($db, string $recordKey = null)          : void;

    /**
     * @param Model $db
     */
    abstract protected function onInsert($db)                                   : void;

    /**
     * @param Model $db
     * @param string $recordKey
     */
    abstract protected function onUpdate($db, string $recordKey)                : void;

    /**
     * @param Model $db
     * @param string $recordKey
     */
    abstract protected function onDelete($db, string $recordKey)                : void;

    /**
     * @return stdClass
     */
    private static function dtd() : stdClass
    {
        $item                                                                   = new static();
        return (object) [
                "collection"        => $item::COLLECTION,
                "table"             => $item::TABLE,
                "dataResponse"      => $item::DATARESPONSE,
                "required"          => $item::REQUIRED,
                "primaryKey"        => $item::PRIMARY_KEY
            ];
    }

    /**
     * @param array|null $query
     * @param array|null $order
     * @param int|null $limit
     * @param int|null $offset
     * @param int|null $draw
     * @param string|array|null $toDataResponse
     * @return DataTableResponse
     * @throws Exception
     * @todo da tipizzare
     */
    public static function search(array $query = null, array $order = null, int $limit = null, int $offset = null, int $draw = null, $toDataResponse = null)
    {
        $dataTableResponse                                                      = new DataTableResponse();
        $item                                                                   = new static();

        $where                                                                  = null;
        if (is_array($query)) {
            $dtd                                                                = $item->db->dtdStore();
            foreach ($query as $key => $value) {
                if (!isset($dtd->$key)) {
                    throw new Exception("Field " . $key . " not found in table " . $item::TABLE . " (" . $item::COLLECTION . ")", 500);
                }

                if (is_array($value)) {
                    foreach ($value as $op => $subvalue) {
                        if (substr($op, 0, 1) == '$') {
                            continue;
                        }

                        if (isset(self::SEARCH_OPERATORS[$op])) {
                            $value[self::SEARCH_OPERATORS[$op]]                 = (
                                (self::SEARCH_OPERATORS[$op] == '$in' || self::SEARCH_OPERATORS[$op] == '$nin') && !is_array($subvalue)
                                ?  explode(",", $subvalue)
                                : $subvalue
                            );
                        } else {
                            $value['$in'][]                                     = $subvalue;
                            unset($value[$op]);
                        }
                    }
                } elseif (strpos($value, "*") !== false) {
                    $value                                                      = ['$regex' => $value];
                }
                $where[$key]                                                    = $value;
            }
        }

        $sort                                                                   = null;
        if (is_array($order)) {
            $fields                                                             = $item::DATARESPONSE;
            sort($fields);
            foreach ($order as $key => $value) {
                if (isset($value["column"])) {
                    if (is_array($value["column"])) {
                        throw new Exception("Multi array not supported in order: " . $key, 500);
                    }
                    if (!isset($fields[$value["column"]]) && is_numeric($value["column"])) {
                        throw new Exception("Order Column value not found: " . $key, 500);
                    }

                    $sort[$fields[$value["column"]] ?? $value["column"]]        = $value["dir"] ?? "asc";
                } elseif (!is_array($value)) {
                    $sort[$key]                                                 = $value;
                }
            }
        }

        $toDataResponse                                                         = $toDataResponse ?? $item::DATARESPONSE;
        if (!empty($toDataResponse) && !is_array($toDataResponse) && !($toDataResponse = Model::get($toDataResponse))) {
            throw new Exception("Model not found", 404);
        }

        if ($item::DELETE_LOGICAL_FIELD) {
            $where[$item::DELETE_LOGICAL_FIELD]                                 = false;
            foreach ($item->tables as $table) {
                $where[$table . "." . $item::DELETE_LOGICAL_FIELD]              = false;
            }
        }

        $recordset                                                              = $item->db
            ->table($item::TABLE, $toDataResponse)
            ->read($where, $sort, $limit, $offset);

        if ($recordset->countRecordset()) {
            $dataTableResponse->fill($recordset->getAllArray());
            $dataTableResponse->recordsTotal                                    = $recordset->countTotal();
            $dataTableResponse->recordsFiltered                                 = ($where ? $recordset->countRecordset() : $dataTableResponse->recordsTotal);
            $dataTableResponse->draw                                            = $draw + 1;
        } else {
            $dataTableResponse->error(204, self::ERROR_RECORDSET_EMPTY);
        }
        return $dataTableResponse;
    }

    /**
     * @param array $where
     * @return OrmResults
     * @throws Exception
     */
    public static function deleteAll(array $where) : OrmResults
    {
        $item                                                                   = new static();
        $db                                                                     = $item
                                                                                    ->db
                                                                                    ->table(static::TABLE);
        return ($item::DELETE_LOGICAL_FIELD
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
        $this->loadCollection();
        $this->read($where);
        $this->fill($fill);
        $this->setRecordKey($record_key);

        $this->storedData = array_replace($this->storedData, $fill ?? []);

        $this->onLoad($this->db, $this->recordKey);

        if (!$this->isStored()) {
            $this->onCreate($this->db);
        } elseif ($this->isChanged()) {
            $this->onChange($this->db, $this->recordKey);
        } else {
            $this->onRead($this->db, $this->recordKey);
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
        /*** TEST
        $user = new UserData(["ID" => 1]);
        print_r($user->toDataResponse());

        $user = new Model("user");
        $res = $user->read(["ID" => 1]);
        print_r($res->get(0)->toDataResponse());
        die();
        */

        $this->model                                                            = $model_name;
        $this->models[$this->model]                                             = (
            $this->model
            ? new OrmModel($this->model, $this, $fill)
            : null
        );

        return $this->models[$this->model];
    }

    /**
     *
     * @throws Exception
     */
    private function loadCollection()
    {
        $collection_name                                                        = static::COLLECTION ?? $this->getClassName();

        $this->db                                                               = new Model();
        $this->db->loadCollection($collection_name, static::DELETE_LOGICAL_FIELD);
        $this->db->table(static::TABLE);
        $this->informationSchema                                                = $this->db->informationSchema();

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

                    $informationSchemaJoin                                      = $this->db->informationSchema($dtd->table);
                    if (isset($informationSchemaJoin->relationship[static::TABLE])) {
                        /**
                         * OneToOne
                         */
                        $table                                                  = $informationSchemaJoin->schema["alias"];
                        $this->oneToOne[$table]                                 = $this->setRelationship($join, $dtd, $informationSchemaJoin, (object) $informationSchemaJoin->relationship[static::TABLE]);
                    } elseif (isset($this->informationSchema->relationship[$dtd->table])) {
                        /**
                         * OneToMany
                         */
                        $table                                                  = $dtd->table;
                        $this->oneToMany[$table]                                = $this->setRelationship($join, $dtd, $informationSchemaJoin, (object) $this->informationSchema->relationship[$table]);
                    }

                    if ($table) {
                        if (!property_exists($this, $table)) {
                            throw new Exception("Missing property " . $table . " on " . $this->getClassName(), 500);
                        } elseif (!is_array($this->{$table})) {
                            $this->{$table}                                     = [];
                        }
                    }

                    $this->tables[]                                             = $dtd->table;
                    self::$buffer["db"]->join($dtd->table, $fields ?? $dtd->dataResponse);
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
     * @return OrmItem
     * @throws Exception
     */
    public function fillByObject(object $obj) : self
    {
        $this->fill((array) $obj);

        return $this;
    }

    /**
     * @param array|null $fields
     * @return OrmItem
     * @throws Exception
     */
    public function fill(array $fields = null) : self
    {
        if ($fields) {
            $this->fillOneToOne($fields);
            $this->fillOneToMany($fields);

            $this->autoMapping($fields);
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
        $this->onApply($this->db, $this->recordKey);

        if ($this->recordKey) {
            $this->onUpdate($this->db, $this->recordKey);
        } else {
            $this->onInsert($this->db);
        }

        $vars                                                                   = $this->fieldSetPurged();

        $this->applyOneToOne($vars);

        //$vars = $this->fieldConvert($vars); //@todo da finire
        $this->verifyRequire($vars);
        $this->verifyValidator($vars);

        if ($this->recordKey) {
            if (($storedVars = array_intersect_key($this->storedData, $vars)) != $vars) {
                $this->db->update(array_diff_assoc($vars, $storedVars), [$this->primaryKey => $this->recordKey]);
            }
        } else {
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

            /**
             * Skip update if DataStored = DataProperty
             */
            if ($relDataDB == $relData) {
                $this->{$table}                                                 = array_intersect_key($this->{$table}, $oneToOne->dataResponse);
                continue;
            }

            /**
             * One to One
             */
            if (isset($this->{$table}[$oneToOne->primaryKey]) && ($relDataDB[$oneToOne->primaryKey] ?? null) != $this->{$table}[$oneToOne->primaryKey]) {
                $obj = new $oneToOne->mapClass([$oneToOne->primaryKey => $this->{$table}[$oneToOne->primaryKey]]);
                $obj->fill($relData);
            } elseif (isset($this->primaryIndexes[$oneToOne->dbExternal])) {
                $obj = $oneToOne->mapClass::load($relDataDB, $this->primaryIndexes[$oneToOne->dbExternal])
                        ->setIndexes($oneToOne->indexes);
                $obj->fill($relData);
            } else {
                $obj = $oneToOne->mapClass::load($relData);
            }

            $vars[$oneToOne->dbExternal]                                        = $obj->apply();
            $this->{$table}                                                     = $obj->toArray();
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
                            $this->{$table}[$index]                             = array_intersect_key($this->{$table}[$index], $oneToMany->dataResponse);
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
            $item                                                               = $this->db
                ->readOne($where);

            if ($item->countRecordset()) {
                $this->recordKey                                                = $item->key(0);
                $this->primaryKey                                               = $item->getPrimaryKey();
                $this->primaryIndexes                                           = $item->getPrimaryIndexes(0);
                $this->indexes                                                  = $item->indexes();

                if ($record = $item->getArray(0)) {
                    $this->storedData = json_decode(json_encode($record), true);

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
     * @todo da tipizzare
     */
    public function toDataResponse()
    {
        $response = new DataResponse($this->getPublicVars());
        if (!$this->recordKey) {
            $response->error(404, $this->db->getName() . " not stored");
        }

        if ($this->model) {
            $response->set($this->model, $this->models[$this->model]->toArray());
        }

        return $response;
    }

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

                            $join::deleteAll([$relationship->primary => $this->primaryIndexes[$relationship->external]]);
                        } elseif (isset($this->informationSchema->relationship[$dtd->table])) {
                            /**
                             * OneToMany
                             */
                            $relationship                                           = (object) $this->informationSchema->relationship[$dtd->table];

                            $join::deleteAll([$relationship->external => $this->recordKey]);
                        }
                    }
                }
            }
            if (static::DELETE_LOGICAL_FIELD) {
                $this->db->update([static::DELETE_LOGICAL_FIELD => true], [$this->primaryKey => $this->recordKey]);
            } else {
                $this->db->delete([$this->primaryKey => $this->recordKey]);
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
     * @param array|null $fields
     * @return array
     */
    private function fieldSetPurged(array $fields = null) : array
    {
        return ($fields
            ? array_intersect_key(get_object_vars($this), $fields)
            : array_intersect_key(get_object_vars($this), $this->informationSchema->dtd)
        );
    }

    protected function setRecordKey(string $key = null) : self
    {
        if ($key) {
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
        $required                                                               = array_diff_key(array_fill_keys(static::REQUIRED, true), array_filter($vars));
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
        $dtd                                                                    = $this->db->dtdStore();
        foreach ($validators as $field => $value) {
            if (is_array(static::VALIDATOR[$field])) {
                if ($dtd->$field == Database::FTYPE_ARRAY || $dtd->$field == Database::FTYPE_ARRAY_OF_NUMBER) {
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
