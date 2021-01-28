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
    protected const PRIMARYKEY                                                  = null;
    protected const JOINS                                                       = [];

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

    private $indexes                                                            = [];
    private $rawData                                                            = [];
    private $storedData                                                         = [];

    private $oneToOne                                                           = [];
    private $oneToMany                                                          = [];
    private $informationSchema                                                  = null;

    abstract protected function onLoad() : void;
    abstract protected function onCreate() : void;

    abstract protected function onRead(string $recordKey, string $primaryKey) : void;
    abstract protected function onChange(string $recordKey, string $primaryKey) : void;

    abstract protected function onApply() : void;
    abstract protected function onInsert() : void;
    abstract protected function onUpdate(string $recordKey, string $primaryKey) : void;
    abstract protected function onDelete(string $recordKey, string $primaryKey) : void;

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
                "primaryKey"        => $item::PRIMARYKEY
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
        if (!empty($toDataResponse) && !is_array($toDataResponse)) {
            $toDataResponse                                                     = Model::get($toDataResponse);
        }

        $recordset                                                              = $item->db
            ->table($item::TABLE, $toDataResponse)
            ->read($where, $sort, $limit, $offset);

        if ($recordset->countRecordset()) {
            $dataTableResponse->fill($recordset->getAllArray());
            $dataTableResponse->recordsFiltered                                 = $recordset->countRecordset();
            $dataTableResponse->recordsTotal                                    = $recordset->countTotal();
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
        return $item->db
            ->table($item::TABLE)
            ->delete($where);
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
        $this->loadCollection(($where || $fill));
        $this->read($where);
        $this->fill($fill);
        $this->setRecordKey($record_key);

        $this->storedData = array_replace($this->storedData, $fill ?? []);


        $this->onLoad();

        if (!$this->isStored()) {
            $this->onCreate();
        } elseif ($this->isChanged()) {
            $this->onChange($this->recordKey, $this->primaryKey);
        } else {
            $this->onRead($this->recordKey, $this->primaryKey);
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
     * @param bool $extendPrimaryKey
     * @throws Exception
     */
    private function loadCollection(bool $extendPrimaryKey = false)
    {
        $collection_name                                                        = static::COLLECTION ?? $this->getClassName();

        $this->db                                                               = new Model();
        $this->db->loadCollection($collection_name);
        $this->db->table(static::TABLE);
        $this->informationSchema                                                = $this->db->informationSchema();

        if (!empty(static::JOINS)) {
            if (!isset(self::$buffer["current"])) {
                self::$buffer["current"]                                        = static::class;
            }
            self::$buffer["items"][static::class]                               = true;
            foreach (static::JOINS as $join => $fields) {
                if (is_numeric($join)) {
                    $join                                                       = $fields;
                    $fields                                                     = null;
                }

                if (!isset(self::$buffer["items"][$join]) && method_exists($join, 'dtd')) {
                    $table                                                      = null;
                    /**
                     * @var OrmItem $join
                     */
                    $dtd                                                        = $join::dtd();

                    //da fare procedura ricorsiva che discende nelle join dei mapclass relazionati
                    //$this->db->join($dtd->table, $fields ?? $dtd->dataResponse);

                    $informationSchemaJoin                                      = $this->db->informationSchema($dtd->table);
                    if (isset($informationSchemaJoin->relationship[static::TABLE])) {
                        /**
                         * OneToOne
                         */
                        $table                                                  = $informationSchemaJoin->schema["alias"];
                        $this->oneToOne[$table]                                 = $this->setRelationship($join, $dtd, $informationSchemaJoin);
                    } elseif (isset($this->informationSchema->relationship[$dtd->table])) {
                        /**
                         * OneToMany
                         */
                        $table                                                  = $dtd->table;
                        $this->oneToMany[$table]                                = $this->setRelationship($join, $dtd, $this->informationSchema);
                        if ($extendPrimaryKey) {
                            $dtd->dataResponse[]                                = $informationSchemaJoin->key;
                        }
                    }

                    if ($table) {
                        if (!property_exists($this, $table)) {
                            throw new Exception("Missing property " . $table . " on " . $this->getClassName(), 500);
                        } elseif (!is_array($this->{$table})) {
                            $this->{$table}                                     = [];
                        }
                    }

                    $this->db->join($dtd->table, $fields ?? $dtd->dataResponse);
                }
            }

            if (self::$buffer["current"] == static::class) {
                self::$buffer                                                   = null;
            }
        }
    }

    private function setRelationship(string $mapClass, stdClass $dtd, stdClass $informationSchema) : stdClass
    {
        $rel                                                    = new stdClass();
        $rel->mapClass                                          = $mapClass;
        $rel->primaryKey                                        = $dtd->primaryKey;
        $rel->informationSchema                                 = $informationSchema;
        $rel->dataResponse                                      = array_fill_keys($dtd->dataResponse, true);

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
            $this->rawData[$table]                                              = $fields[$table];

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

            $relationship                                                       = (object) $oneToMany->informationSchema->relationship[$table];
            $primaryKey                                                         = $oneToMany->primaryKey ?? $relationship->primary;
            if (empty($this->{$table})) {
                $this->{$table}                                                 = $fields[$table];
                $this->rawData[$table]                                          = $fields[$table];
            } else {
                $keys                                                           = array_flip(array_column($this->{$table} ?? [], $primaryKey));
                $data                                                           = [];
                foreach ($fields[$table] as $vars) {
                    if (!empty($vars[$primaryKey]) && isset($keys[$vars[$primaryKey]])) {
                        /**
                         * Update
                         */
                        $data[$keys[$vars[$primaryKey]]]                        = array_replace($this->{$table}[$keys[$vars[$primaryKey]]], $vars);
                        $this->rawData[$table][$keys[$vars[$primaryKey]]]       = $vars;
                    } else {
                        /**
                         * Insert
                         */
                        $index                                                  = count($this->{$table}) + count($data);
                        $data[$index]                                           = $vars;
                        $this->rawData[$table][$index]                          = $vars;
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
        $this->onApply();

        if ($this->recordKey) {
            $this->onUpdate($this->recordKey, $this->primaryKey);
        } else {
            $this->onInsert();
        }

        $vars                                                                   = array_intersect_key($this->fieldSetPurged(), $this->informationSchema->dtd);

        $this->applyOneToOne($vars);

        //$vars = $this->fieldConvert($vars); //@todo da finire
        $this->verifyRequire($vars);
        $this->verifyValidator($vars);

        if ($this->recordKey) {
            if (($storedVars = array_intersect_key($this->storedData, $vars)) != $vars) {
                $this->db->update(array_diff_assoc($vars, $storedVars), [$this->primaryKey => $this->recordKey]);
            }
        } else {
            $item                                                               = $this->db->insert(array_merge($vars, $this->indexes));
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
            if (array_intersect_key($relDataDB, $relData) == $relData) {
                $this->{$table}                                                 = array_intersect_key($this->{$table}, $oneToOne->dataResponse);
                continue;
            }

            /**
             * Many to One
             */
            $relationship                                                       = (object) $oneToOne->informationSchema->relationship[static::TABLE];
            $external_key                                                       = $relationship->external;

            $obj = (
                !isset($this->indexes[$relationship->external]) && isset($this->{$table}[$oneToOne->primaryKey])
                ? (new $oneToOne->mapClass([$oneToOne->primaryKey => $this->{$table}[$oneToOne->primaryKey]], $relData))
                : $oneToOne->mapClass::load($relDataDB, $this->indexes[$relationship->external] ?? null)
            );

            $obj->fill($this->rawData[$table] ?? null);
            $vars[$external_key]                                                = $obj->apply();
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
            $relDataDB                                                          = $this->storedData[$table] ?? null;
            $relData                                                            = $this->{$table} ?? [];

            $relationship                                                       = (object) $oneToMany->informationSchema->relationship[$table];
            $indexes                                                            = [$relationship->external => $this->recordKey];
            $primaryKey                                                         = $oneToMany->primaryKey ?? $relationship->primary;

            $keys                                                               = array_flip(array_column($this->{$table} ?? [], $primaryKey));
            foreach ($relData as $index => $var) {
                if (!is_array($var)) {
                    throw new Exception("Relationship is oneToMany (" . $oneToMany->informationSchema->table . " => " . $table . "). Property '" . $table . "' must be an array of items in parent class.", 500);
                }

                $var                                                            = array_intersect_key($var, $oneToMany->informationSchema->dtd);
                /**
                 * Skip update if DataStored = DataProperty
                 */
                if (isset($relDataDB[$index]) && array_intersect_key($relDataDB[$index], $var)  == $var) {
                    $this->{$table}[$index]                                     = array_intersect_key($this->{$table}[$index], $oneToMany->dataResponse);
                    continue;
                }

                if (!empty($var[$primaryKey]) && isset($keys[$var[$primaryKey]]) && isset($this->{$table}[$keys[$var[$primaryKey]]][$relationship->primary])) {
                    /**
                     * Update
                     */
                    $obj                                                        = $oneToMany->mapClass::load($relDataDB[$index], $this->{$table}[$keys[$var[$primaryKey]]][$relationship->primary])
                                                                                    ->setIndexes($indexes);
                    $obj->fill($this->rawData[$table][$keys[$var[$primaryKey]]] ?? null);
                    $obj->apply();
                    $this->{$table}[$keys[$var[$primaryKey]]]                   = $obj->toArray();
                } else {
                    /**
                     * Insert
                     */
                    $obj                                                        = $oneToMany->mapClass::load($var)
                                                                                    ->setIndexes($indexes);
                    $obj->fill($this->rawData[$table][$index] ?? null);
                    $obj->apply();
                    $this->{$table}[$index]                                     = $obj->toArray();
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
                $this->indexes                                                  = $item->indexes(0);

                if ($record = $item->getArray(0)) {
                    $this->storedData = $record;
                    $this->autoMapping($record);
                }
            }
        }
    }

    /**
     * @return DataResponse
     * @todo da tipizzare
     */
    public function toDataResponse()
    {
        $response = new DataResponse($this->toArray());
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
     * @return OrmResults|null
     * @throws Exception
     */
    public function delete() : ?OrmResults
    {
        if ($this->primaryKey && $this->recordKey) {
            $this->onDelete($this->recordKey, $this->primaryKey);

            return $this->db->delete([$this->primaryKey => $this->recordKey]);
        }

        return null;
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
        $vars = array_intersect_key($this->fieldSetPurged(), $this->informationSchema->dtd);
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
            : get_object_vars($this)
        );
    }

    protected function setRecordKey(string $key = null) : self
    {
        if ($key) {
            $this->recordKey = $key;
            $this->primaryKey = $this->db->informationSchema()->key;
        }

        return $this;
    }

    protected function setIndexes(array $indexes = null) : self
    {
        $this->indexes      = $indexes ?? [];

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
            } elseif (method_exists($this, static::VALIDATOR[$field])) {
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
