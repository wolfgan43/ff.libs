<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace phpformsframework\libs\storage;

use phpformsframework\libs\Configurable;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\dto\ConfigRules;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\storage\dto\OrmResults;
use phpformsframework\libs\Exception;
use stdClass;

/**
 * Class DB
 * @package phpformsframework\libs
 */
class Model implements Configurable, Dumpable
{
    private const ERROR_BUCKET                                                          = Orm::ERROR_BUCKET;

    private const ERROR_MODEL_NOT_FOUND                                                 = "Model not Found";
    private const COLLECTION                                                            = "collection";
    private const TABLE                                                                 = "table";
    private const MAPCLASS                                                              = "mapclass";
    private const RAWDATA                                                               = "rawdata";
    private const READ                                                                  = "read";
    private const INSERT                                                                = "insert";
    private const FAKE                                                                  = "fake";
    private const DTD                                                                   = "dtd";
    private const DOT                                                                   = ".";
    private const SELECT_ALL                                                            = ".*";

    private static $models                                                              = null;

    private $collection                                                                 = null;
    private $table                                                                      = null;
    private $mapclass                                                                   = null;
    private $select                                                                     = null;
    private $selectJoin                                                                 = [];
    private $where                                                                      = [];
    /**
     * @var stdClass
     */
    private $schema                                                                     = null;
    private $name                                                                       = null;

    private $logical_fields                                                             = null;

    /**
     * @return array
     */
    public static function dump() : array
    {
        return array(
            "models"    => self::$models
        );
    }

    public static function get(string $model_name) : ?array
    {
        return self::$models[$model_name][self::RAWDATA] ?? null;
    }


    /**
     * DB constructor.
     * @todo da valutare poiche da problemi di omonimia. Se mai usata da togliere $collection_or_model
     * @param string|null $collection_or_model
     */
    public function __construct(string $collection_or_model = null)
    {
        if (isset(self::$models[$collection_or_model])) {
            $this->schema                                                               = (object) self::$models[$collection_or_model];
            $this->name                                                                 = $collection_or_model;
        } else {
            $this->collection                                                           = $collection_or_model;
        }
    }

    /**
     * @param string $collection_name
     * @param array|null $logical_fields
     * @return Model
     */
    public function loadCollection(string $collection_name, array $logical_fields = null) : self
    {
        $this->schema                                                                   = null;
        $this->name                                                                     = null;
        $this->collection                                                               = $collection_name;
        $this->logical_fields                                                           = $logical_fields;

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

        $this->schema                                                                   = (object) (self::$models[$model_name]);
        $this->name                                                                     = $model_name;
        $this->collection                                                               = null;

        return $this;
    }

