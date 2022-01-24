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

use phpformsframework\libs\Config;
use phpformsframework\libs\Configurable;
use phpformsframework\libs\Debug;
use phpformsframework\libs\dto\ConfigRules;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\storage\dto\OrmResults;
use phpformsframework\libs\Exception;
use phpformsframework\libs\storage\dto\Schema;
use phpformsframework\libs\util\TypesConverter;
use stdClass;

/**
 * Class Model
 * @package phpformsframework\libs
 */
class Model implements Configurable, Dumpable
{
    use TypesConverter;

    private const ERROR_BUCKET                                                          = Orm::ERROR_BUCKET;

    private const ERROR_MODEL_NOT_FOUND                                                 = "Model not Found";
    private const KEY                                                                   = "id";
    private const COLLECTION                                                            = "collection";
    private const TABLE                                                                 = "table";
    private const MAPCLASS                                                              = "mapclass";
    private const FIELD                                                                 = "field";
    private const COLUMNS                                                               = "columns";
    private const READ                                                                  = "read";
    private const INSERT                                                                = "insert";
    private const MOCK                                                                  = "mock";
    private const DTD                                                                   = "dtd";
    private const HOOKS_READ                                                            = "onRead";
    private const HOOKS_WRITE                                                           = "onWrite";
    private const PROPERTIES                                                            = "properties";
    private const OPTION                                                                = "option";
    private const DOT                                                                   = ".";
    private const SELECT_ALL                                                            = ".*";

    private const DEFAULT_FIELD_TYPE                                                    = "string";

    private static $models                                                              = null;

    private $collection                                                                 = null;
    private $table                                                                      = null;
    private $select                                                                     = null;
    private $selectJoin                                                                 = [];
    private $where                                                                      = [];
    /**
     * @var Schema
     */
    public $schema                                                                      = null;
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

    /**
     * @param string $model_name
     * @return array|null
     */
    public static function columns(string $model_name) : ?array
    {
        return self::$models[$model_name][self::COLUMNS] ?? null;
    }


