<?php
/**
 * VGallery: CMS based on FormsFramework
 * Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage core
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/gpl-3.0.html
 *  @link https://github.com/wolfgan43/vgallery
 */

namespace phpformsframework\libs\storage;

use phpformsframework\libs\Debug;
use phpformsframework\libs\Error;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Log;
use phpformsframework\libs\Mappable;

/**
 * Class OrmModel
 * @package phpformsframework\libs\storage
 */
class OrmModel extends Mappable
{
    public const ERROR_BUCKET                                                               = Orm::ERROR_BUCKET;
    private const RESULT                                                                    = Database::RESULT;
    private const INDEX                                                                     = Database::INDEX;
    private const INDEX_PRIMARY                                                             = Database::INDEX_PRIMARY;
    private const RAWDATA                                                                   = Database::RAWDATA;
    private const COUNT                                                                     = Database::COUNT;

    protected $bucket                                                                       = null;
    protected $type                                                                         = null;
    protected $main_table                                                                   = null;

    protected $connectors                                                                   = array();

    protected $adapters                                                                     = array();
    protected $struct                                                                       = null;
    protected $relationship                                                                 = null;
    protected $indexes                                                                      = null;
    protected $tables                                                                       = null;

    private $services_by_data                                                               = array();
    private $data                                                                           = array();
    private $main                                                                           = array();
    private $subs                                                                           = array();
    private $exts                                                                           = array();

    private $result                                                                         = array();
    private $count                                                                          = null;

    /**
     * OrmModel constructor.
     * @param string $map_name
     * @param string|null $main_table
     */
    public function __construct(string $map_name, string $main_table = null)
    {
        parent::__construct($map_name);

        if ($main_table) {
            if (!isset($this->tables[$main_table])) {
                Error::register("MainTable '" . $main_table . "' not found in " . $map_name);
            }
            $this->main_table                                                               = $main_table;
        }

        $this->adapters                                                                     = array_intersect_key($this->connectors, $this->adapters);
    }

    /**
     * @param string $table_name
     * @return array
     */
    private function getStruct(string $table_name) : array
    {
        $res                                                                                = array();
        $res["mainTable"]                                                                   = $this->main_table;

        $res["table"]                                                                       = (
            isset($this->tables[$table_name])
                                                                                                ? $this->tables[$table_name]
                                                                                                : null
                                                                                            );
        /*if (!isset($table["name"])) {
            $table["name"]                                                                  = $table_name;
        }*/
        $res["struct"]                                                                      = (
            isset($this->struct[$table_name])
                                                                                                ? $this->struct[$table_name]
                                                                                                : null
                                                                                            );
        $res["indexes"]                                                                     = (
            isset($this->indexes[$table_name])
                                                                                                ? $this->indexes[$table_name]
                                                                                                : array()
                                                                                            );
        $res["relationship"]                                                                = (
            isset($this->relationship[$table_name])
                                                                                                ? $this->relationship[$table_name]
                                                                                                : null
                                                                                            );
        $res["key_primary"]                                                                 = (
            $res["struct"]
                                                                                                ? (string) array_search(DatabaseAdapter::FTYPE_PRIMARY, $res["struct"])
                                                                                                : null
                                                                                            );
        return $res;
    }

    /**
     * @param string $table_name
     * @return string|null
     */
    private function getTableAlias(string $table_name) : ?string
    {
        return (isset($this->tables[$table_name]["alias"])
            ? $this->tables[$table_name]["alias"]
            : null
        );
    }

    /**
     * @return string|null
     */
    private function getMainTable() : ?string
    {
        return $this->main_table;
    }

    /**
     * @return string|null
     */
    private function getName() : ?string
    {
        return $this->type;
    }

    /**
     * @return OrmModel
     */
    private function getMainModel() : self
    {
        return ($this->type == $this->bucket
            ? $this
            : Orm::getInstance($this->bucket)
        );
    }

    /**
     * @param null|array $struct
     * @param bool $rawdata
     * @return Database
     */
    private function setStorage(array $struct = null, bool $rawdata = false) : Database
    {
        if (!$struct) {
            $struct                                                                         = $this->getStruct($this->getMainTable());
        }

        return Database::getInstance($this->adapters, $struct, $rawdata);
    }


    /**
     * @param null|array $fields
     * @param null|array $where
     * @param null|array $sort
     * @param int $limit
     * @param int|null $offset
     * @return array|bool|null
     */
    public function read(array $fields = null, array $where = null, array $sort = null, int $limit = null, int $offset = null)
    {
        Debug::stopWatch("orm/read");

        $res                                                                                = $this->get($where, $fields, $sort, $limit, $offset);
        Log::debugging(array(
            "action"    => "read",
            "fields"    => $fields,
            "where"     => $where,
            "sort"      => $sort,
            "limit"     => $limit,
            "offset"    => $offset,
            "exTime"    => Debug::stopWatch("orm/read")
        ), static::ERROR_BUCKET, static::ERROR_BUCKET, "read");

        return $res;
    }