    /**
     * @param string|null $table_name
     * @param array|null $fields
     * @return Model
     */
    public function table(string $table_name = null, array $fields = null) : self
    {
        $this->table                                                                    = $table_name;
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
     * @throws Exception
     */
    public function read(array $where = null, array $sort = null, int $limit = null, int $offset = null) : OrmResults
    {
        $select                                                                         = (
            $this->select ?? $this->schema->read ?? []
        ) + $this->selectJoin;

        return $this->getOrm()->read($select, $this->setWhere($where), $sort, $limit, $offset);
    }

    /**
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $offset
     * @return OrmResults
     * @throws Exception
     */
    public function readOne(array $where = null, array $sort = null, int $offset = null) : OrmResults
    {
        $select                                                                         = (
            $this->select ?? $this->schema->read ?? []
            ) + $this->selectJoin;

        return $this->getOrm()->read($select, $this->setWhere($where), $sort, 1, $offset, false);
    }

    /**
     * @param array $fields
     * @return OrmResults
     * @throws Exception
     */
    public function insert(array $fields) : OrmResults
    {
        $insert                                                                         = array_replace(
            $this->where,
            $this->schema
            ? $this->fill($this->schema->insert, $fields)
            : $this->fieldSet($fields, $this->table)
        );

        return $this->getOrm()->insert($insert);
    }

    /**
     * @param array $set
     * @param array|null $where
     * @return OrmResults
     * @throws Exception
     */
    public function update(array $set, array $where = null) : OrmResults
    {
        return $this->getOrm()->update($this->fieldSet($set, $this->table), $where);
    }

    /**
     * @param array $where
     * @return OrmResults
     * @throws Exception
     */
    public function delete(array $where) : OrmResults
    {
        return $this->getOrm()->delete($where);
    }

    /**
     * @return int
     * @throws Exception
     */
    public function count() : int
    {
        return $this->getOrm()->cmd("count");
    }

    /**
     * @return OrmResults
     */
    public function readByMock() : OrmResults
    {
        return new OrmResults(1, array($this->schema->fake), null, null, null, $this->schema->mapclass);
    }

    /**
     * @param string|null $table_name
     * @return stdClass
     */
    public function dtdStore(string $table_name = null) : stdClass
    {
        return (object) $this->getOrm()->dtd($table_name ?? $this->schema->table ?? $this->table);
    }

    /**
     * @return array
     */
    public function dtdModel() : ?array
    {
        return $this->schema->dtd ?? null;
    }

    /**
     * @return array
     */
    public function dtdRaw() : ?array
    {
        return $this->schema->rawdata ?? null;
    }

    /**
     * @param string|null $table
     * @return stdClass|null
     */
    public function informationSchema(string $table = null) : ?stdClass
    {
        return $this->getOrm()->informationSchema($table ?? $this->schema->table ?? $this->table);
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
        return ($this->schema
            ? Orm::getInstance($this->schema->collection, $this->schema->table, $this->mapclass ?? $this->schema->mapclass)
            : Orm::getInstance($this->collection, $this->table, $this->mapclass)
        )->setLogicalField($this->logical_fields);
    }

    /**
     * @param array|null $where
     * @return array|null
     */
    private function setWhere(array $where = null) : ?array
    {
        if ($where && ($table = $this->schema->table ?? $this->table ?? null)) {
            foreach ($where as $key => $value) {
                if (strpos($key, ".") === false) {
                    $key = $table . "." . $key;
                }
                $this->where[$key]                                                      = $value;
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
            $request_key                                                                = array();
            $request_value                                                              = array();
            foreach ($request as $key => $value) {
                if (is_array($value)) {
                    $value                                                              = json_encode($value);
                }

                $request_key[]                                                          = '$' . $key . "#";
                $request_key[]                                                          = '$' . $key . " ";
                $request_value[]                                                        = $value . "#";
                $request_value[]                                                        = $value . " ";
            }

            $prototype                                                                  = str_replace(
                $request_key,
                $request_value,
                implode("#", $model) . "#"
            );
            $prototype = preg_replace('/\$[a-zA-Z]+/', "", $prototype);


            $model                                                                      = array_combine(
                array_keys($model),
                explode("#", substr($prototype, 0, -1))
            );
        }

        return array_filter($model);
    }

    /**
     * @param array|null $ref
     * @param string|null $table_name
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
                $ref[$table_name . self::SELECT_ALL]                                    = $table_name . self::SELECT_ALL;
            }
        } else {
            $ref                                                                        = array();
        }
    }

    /**
     * @param array|null $fields
     * @param string|null $table_name
     * @return array
     */
    private function fieldSet(array $fields, string $table_name = null) : array
    {
        $res                                                                            = [];
        if ($table_name) {
            $errors                                                                     = null;
            $dtd                                                                        = self::dtdStore($table_name);
            foreach ($fields as $field => $value) {
                if (is_array($value) && !isset($dtd->$field)) {
                    $res                                                                = array_merge($res, self::fieldSet($value, $field));
                } elseif (isset($dtd->$field)) {
                    $res[$table_name . self::DOT . $field]                              = $value;
                } else {
                    $errors[]                                                           = "Missing Field: " . $field;
                }
            }

            Debug::set($errors, static::ERROR_BUCKET . "::table->" . $table_name);
        } else {
            $res                                                                        = $fields;
        }

        return $res;
    }

    /**
     * @access private
     * @param ConfigRules $configRules
     * @return ConfigRules
     */
    public static function loadConfigRules(ConfigRules $configRules) : ConfigRules
    {
        return $configRules
            ->add("models");
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig(array $config)
    {
        self::$models                                                                   = $config["models"];
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
        $schema                                                                         = array();
        foreach ($models as $model) {
            $model_attr                                                                 = (object) Dir::getXmlAttr($model);
            if (isset($model_attr->name)) {
                $model_name                                                             = $model_attr->name;

                $schema[$model_name][self::COLLECTION]                                  = $model_attr->collection   ?? null;
                $schema[$model_name][self::TABLE]                                       = $model_attr->table        ?? null;
                $schema[$model_name][self::MAPCLASS]                                    = $model_attr->mapclass     ?? null;

                if (isset($model["field"])) {
                    $fields                                                             = $model["field"];
                    $prefix                                                             = (
                        $schema[$model_name][self::COLLECTION]
                        ? $schema[$model_name][self::COLLECTION] . self::DOT
                        : null
                    );
                    foreach ($fields as $field) {
                        $attr                                                           = (object) Dir::getXmlAttr($field);
                        if (!isset($attr->name) || isset($schema[$model_name][self::READ][$prefix . $attr->db])) {
                            continue;
                        }
                        $key                                                            = $attr->name;
                        $schema[$model_name][self::RAWDATA][]                           = $key;
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
                }
            } else {
                Exception::warning("Model name not set or Fields empty", static::ERROR_BUCKET);
            }
        }

        self::$models                                                                   = $schema;
    }
}