    /**
     * DB constructor.
     * @todo da valutare poiche da problemi di omonimia. Se mai usata da togliere $collection_or_model
     * @param string|null $collection_or_model
     */
    public function __construct(string $collection_or_model = null)
    {
        $this->name                                                                     = $collection_or_model;

        if (isset(self::$models[$collection_or_model])) {
            $this->schema                                                               = new Schema(self::$models[$collection_or_model]);
            $this->collection                                                           = $this->schema->collection;
            $this->table                                                                = $this->schema->table;
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
        $this->collection                                                               = $collection_name;
        $this->logical_fields                                                           = $logical_fields;

        return $this;
    }

    /**
     * @param string $model_name
     * @return Model
     * @throws Exception
     */
    public function load(string $model_name) : self
    {
        if (!isset(self::$models[$model_name])) {
            throw new Exception(self::ERROR_MODEL_NOT_FOUND . ": " . $model_name, 501);
        }

        $this->schema                                                                   = new Schema(self::$models[$model_name]);
        $this->collection                                                               = $this->schema->collection;
        $this->table                                                                    = $this->schema->table;

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
     * @param string $collection
     * @param string $table_name
     * @param array|null $fields
     * @return Model
     */
    public function join(string $collection, string $table_name, array $fields = null) : self
    {
        $this->fieldSelect($this->selectJoin, ($this->collection != $collection ? $collection . "." : "") . $table_name, $fields);

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
        return new OrmResults(1, array($this->schema->mock), null, null, null, $this->schema->mapclass);
    }

    /**
     * @param array $convertTo
     * @param array $convertIn
     * @return Schema
     * @throws Exception
     */
    public function schema(array $convertTo = [], array $convertIn = []) : Schema
    {
        if (!$this->schema) {
            throw new Exception(self::ERROR_MODEL_NOT_FOUND . ": " . $this->name, 501);
        }

        $this->schema->to = $convertTo;
        $this->schema->in = $convertIn;

        return $this->schema;
    }

    /**
     * @param string|null $table_name
     * @param string|null $collection
     * @return stdClass
     */
    public function dtd(string $table_name = null, string $collection = null) : stdClass
    {
        return (object) Orm::dtd($collection ?? $this->collection, $table_name ?? $this->table);
    }

    /**
     * @param string|null $table
     * @param string|null $collection
     * @return stdClass|null
     */
    public function informationSchema(string $table = null, string $collection = null) : ?stdClass
    {
        return Orm::informationSchema($collection ?? $this->collection, $table ?? $this->table);
    }
    /**
     * @return string|null
     */
    public function getName() : ?string
    {
        return $this->name . DIRECTORY_SEPARATOR . $this->table;
    }

    /**
     * @return Orm
     */
    private function getOrm() : Orm
    {
        return ($this->schema
            ? Orm::model($this->schema)
            : Orm::getInstance($this->collection, $this->table)
        )->setLogicalField($this->logical_fields, true);
    }

    /**
     * @param array|null $where
     * @return array|null
     */
    private function setWhere(array $where = null) : ?array
    {
        if ($where && !empty($this->table)) {
            foreach ($where as $key => $value) {
                if (strpos($key, ".") === false) {
                    $key = $this->table . "." . $key;
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
            $model = $this->mergeRequest($model, $request);
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
     * @param array $fields
     * @param string|null $table_name
     * @return array
     */
    private function fieldSet(array $fields, string $table_name = null) : array
    {
        $res                                                                            = [];
        if ($table_name) {
            $errors                                                                     = null;
            $dtd                                                                        = $this->dtd($table_name);
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
            "models"        => self::$models
        );
    }

    /**
     * @param array $models
     */
    private static function loadModels(array $models) : void
    {
        $extends = [];
        foreach ($models as $model) {
            if (self::loadModel($model)) {
                $extends[] = $model;
            }
        }
        foreach ($extends as $extend) {
            self::loadModel($extend, true);
        }
    }

    private static function loadModel(array $model, bool $isExtend = null) : ?bool
    {
        $model_attr                                                                             = Config::getXmlAttr($model);
        if ($isExtend) {
            $extend                                                                             = self::$models[$model_attr->extends];
            $schema_name                                                                        = $model_attr->extends . DIRECTORY_SEPARATOR . $model_attr->name;

            $schema[self::COLLECTION]                                                           = $extend[self::COLLECTION];
            $schema[self::TABLE]                                                                = $extend[self::TABLE];
            $schema[self::MAPCLASS]                                                             = $extend[self::MAPCLASS]     ?? null;
        } elseif (!empty($model_attr->extends)) {
            return true;
        } elseif (empty($model_attr->table) || empty($model_attr->collection)) {
            Error::registerWarning("Model attribute: 'collection' and 'table' are required", static::ERROR_BUCKET);
            return null;
        } else {
            $schema                                                                             = [];
            $schema_name                                                                        = $model_attr->name ?? $model_attr->table;

            $schema[self::COLLECTION]                                                           = $model_attr->collection;
            $schema[self::TABLE]                                                                = $model_attr->table;
            $schema[self::MAPCLASS]                                                             = $model_attr->mapclass     ?? null;
        }
        $schema[self::KEY]                                                                      = $schema_name;
        if (!empty($model[self::FIELD])) {
            if (!isset($model[self::FIELD][0])) {
                $model[self::FIELD]                                                             = array($model[self::FIELD]);
            }

            foreach ($model[self::FIELD] as $field) {
                $attr                                                                           = Config::getXmlAttr($field);
                $orm_field                                                                      = array_search($attr->name, $extend[self::READ] ?? []) ?: self::dbField($attr->db ?? $attr->name, $schema);
                if (!isset($attr->name) || isset($schema[self::READ][$orm_field])) {
                    continue;
                }

                $key                                                                            = $attr->name;

                /**
                 * Columns and Dtd
                 */
                $schema[self::COLUMNS][]                                                        = $key;
                $schema[self::DTD][$key]                                                        = $attr->type ?? $extend[self::DTD][$key] ?? self::DEFAULT_FIELD_TYPE;

                /**
                 * Mock
                 */
                if (!empty($attr->mock) || !empty($extend[self::MOCK][$key])) {
                    $schema[self::MOCK][$key]                                                   = $attr->mock ?? $extend[self::MOCK][$key];
                }

                /**
                 * Read and Insert
                 */
                $schema[self::READ][$orm_field]                                                 = $key;
                if (isset($attr->request)) {
                    $schema[self::INSERT][$orm_field]                                           = $attr->request;
                } elseif (isset($extend[self::INSERT][$orm_field])) {
                    $schema[self::INSERT][$orm_field]                                           = $extend[self::INSERT][$orm_field];
                }

                /**
                 * Options
                 */
                if (!empty($field[self::OPTION])) {
                    if (!isset($field[self::OPTION][0])) {
                        $field[self::OPTION]                                                    = array($field[self::OPTION]);
                    }
                    foreach ($field[self::OPTION] as $option) {
                        $attrOpt                                                                = Config::getXmlAttr($option);
                        if (!$attrOpt->value) {
                            continue;
                        }

                        $schema[self::OPTION][$key][$attrOpt->key ?? $attrOpt->value]           = $attrOpt->value;
                    }
                } elseif (isset($extend[self::OPTION][$key])) {
                    $schema[self::OPTION][$key]                                                 = $extend[self::OPTION][$key];
                }

                /**
                 * Hooks
                 */
                if (isset($attr->onProcessField)) {
                    $schema[self::HOOKS_READ][$key]                                             = $attr->onProcessField;
                } elseif (isset($extend[self::HOOKS_READ][$key])) {
                    $schema[self::HOOKS_READ][$key]                                             = $extend[self::HOOKS_READ][$key];
                }
                if (isset($attr->onStoreField)) {
                    $schema[self::HOOKS_WRITE][$key]                                            = $attr->onStoreField;
                } elseif (isset($extend[self::HOOKS_WRITE][$key])) {
                    $schema[self::HOOKS_WRITE][$key]                                            = $extend[self::HOOKS_WRITE][$key];
                }

                unset($attr->db, $attr->mock, $attr->request, $attr->onProcessField);

                $properties                                                                     = (array) $attr;
                if (!empty($properties) || !empty($extend[self::PROPERTIES][$key])) {
                    $schema[self::PROPERTIES][$key]                                             = array_replace($extend[self::PROPERTIES][$key] ?? [], $properties);
                }
            }
        }

        self::$models[$schema_name]                                                             = $schema;

        return false;
    }

    /**
     * @param string $name
     * @param array $schema
     * @return string
     */
    private static function dbField(string $name, array $schema) : string
    {
        switch (substr_count($name, self::DOT)) {
            case 0:
                return $schema[self::COLLECTION] . self::DOT . $schema[self::TABLE] . self::DOT . $name;
            case 1:
                return $schema[self::COLLECTION] . self::DOT . $name;
            default:
                return $name;
        }
    }
}