    /**
     * @param null|array $fields
     * @param null|array $where
     * @param null|array $sort
     * @param null|int $limit
     * @param int $offset
     * @return array|bool|null
     */
    public function readRawData(array $fields = null, array $where = null, array $sort = null, int $limit = null, int $offset = null)
    {
        Debug::stopWatch("orm/readRawData");

        $res                                                                                = $this->get($where, $fields, $sort, $limit, $offset, true);
        Log::debugging(array(
            "action"    => "readRawData",
            "fields"    => $fields,
            "where"     => $where,
            "sort"      => $sort,
            "limit"     => $limit,
            "offset"    => $offset,
            "exTime"    => Debug::stopWatch("orm/readRawData")
        ), static::ERROR_BUCKET, static::ERROR_BUCKET, "readRawData");

        return $res;
    }

    /**
     * @param array $insert
     * @return array|null
     */
    public function insertUnique(array $insert) : ?array
    {
        Debug::stopWatch("orm/unique");

        $res                                                                                = $this->set($insert, null, $insert);
        Log::debugging(array(
            "action"    => "insertUnique",
            "insert"    => $insert,
            "exTime"    => Debug::stopWatch("orm/unique")
        ), static::ERROR_BUCKET, static::ERROR_BUCKET, "insertUnique");

        return $res;
    }

    /**
     * @param array $insert
     * @return array|null
     */
    public function insert(array $insert) : ?array
    {
        Debug::stopWatch("orm/insert");

        $res                                                                                = $this->set(null, null, $insert);
        Log::debugging(array(
            "action"    => "insert",
            "insert"    => $insert,
            "exTime"    => Debug::stopWatch("orm/insert")
        ), static::ERROR_BUCKET, static::ERROR_BUCKET, "insert");
        return $res;
    }

    /**
     * @param array $set
     * @param array $where
     * @return array|null
     */
    public function update(array $set, array $where) : ?array
    {
        Debug::stopWatch("orm/update");

        $res                                                                                = $this->set($where, $set);
        Log::debugging(array(
            "action"    => "update"
            , "set"     => $set
            , "where"   => $where
            , "exTime"  => Debug::stopWatch("orm/update")
        ), static::ERROR_BUCKET, static::ERROR_BUCKET, "update");

        return $res;
    }

    /**
     * @param array $where
     * @param null|array $set
     * @param null|array $insert
     * @return array|null
     */
    public function write(array $where, array $set = null, array $insert = null) : ?array
    {
        Debug::stopWatch("orm/write");

        $res                                                                                = $this->set($where, $set, $insert);
        Log::debugging(array(
            "action"  => "write",
            "where"   => $where,
            "set"     => $set,
            "insert"  => $insert,
            "exTime"  => Debug::stopWatch("orm/write")
        ), static::ERROR_BUCKET, static::ERROR_BUCKET, "write");

        return $res;
    }

    /**
     * @todo da verificare perchè e diverso dagli altri medoti
     * @param string $action
     * @param null|array $where
     * @param null|array $fields
     * @return array|null
     */
    public function cmd(string $action, array $where = null, array $fields = null) : ?array
    {
        $this->clearResult();

        $this->resolveFieldsByScopes(array(
            "select"                                                                        => $fields
            , "where"                                                                       => $where
        ));

        $this->execSub($action);

        $this->cmdData($action);

        return $this->getResult(true);
    }

    /**
     * @param array $where
     * @return array|null
     */
    public function delete(array $where) : ?array
    {
        Debug::stopWatch("orm/delete");

        $res                                                                                = null;
        Log::debugging(array(
            "action"    => "delete",
            "where"     => $where,
            "exTime"    => Debug::stopWatch("orm/delete")
        ), static::ERROR_BUCKET, static::ERROR_BUCKET, "delete");

        return $res;
    }

