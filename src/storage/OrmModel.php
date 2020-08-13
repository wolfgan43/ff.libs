<?php
namespace phpformsframework\libs\storage;

use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\dto\Mapping;
use phpformsframework\libs\Model;
use phpformsframework\libs\storage\dto\OrmResults;
use Exception;

/**
 * Class OrmModel
 * @package phpformsframework\libs\storage
 */
class OrmModel
{
    use ClassDetector;
    use Mapping;
    use OrmUtil;

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
}
