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
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Log;
use phpformsframework\libs\Error;

/**
 * Class Orm
 * @package phpformsframework\libs\storage
 */
class Orm implements Dumpable
{
    const ERROR_BUCKET                                                                      = "orm";

    private const RESULT                                                                    = "result";
    private const INDEX                                                                     = "index";
    private const RAWDATA                                                                   = "rawdata";

    private static $singleton                                                               = array();
    private static $data                                                                    = array();
    private static $services_by_data                                                        = array();
    private static $result                                                                  = array();

    private static $service                                                                 = null;

    /**
     * @param string $ormModel
     * @param string $mainTable
     * @return OrmModel
     */
    public static function getInstance(string $ormModel, string $mainTable = null) : OrmModel
    {
        return self::setSingleton($ormModel, $mainTable);
    }

    /**
     * @return array
     */
    public static function dump() : array
    {
        return self::$singleton;
    }

    /**
     * @param string $ormModel
     * @param string|null $mainTable
     * @return OrmModel
     */
    private static function setSingleton(string $ormModel, string $mainTable = null) : OrmModel
    {
        if (!isset(self::$singleton[$ormModel])) {
            self::$singleton[$ormModel]                                        = new OrmModel($ormModel, $mainTable);
        }

        return self::$singleton[$ormModel];
    }

    /**
     * @param string $model
     * @return OrmModel
     */
    private static function getModel(string $model = null) : OrmModel
    {
        return ($model
            ? self::setSingleton($model)
            :  self::$service
        );
    }

    /**
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param int $limit
     * @param int|null $offset
     * @param OrmModel|null $ormModel
     * @return array|null
     */
    public static function readRawData(array $where = null, array $fields = null, array $sort = null, int $limit = null, int $offset = null, OrmModel $ormModel = null) : ?array
    {
        Debug::dumpCaller("Read RawData: " . print_r($fields, true) . " Where: " . print_r($where, true) . " Sort: " . print_r($sort, true) . " Limit: " . print_r($limit, true));

        $res                                                                                = self::get($where, $fields, $sort, $limit, $offset, $ormModel, true);

        if (Kernel::$Environment::DEBUG) {
            Log::debugging(array(
                "action"    => "readRawData"
                , "data"    => self::$data
                , "exTime"  => Debug::exTimeApp()
            ));
        }

        return $res;
    }

