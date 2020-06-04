<?php
namespace phpformsframework\libs\storage;

use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\dto\DataTableResponse;
use phpformsframework\libs\dto\Mapping;
use phpformsframework\libs\Model;
use phpformsframework\libs\security\Validator;
use Exception;
use phpformsframework\libs\storage\dto\OrmResults;

/**
 * Class OrmItem
 * @package phpformsframework\libs\storage
 */
class OrmItem
{
    use ClassDetector;
    use Mapping;

    private const PRIVATE_PROPERTIES                                            = [
        "dbCollection"  => true,
        "dbTable"       => true,
        "dbJoins"       => true,
        "dbRequired"    => true,
        "dbValidator"   => true,
        "dbConversion"  => true,
        "toDataResponse"=> true,
        "db"            => true,
        "where"         => true,
        "primaryKey"    => true,
        "recordKey"     => true,
        "model"         => true
    ];

    protected $dbCollection                                                     = null;
    protected $dbTable                                                          = null;
    protected $dbJoins                                                          = [];
    protected $dbRequired                                                       = [];
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
    protected $dbValidator                                                      = [];
    protected $dbConversion                                                     = [];

    protected $toDataResponse                                                   = [];

    /**
     * @var Model|null
     */
    private $db                                                                 = null;
    private $where                                                              = null;
    private $primaryKey                                                         = null;
    private $recordKey                                                          = null;
    private $model                                                              = null;

    /**
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @return DataTableResponse
     */
    public static function search(array $where = null, array $sort = null, int $limit = null, int $offset = null) : DataTableResponse
    {
        $dataTableResponse                                                      = new DataTableResponse();
        $item                                                                   = new static();
        $recordset                                                              = $item->db
            ->table($item->dbTable, $item->toDataResponse)
            ->read($where, $sort, $limit, $offset);

        if ($recordset->countRecordset()) {
            $dataTableResponse->fill($recordset->toArray());
            $dataTableResponse->recordsFiltered                                 = $recordset->countRecordset();
            $dataTableResponse->recordsTotal                                    = $recordset->countTotal();
        } else {
            $dataTableResponse->error(404, "not Found");
        }
        return $dataTableResponse;
    }

    /**
     * OrmItem constructor.
     * @param array $where
     * @param string|null $model_name
     */
    public function __construct(array $where = null, string $model_name = null)
    {
        $this->where                                                            = $where;
        if ($model_name) {
            $this->loadModel($model_name);
        } else {
            $this->loadCollection();
        }

        $this->read();
    }

    /**
     * @param string $model_name
     * @throws Exception
     */
    private function loadModel(string $model_name)
    {
        $this->db                                                               = new Model();
        $this->db->loadModel($model_name);
        $dtd = $this->db->dtdModel();
        foreach ($dtd as $item) {
         //da finire
        }

    }

    /**
     *
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
    public function fillWithObject(object $obj) : self
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
        //$vars = $this->fieldConvert($vars); //@todo da finire
        $this->verifyRequire($vars);
        $this->verifyValidator($vars);

        if ($this->recordKey) {
            $this->db->update($vars, [$this->primaryKey => $this->recordKey]);
        } else {
            $item                                                               = $this->db->insert($vars);
            $this->recordKey                                                    = $item->key(0);
            $this->primaryKey                                                   = $item->getPrimaryKey();
        }

        return $this->recordKey;
    }

    /**
     * @return OrmResults|null
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
     * @return DataResponse
     */
    public function toDataResponse() : DataResponse
    {
        $response = new DataResponse();
        if ($this->recordKey) {
            $response->fill($this->fieldSetPurged(array_fill_keys($this->toDataResponse, true)));
        } else {
            $response->error(404, $this->getClassName() . " not Found");
        }

        return $response;
    }

    /**
     * @param array $fields
     * @return array
     */
    private function fieldSetPurged(array $fields = null) : array
    {
        return ($fields
            ? array_intersect_key(get_object_vars($this), $fields)
            : array_diff_key(get_object_vars($this), self::PRIVATE_PROPERTIES)
        );
    }

    /**
     * @param array $vars
     * @return array
     */
    private function fieldConvert(array $vars) : array
    {
        //@todo da finire con funzioni vere probabilmente
        foreach ($this->dbConversion as $field => $convert) {
            if (array_key_exists($field, $vars)) {
                $vars[$convert] = $vars[$field];
                unset($vars[$field]);
            }
        }
        return $vars;
    }

    /**
     * @param array $vars
     * @throws Exception
     */
    private function verifyRequire(array $vars) : void
    {
        $required                                                               = array_diff_key(array_fill_keys($this->dbRequired, true), array_filter($vars));
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
        $validators                                                             = array_intersect_key($vars, $this->dbValidator);
        $dtd                                                                    = $this->db->dtd();
        foreach ($validators as $field => $value) {
            if (is_array($this->dbValidator[$field])) {
                if ($dtd->$field == Database::FTYPE_ARRAY || $dtd->$field == Database::FTYPE_ARRAY_OF_NUMBER) {
                    $arrField                                                   = explode(",", str_Replace(", ", ",", $value));
                    if (count(array_diff($arrField, $this->dbValidator[$field]))) {
                        $errors[]                                               = $field . " must be: [" . implode(", ", $this->dbValidator[$field]) . "]";
                    }
                } elseif (!in_array($value, $this->dbValidator[$field])) {
                    $errors[]                                                   = $field . " must be: [" . implode(", ", $this->dbValidator[$field]) . "]";
                }
            } elseif (method_exists($this, $this->dbValidator[$field])) {
                if (!$this->{$this->dbValidator[$field]}($value)) {
                    $errors[]                                                   = $field . " not valid";
                }
            } else {
                $validator                                                      = Validator::is($value, $field, $this->dbValidator[$field]);
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
     *
     */
    private function read() : void
    {
        if ($this->where) {
            $item                                                               = $this->db
                ->read($this->where, null, 1);

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
     * @param array $errors
     * @param string|null $suffix
     * @throws Exception
     */
    private function error(array $errors, string $suffix = null) : void
    {
        throw new Exception($this->getClassName() .  "::db->" . ($this->recordKey ? "update" : "insert") . ": " . implode(", ", $errors) . $suffix, 400);
    }
}
