<?php
namespace phpformsframework\libs\storage;

use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\dto\Mapping;
use phpformsframework\libs\Model;
use phpformsframework\libs\security\Validator;
use Exception;
use phpformsframework\libs\storage\dto\OrmResults;

/**
 * Class OrmModel
 * @package phpformsframework\libs\storage
 */
class OrmModel
{
    use ClassDetector;
    use Mapping;

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

    /**
     * @var Model|null
     */
    private $db                                                                 = null;
    private $primaryKey                                                         = null;
    private $recordKey                                                          = null;

    private $data                                                               = null;

    /**
     * OrmItem constructor.
     * @param array $where
     * @param string|null $model_name
     * @throws Exception
     */
    public function __construct(string $model_name, array $where = null)
    {
        $this->loadModel($model_name);
        $this->read($where);
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value) : void
    {
        if (array_key_exists($name, $this->data)) {
            $this->data[$name]                                                  = $value;
        }
    }

    /**
     * @param string|null $name
     * @throws Exception
     */
    private function loadModel(string $name = null)
    {
        $this->db                                                               = new Model();
        $this->db->loadModel($name);
        $this->data                                                             = $this->db->dtdModel();
    }

    /**
     * @param object $obj
     * @return OrmModel
     */
    public function fillWithObject(object $obj) : self
    {
        $this->fill((array) $obj);

        return $this;
    }

    /**
     * @param array $fields
     * @return OrmModel
     */
    public function fill(array $fields) : self
    {
        $this->data                                                             = array_replace($this->data, array_intersect_key($fields, $this->data));

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function apply() : string
    {
        $vars                                                                   = $this->data;
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
        $response                                                               = new DataResponse($this->data);
        if (!$this->recordKey) {
            $response->error(404, $this->db->getName() . " not stored");
        }

        return $response;
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
                    $arrField                                                   = explode(",", str_replace(", ", ",", $value));
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

                $this->data                                                     = $item->getArray(0);
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
