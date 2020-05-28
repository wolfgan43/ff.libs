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
        "db"            => true,
        "where"         => true,
        "primaryKey"    => true,
        "recordKey"     => true
    ];

    protected $dbCollection                                                     = null;
    protected $dbTable                                                          = null;
    protected $dbJoins                                                          = [];
    protected $dbRequired                                                       = [];
    protected $dbValidator                                                      = [];
    protected $dbConversion                                                     = [];

    protected $toDataResponse                                                   = [];

    private $db                                                                 = null;
    private $where                                                              = null;
    private $primaryKey                                                         = null;
    private $recordKey                                                          = null;

    /**
     * OrmItem constructor.
     * @param array $where
     */
    public function __construct(array $where = null)
    {
        $this->where                                                            = $where;
        $collection                                                             = $this->dbCollection ?? $this->getClassName();

        $this->db                                                               = new Model($collection);
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
     * @param array $where
     * @return OrmResults
     */
    public function list(array $where) : OrmResults
    {
        return $this->db->read($where);
    }

    /**
     * @return DataResponse
     */
    public function toDataResponse() : DataResponse
    {
        return new DataResponse($this->fieldSetPurged(array_fill_keys($this->toDataResponse, true)));
    }

    /**
     * @param array $fields
     * @return array
     */
    private function fieldSetPurged(array $fields = self::PRIVATE_PROPERTIES) : array
    {
        return array_diff_key(get_object_vars($this), $fields);
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
            $item                                                               = $this->db
                ->read($this->where, null, 1);

            $this->recordKey                                                    = $item->key(0);
            $this->primaryKey                                                   = $item->getPrimaryKey();

            if ($record = $item->getArray(0)) {
                $this->autoMapping($record);
            }
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
