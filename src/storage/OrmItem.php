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
    private const PRIVATE_PROPERTIES                                            = [
        "dbCollection"  => true,
        "dbTable"       => true,
        "dbJoins"       => true,
        "dbRequired"    => true,
        "dbValidator"   => true,
        "dbConversion"  => true,
        "toDataResponse"=> true,
        "db"            => true,
        "primaryKey"    => true,
        "recordKey"     => true,
        "models"        => true,
        "model"         => true
    ];

    protected $dbCollection                                                     = null;
    protected $dbTable                                                          = null;
    protected $dbJoins                                                          = [];

    protected $toDataResponse                                                   = [];

    /**
     * @var OrmModel[]|null
     */
    private $models                                                             = [];
    private $model                                                              = null;

    /**
     * @param array|null $query
     * @param array|null $order
     * @param int|null $limit
     * @param int|null $offset
     * @param int $draw
     * @return DataTableResponse
     * @throws Exception
     */
    public static function search(array $query = null, array $order = null, int $limit = null, int $offset = null, int $draw = null) : DataTableResponse
    {
        $dataTableResponse                                                      = new DataTableResponse();
        $item                                                                   = new static();

        $where                                                                  = null;
        if (is_array($query)) {
            foreach ($query as $key => $value) {
                if (!isset($item->$key)) {
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
     * @return static
     */
    public static function load(array $data) : self
    {
        return (new static())->fill($data);
    }

    /**
     * OrmItem constructor.
     * @param array|null $where
     * @throws Exception
     */
    public function __construct(array $where = null)
    {
        $this->loadCollection();
        $this->read($where);
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
            if (is_int($join)) {
                $this->db->join($fields);
            } else {
                $this->db->join($join, $fields);
            }
        }
    }

    /**
     * @param object $obj
     * @return OrmItem
     */
    public function fillByObject(object $obj) : self
    {
        $this->fill((array) $obj);

        return $this;
    }

    /**
     * @param array $fields
     * @return OrmItem
     */
    public function fill(array $fields) : self
    {
        $this->autoMapping($fields);

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function apply() : string
    {
        $vars                                                                   = $this->fieldSetPurged();

        //$this->setDefaults($vars);
        //$vars = $this->fieldConvert($vars); //@todo da finire
        $this->verifyRequire($vars);
        $this->verifyValidator($vars);

        if ($this->recordKey) {
            $this->db->update($vars, [$this->primaryKey => $this->recordKey]);
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
        if ($where) {
            $item                                                               = $this->db
                ->read($where, null, 1);

            if ($item->countRecordset()) {
                $this->recordKey                                                = $item->key(0);
                $this->primaryKey                                               = $item->getPrimaryKey();

                if ($record = $item->getArray(0)) {
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
        $response = new DataResponse($this->fieldSetPurged(array_fill_keys($this->toDataResponse, true)));
        if (!$this->recordKey) {
            $response->error(404, $this->db->getName() . " not stored");
        }

        if ($this->model) {
            $response->set($this->model, $this->models[$this->model]->toDataResponse()->toArray());
        }

        return $response;
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
            : array_diff_key(get_object_vars($this), self::PRIVATE_PROPERTIES)
        );
    }
}
