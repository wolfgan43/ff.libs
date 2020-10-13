<?php
namespace phpformsframework\libs\storage;

use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\dto\DataTableResponse;
use phpformsframework\libs\dto\Mapping;
use phpformsframework\libs\storage\dto\OrmResults;
use Exception;
use stdClass;

/**
 * Class OrmItem
 * @package phpformsframework\libs\storage
 */
class OrmItem
{
    use ClassDetector;
    use Mapping;
    use OrmUtil;

    private const SEARCH_OPERATORS                                              = [
        'gt'            => '$gt',
        'gte'           => '$gte',
        'lt'            => '$lt',
        'lte'           => '$lte',
        'ne'            => '$ne',
        'nin'           => '$nin',
        'regex'         => '$regex',
    ];

    protected $dbCollection                                                     = null;
    protected $dbTable                                                          = null;
    protected $dbPrimaryKey                                                     = null;
    protected $dbJoins                                                          = [];

    protected $toDataResponse                                                   = [];

    /**
     * @var OrmModel[]|null
     */
    private $models                                                             = [];
    private $model                                                              = null;

    private $indexes                                                            = [];
    private $rawData                                                            = [];
    private $storedData                                                         = [];

    private $oneToMany                                                          = [];
    private $manyToOne                                                          = [];

    /**
     * @return stdClass
     */
    private static function dtd() : stdClass
    {
        $item                                                                   = new static();
        return (object) [
                "collection"        => $item->dbCollection,
                "table"             => $item->dbTable,
                "dataResponse"      => $item->toDataResponse,
                "required"          => $item->dbRequired,
                "key"               => $item->dbPrimaryKey
            ];
    }

    /**
     * @param array|null $query
     * @param array|null $order
     * @param int|null $limit
     * @param int|null $offset
     * @param int|null $draw
     * @return DataTableResponse
     * @throws Exception
     */
    public static function search(array $query = null, array $order = null, int $limit = null, int $offset = null, int $draw = null) : DataTableResponse
    {
        $dataTableResponse                                                      = new DataTableResponse();
        $item                                                                   = new static();

        $where                                                                  = null;
        if (is_array($query)) {
            $dtd                                                                = $item->db->dtdStore();
            foreach ($query as $key => $value) {
                if (!isset($dtd->$key)) {
                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $op => $subvalue) {
                        if (substr($op, 0, 1) == '$') {
                            continue;
                        }

                        if (isset(self::SEARCH_OPERATORS[$op])) {
                            $value[self::SEARCH_OPERATORS[$op]]                 = $subvalue;
                        } else {
                            $value['$in'][]                                     = $subvalue;
                        }
                    }
                } elseif (stristr($value, "*")) {
                    $value                                                      = ['$regex' => $value];
                }
                $where[$key]                                                    = $value;
            }
        }

        $sort                                                                   = null;
        if (is_array($order)) {
            $fields                                                             = $item->toDataResponse;
            sort($fields);
            foreach ($order as $key => $value) {
                if (isset($value["column"])) {
                    if (is_array($value["column"])) {
                        throw new Exception("Multi array not supported in order: " . $key, 400);
                    }
                    if (!isset($fields[$value["column"]]) && is_numeric($value["column"])) {
                        throw new Exception("Order Column value not found: " . $key, 404);
                    }

                    $sort[$fields[$value["column"]] ?? $value["column"]]        = $value["dir"] ?? "asc";
                } elseif (!is_array($value)) {
                    $sort[$key]                                                 = $value;
                }
            }
        }

        $recordset                                                              = $item->db
            ->table($item->dbTable, $item->toDataResponse)
            ->read($where, $sort, $limit, $offset);

        if ($recordset->countRecordset()) {
            $dataTableResponse->fill($recordset->getAllArray());
            $dataTableResponse->recordsFiltered                                 = $recordset->countRecordset();
            $dataTableResponse->recordsTotal                                    = $recordset->countTotal();
            $dataTableResponse->draw                                            = $draw + 1;
        } else {
            //$dataTableResponse->error(404, "not Found");
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
            ->table($item->dbTable)
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
            ->table($item->dbTable)
            ->update($set, $where);
    }

    /**
     * @param array $data
     * @param array|null $indexes
     * @param string|null $record_key
     * @return static
     * @throws Exception
     */
    public static function load(array $data, array $indexes = null, string $record_key = null) : self
    {
        return (new static(null, $data, $record_key))
            ->setIndexes($indexes);
    }