    /**
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|int $limit
     * @param int|null $offset
     * @param OrmModel|null $ormModel
     * @return array|null
     */
    public static function read(array $where = null, array $fields = null, array $sort = null, int $limit = null, int $offset = null, OrmModel $ormModel = null) : ?array
    {
        Debug::dumpCaller("Read RawData: " . print_r($fields, true) . " Where: " . print_r($where, true) . " Sort: " . print_r($sort, true) . " Limit: " . print_r($limit, true));
        Debug::stopWatch("orm/read");

        $res                                                                                = self::get($where, $fields, $sort, $limit, $offset, $ormModel, false);

        $exTime = Debug::stopWatch("orm/read");
        if (Kernel::$Environment::DEBUG) {
            Log::debugging(array(
                "action"    => "read"
                , "data"    => self::$data
                , "exTime"  => $exTime
            ));
        }
        return $res;
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
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param int $limit
     * @param int|null $offset
     * @param OrmModel|null $ormModel
     * @param bool $result_raw_data
     * @return array|null
     */
    private static function get(array $where = null, array $fields = null, array $sort = null, int $limit = null, int $offset = null, OrmModel $ormModel = null, bool $result_raw_data = false) : ?array
    {
        self::clearResult($ormModel);
        $counter                                                                            = null;
        $single_service                                                                     = self::resolveFieldsByScopes(array(
                                                                                                "select"    => $fields,
                                                                                                "where"     => $where,
                                                                                                "sort"      => $sort
                                                                                            ));
        if ($single_service) {
            self::getDataSingle(self::$services_by_data["last"], self::$services_by_data["last_table"], $limit, $offset);
        } else {
            if (isset(self::$data["sub"]) && is_array(self::$data["sub"]) && count(self::$data["sub"])) {
                foreach (self::$data["sub"] as $controller => $tables) {
                    foreach ($tables as $table => $params) {
                        /**
                         * Run KeyUnique in Sub query
                         */
                        $keys_unique                                                        = (
                            isset($params["def"]["indexes"]) && is_array($params["def"]["indexes"])
                                                                                                ? array_keys($params["def"]["indexes"], "unique")
                                                                                                : array()
                                                                                            );
                        if (count($keys_unique)) {
                            $where_unique                                                   = array_intersect($keys_unique, array_keys($params["where"]));
                            if ($where_unique == $keys_unique) {
                                foreach ($where_unique as $where_unique_index => $where_unique_key) {
                                    if (isset($params["where"][$where_unique_key]['$regex'])) {
                                        unset($where_unique[$where_unique_index]);
                                    }
                                }

                                if (count($where_unique)) {
                                    self::$data["sub"][$controller][$table]["runned"]       = true;
                                    if (self::getData($params, $controller, $table) === null && $params["where"]) {
                                        return self::getResult($result_raw_data);
                                    }

                                    //unset(self::$data["exts"]);
                                }
                            }
                        }
                    }
                }
            }
            $countRunner = 0;
            while (self::throwRunner($limit, $offset) > 0) {
                $countRunner++;
            };
        }

        return self::getResult($result_raw_data);
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return int
     */
    private static function throwRunner(int $limit = null, int $offset = null) : int
    {
        $counter = 0;

        /**
         * Run Main query if where isset
         */
        if (!isset(self::$data["main"]["runned"]) && isset(self::$data["main"]["where"]) && self::getData(self::$data["main"], self::$data["main"]["service"], self::$data["main"]["def"]["mainTable"], $limit, $offset)) {
            self::$data["main"]["runned"]                                               = true;
            $counter++;
        }

        /**
         * Run Sub query if where isset
         */
        if (isset(self::$data["sub"]) && is_array(self::$data["sub"]) && count(self::$data["sub"])) {
            foreach (self::$data["sub"] as $controller => $tables) {
                foreach ($tables as $table => $params) {
                    if (!isset($params["runned"]) && isset($params["where"]) && self::getData($params, $controller, $table)) {
                        self::$data["sub"][$controller][$table]["runned"] = true;
                        $counter++;
                    }
                }
            }
        }

        return $counter;
    }
    /**
     * @param string|null $controller
     * @param string|null $table
     * @return array|null
     */
    private static function getCurrentData(string $controller = null, string $table = null) : ?array
    {
        $data       = self::$data["main"];
        if ($controller || $table) {
            $data   = (self::$data["sub"][$controller][(
                $table
                ? $table
                : self::getModel($controller)->getMainTable()
            )]);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string $controller
     * @param string $table
     * @param int $limit
     * @param int|null $offset
     * @return bool
     */
    private static function getData(array $data, string $controller, string $table, int $limit = null, int $offset = null) : bool
    {
        if (isset($data["where"])) {
            $main_table                                                                     = $data["def"]["mainTable"];
            $ormModel                                                                       = self::getModel($controller);
            $regs                                                                           = $ormModel
                                                                                                ->setStorage($data["def"], false, false)
                                                                                                ->read(
                                                                                                    self::getCurrentScope($data, "where"),
                                                                                                    self::getCurrentScope($data, "select"),
                                                                                                    self::getCurrentScope($data, "sort"),
                                                                                                    $limit,
                                                                                                    $offset
                                                                                                );
            if (is_array($regs)) {
                if (isset($regs[self::RESULT])) {
                    $thisTable                                                              = $table;
                    $aliasTable                                                             = $data["def"]["table"]["alias"];
                    self::$result[$thisTable]                                               = $regs[self::RESULT];
                    /**
                        @todo da fare la gestione del count
                        if($thisTable == $main_table) {
                        print_r($regs["count"]);
                        }
                     */
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

                            if (isset(self::$data["exts"]["done"][$thisTable . "." . $thisKey][$relTable . "." . $relKey])) {
                                continue;
                            }

                            if ($main_table == $relTable) {
                                $whereRef =& self::$data["main"];
                            } elseif (isset(self::$data["sub"][$controller][$relTable])) {
                                $whereRef =& self::$data["sub"][$controller][$relTable];
                            } elseif (isset(self::$services_by_data["tables"][$controller . "." . $relTable])) {
                                Error::register("Relationship not found: " . $thisTable . "." . $thisKey . " => " . $relTable . "." . $relKey);
                            }

                            $keyValue = array_column($regs[self::INDEX], $thisKey);
                            if (isset($whereRef) && count($keyValue)) {
                                self::whereBuilder($whereRef, $keyValue, $relKey);
                            } elseif (isset(self::$services_by_data["tables"][$controller . "." . $relTable])) {
                                Error::register("Relationship found but missing keyValue in result: " . $thisTable . " => " . $thisKey . " (" . $relTable . "." . $relKey . ")");
                            }

                            //@todo modo A/B
                            self::$data["exts"]["rel"][$relTable][$thisTable] = $keyValue;
                            //self::$data["exts"]["rel"][$relTable][$thisTable] = array_flip($keyValue);
                            self::$data["exts"]["done"][$thisTable . "." . $thisKey][$relTable . "." . $relKey] = true;

                            if (isset(self::$result[$relTable])) {
                                foreach ($keyValue as $keyCounter => $keyID) {
                                    if (isset($regs[self::INDEX][$keyCounter][$thisKey]) && $regs[self::INDEX][$keyCounter][$thisKey] == $keyID) {
                                        if ($main_table == $thisTable) {
                                            //@todo modo A/B
                                            $keyParents = array_keys(self::$data["exts"]["rel"][$thisTable][$relTable], $keyID);
                                            //$keyParent = self::$data["exts"]["rel"][$thisTable][$relTable][$keyID];

                                            //@todo modo A/B
                                            foreach ($keyParents as $keyParent) {
                                                /**
                                                 * Remove if exist reference of Result in sub table for prevent circular references
                                                 */
                                                unset(self::$result[$relTable][$keyParent][$aliasTable]);

                                                /**
                                                 * Remove External Key in Result
                                                 */
                                                /*if ($oneToMany) {
                                                    unset(self::$result[$thisTable][$keyParent][$thisKey]);
                                                } else {
                                                    unset(self::$result[$relTable][$keyParent][$relKey]);
                                                }*/
                                                if (!$oneToMany && !$manyToMany) {
                                                    self::$result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)][]   =& self::$result[$relTable][$keyParent];
                                                } else {
                                                    self::$result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)]     =& self::$result[$relTable][$keyParent];
                                                }
                                                //@todo modo A/B
                                            }
                                        } elseif ($manyToMany) {
                                            //@todo modo A/B
                                            $keyParents = array_keys(self::$data["exts"]["rel"][$thisTable][$relTable], $keyID);
                                            //$keyParent = self::$data["exts"]["rel"][$thisTable][$relTable][$keyID];
                                            //@todo modo A/B
                                            foreach ($keyParents as $keyParent) {
                                                /**
                                                 * Remove if exist reference of Result in sub table for prevent circular references
                                                 */
                                                unset(self::$result[$relTable][$keyParent][$aliasTable]);

                                                /**
                                                 * Remove External Key in Result
                                                 */
                                                /*if ($oneToMany) {
                                                    unset(self::$result[$thisTable][$keyCounter][$thisKey]);
                                                }*/

                                                self::$result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)] =& self::$result[$relTable][$keyParent];
                                            } //@todo modo A/B
                                        } else {
                                            //@todo modo A/B
                                            $keyParents = array_keys(self::$data["exts"]["rel"][$thisTable][$relTable], $keyID);
                                            //$keyParent = self::$data["exts"]["rel"][$thisTable][$relTable][$keyID];

                                            /**
                                             * Remove if exist reference of Result in sub table for prevent circular references
                                             */
                                            unset(self::$result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)]);

                                            //@todo modo A/B
                                            foreach ($keyParents as $keyParent) {
                                                /**
                                                 * Remove External Key in Result
                                                 */
                                                /*if ($oneToMany) {
                                                    unset(self::$result[$thisTable][$keyCounter][$thisKey]);
                                                } else {
                                                    unset(self::$result[$relTable][$keyParent][$relKey]);
                                                }*/
                                                if ($oneToMany || $manyToMany) {
                                                    self::$result[$relTable][$keyParent][$aliasTable][] =& self::$result[$thisTable][$keyCounter];
                                                } else {
                                                    self::$result[$relTable][$keyParent][$aliasTable] =& self::$result[$thisTable][$keyCounter];
                                                }
                                                //@todo modo A/B
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $controller
     * @param string $table
     * @param int|null $limit
     * @param int|null $offset
     */
    private static function getDataSingle(string $controller, string $table, int $limit = null, int $offset = null) : void
    {
        $data                                                                               = (
            isset(self::$data["sub"][$controller]) && isset(self::$data["sub"][$controller][$table])
                                                                                                ? self::$data["sub"][$controller][$table]
                                                                                                : self::$data["main"]
                                                                                            );
        if ($data) {
            $regs                                                                           = self::getModel(
                isset($data["service"])
                                                                                                    ? $data["service"]
                                                                                                    : $controller
                                                                                                )
                                                                                                ->setStorage($data["def"], false, true)
                                                                                                ->read(
                                                                                                    self::getCurrentScope($data, "where"),
                                                                                                    self::getCurrentScope($data, "select"),
                                                                                                    self::getCurrentScope($data, "sort"),
                                                                                                    $limit,
                                                                                                    $offset

                                                                                                );
            if (is_array($regs) && $regs[self::RAWDATA]) {
                self::$result                                                               = $regs[self::RAWDATA];
            }
        } else {
            Error::register("normalize data is empty", static::ERROR_BUCKET);
        }
    }

    /**
     * @todo da tipizzare
     * @param array $insert
     * @param OrmModel|null $ormModel
     * @return array|bool|null
     */
    public static function insertUnique(array $insert, OrmModel $ormModel = null)
    {
        Debug::dumpCaller("Insert unique: " . print_r($insert, true));
        Debug::stopWatch("orm/unique");

        $res                                                                                = self::set($insert, null, $insert, $ormModel);

        $exTime = Debug::stopWatch("orm/unique");
        if (Kernel::$Environment::DEBUG) {
            Log::debugging(array(
                "action"    => "insertUnique",
                "data"      => self::$data,
                "exTime"    => $exTime
            ));
        }
        return $res;
    }

    /**
     * @todo da tipizzare
     * @param array $insert
     * @param OrmModel|null $ormModel
     * @return array|bool|null
     */
    public static function insert(array $insert, OrmModel $ormModel = null)
    {
        Debug::dumpCaller("Insert: " . print_r($insert, true));
        Debug::stopWatch("orm/insert");

        $res                                                                                = self::set(null, null, $insert, $ormModel);

        $exTime = Debug::stopWatch("orm/insert");
        if (Kernel::$Environment::DEBUG) {
            Log::debugging(array(
                "action"    => "insert"
                , "data"    => self::$data
                , "exTime"  => $exTime
            ));
        }
        return $res;
    }

    /**
     * @todo da tipizzare
     * @param array $set
     * @param array $where
     * @param OrmModel|null $ormModel
     * @return array|bool|null
     */
    public static function update(array $set, array $where, OrmModel $ormModel = null)
    {
        Debug::dumpCaller("Update: " . print_r($set, true) . " Where: " . print_r($where, true));
        Debug::stopWatch("orm/update");

        $res                                                                                = self::set($where, $set, null, $ormModel);

        $exTime = Debug::stopWatch("orm/update");
        if (Kernel::$Environment::DEBUG) {
            Log::debugging(array(
                "action"    => "insert"
                , "data"    => self::$data
                , "exTime"  => $exTime
            ));
        }
        return $res;
    }

    /**
     * @todo da tipizzare
     * @param array $where
     * @param null|array $set
     * @param null|array $insert
     * @param OrmModel|null $ormModel
     * @return array|bool|null
     */
    public static function write(array $where, array $set = null, array $insert = null, OrmModel $ormModel = null)
    {
        Debug::dumpCaller("Write: " . print_r($where, true) . " Set: " . print_r($set, true) . " Insert: " . print_r($insert, true));
        Debug::stopWatch("orm/write");

        $res                                                                                = self::set($where, $set, $insert, $ormModel);

        $exTime = Debug::stopWatch("orm/write");
        if (Kernel::$Environment::DEBUG) {
            Log::debugging(array(
                "action"    => "write"
                , "data"    => self::$data
                , "exTime"  => $exTime
            ));
        }
        return $res;
    }
    /**
     * @todo da tipizzare
     * @param $where
     * @param OrmModel $ormModel
     * @return array|bool|null
     * @todo: da fare
     */
    public static function delete(array $where, OrmModel $ormModel = null)
    {
        Debug::dumpCaller("Insert: " . print_r($where, true));
        Debug::stopWatch("orm/delete");

        $res = !(bool) $ormModel;

        $exTime = Debug::stopWatch("orm/delete");
        if (Kernel::$Environment::DEBUG) {
            Log::debugging(array(
                "action"    => "insert"
                , "data"    => self::$data
                , "exTime"  => $exTime
            ));
        }
        return $res;
    }


    /**
     * @todo da tipizzare
     * @param null|array $where
     * @param null|array $set
     * @param null|array $insert
     * @param OrmModel|null $ormModel
     * @return array|bool|null
     */
    private static function set(array $where = null, array $set = null, array $insert = null, OrmModel $ormModel = null)
    {
        self::clearResult($ormModel);

        self::resolveFieldsByScopes(array(
            "insert"                                                                        => $insert
            , "set"                                                                         => $set
            , "where"                                                                       => $where
        ));

        self::execSub();

        self::setData();

        if (isset(self::$data["rev"]) && is_array(self::$data["rev"]) && count(self::$data["rev"])) {
            foreach (self::$data["rev"] as $table => $controller) {
                self::setData($controller, $table);
            }
        }

        return self::getResult(true);
    }

    /**
     * @param string|null $controller
     * @param string|null $table
     */
    private static function setData(string $controller = null, string $table = null) : void
    {
        $key                                                                                = null;
        $data                                                                               = self::getCurrentData($controller, $table);
        $modelName                                                                          = (
            isset($data["service"])
                                                                                                ? $data["service"]
                                                                                                : $controller
                                                                                            );
        $ormModel                                                                           = self::getModel($modelName);
        $storage                                                                            = $ormModel->setStorage($data["def"]);
        $key_name                                                                           = $data["def"]["key_primary"];

        if (isset($data["insert"]) && !isset($data["set"]) && isset($data["where"])) {
            $data["insert"]                                                                 = self::getFields($data["insert"]);
            $regs                                                                           = $storage->read($data["insert"], array($key_name => true));

            if (is_array($regs)) {
                self::setKeyRelationship($key_name, $regs["keys"][0], $data, $controller);
            } else {
                $regs                                                                       = $storage->insert($data["insert"], $data["def"]["table"]["name"]);
                if (is_array($regs)) {
                    $key                                                                    = $regs["keys"][0];
                }
            }
        } elseif (isset($data["insert"]) && !isset($data["set"]) && !isset($data["where"])) {
            $data["insert"]                                                                 = self::getFields($data["insert"]);
            $regs                                                                           = $storage->insert($data["insert"], $data["def"]["table"]["name"]);
            if (is_array($regs)) {
                $key                                                                        = $regs["keys"][0];
            }
        } elseif (!isset($data["insert"]) && isset($data["set"]) && !isset($data["where"])) {
            if (isset($data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]) && isset(self::$data["main"]["where"])) {
                $key_main_primary                                                           = $data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]["primary"];
                if (!isset(self::$data["main"]["where"][$key_main_primary])) {
                    $regs                                                                   = self::getModel($modelName)
                                                                                                ->setStorage(self::$data["main"]["def"])
                                                                                                ->read(self::$data["main"]["where"], array($key_main_primary => true), null, null, null, self::$data["main"]["def"]["table"]["name"]);
                    if (is_array($regs)) {
                        self::$data["main"]["where"][$key_main_primary]                     = $regs["keys"][0];
                    }
                }
                $external_name                                                              = $data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]["external"];
                $primary_name                                                               = $data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]["primary"];
                if (!isset($data["def"]["struct"][$external_name])) {
                    if (!isset(self::$data["main"]["where"][$external_name])) {
                        self::setMainIndexes($ormModel);
                    }

                    $data["where"][$primary_name]                                           = self::$data["main"]["where"][$external_name];
                } elseif (isset(self::$data["main"]["where"][$primary_name])) {
                    $data["where"][$external_name]                                          = self::$data["main"]["where"][$primary_name];
                }
            }

            self::$result["update"][$data["def"]["table"]["alias"]]                         = isset($data["where"]) && $storage->update($data["set"], $data["where"], $data["def"]["table"]["name"]);
        } elseif (!isset($data["insert"]) && isset($data["set"]) && isset($data["where"])) {
            self::$result["update"][$data["def"]["table"]["alias"]]                         = $storage->update($data["set"], $data["where"], $data["def"]["table"]["name"]);
        } elseif (!isset($data["insert"]) && !isset($data["set"]) && isset($data["where"])) {
            $regs                                                                           = $storage->read($data["where"], array($key_name => true), null, null, null, $data["def"]["table"]["name"]);
            if (is_array($regs)) {
                $key                                                                        = $regs["keys"][0];
            }
        } elseif (isset($data["insert"]) && isset($data["set"]) && isset($data["where"])) {
            $regs                                                                           = $storage->write(
                $data["insert"],
                array(
                    "set"       => $data["set"],
                    "where"     => $data["where"]
                ),
                $data["def"]["table"]["name"]
            );
            if (is_array($regs)) {
                $key                                                                        = $regs["keys"][0];
            }
        }

        self::setKeyRelationship($key_name, $key, $data, $controller);

        if ($key !== null) {
            self::$result["keys"][$data["def"]["table"]["alias"]] = $key;
        }
    }

    /**
     * @param string $key_name
     * @param string|null $key
     * @param array|null $data
     * @param string|null $controller
     */
    private static function setKeyRelationship(string $key_name, string $key = null, array $data = null, string $controller = null) : void
    {
        if ($key && is_array($data["def"]["relationship"]) && count($data["def"]["relationship"])) {
            if (!$controller) {
                $controller = self::$data["main"]["service"];
            }
            foreach ($data["def"]["relationship"] as $tbl => $rel) {
                if (isset($rel["external"]) && isset(self::$data["sub"][$controller][$tbl])) {
                    $field_ext                                                              = $rel["external"];
                    if (isset($data["def"]["struct"][$field_ext])) {
                        $field_ext                                                          = $rel["primary"];
                    }
                    if ($key && $field_ext && $field_ext != $key_name) {
                        if ($tbl != self::$data["main"]["def"]["mainTable"]) {
                            $rev_controller                                                 = self::$data["rev"][$tbl];

                            if (isset(self::$data["sub"][$rev_controller][$tbl]["insert"])) {
                                self::$data["sub"][$rev_controller][$tbl]["insert"][$field_ext]   = $key;
                            }
                            if (isset(self::$data["sub"][$rev_controller][$tbl]["set"])) {
                                self::$data["sub"][$rev_controller][$tbl]["where"][$field_ext]    = $key;
                            }
                        } else {
                            if (isset(self::$data["main"]["insert"])) {
                                self::$data["main"]["insert"][$field_ext]                   = $key;
                            }
                            if (isset(self::$data["main"]["set"])) {
                                self::$data["main"]["where"][$field_ext]                    = $key;
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
    private static function execSub(string $cmd = null) : void
    {
        if (isset(self::$data["sub"]) && is_array(self::$data["sub"]) && count(self::$data["sub"])) {
            foreach (self::$data["sub"] as $controller => $tables) {
                foreach ($tables as $table => $params) {
                    $field_ext                                                              = (
                        isset($params["def"]["relationship"][$params["def"]["mainTable"]]["external"])
                                                                                                ? $params["def"]["relationship"][$params["def"]["mainTable"]]["external"]
                                                                                                : null
                                                                                            );
                    $field_main_ext                                                         = (
                        isset($params["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]["external"])
                                                                                                ? $params["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]["external"]
                                                                                                : null
                                                                                            );

                    if (isset($params["def"]["struct"][$field_ext]) || isset($params["def"]["struct"][$field_main_ext])) {
                        self::$data["rev"][$table]                                          = $controller;
                    } else {
                        if ($cmd) {
                            self::cmdData($cmd, $controller, $table);
                        } else {
                            self::setData($controller, $table);
                        }
                    }
                }
            }
        }
    }

    /**
     * @todo da tipizzare
     * @param string $action
     * @param null|array $where
     * @param null|array $fields
     * @param null|OrmModel $ormModel
     * @return array|mixed|null
     */
    public static function cmd(string $action, array $where = null, array $fields = null, OrmModel $ormModel = null)
    {
        self::clearResult($ormModel);

        self::resolveFieldsByScopes(array(
            "select"                                                                        => $fields
            , "where"                                                                       => $where
        ));

        self::execSub($action);

        self::cmdData($action);

        return self::getResult(true);
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
     * @param string $command
     * @param string|null $controller
     * @param string|null $table
     */
    private static function cmdData(string $command, string $controller = null, string $table = null) : void
    {
        $data                                                                               = self::getCurrentData($controller, $table);
        if (isset($data["where"])) {
            $where                                                                          = self::getCurrentScope($data, "where");
            $ormModel                                                                       = self::getModel(
                $data["service"]
                                                                                                ? $data["service"]
                                                                                                : $controller
                                                                                            );
            $storage                                                                        = $ormModel->setStorage($data["def"]);
            $regs                                                                           = $storage->cmd(
                $command,
                $where
                                                                                            );
            self::$result["cmd"][$data["def"]["table"]["alias"]]                            = $regs;
        }
    }


    /**
     * @param OrmModel $ormModel
     */
    private static function setMainIndexes(OrmModel $ormModel) : void
    {
        $res                                                                                = $ormModel
                                                                                                ->getMainModel()
                                                                                                ->setStorage(self::$data["main"]["def"])
                                                                                                ->read(
                                                                                                    self::$data["main"]["where"],
                                                                                                    array_keys(self::$data["main"]["def"]["indexes"])
                                                                                                );
        if (is_array($res)) {
            self::$data["main"]["where"] = array_replace(self::$data["main"]["where"], $res);
        }
    }

    /**
     * @param array $data
     * @return bool
     */
    private static function resolveFieldsByScopes(array $data) : bool
    {
        foreach ($data as $scope => $fields) {
            self::resolveFields($fields, $scope);
        }

        $is_single_service                                                                  = (count(self::$services_by_data["services"]) == 1);

        if (isset(self::$services_by_data["last"]) && $is_single_service) {
            self::$service                                                                  = self::setSingleton(self::$services_by_data["last"]);
        }

        if ((!isset(self::$data["main"]) || !(isset(self::$data["main"]["where"]) || isset(self::$data["main"]["select"]) || isset(self::$data["main"]["insert"]))) && $is_single_service) {
            $subService                                                                     = key(self::$services_by_data["services"]);
            $ormModel                                                                       = self::getModel($subService);
            $subTable                                                                       = $ormModel->getMainTable();

            if (isset(self::$data["sub"][$subService]) && isset(self::$data["sub"][$subService][$subTable])) {
                self::$data["main"]                                                         = self::$data["sub"][$subService][$subTable];
            } else {
                self::$data["main"]["def"]                                                  = $ormModel->getStruct($subTable);
            }
            self::$data["main"]["service"]                                                  = $subService;

            unset(self::$data["sub"][$subService][$subTable]);
            if (!count(self::$data["sub"][$subService])) {
                unset(self::$data["sub"][$subService]);
            }
            if (!count(self::$data["sub"])) {
                unset(self::$data["sub"]);
            }

            if ($data["where"] === true) {
                self::$data["sub"][$subService]["state"]["where"] = true;
            }
        } else {
            $ormModel                                                                       = self::getModel();
            $mainTable                                                                      = $ormModel->getMainTable();

            self::$data["main"]["def"]                                                      = $ormModel->getStruct($mainTable);
            self::$data["main"]["service"]                                                  = $ormModel->getName();

            if ($data["where"] === true) {
                self::$data["main"]["where"] = true;
            }
        }

        if (!isset(self::$data["main"]["select"]) && isset($data["select"]) && !$is_single_service) {
            $key_name                                                                       = self::$data["main"]["def"]["key_primary"];
            self::$data["main"]["select"][$key_name]                                        = $key_name;
            self::$data["main"]["select_is_empty"]                                          = true;
        }

        if (isset(self::$data["main"]["select"]) && isset(self::$data["main"]["select"]["*"])) {
            self::$data["main"]["select"] = self::getAllFields(self::$data["main"]["def"]["struct"], self::$data["main"]["def"]["indexes"]); // array_combine(array_keys(self::$data["main"]["def"]["struct"]), array_keys(self::$data["main"]["def"]["struct"]));
        }

        return (!isset(self::$services_by_data["use_alias"]) && is_array(self::$services_by_data["tables"]) && count(self::$services_by_data["tables"]) === 1);
    }

    /**
     * @param array $source
     * @param array $exclude
     * @return array
     */
    private static function getAllFields(array $source, array $exclude = null) : array
    {
        $diff = array_keys(array_diff_key($source, (array) $exclude));
        return array_combine($diff, $diff);
    }

    /**
     * @param array|null $fields
     * @param string $scope
     */
    private static function resolveFields(array $fields = null, $scope = "fields") : void
    {
        if (is_array($fields) && count($fields)) {
            $ormModel                                                                       = self::getModel();
            $mainService                                                                    = $ormModel->getName();
            $mainTable                                                                      = $ormModel->getMainTable();
            if ($scope == "select" || $scope == "where" || $scope == "sort") {
                self::$services_by_data["last"]                                             = $mainService;
                self::$services_by_data["last_table"]                                       = $mainTable;
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
                    if (isset(self::$services_by_data["use_alias"])) {
                        self::$services_by_data["use_alias"]++;
                    } else {
                        self::$services_by_data["use_alias"] = 1;
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

                self::$services_by_data["services"][$service]                               = true;
                self::$services_by_data["tables"][$service . "." . $table]                  = true;
                self::$services_by_data[$scope][$service]                                   = true;
                if ($scope == "select" || $scope == "where" || $scope == "sort") {
                    self::$services_by_data["last"]                                         = $service;
                    self::$services_by_data["last_table"]                                   = $table;
                }
                if ($fIndex === null || $fIndex < 0) {
                    if ($is_or) {
                        self::$data["main"][$scope]['$or'][$parts[abs($fIndex)]]            = (
                            $alias === true && $scope == "select"
                                                                                                ? $parts[abs($fIndex)]
                                                                                                : $alias
                                                                                            );
                    } else {
                        self::$data["main"][$scope][$parts[abs($fIndex)]]                   = (
                            $alias === true && $scope == "select"
                                                                                                ? $parts[abs($fIndex)]
                                                                                                : $alias
                                                                                            );
                    }
                    continue;
                }

                if (!isset(self::$data["sub"]) || !isset(self::$data["sub"][$service][$table]["def"])) {
                    self::$data["sub"][$service][$table]["def"]                             = self::getModel($service)->getStruct($table);
                }

                if (!isset(self::$data["sub"][$service][$table]["def"]["struct"][$parts[$fIndex]])) {
                    if ($scope == "select" && $parts[$fIndex] == "*") {
                        if (is_array(self::$data["sub"][$service][$table]["def"]["struct"])) {
                            self::$data["sub"][$service][$table][$scope]                    = self::getAllFields(self::$data["sub"][$service][$table]["def"]["struct"], self::$data["sub"][$service][$table]["def"]["indexes"]); // array_combine(array_keys(self::$data["sub"][$service][$table]["def"]["struct"]), array_keys(self::$data["sub"][$service][$table]["def"]["struct"]));
                        } else {
                            Error::register("Undefined Struct on Table: `" . $table . "` Model: `" . $service . "`", static::ERROR_BUCKET);
                        }
                    } else {
                        Error::register("missing field: `" . $parts[$fIndex] . "` on Table: `" . $table . "` Model: `" . $service . "`", static::ERROR_BUCKET);
                    }
                    continue;
                }

                if ($scope == "insert") {
                    self::$data["sub"][$service][$table]["insert"][$parts[$fIndex]]         = $alias;
                } else {
                    if ($is_or) {
                        self::$data["sub"][$service][$table][$scope]['$or'][$parts[$fIndex]]= (
                            $alias === true && $scope == "select"
                            ? $parts[$fIndex]
                            : $alias
                        );
                    } else {
                        self::$data["sub"][$service][$table][$scope][$parts[$fIndex]]       = (
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
     * @param array|null $fields
     * @param array|null $indexes
     * @param string|null $primary_key
     * @return array
     */
    private static function getFields(array $fields = null, array &$indexes = null, string $primary_key = null) : array
    {
        $res                                                                                = null;
        if (is_array($fields) && count($fields)) {
            $res                                                                            = $fields;
            if (!isset($res["*"])) {
                if (is_array($indexes) && count($indexes)) {
                    $res                                                                    = $res + array_fill_keys(array_keys($indexes), true);

                    foreach ($fields as $field_key => $field_ext) {
                        if (isset($indexes[$field_key])) {
                            unset($indexes[$field_key]);
                        }
                        if (isset($indexes[$field_ext])) {
                            unset($indexes[$field_ext]);
                        }
                    }
                }
            }
        }

        if (!$res) {
            if (is_array($indexes) && count($indexes)) {
                $res = array_fill_keys(array_keys($indexes), true);
            }
        }

        if ($primary_key && !isset($res[$primary_key])) {
            $res[$primary_key] = true;
        }
        return $res;
    }

    /**
     * @param OrmModel $ormModel
     */
    private static function clearResult(OrmModel $ormModel) : void
    {
        self::$data                                                         = array();
        self::$result                                                       = array();
        self::$services_by_data                                             = array();
        self::$service                                                      = $ormModel;

        Error::clear(static::ERROR_BUCKET);
    }

    /**
     * @param bool $rawdata
     * @return array|null
     */
    private static function getResult(bool $rawdata = false) : ?array
    {
        if ($rawdata || count(self::$result) > 1) {
            if (isset(self::$result["keys"])) {
                $res                                                    = self::$result["keys"];
            } elseif (isset(self::$result["update"])) {
                $res                                                    = self::$result["update"];
            } elseif (isset(self::$result["cmd"])) {
                $res                                                    = self::$result["cmd"];
            } else {
                $res                                                    = ( //@todo da togliere gestendo la relazione esternamente al result
                    count(self::$services_by_data["tables"]) > 1
                                                                            ? self::$result[self::$data["main"]["def"]["mainTable"]]
                                                                            : self::$result
                                                                        );
                if (isset($res[0]) && count($res) == 1) {
                    $res                                                = $res[0];
                }
            }
        } elseif (isset(self::$result[self::$data["main"]["def"]["mainTable"]])) {
            $res                                                        = current(self::$result);

            if (isset(self::$data["main"]["service"]) && isset(self::$data["sub"]) && isset(self::$data["sub"][self::$data["main"]["service"]]) && is_array(self::$data["sub"][self::$data["main"]["service"]]) && count(self::$data["sub"][self::$data["main"]["service"]]) == 1 && is_array($res) && count($res) == 1) {
                $res                                                    = current($res);
            }
            //todo: da veridicare prima era $this->service
            $service_name                                               = self::getModel()->getName();
            if (is_array($res) && count($res) == 1 && isset($res[$service_name])) {
                $res                                                    = $res[$service_name];
            }
        } elseif (isset(self::$result[0])) {
            $res = self::$result[0];
        } else {
            $res = null;
        }

        return $res;
    }
}