    /**
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param int $limit
     * @param int|null $offset
     * @param bool $result_raw_data
     * @return array|null
     */
    private function get(array $where = null, array $fields = null, array $sort = null, int $limit = null, int $offset = null, bool $result_raw_data = false) : ?array
    {
        $this->clearResult();
        $single_service                                                                     = $this->resolveFieldsByScopes(array(
                                                                                                "select"    => $fields,
                                                                                                "where"     => $where,
                                                                                                "sort"      => $sort
                                                                                            ));
        if ($single_service) {
            $this->getDataSingle($this->services_by_data["last"], $this->services_by_data["last_table"], $limit, $offset);
        } else {
            $countRunner                                                                    = $this->throwRunnerSubs(true);
            while ($this->throwRunner($limit, $offset) > 0) {
                $countRunner++;
            }
        }

        return $this->getResult($result_raw_data);
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return int
     */
    private function throwRunner(int $limit = null, int $offset = null) : int
    {
        $counter = 0;

        /**
         * Run Main query if where isset
         */
        if (!isset($this->main["runned"]) && isset($this->main["where"])) {
            $this->setCount($this->getData($this->main, $this->main["service"], $this->main["def"]["mainTable"], $limit, $offset));
            $this->main["runned"]                                               = true;
            $counter++;
        }

        $counter += $this->throwRunnerSubs();

        return $counter;
    }

    /**
     * @param bool $unique
     * @return int
     */
    private function throwRunnerSubs(bool $unique = false) : int
    {
        $counter = 0;
        /**
         * Run Sub query if where isset
         */
        if (isset($this->subs) && is_array($this->subs) && count($this->subs)) {
            foreach ($this->subs as $controller => $tables) {
                foreach ($tables as $table => $params) {
                    if (!isset($params["runned"]) && isset($params["where"])
                        && (!$unique || array_search("unique", $params["def"]["indexes"]) !== false)
                    ) {
                        $this->getData($params, $controller, $table);
                        $this->subs[$controller][$table]["runned"] = true;
                        $counter++;
                    }
                }
            }
        }

        return $counter;
    }

    /**
     * @param array $data
     * @param string $controller
     * @param string $table
     * @param int $limit
     * @param int|null $offset
     * @return int|null
     */
    private function getData(array $data, string $controller, string $table, int $limit = null, int $offset = null) : ?int
    {
        $count                                                                              = null;
        if (isset($data["where"])) {
            $main_table                                                                     = $data["def"]["mainTable"];
            $ormModel                                                                       = $this->getModel($controller);
            $regs                                                                           = $ormModel
                                                                                                ->setStorage($data["def"], false)
                                                                                                ->read(
                                                                                                    $this->getCurrentScope($data, "where"),
                                                                                                    $this->getCurrentScope($data, "select"),
                                                                                                    $this->getCurrentScope($data, "sort"),
                                                                                                    $limit,
                                                                                                    $offset
                                                                                                );
            if (isset($regs[self::RESULT])) {
                $thisTable                                                              = $table;
                $aliasTable                                                             = $data["def"]["table"]["alias"];
                $this->result[$thisTable]                                               = $regs[self::RESULT];
                $count                                                                  = $regs[self::COUNT];

                if (is_array($data["def"]["relationship"]) && count($data["def"]["relationship"])) {
                    foreach ($data["def"]["relationship"] as $ref => $relation) {
                        unset($whereRef);
                        $manyToMany     = false;
                        $oneToMany      = isset($relation["tbl"]) && isset($data["def"]["struct"][$ref]);
                        if ($oneToMany) {
                            $thisKey    = $ref;

                            $relTable   = $relation["tbl"];
                            $relKey     = $relation["key"];

                            if ($relTable != $main_table && isset($data["def"]["relationship"][$main_table])) {
                                $manyToMany = true;
                            }
                        } elseif (isset($data["def"]["struct"][$relation["external"]])) {
                            if ($ref != $main_table && isset($data["def"]["relationship"][$main_table])) {
                                $manyToMany = true;
                            }
                            if (isset($data["def"]["relationship"][$relation["external"]])) {
                                $oneToMany  = true;
                            }

                            $thisKey    = $relation["external"];

                            $relTable   = $ref;
                            $relKey     = $relation["primary"];
                        } else {
                            $thisKey    = $relation["primary"];

                            $relTable   = $ref;
                            $relKey     = $relation["external"];
                        }

                        if (isset($this->exts["done"][$thisTable . "." . $thisKey][$relTable . "." . $relKey])) {
                            continue;
                        }

                        if ($main_table == $relTable) {
                            $whereRef =& $this->main;
                        } elseif (isset($this->subs[$controller][$relTable])) {
                            $whereRef =& $this->subs[$controller][$relTable];
                        } elseif (isset($this->services_by_data["tables"][$controller . "." . $relTable])) {
                            Error::register("Relationship not found: " . $thisTable . "." . $thisKey . " => " . $relTable . "." . $relKey);
                        }

                        $keyValue = array_column($regs[self::INDEX], $thisKey);
                        if (isset($whereRef) && count($keyValue)) {
                            $this->whereBuilder($whereRef, $keyValue, $relKey);
                        } elseif (isset($this->services_by_data["tables"][$controller . "." . $relTable])) {
                            Error::register("Relationship found but missing keyValue in result: " . $thisTable . " => " . $thisKey . " (" . $relTable . "." . $relKey . ")");
                        }

                        $this->exts["rel"][$relTable][$thisTable] = $keyValue;
                        $this->exts["done"][$thisTable . "." . $thisKey][$relTable . "." . $relKey] = true;

                        if (isset($this->result[$relTable])) {
                            foreach ($keyValue as $keyCounter => $keyID) {
                                if (isset($regs[self::INDEX][$keyCounter][$thisKey]) && $regs[self::INDEX][$keyCounter][$thisKey] == $keyID) {
                                    if ($main_table == $thisTable) {
                                        $keyParents = array_keys($this->exts["rel"][$thisTable][$relTable], $keyID);

                                        foreach ($keyParents as $keyParent) {
                                            /**
                                             * Remove if exist reference of Result in sub table for prevent circular references
                                             */
                                            unset($this->result[$relTable][$keyParent][$aliasTable]);

                                            if (!$oneToMany && !$manyToMany) {
                                                $this->result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)][]   =& $this->result[$relTable][$keyParent];
                                            } else {
                                                $this->result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)]     =& $this->result[$relTable][$keyParent];
                                            }
                                        }
                                    } elseif ($manyToMany) {
                                        $keyParents = array_keys($this->exts["rel"][$thisTable][$relTable], $keyID);
                                        foreach ($keyParents as $keyParent) {
                                            /**
                                             * Remove if exist reference of Result in sub table for prevent circular references
                                             */
                                            unset($this->result[$relTable][$keyParent][$aliasTable]);

                                            $this->result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)] =& $this->result[$relTable][$keyParent];
                                        }
                                    } else {
                                        $keyParents = array_keys($this->exts["rel"][$thisTable][$relTable], $keyID);

                                        /**
                                         * Remove if exist reference of Result in sub table for prevent circular references
                                         */
                                        unset($this->result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)]);

                                        foreach ($keyParents as $keyParent) {
                                            if ($oneToMany || $manyToMany) {
                                                $this->result[$relTable][$keyParent][$aliasTable][] =& $this->result[$thisTable][$keyCounter];
                                            } else {
                                                $this->result[$relTable][$keyParent][$aliasTable] =& $this->result[$thisTable][$keyCounter];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $count;
    }

    /**
     * @param string $controller
     * @param string $table
     * @param int|null $limit
     * @param int|null $offset
     */
    private function getDataSingle(string $controller, string $table, int $limit = null, int $offset = null) : void
    {
        $data                                                                               = (
            isset($this->subs[$controller]) && isset($this->subs[$controller][$table])
                                                                                                ? $this->subs[$controller][$table]
                                                                                                : $this->main
                                                                                            );
        if ($data) {
            $regs                                                                           = $this->getModel(
                isset($data["service"])
                                                                                                    ? $data["service"]
                                                                                                    : $controller
                                                                                                )
                                                                                                ->setStorage($data["def"], true)
                                                                                                ->read(
                                                                                                    $this->getCurrentScope($data, "where"),
                                                                                                    $this->getCurrentScope($data, "select"),
                                                                                                    $this->getCurrentScope($data, "sort"),
                                                                                                    $limit,
                                                                                                    $offset

                                                                                                );
            if (is_array($regs) && $regs[self::RAWDATA]) {
                $this->result[$data["def"]["mainTable"]]                                        = $regs[self::RAWDATA];
            }
        } else {
            Error::register("normalize data is empty", static::ERROR_BUCKET);
        }
    }


    /**
     * @param null|array $where
     * @param null|array $set
     * @param null|array $insert
     * @return array|null
     */
    private function set(array $where = null, array $set = null, array $insert = null) :?array
    {
        $this->clearResult();

        $this->resolveFieldsByScopes(array(
            "insert"                                                                        => $insert
            , "set"                                                                         => $set
            , "where"                                                                       => $where
        ));

        $this->execSub();

        $this->setData();

        if (isset($this->data["rev"]) && is_array($this->data["rev"]) && count($this->data["rev"])) {
            foreach ($this->data["rev"] as $table => $controller) {
                $this->setData($controller, $table);
            }
        }

        return $this->getResult(true);
    }

    /**
     * @param string|null $controller
     * @param string|null $table
     */
    private function setData(string $controller = null, string $table = null) : void
    {
        $insert_key                                                                         = null;
        $data                                                                               = $this->getCurrentData($controller, $table);
        $modelName                                                                          = (
            isset($data["service"])
                                                                                                ? $data["service"]
                                                                                                : $controller
                                                                                            );
        $ormModel                                                                           = $this->getModel($modelName);
        $storage                                                                            = $ormModel->setStorage($data["def"]);
        $key_name                                                                           = $data["def"]["key_primary"];

        if (isset($data["insert"]) && !isset($data["set"]) && isset($data["where"])) {
            $regs                                                                           = $storage->read($data["insert"], array($key_name => true));
            if (is_array($regs)) {
                $this->setKeyRelationship($key_name, array_column($regs[self::INDEX], $key_name), $data, $controller);
            } else {
                $regs                                                                       = $storage->insert($data["insert"], $data["def"]["table"]["name"]);
                if (is_array($regs)) {
                    $insert_key                                                             = $regs[self::INDEX_PRIMARY][$key_name];
                }
            }
        } elseif (isset($data["insert"]) && !isset($data["set"]) && !isset($data["where"])) {
            $regs                                                                           = $storage->insert($data["insert"], $data["def"]["table"]["name"]);

            if (is_array($regs)) {
                $insert_key                                                                 = $regs[self::INDEX_PRIMARY][$key_name];
            }
        } elseif (!isset($data["insert"]) && isset($data["set"]) && !isset($data["where"])) {
            if (isset($data["def"]["relationship"][$this->main["def"]["mainTable"]]) && isset($this->main["where"])) {
                $key_main_primary                                                           = $data["def"]["relationship"][$this->main["def"]["mainTable"]]["primary"];
                if (!isset($this->main["where"][$key_main_primary])) {
                    $regs                                                                   = $this->getModel($modelName)
                                                                                                ->setStorage($this->main["def"])
                                                                                                ->read($this->main["where"], array($key_main_primary => true), null, null, null, $this->main["def"]["table"]["name"]);
                    if (is_array($regs)) {
                        $this->main["where"][$key_main_primary]                     = array_column($regs[self::INDEX], $key_main_primary);
                    }
                }
                $external_name                                                              = $data["def"]["relationship"][$this->main["def"]["mainTable"]]["external"];
                $primary_name                                                               = $data["def"]["relationship"][$this->main["def"]["mainTable"]]["primary"];
                if (!isset($data["def"]["struct"][$external_name])) {
                    if (!isset($this->main["where"][$external_name])) {
                        $this->setMainIndexes($ormModel);
                    }

                    $data["where"][$primary_name]                                           = $this->main["where"][$external_name];
                } elseif (isset($this->main["where"][$primary_name])) {
                    $data["where"][$external_name]                                          = $this->main["where"][$primary_name];
                }
            }

            $this->result["update"][$data["def"]["table"]["alias"]]                         = isset($data["where"]) && (bool) $storage->update($data["set"], $data["where"], $data["def"]["table"]["name"]);
        } elseif (!isset($data["insert"]) && isset($data["set"]) && isset($data["where"])) {
            $this->result["update"][$data["def"]["table"]["alias"]]                         = (bool) $storage->update($data["set"], $data["where"], $data["def"]["table"]["name"]);
        } elseif (!isset($data["insert"]) && !isset($data["set"]) && isset($data["where"])) {
            Error::register("Catrina: data not managed", static::ERROR_BUCKET);
        } elseif (isset($data["insert"]) && isset($data["set"]) && isset($data["where"])) {
            $regs                                                                           = $storage->write(
                $data["insert"],
                array(
                    "set"       => $data["set"],
                    "where"     => $data["where"]
                ),
                $data["def"]["table"]["name"]
            );
            if (isset($regs["action"]) && $regs["action"] == "insert") {
                $insert_key                                                                 = $regs[self::INDEX_PRIMARY][$key_name];
            }
        }

        $this->setKeyRelationship($key_name, $insert_key, $data, $controller);

        if ($insert_key !== null) {
            $this->result["insert"][$data["def"]["table"]["alias"]]                         = $insert_key;
        }
    }

    /**
     * @param OrmModel $ormModel
     */
    private function setMainIndexes(OrmModel $ormModel) : void
    {
        $res                                                                                = $ormModel
                                                                                                ->getMainModel()
                                                                                                ->setStorage($this->main["def"])
                                                                                                ->read(
                                                                                                    $this->main["where"],
                                                                                                    array_keys($this->main["def"]["indexes"])
                                                                                                );
        if (is_array($res)) {
            $this->main["where"]                                                            = array_replace($this->main["where"], $res);
        }
    }

    /**
     * @todo da tipizzare
     * @param string $key_name
     * @param string|array|null $key
     * @param array|null $data
     * @param string|null $controller
     */
    private function setKeyRelationship(string $key_name, $key = null, array $data = null, string $controller = null) : void
    {
        if ($key && is_array($data["def"]["relationship"]) && count($data["def"]["relationship"])) {
            if (!$controller) {
                $controller                                                                 = $this->main["service"];
            }
            foreach ($data["def"]["relationship"] as $tbl => $rel) {
                if (isset($rel["external"]) && isset($this->subs[$controller][$tbl])) {
                    $field_ext                                                              = $rel["external"];
                    if (isset($data["def"]["struct"][$field_ext])) {
                        $field_ext                                                          = $rel["primary"];
                    }
                    if ($key && $field_ext && $field_ext != $key_name) {
                        if ($tbl != $this->main["def"]["mainTable"]) {
                            $rev_controller                                                 = $this->data["rev"][$tbl];

                            if (isset($this->subs[$rev_controller][$tbl]["insert"])) {
                                $this->subs[$rev_controller][$tbl]["insert"][$field_ext]   = $key;
                            }
                            if (isset($this->subs[$rev_controller][$tbl]["set"])) {
                                $this->subs[$rev_controller][$tbl]["where"][$field_ext]    = $key;
                            }
                        } else {
                            if (isset($this->main["insert"])) {
                                $this->main["insert"][$field_ext]                           = $key;
                            }
                            if (isset($this->main["set"])) {
                                $this->main["where"][$field_ext]                            = $key;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string|null $cmd
     */
    private function execSub(string $cmd = null) : void
    {
        if (isset($this->subs) && is_array($this->subs) && count($this->subs)) {
            foreach ($this->subs as $controller => $tables) {
                foreach ($tables as $table => $params) {
                    $field_ext                                                              = (
                        isset($params["def"]["relationship"][$params["def"]["mainTable"]]["external"])
                                                                                                ? $params["def"]["relationship"][$params["def"]["mainTable"]]["external"]
                                                                                                : null
                                                                                            );
                    $field_main_ext                                                         = (
                        isset($params["def"]["relationship"][$this->main["def"]["mainTable"]]["external"])
                                                                                                ? $params["def"]["relationship"][$this->main["def"]["mainTable"]]["external"]
                                                                                                : null
                                                                                            );

                    if (isset($params["def"]["struct"][$field_ext]) || isset($params["def"]["struct"][$field_main_ext])) {
                        $this->data["rev"][$table]                                          = $controller;
                    } else {
                        if ($cmd) {
                            $this->cmdData($cmd, $controller, $table);
                        } else {
                            $this->setData($controller, $table);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $command
     * @param string|null $controller
     * @param string|null $table
     */
    private function cmdData(string $command, string $controller = null, string $table = null) : void
    {
        $data                                                                               = $this->getCurrentData($controller, $table);
        if (isset($data["where"])) {
            $ormModel                                                                       = $this->getModel(
                $data["service"]
                                                                                                ? $data["service"]
                                                                                                : $controller
                                                                                            );
            $storage                                                                        = $ormModel->setStorage($data["def"]);
            $regs                                                                           = $storage->cmd(
                $command,
                $this->getCurrentScope($data, "where")
                                                                                            );
            $this->result["cmd"]                                                            = $regs;
        }
    }

    /**
     * @param string|null $controller
     * @param string|null $table
     * @return array|null
     */
    private function getCurrentData(string $controller = null, string $table = null) : ?array
    {
        $data       = $this->main;
        if ($controller || $table) {
            $data   = ($this->subs[$controller][(
                $table
                ? $table
                : $this->getModel($controller)->getMainTable()
            )]);
        }

        return $data;
    }

    /**
     * @param bool $rawdata
     * @return array|null
     */
    private function getResult(bool $rawdata = false) : ?array
    {
        if (1 || $rawdata || count($this->result) > 1) {
            if (isset($this->result["insert"])) {
                $res                                                                        = $this->result["insert"];
            } elseif (isset($this->result["update"])) {
                $res                                                                        = $this->result["update"];
            } elseif (isset($this->result["cmd"])) {
                $res                                                                        = $this->result["cmd"];
            } else {
                $res                                                                        = (
                    isset($this->result[$this->main["def"]["mainTable"]])
                    ? $this->result[$this->main["def"]["mainTable"]]
                    : null
                );

                /*
                                $res                                                                        = ( //@todo da togliere gestendo la relazione esternamente al result
                                    count($this->services_by_data["tables"]) > 1
                                                                                                                ? $this->result[$this->main["def"]["mainTable"]]
                                                                                                                : $this->result
                                                                                                            );*/
                if (isset($res[0]) && count($res) == 1) {
                    $res                                                                    = $res[0];
                }
            }
        } elseif (isset($this->result[$this->main["def"]["mainTable"]])) {
            $res                                                                            = current($this->result);

            if (isset($this->main["service"]) && isset($this->subs) && isset($this->subs[$this->main["service"]]) && is_array($this->subs[$this->main["service"]]) && count($this->subs[$this->main["service"]]) == 1 && is_array($res) && count($res) == 1) {
                $res                                                                        = current($res);
            }

            $service_name                                                                   = $this->getName();
            if (is_array($res) && count($res) == 1 && isset($res[$service_name])) {
                $res                                                                        = $res[$service_name];
            }
        } elseif (isset($this->result[0])) {
            $res                                                                            = $this->result[0];
        } else {
            $res                                                                            = null;
        }

        return $res;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function resolveFieldsByScopes(array $data) : bool
    {
        foreach ($data as $scope => $fields) {
            $this->resolveFields($fields, $scope);
        }

        $is_single_service                                                                  = (count($this->services_by_data["services"]) == 1);

        if ((!isset($this->main) || !(isset($this->main["where"]) || isset($this->main["select"]) || isset($this->main["insert"]))) && $is_single_service) {
            $subService                                                                     = key($this->services_by_data["services"]);
            $ormModel                                                                       = $this->getModel($subService);
            $subTable                                                                       = $ormModel->getMainTable();

            if (isset($this->subs[$subService]) && isset($this->subs[$subService][$subTable])) {
                $this->main                                                                 = $this->subs[$subService][$subTable];
            } else {
                $this->main["def"]                                                          = $ormModel->getStruct($subTable);
            }
            $this->main["service"]                                                          = $subService;

            unset($this->subs[$subService][$subTable]);
            if (!count($this->subs[$subService])) {
                unset($this->subs[$subService]);
            }
            if (!count($this->subs)) {
                unset($this->subs);
            }

            if ($data["where"] === true) {
                $this->subs[$subService]["state"]["where"] = true;
            }
        } else {
            $mainTable                                                                      = $this->getMainTable();

            $this->main["def"]                                                              = $this->getStruct($mainTable);
            $this->main["service"]                                                          = $this->getName();

            if ($data["where"] === true) {
                $this->main["where"] = true;
            }
        }

        if (!isset($this->main["select"]) && isset($data["select"]) && !$is_single_service) {
            $key_name                                                                       = $this->main["def"]["key_primary"];
            $this->main["select"][$key_name]                                                = $key_name;
            $this->main["select_is_empty"]                                                  = true;
        }

        if (isset($this->main["select"]) && isset($this->main["select"]["*"])) {
            $this->main["select"] = $this->getAllFields($this->main["def"]["struct"], $this->main["def"]["indexes"]);
        }

        return (!isset($this->services_by_data["use_alias"]) && is_array($this->services_by_data["tables"]) && count($this->services_by_data["tables"]) === 1);
    }

    /**
     * @param array|null $fields
     * @param string $scope
     */
    private function resolveFields(array $fields = null, $scope = "fields") : void
    {
        if (is_array($fields) && count($fields)) {
            $mainService                                                                    = $this->getName();
            $mainTable                                                                      = $this->getMainTable();
            if ($scope == "select" || $scope == "where" || $scope == "sort") {
                $this->services_by_data["last"]                                             = $mainService;
                $this->services_by_data["last_table"]                                       = $mainTable;
            }
            $is_or                                                                          = false;
            if (isset($fields['$or'])) {
                $fields                                                                     = $fields['$or'];
                $is_or                                                                      = true;
            }

            foreach ($fields as $key => $alias) {
                $table                                                                      = null;
                $fIndex                                                                     = null;
                $service                                                                    = $mainService;
                if (is_numeric($key)) {
                    $key                                                                    = $alias;
                    if ($scope != "insert" && $scope != "set") {
                        $alias = true;
                    }
                } elseif (is_null($alias)) {
                    $alias                                                                  = null;
                }

                if ($scope == "select" && $alias && is_string($alias)) {
                    if (isset($this->services_by_data["use_alias"])) {
                        $this->services_by_data["use_alias"]++;
                    } else {
                        $this->services_by_data["use_alias"] = 1;
                    }
                }

                $parts                                                                      = explode(".", $key);
                switch (count($parts)) {
                    case "4":
                        if (Kernel::$Environment::DEBUG) {
                            Debug::dump("Wrong Format: " . $key);
                            exit;
                        }
                        break;
                    case "3":
                        $service                                                            = $parts[0];
                        $table                                                              = $parts[1];
                        $fIndex                                                             = (
                            $service == $mainService && $table == $mainTable
                                                                                                ? -2
                                                                                                : 2
                                                                                            );
                        break;
                    case "2":
                        $table                                                              = $parts[0];
                        $fIndex                                                             = (
                            $table == $mainTable
                                                                                                ? -1
                                                                                                : 1
                                                                                            );
                        break;
                    case "1":
                        $table                                                              = $mainTable;
                        $fIndex                                                             = null;
                        // no break
                    default:
                }

                $this->services_by_data["services"][$service]                               = true;
                $this->services_by_data["tables"][$service . "." . $table]                  = true;
                $this->services_by_data[$scope][$service]                                   = true;
                if ($scope == "select" || $scope == "where" || $scope == "sort") {
                    $this->services_by_data["last"]                                         = $service;
                    $this->services_by_data["last_table"]                                   = $table;
                }
                if ($fIndex === null || $fIndex < 0) {
                    if ($is_or) {
                        $this->main[$scope]['$or'][$parts[abs($fIndex)]]                    = (
                            $alias === true && $scope == "select"
                                                                                                ? $parts[abs($fIndex)]
                                                                                                : $alias
                                                                                            );
                    } else {
                        $this->main[$scope][$parts[abs($fIndex)]]                           = (
                            $alias === true && $scope == "select"
                                                                                                ? $parts[abs($fIndex)]
                                                                                                : $alias
                                                                                            );
                    }
                    continue;
                }

                if (!isset($this->subs) || !isset($this->subs[$service][$table]["def"])) {
                    $this->subs[$service][$table]["def"]                                    = $this->getModel($service)->getStruct($table);
                }

                if (!isset($this->subs[$service][$table]["def"]["struct"][$parts[$fIndex]])) {
                    if ($scope == "select" && $parts[$fIndex] == "*") {
                        if (is_array($this->subs[$service][$table]["def"]["struct"])) {
                            $this->subs[$service][$table][$scope]                           = $this->getAllFields($this->subs[$service][$table]["def"]["struct"], $this->subs[$service][$table]["def"]["indexes"]);
                        } else {
                            Error::register("Undefined Struct on Table: `" . $table . "` Model: `" . $service . "`", static::ERROR_BUCKET);
                        }
                    } else {
                        Error::register("missing field: `" . $parts[$fIndex] . "` on Table: `" . $table . "` Model: `" . $service . "`", static::ERROR_BUCKET);
                    }
                    continue;
                }

                if ($scope == "insert") {
                    $this->subs[$service][$table]["insert"][$parts[$fIndex]]                = $alias;
                } else {
                    if ($is_or) {
                        $this->subs[$service][$table][$scope]['$or'][$parts[$fIndex]]       = (
                            $alias === true && $scope == "select"
                            ? $parts[$fIndex]
                            : $alias
                        );
                    } else {
                        $this->subs[$service][$table][$scope][$parts[$fIndex]]              = (
                            $alias === true && $scope == "select"
                            ? $parts[$fIndex]
                            : $alias
                        );
                    }
                }
            }
        }
    }

    /**
     * @param string $model
     * @param string|null $mainTable
     * @return OrmModel
     */
    private function getModel(string $model = null, string $mainTable = null) : OrmModel
    {
        return ($model
            ? Orm::getInstance($model, $mainTable)
            : $this
        );
    }

    /**
     * @param array $ref
     * @param array $keys
     * @param string $field
     */
    private static function whereBuilder(array &$ref, array $keys, string $field) : void
    {
        if (!isset($ref["where"])) {
            $ref["where"]                                                                   = array();
        }
        if (!isset($ref["where"][$field])) {
            $ref["where"][$field]                                                           = (
                count($keys) == 1
                ? $keys[0]
                : $keys
            );
        } elseif (is_array($ref["where"][$field])) {
            $ref["where"][$field]                                                           = $ref["where"][$field] + $keys;
        } else {
            $ref["where"][$field]                                                           = array($ref["where"][$field]) + $keys;
        }
    }

    /**
     * @param array $source
     * @param array $exclude
     * @return array
     */
    private static function getAllFields(array $source, array $exclude = null) : array
    {
        $diff                                                                               = array_keys(array_diff_key($source, $exclude));
        return array_combine($diff, $diff);
    }

    /**
     * @param array $data
     * @param string $scope
     * @return array|null
     */
    private static function getCurrentScope(array $data, string $scope) : ?array
    {
        return (isset($data[$scope])
            ? $data[$scope]
            : null
        );
    }

    /**
     * @param int|null $count
     */
    private function setCount(int $count = null)
    {
        if ($count) {
            $this->count                                                    = $count;
        }
    }



























    private function clearResult() : void
    {
        $this->data                                                         = array();
        $this->exts                                                         = array();
        $this->main                                                         = array();
        $this->subs                                                         = array();
        $this->result                                                       = array();
        $this->count                                                        = null;
        $this->services_by_data                                             = array();

        Error::clear(static::ERROR_BUCKET);
    }
}