    /**
     * OrmItem constructor.
     * @param array|null $where
     * @param array|null $fill
     * @param string|null $recordKey
     * @throws Exception
     */
    public function __construct(array $where = null, array $fill = null, string $recordKey = null)
    {
        $this->loadCollection();
        $this->read($where);
        $this->fill($fill);
        $this->setRecordKey($recordKey);
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
        $collection_name                                                        = $this->dbCollection ?? $this->getClassName();

        $this->db                                                               = new Model();
        $this->db->loadCollection($collection_name);
        $this->db->table($this->dbTable);

        if (!empty($this->dbJoins)) {
            $informationSchema                                                  = $this->db->informationSchema();
            foreach ($this->dbJoins as $join => $fields) {
                if (is_numeric($join)) {
                    $join                                                       = $fields;
                    $fields                                                     = null;
                }

                if (method_exists($join, 'dtd')) {
                    /**
                     * @var OrmItem $join
                     */
                    $dtd                                                        = $join::dtd();
                    if (!isset($this->{$dtd->table})) {
                        $this->{$dtd->table}                                    = [];
                    }
                    //da trovare un modo di inserire gli id per le tabelle join e poi rimuoverlo se non era gia dichiarato nel toDataResponse
                    $dtd->dataResponse[] = "id";
                    $this->db->join($dtd->table, $fields ?? $dtd->dataResponse);

                    $informationSchemaJoin                                      = $this->db->informationSchema($dtd->table);
                    if (isset($informationSchemaJoin->relationship[$this->dbTable])) {
                        /**
                         * ManyToOne
                         */
                        $rel                                                    = new stdClass();
                        $rel->mapClass                                          = $join;
                        $rel->informationSchema                                 = $informationSchemaJoin;

                        $this->manyToOne[$dtd->table]                           = $rel;
                    } elseif (isset($informationSchema->relationship[$dtd->table])) {
                        /**
                         * OneToMany
                         */
                        $rel                                                    = new stdClass();
                        $rel->mapClass                                          = $join;
                        $rel->primaryKey                                        = $dtd->key;
                        $rel->informationSchema                                 = $informationSchema;

                        $this->oneToMany[$dtd->table]                           = $rel;
                    }
                } else {
                    $this->db->join($join, $fields);
                }
            }
        }
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
            $this->fillManyToOne($fields);
            $this->fillOneToMany($fields);

            $this->autoMapping($fields);
        }

        return $this;
    }

    /**
     * @param array $fields
     * @throws Exception
     */
    private function fillManyToOne(array &$fields) : void
    {
        foreach (array_intersect_key($this->manyToOne, $fields) as $table => $manyToOne) {
            if (!is_array($fields[$table])) {
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
        $vars                                                                   = array_merge(array_diff_key($this->fieldSetPurged(), $this->oneToMany, $this->manyToOne), $this->indexes);
        $informationSchema                                                      = $this->db->informationSchema();
        $storedData                                                             = array_diff_key($this->storedData, $this->oneToMany, $this->manyToOne);

        $this->applyManyToOne($vars);

        //$vars = $this->fieldConvert($vars); //@todo da finire
        $this->verifyRequire($vars);
        $this->verifyValidator($vars);

        $vars = array_intersect_key($vars, $informationSchema->dtd);
        if ($this->recordKey) {
            if ($storedData != $vars) {
                $this->db->update($vars, [$this->primaryKey => $this->recordKey]);
            }
        } else {
            $item                                                               = $this->db->insert($vars);
            $this->recordKey                                                    = $item->key(0);
            $this->primaryKey                                                   = $item->getPrimaryKey();
            if (!$this->recordKey) {
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
    private function applyManyToOne(array &$vars) : void
    {
        /**
         * @var OrmItem $obj
         */
        foreach ($this->manyToOne as $table => $manyToOne) {
            $relDataDB                                                          = $this->storedData[$table] ?? null;
            $relData                                                            = array_intersect_key($this->{$table}, $manyToOne->informationSchema->dtd);

            /**
             * Skip update if DataStored = DataProperty
             */
            if ($relDataDB == $relData) {
                continue;
            }

            /**
             * Many to One
             */
            $relationship                                                       = (object) $manyToOne->informationSchema->relationship[$this->dbTable];
            $external_key                                                       = $relationship->external;

            $obj                                                                = $manyToOne->mapClass::load($relData, null, $this->indexes[$relationship->external] ?? null);
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
            $relData                                                            = $this->{$table};

            $relationship                                                       = (object) $oneToMany->informationSchema->relationship[$table];
            $where                                                              = [$relationship->external => $this->recordKey];
            $primaryKey                                                         = $oneToMany->primaryKey ?? $relationship->primary;

            $keys                                                               = array_flip(array_column($this->{$table} ?? [], $primaryKey));
            foreach ($relData as $index => $var) {
                /**
                 * Skip update if DataStored = DataProperty
                 */
                if (isset($relDataDB[$index]) && $relDataDB[$index] == $var) {
                    continue;
                }

                if (!empty($var[$primaryKey]) && isset($keys[$var[$primaryKey]]) && isset($this->{$table}[$keys[$var[$primaryKey]]][$relationship->primary])) {
                    /**
                     * Update
                     */
                    $obj                                                        = $oneToMany->mapClass::load($var, $where, $this->{$table}[$keys[$var[$primaryKey]]][$relationship->primary]);
                    $obj->fill($this->rawData[$table][$keys[$var[$primaryKey]]] ?? null);
                    $obj->apply();
                    $this->{$table}[$keys[$var[$primaryKey]]]                   = $obj->toArray();
                } else {
                    /**
                     * Insert
                     */
                    $obj                                                        = $oneToMany->mapClass::load($var, $where);
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
        return $this->fieldSetPurged(array_fill_keys($this->toDataResponse, true));
    }

    /**
     * @return OrmResults|null
     * @throws Exception
     */
    public function delete() : ?OrmResults
    {
        return ($this->primaryKey && $this->recordKey
            ? $this->db->delete([$this->primaryKey => $this->recordKey])
            : null
        );
    }

    /**
     * @return bool
     */
    public function isStored() : bool
    {
        return !empty($this->recordKey);
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

    private function setRecordKey(string $key = null) : self
    {
        if ($key) {
            $this->recordKey = $key;
            $this->primaryKey = $this->db->informationSchema()->key;
        }

        return $this;
    }

    private function setIndexes(array $indexes = null) : self
    {
        $this->indexes      = $indexes ?? [];

        return $this;
    }
}
