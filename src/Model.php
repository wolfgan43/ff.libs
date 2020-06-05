<?php
namespace phpformsframework\libs;

use Exception;
use phpformsframework\libs\storage\dto\OrmResults;
use phpformsframework\libs\storage\Orm;
use stdClass;

/**
 * Class DB
 * @package phpformsframework\libs
 */
class Model implements Configurable, Dumpable
{
    private const ERROR_BUCKET                                                      = "model";
    private const ERROR_MODEL_NOT_FOUND                                             = "Model not Found";
    private const BUCKET                                                            = "bucket";
    private const OUTPUT                                                            = "output";
    private const MAPCLASS                                                          = "mapclass";
    private const READ                                                              = "read";
    private const INSERT                                                            = "insert";
    private const FAKE                                                              = "fake";
    private const DTD                                                               = "dtd";
    private const DOT                                                               = ".";
    private const SELECT_ALL                                                        = ".*";

    private static $models                                                          = null;

    /**
     * @var Orm|null
     */
    private $orm                                                                    = null;
    private $collection                                                             = null;
    private $table                                                                  = null;
    private $mapclass                                                               = null;
    private $select                                                                 = null;
    private $selectJoin                                                             = [];
    private $where                                                                  = [];
    /**
     * @var stdClass
     */
    private $schema                                                                 = null;
    private $name                                                                   = null;



    /**
     * @return array
     */
    public static function dump() : array
    {
        return array(
            "models"    => self::$models
        );
    }

    /**
     * @param string|null $collection
     * @param string|null $mainTable
     * @param string|null $mapClass
     * @return Orm
     */
    public static function orm(string $collection = null, string $mainTable = null, string $mapClass = null) : Orm
    {
        return Orm::getInstance($collection, $mainTable, $mapClass);
    }


    /**
     * DB constructor.
     * @todo da valutare poiche da problemi di omonimia. Se mai usata da togliere $collection_or_model
     * @param string|null $collection_or_model
     */
    public function __construct(string $collection_or_model = null)
    {
        if (isset(self::$models[$collection_or_model])) {
            $this->schema                                                           = (object) self::$models[$collection_or_model];
            $this->name                                                             = $collection_or_model;
        } else {
            $this->collection                                                       = $collection_or_model;
        }
    }

    /**
     * @param string $collection_name
     * @return Model
     */
    public function loadCollection(string $collection_name) : self
    {
        $this->schema                                                               = null;
        $this->name                                                                 = null;
        $this->collection                                                           = $collection_name;

        return $this;
    }

    /**
     * @param string $model_name
     * @return Model
     * @throws Exception
     */
    public function loadModel(string $model_name) : self
    {
        if (!isset(self::$models[$model_name])) {
            throw new Exception(self::ERROR_MODEL_NOT_FOUND . ": " . $model_name, 501);
        }

        $this->schema                                                               = (object) (self::$models[$model_name]);
        $this->name                                                                 = $model_name;
        $this->collection                                                           = null;

        return $this;
    }

    /**
     * @param string|null $table_name
     * @param array|null $fields
     * @return Model
     */
    public function table(string $table_name = null, array $fields = null) : self
    {
        $this->table                                                                = $table_name;
        $this->fieldSelect($this->select, $table_name, $fields);

        return $this;
    }

    /**
     * @param string $table_name
     * @param array|null $fields
     * @return Model
     */
    public function join(string $table_name, array $fields = null) : self
    {
        $this->fieldSelect($this->selectJoin, $table_name, $fields);

        return $this;
    }

    /**
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @return OrmResults
     */
    public function read(array $where = null, array $sort = null, int $limit = null, int $offset = null) : OrmResults
    {
        $select                                                                     = (
            $this->select ?? $this->schema->read ?? []
        ) + $this->selectJoin;

        return $this->getOrm()->read($select, $this->setWhere($where), $sort, $limit, $offset);
    }

    /**
     * @param array $fields
     * @return OrmResults
     */
    public function insert(array $fields) : OrmResults
    {
        $insert                                                                     = array_replace(
            $this->where,
            $this->schema
            ? $this->fill($this->schema->insert, $fields)
            : $this->fieldSet($fields, $this->table)
        );
        return $this->getOrm()->insert($insert);
    }

    /**
     * @param array $set
     * @param array $where
     * @return OrmResults
     */
    public function update(array $set, array $where) : OrmResults
    {
        return $this->getOrm()->update($this->fieldSet($set, $this->table), $where);
    }

    /**
     * @param array $where
     * @return OrmResults
     */
    public function delete(array $where) : OrmResults
    {
        return $this->getOrm()->delete($where);
    }
    /**
     * @return OrmResults
     */
    public function readByMock() : OrmResults
    {
        return new OrmResults(array($this->schema->fake), 1, null, null, $this->schema->mapclass);
    }

    /**
     * @return array
     */
    public function readByRequest() : array
    {
        return $this->fill($this->schema->insert, Request::rawdata(true));
    }

    /**
     * @return stdClass
     */
    public function dtd() : stdClass
    {
        return (object) $this->getOrm()->dtd($this->schema->output ?? $this->table);
    }

    /**
     * @return array
     */
    public function dtdModel() : ?array
    {
        return $this->schema->dtd ?? null;
    }

