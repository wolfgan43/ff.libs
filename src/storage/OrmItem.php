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
        "primaryKey"    => true,
        "recordKey"     => true,
        "models"        => true
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
    private $primaryKey                                                         = null;
    private $recordKey                                                          = null;
    /**
     * @var OrmModel|null
     */
    private $models                                                             = [];

    /**
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @return DataTableResponse
     * @throws Exception
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
     */
    public function __construct(array $where = null)
    {
        $this->loadCollection();
        $this->read($where);
    }

    /**
     * @param string $name
     * @return OrmModel|null
     * @throws Exception
     */
    public function __get(string $name) : ?OrmModel
    {
        return (property_exists($this, $name)
            ? $this->$name
            : $this->getModel($name)
        );
    }

    /**
     * @param string $name
     * @param array|null $where
     * @return OrmModel|null
     * @throws Exception
     */
    public function getModel(string $name, array $where = null) : ?OrmModel
    {
        if (!isset($this->models[$name])) {
            $this->loadModel($name, $where);
        }
        return $this->models[$name];
    }

    /**
     * @return string|null
     */
    public function getID() : ?string
    {
        return $this->recordKey;
    }

    /**
     * @param string|null $name
     * @param array|null $where
     * @throws Exception
     */
    private function loadModel(string $name, array $where = null)
    {
        $this->models[$name]                                                    = new OrmModel($name, $where);
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
            if (!$this->recordKey) {
                throw new Exception($this->db->getName() .  "::db->insert Missing primary " . $this->primaryKey, 500);
            }
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
        $response = new DataResponse($this->fieldSetPurged(array_fill_keys($this->toDataResponse, true)));
        if (!$this->recordKey) {
            $response->error(404, $this->db->getName() . " not stored");
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
     * @param array|null $where
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
     * @param array $errors
     * @param string|null $suffix
     * @throws Exception
     */
    private function error(array $errors, string $suffix = null) : void
    {
        throw new Exception($this->db->getName() .  "::db->" . ($this->recordKey ? "update" : "insert") . ": " . implode(", ", $errors) . $suffix, 400);
    }
}
