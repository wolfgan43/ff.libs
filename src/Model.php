<?php
namespace phpformsframework\libs;

use phpformsframework\libs\storage\dto\OrmResults;
use phpformsframework\libs\storage\OrmModel;
use stdClass;

/**
 * Class DB
 * @package phpformsframework\libs
 */
class Model implements Configurable, Dumpable
{
    private const ERROR_BUCKET                                  = "model";
    private const BUCKET                                        = "bucket";
    private const OUTPUT                                        = "output";
    private const MAPCLASS                                      = "mapclass";
    private const READ                                          = "read";
    private const INSERT                                        = "insert";
    private const FAKE                                          = "fake";
    private const DTD                                           = "dtd";

    private static $models                                      = null;

    /**
     * @var OrmModel|null
     */
    private $orm                                                = null;
    private $collection                                         = null;
    private $table                                              = null;
    private $mapclass                                           = null;
    private $select                                             = [];
    private $selectJoin                                         = [];
    /**
     * @var stdClass
     */
    private $schema                                             = null;



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
     * @param string|null $ormModel
     * @param string|null $mainTable
     * @param string|null $mapClass
     * @return OrmModel
     */
    public static function orm(string $ormModel = null, string $mainTable = null, string $mapClass = null) : OrmModel
    {
        return OrmModel::getInstance($ormModel, $mainTable, $mapClass);
    }


    /**
     * DB constructor.
     * @param string|null $collection_or_model
     */
    public function __construct(string $collection_or_model = null)
    {
        $this->setSchema($collection_or_model);
    }

    /**
     * @param string|null $table_name
     * @param array|null $fields
     * @return Model
     */
    public function table(string $table_name = null, array $fields = null) : self
    {
        $this->table                                            = $table_name;
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
        $select = (
            empty($this->select)
            ? $this->schema->read ?? []
            : $this->select
        ) + $this->selectJoin;

        return $this->getOrm()->read($select, $where, $sort, $limit, $offset);
    }

    /**
     * @param array $fields
     * @return OrmResults
     */
    public function insert(array $fields) : OrmResults
    {
        $insert = (
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
     * @return array|null
     */
    public function delete(array $where) : ?array
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
     * @return array
     */
    public function dtd() : array
    {
        return $this->schema->dtd;
    }

    /**
     * @return OrmModel
     */
    private function getOrm() : OrmModel
    {
        if ($this->schema) {
            $this->orm                                      =& OrmModel::getInstance($this->schema->bucket, $this->schema->output, $this->mapclass ?? $this->schema->mapclass);
        } else {
            $this->orm                                      =& OrmModel::getInstance($this->collection, $this->table, $this->mapclass);
        }

        return $this->orm;
    }

    /**
     * @param array $model
     * @param array $request
     * @return array
     */
    private function fill(array $model, array $request) : array
    {
        if (!empty($request)) {
            $request_key                                        = array();
            $request_value                                      = array();
            foreach ($request as $key => $value) {
                if (is_array($value)) {
                    $value                                      = json_encode($value);
                }

                $request_key[]                                  = '$' . $key . "#";
                $request_key[]                                  = '$' . $key . " ";
                $request_value[]                                = $value . "#";
                $request_value[]                                = $value . " ";
            }

            $prototype                                          = str_replace(
                $request_key,
                $request_value,
                implode("#", $model) . "#"
            );
            $prototype = preg_replace('/\$[a-zA-Z]+/', "", $prototype);


            $model                                              = array_combine(
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
    private function fieldSelect(array &$ref, string $table_name = null, array $fields = null) : void
    {
        if ($table_name) {
            if ($fields) {
                foreach ($fields as $field => $alias) {
                    $ref[$table_name . "." . (is_int($field) ? $alias : $field)]    = $alias;
                }
            } else {
                $ref[$table_name . ".*"]                                            = $table_name . ".*";
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

                $res[$table_name . "." . $field]                                    = $value;
            }
        } else {
            $res = $fields;
        }
        return $res;
    }
    /**
     * @param string|null $model_name
     * @return void
     */
    private function setSchema(string $model_name = null) : void
    {
        if (isset(self::$models[$model_name])) {
            $this->schema                                       = (object) self::$models[$model_name];
        } else {
            $this->collection                                   = $model_name;
        }
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
        self::$models                                           = $config["models"];
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
        $schema                                                                 = array();
        foreach ($models as $model) {
            $model_attr                                                         = (object) Dir::getXmlAttr($model);
            if (isset($model_attr->name) && isset($model["field"])) {
                $model_name                                                     = $model_attr->name;

                $schema[$model_name][self::BUCKET]                              = $model_attr->bucket   ?? null;
                $schema[$model_name][self::OUTPUT]                              = $model_attr->output   ?? null;
                $schema[$model_name][self::MAPCLASS]                            = $model_attr->mapclass ?? null;

                $fields                                                         = $model["field"];
                $prefix                                                         = (
                    $schema[$model_name][self::BUCKET]
                    ? $schema[$model_name][self::BUCKET] . "."
                    : null
                );
                foreach ($fields as $field) {
                    $attr                                                       = (object) Dir::getXmlAttr($field);
                    if (!isset($attr->name)) {
                        continue;
                    }
                    $key                                                        = $attr->name;

                    $schema[$model_name][self::DTD][$key]                       = $attr->validator ?? null;

                    if (isset($attr->fake)) {
                        $schema[$model_name][self::FAKE][$key]                  = $attr->fake;
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

        self::$models                                                           = $schema;
    }
}

/*
App::db("user")
    ->where("last_login")           ->greater("12123123")
    ->where("username", true)   ->equal("pippo")
    ->filter(["name", "surname"])
    ->read();


$pippo = App::db("patient")
    ->filter(["name", "surname"])
    ->where("name", true)->equal("alesssandro")
    ->where("role", "anagraph_role")->equal("dottore")
    ->read(2, 1);



App::db("user")
    ->set("email", "pippo@pluto.it")
    ->set("name", "pippo")

    ->where("last_login")->grater("12123123")
    ->where("username")->equal("pippo")

    ->update();


App::db("user")
    ->insert
        ->set("uuid")
        ->set("user", "pippo")
        ->set("email", "pippo@pluto.it")
    ->execute();
*/
