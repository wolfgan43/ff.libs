<?php
namespace phpformsframework\libs\storage;

use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\dto\Mapping;
use phpformsframework\libs\Model;
use phpformsframework\libs\security\Validator;
use Exception;

/**
 * Class OrmItem
 * @package phpformsframework\libs\storage
 */
class OrmItem
{
    use ClassDetector;
    use Mapping;

    private const PRIVATE_PROPERTIES        = [
        "dbCollection"  => true,
        "dbTable"       => true,
        "dbJoins"       => true,
        "dbRequired"    => true,
        "dbValidator"   => true,
        "dbConversion"  => true,
        "db"            => true,
        "where"         => true,
        "primaryKey"    => true,
        "recordKey"     => true
    ];

    protected $dbCollection                 = null;
    protected $dbTable                      = null;
    protected $dbJoins                      = [];
    protected $dbRequired                   = [];
    protected $dbValidator                  = [];
    protected $dbConversion                 = [];

    private $db                             = null;
    private $where                          = null;
    private $primaryKey                     = null;
    private $recordKey                      = null;
    /**
     * OrmItem constructor.
     * @param array $where
     */
    public function __construct(array $where = null)
    {
        $this->where                        = $where;
        $collection                         = $this->dbCollection ?? $this->getClassName();

        $this->db                           = new Model($collection);
        $this->db->table($this->dbTable);
        foreach ($this->dbJoins as $join => $fields) {
            if (is_int($join)) {
                $this->db->join($fields);
            } else {
                $this->db->join($join, $fields);
            }
        }

        $this->read();
    }

    /**
     * @return int
     * @throws Exception
     */
    public function apply() : int
    {
        $vars                                                                   = $this->fieldSetPurged();
        //$vars = $this->fieldConvert($vars); //@todo da finire
        $this->verifyRequire($vars);
        $this->verifyValidator($vars);

        if ($this->recordKey) {
            $item                                                               = $this->db->update($vars, [$this->primaryKey => $this->recordKey]);
        } else {
            $item                                                               = $this->db->insert($vars);
            $this->recordKey                                                    = $item->key(0);
            $this->primaryKey                                                   = $item->getPrimaryKey();
        }
        return $item->countRecordset();
    }

    /**
     * @return array|null
     */
    public function delete() : ?array
    {
        return ($this->primaryKey && $this->recordKey
            ? $this->db->delete([$this->primaryKey => $this->recordKey])
            : null
        );
    }

    /**
     * @return array
     */
    private function fieldSetPurged() : array
    {
        return array_diff_key(get_object_vars($this), self::PRIVATE_PROPERTIES);
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
            $this->error(array_keys($required), ($this->recordKey ? "update" : "insert"), "are required");
        }
    }

    /**
     * @param array $vars
     * @throws Exception
     */
    private function verifyValidator(array $vars) : void
    {
        $errors                                                                 = null;
        $validators                                                             = array_intersect_key($vars, array_fill_keys($this->dbValidator, true));
        foreach ($validators as $field => $value) {
            $validator                                                          = Validator::is($value, $field, $this->dbValidator[$field]);
            if ($validator->isError()) {
                $errors[]                                                       = $validator->error;
            }
        }

        if ($errors) {
            $this->error($errors, ($this->recordKey ? "update" : "insert"));
        }
    }

    private function read()
    {
        if ($this->where) {
            $item                           = $this->db
                ->read($this->where, null, 1);

            $this->recordKey                = $item->key(0);
            $this->primaryKey               = $item->getPrimaryKey();

            $this->autoMapping($item->getArray(0, false));
        }
    }

    /**
     * @param array $errors
     * @param string $action
     * @param string|null $suffix
     * @throws Exception
     */
    private function error(array $errors, string $action, string $suffix = null) : void
    {
        throw new Exception(static::class .  "::db->" . $action . ": fields (" . implode(", ", $errors) . ") " . $suffix);

    }
}
