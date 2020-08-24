<?php
namespace phpformsframework\libs\storage;

use phpformsframework\libs\Model;
use phpformsframework\libs\security\Validator;
use Exception;

/**
 * Trait OrmUtil
 * @package phpformsframework\libs\storage
 */
trait OrmUtil
{
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
        $dtd                                                                    = $this->db->dtdStore();
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
     * @param array $errors
     * @param string|null $suffix
     * @throws Exception
     */
    private function error(array $errors, string $suffix = null) : void
    {
        throw new Exception($this->db->getName() .  "::db->" . ($this->recordKey ? "update" : "insert") . ": " . implode(", ", $errors) . $suffix, 400);
    }
}
