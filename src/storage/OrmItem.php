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
    /*private const PRIVATE_PROPERTIES                                            = [
        "dbCollection"  => true,
        "dbTable"       => true,
        "dbPrimaryKey"  => true,
        "dbJoins"       => true,
        "dbRequired"    => true,
        "dbValidator"   => true,
        "dbConversion"  => true,
        "toDataResponse"=> true,
        "db"            => true,
        "primaryKey"    => true,
        "recordKey"     => true,
        "indexes"       => true,
        "rawData"       => true,
        "models"        => true,
        "model"         => true
    ];*/

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
        foreach ($this->dbJoins as $join => $fields) {
            if (is_numeric($join)) {
                $join                                                           = $fields;
                $fields                                                         = null;
            }

            if (method_exists($join, 'dtd')) {
                /**
                 * @var OrmItem $join
                 */
                $dtd                                                            = $join::dtd();
                //da trovare un modo di inserire gli id per le tabelle join e poi rimuoverlo se non era gia dichiarato nel toDataResponse
                $dtd->dataResponse[] = "id";
                $this->db->join($dtd->table, $fields ?? $dtd->dataResponse);
            } else {
                $this->db->join($join, $fields);
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
            $informationSchema = $this->db->informationSchema();
            foreach ($this->dbJoins as $mapClass) {
                if (!method_exists($mapClass, 'dtd')) {
                    continue;
                }

                $dtd = $mapClass::dtd();
                if (isset($fields[$dtd->table])) {
                    $informationSchemaJoin = $this->db->informationSchema($dtd->table);
                    if (isset($informationSchemaJoin->relationship[$this->dbTable]) && is_array($fields[$dtd->table])) {
                        /**
                         * Many to One
                         */
                        if (!is_array($fields[$dtd->table])) {
                            $this->error([$dtd->table . " must be object"]);
                        }

                        $this->{$dtd->table} = array_replace($this->{$dtd->table} ?? [], $fields[$dtd->table]);
                        $this->rawData[$dtd->table] = $fields[$dtd->table];
                    } elseif (isset($informationSchema->relationship[$dtd->table])) {
                        /**
                         * One to Many
                         */
                        if (!is_array($fields[$dtd->table]) || !isset($fields[$dtd->table][0])) {
                            $this->error([$dtd->table . " must be array of object"]);
                        }

                        $relationship = (object) $informationSchema->relationship[$dtd->table];
                        $primaryKey = $dtd->key ?? $relationship->primary;
                        if (empty($this->{$dtd->table})) {
                            $this->{$dtd->table} = $fields[$dtd->table];
                            $this->rawData[$dtd->table] = $fields[$dtd->table];
                        } else {
                            $keys = array_flip(array_column($this->{$dtd->table} ?? [], $primaryKey));
                            $data = [];
                            foreach ($fields[$dtd->table] as $vars) {
                                if (!empty($vars[$primaryKey]) && isset($keys[$vars[$primaryKey]])) {
                                    /**
                                     * Update
                                     */
                                    $data[$keys[$vars[$primaryKey]]] = array_replace($this->{$dtd->table}[$keys[$vars[$primaryKey]]], $vars);
                                    $this->rawData[$dtd->table][$keys[$vars[$primaryKey]]] = $vars;
                                } else {
                                    /**
                                     * Insert
                                     */
                                    $index = count($this->{$dtd->table}) + count($data);
                                    $data[$index] = $vars;
                                    $this->rawData[$dtd->table][$index] = $vars;
                                }
                            }

                            $this->{$dtd->table} = array_replace($this->{$dtd->table}, $data);
                        }
                    }

                    unset($fields[$dtd->table]);
                }
            }

            $this->autoMapping($fields);
        }

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function apply() : string
    {
        $vars                                                                   = array_merge($this->fieldSetPurged(), $this->indexes);
        $informationSchema                                                      = $this->db->informationSchema();
        $dbRecord                                                               = $this->dbRecord();

        /**
         * @var OrmItem $mapClass
         */
        foreach ($this->dbJoins as $mapClass) {
            if (!method_exists($mapClass, 'dtd')) {
                continue;
            }

            $dtd = $mapClass::dtd();
            $relData = $vars[$dtd->table];
            $relDataDB = $dbRecord[$dtd->table] ?? null;
            unset($vars[$dtd->table], $dbRecord[$dtd->table]);

            $informationSchemaJoin = $this->db->informationSchema($dtd->table);
            if (isset($informationSchemaJoin->relationship[$this->dbTable])) {
                $relData = array_intersect_key($relData, $informationSchemaJoin->dtd);
                if ($relDataDB == $relData) {
                    continue;
                }

                /**
                 * Many to One
                 */
                $relationship = (object) $informationSchemaJoin->relationship[$this->dbTable];
                $external_key = $relationship->external;

                $obj = $mapClass::load($relData, null, $this->indexes[$relationship->external] ?? null);
                $obj->fill($this->rawData[$dtd->table]);
                $vars[$external_key] = $obj->apply();
                $this->{$dtd->table} = $obj->toArray();
            } elseif (isset($informationSchema->relationship[$dtd->table])) {
                /**
                 * One to Many
                 */
                $relationship = (object) $informationSchema->relationship[$dtd->table];
                $where = [$relationship->external => $this->recordKey];
                $primaryKey = $dtd->key ?? $relationship->primary;

                $keys = array_flip(array_column($this->{$dtd->table} ?? [], $primaryKey));
                foreach ($relData as $index => $var) {
                    if (isset($relDataDB[$index]) && $relDataDB[$index] == $var) {
                        continue;
                    }

                    if (!empty($var[$primaryKey]) && isset($keys[$var[$primaryKey]]) && isset($this->{$dtd->table}[$keys[$var[$primaryKey]]][$relationship->primary])) {
                        /**
                         * Update
                         */
                        $obj = $mapClass::load($var, $where, $this->{$dtd->table}[$keys[$var[$primaryKey]]][$relationship->primary]);
                        $obj->fill($this->rawData[$dtd->table][$keys[$var[$primaryKey]]]);
                        $obj->apply();
                        $this->{$dtd->table}[$keys[$var[$primaryKey]]] = $obj->toArray();
                    } else {
                        /**
                         * Insert
                         */
                        $obj = $mapClass::load($var, $where);
                        $obj->fill($this->rawData[$dtd->table][$index]);
                        $obj->apply();
                        $this->{$dtd->table}[$index] = $obj->toArray();
                    }
                }

                // non ancora gestito

                //togliere da ormmap relationship medicational_administration => therapy
                //togliere da ormmap medicational_admin_id in therapy
                //togliere remove medicational_admin_id in therapy
                //togliere campo therapy da medicationAdministration::class
            }
        }

        //$vars = $this->fieldConvert($vars); //@todo da finire
        $this->verifyRequire($vars);
        $this->verifyValidator($vars);

        $vars = array_intersect_key($vars, $informationSchema->dtd);
        if ($this->recordKey) {
            if ($dbRecord != $vars) {
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

        return $this->recordKey;
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
                    $this->dbRecord($record);
                    $this->autoMapping($record);
                }
            }
        }
    }

    private function dbRecord(array $dbRecord = null) : array
    {
        static $record = null;

        if ($dbRecord) {
            $record = $dbRecord;
        }
        return $record;
    }

    /**
     * @return DataResponse
     * @todo da tipizzare
     */
    public function toDataResponse() : DataResponse
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