    /**
     * @return stdClass
     */
    public function getName() : ?string
    {
        return $this->name ?? $this->collection . DIRECTORY_SEPARATOR . $this->table;
    }

    /**
     * @return Orm
     */
    private function getOrm() : Orm
    {
        return $this->orm ?? $this->setOrm();
    }

    /**
     * @return Orm
     */
    private function setOrm() : Orm
    {
        if ($this->schema) {
            $this->orm                                                              =& Orm::getInstance($this->schema->bucket, $this->schema->output, $this->mapclass ?? $this->schema->mapclass);
        } else {
            $this->orm                                                              =& Orm::getInstance($this->collection, $this->table, $this->mapclass);
        }

        return $this->orm;
    }

    /**
     * @param array|null $where
     * @return array|null
     */
    private function setWhere(array $where = null) : ?array
    {
        if ($where && ($table = $this->schema->output ?? $this->table ?? null)) {
            foreach ($where as $key => $value) {
                if (strpos($key, ".") === false) {
                    $key = $table . "." . $key;
                }
                $this->where[$key]                                                  = $value;
            }
        }
        return $this->where;
    }

    /**
     * @param array $model
     * @param array $request
     * @return array
     */
    private function fill(array $model, array $request) : array
    {
        if (!empty($request)) {
            $request_key                                                            = array();
            $request_value                                                          = array();
            foreach ($request as $key => $value) {
                if (is_array($value)) {
                    $value                                                          = json_encode($value);
                }

                $request_key[]                                                      = '$' . $key . "#";
                $request_key[]                                                      = '$' . $key . " ";
                $request_value[]                                                    = $value . "#";
                $request_value[]                                                    = $value . " ";
            }

            $prototype                                                              = str_replace(
                $request_key,
                $request_value,
                implode("#", $model) . "#"
            );
            $prototype = preg_replace('/\$[a-zA-Z]+/', "", $prototype);


            $model                                                                  = array_combine(
                array_keys($model),
                explode("#", substr($prototype, 0, -1))
            );
        }

        return array_filter($model);
    }

    /**
     * @param array $ref
     * @param string $table_name
     * @param array|null $fields
     */
    private function fieldSelect(array &$ref = null, string $table_name = null, array $fields = null) : void
    {
        if ($table_name) {
            if ($fields) {
                unset($ref[$table_name . self::SELECT_ALL]);
                foreach ($fields as $field => $alias) {
                    $ref[$table_name . self::DOT . (is_int($field) ? $alias : $field)]  = $alias;
                }
            } else {
                $ref[$table_name . self::SELECT_ALL]                                = $table_name . self::SELECT_ALL;
            }
        } else {
            $ref                                                                    = array();
        }
    }

    /**
     * @param string $table_name
     * @param array|null $fields
     * @return array
     */
    private function fieldSet(array $fields, string $table_name = null) : array
    {
        $res                                                                        = [];
        if ($table_name) {
            foreach ($fields as $field => $value) {
                if (is_object($value) || is_array($value)) {
                    continue;
                }

                $res[$table_name . self::DOT . $field]                              = $value;
            }
        } else {
            $res                                                                    = $fields;
        }
        return $res;
    }

    /**
     * @access private
     * @param dto\ConfigRules $configRules
     * @return dto\ConfigRules
     */
    public static function loadConfigRules($configRules)
    {
        return $configRules
            ->add("models")
            ->add("modelsview");
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig(array $config)
    {
        self::$models                                                               = $config["models"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema(array $rawdata) : array
    {
        if (isset($rawdata["model"]) && is_array($rawdata["model"])) {
            self::loadModels($rawdata["model"]);
        }

        return array(
            "models"       => self::$models
        );
    }

    /**
     * @param array $models
     */
    private static function loadModels(array $models) : void
    {
        $schema                                                                     = array();
        foreach ($models as $model) {
            $model_attr                                                             = (object) Dir::getXmlAttr($model);
            if (isset($model_attr->name) && isset($model["field"])) {
                $model_name                                                         = $model_attr->name;

                $schema[$model_name][self::BUCKET]                                  = $model_attr->bucket   ?? null;
                $schema[$model_name][self::OUTPUT]                                  = $model_attr->output   ?? null;
                $schema[$model_name][self::MAPCLASS]                                = $model_attr->mapclass ?? null;

                $fields                                                             = $model["field"];
                $prefix                                                             = (
                    $schema[$model_name][self::BUCKET]
                    ? $schema[$model_name][self::BUCKET] . self::DOT
                    : null
                );
                foreach ($fields as $field) {
                    $attr                                                           = (object) Dir::getXmlAttr($field);
                    if (!isset($attr->name)) {
                        continue;
                    }
                    $key                                                            = $attr->name;

                    $schema[$model_name][self::DTD][$key]                           = $attr->validator ?? null;

                    if (isset($attr->fake)) {
                        $schema[$model_name][self::FAKE][$key]                      = $attr->fake;
                    }
                    if (isset($attr->db)) {
                        $schema[$model_name][self::READ][$prefix . $attr->db]       = $key;

                        if (isset($attr->source)) {
                            $schema[$model_name][self::INSERT][$prefix .$attr->db]  = $attr->source;
                        }
                    }
                }
            } else {
                Error::registerWarning("Model name not set or Fields empty", static::ERROR_BUCKET);
            }
        }

        self::$models                                                               = $schema;
    }
}
