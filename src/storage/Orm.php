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

class Orm implements Dumpable
{
    const ERROR_BUCKET                                                                      = "orm";

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
    public static function getInstance($ormModel, $mainTable = null)
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

    private static function setSingleton($ormModel, $mainTable = null)
    {
        if (!isset(self::$singleton[$ormModel . $mainTable])) {
            self::$singleton[$ormModel . $mainTable]                                        = new OrmModel($ormModel, $mainTable);
        }

        return self::$singleton[$ormModel . $mainTable];
    }

    /**
     * @param string $model
     * @return OrmModel
     */
    private static function getModel($model = null)
    {
        return ($model
            ? self::setSingleton($model)
            :  self::$service
        );
    }

    /**
     * @param OrmModel $ormModel
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|array $limit
     * @return array|bool|null
     */
    public static function readRawData($where = null, $fields = null, $sort = null, $limit = null, $ormModel = null)
    {
        Debug::dumpCaller("Read RawData: " . print_r($fields, true) . " Where: " . print_r($where, true) . " Sort: " . print_r($sort, true) . " Limit: " . print_r($limit, true));

        $res                                                                                = self::get($where, $fields, $sort, $limit, $ormModel, true);

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
     * @param OrmModel $ormModel
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|array $limit
     * @return array|bool|null
     */
    public static function read($where = null, $fields = null, $sort = null, $limit = null, $ormModel = null)
    {
        Debug::dumpCaller("Read RawData: " . print_r($fields, true) . " Where: " . print_r($where, true) . " Sort: " . print_r($sort, true) . " Limit: " . print_r($limit, true));
        Debug::stopWatch("orm/read");

        $res                                                                                = self::get($where, $fields, $sort, $limit, $ormModel, false);

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

    private static function checkmiowhere($keys, $type, $service, $table, $field)
    {
        if (!isset(self::$data[$type][$service][$table]["where"])) {
            self::$data[$type][$service][$table]["where"] = array();
        }
        if (!isset(self::$data[$type][$service][$table]["where"][$field])) {
            self::$data[$type][$service][$table]["where"][$field]   = (
                count($keys) == 1
                ? $keys[0]
                : $keys
            );
        } elseif (is_array(self::$data[$type][$service][$table]["where"][$field])) {
            self::$data[$type][$service][$table]["where"][$field]    = self::$data[$type][$service][$table]["where"][$field] + $keys;
        } else {
            self::$data[$type][$service][$table]["where"][$field]    = array(self::$data[$type][$service][$table]["where"][$field]) + $keys;
        }
    }


    /**
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|array $limit
     * @param OrmModel $ormModel
     * @param bool $result_raw_data
     * @return array|bool|null
     */
    private static function get($where = null, $fields = null, $sort = null, $limit = null, $ormModel = null, $result_raw_data = false)
    {
        self::clearResult($ormModel);
        $counter                                                                            = null;
        $single_service                                                                     = self::resolveFieldsByScopes(array(
                                                                                                "select"    => $fields
                                                                                                , "where"   => $where
                                                                                                , "sort"    => $sort
                                                                                            ));

        if ($single_service) {
            self::getDataSingle(self::$services_by_data["last"], self::$services_by_data["last_table"], $limit);
        } else {
            if (isset(self::$data["sub"]) && is_array(self::$data["sub"]) && count(self::$data["sub"])) {
                foreach (self::$data["sub"] as $controller => $tables) {
                    foreach ($tables as $table => $params) {
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
                                    if (self::getData($controller, $table) === null && $params["where"]) {
                                        return self::getResult($result_raw_data);
                                    }

                                    unset(self::$data["exts"]);
                                }
                            }
                        }
                    }
                }
            }
            if (isset(self::$data["main"]["where"])) {
                self::$data["main"]["runned"]                                               = true;
                if (self::getData(null, null, $limit) === null) {
                    return false;
                }
            }

            if (isset(self::$data["sub"]) && is_array(self::$data["sub"]) && count(self::$data["sub"])) {
                foreach (self::$data["sub"] as $controller => $tables) {
                    foreach ($tables as $table => $params) {
                        if (!isset($params["runned"]) && self::getData($controller, $table, (isset($params["select"]) ? $limit : null)) === null && isset($params["where"])) {
                            return self::getResult($result_raw_data);
                        }
                    }
                }
            }

            if (!isset(self::$data["main"]["runned"]) && isset(self::$data["main"]["where"])) {
                self::getData(null, null, $limit);       //try main table
            }
        }

        return self::getResult($result_raw_data);
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
     * @param null|string $controller
     * @param null|string $table
     * @param null|array $limit
     * @return int|null
     */
    private static function getData(string $controller = null, string $table = null, array $limit = null) : ?int
    {
        $counter                                                                            = false;
        $table_rel                                                                          = false;
        $where                                                                              = null;
        $sort                                                                               = null;

        $data                                                                               = self::getCurrentData($controller, $table);
        $where                                                                              = self::getCurrentWhere($data);

        if (isset($data["sort"])) {
            $sort                                                                           = self::getFields($data["sort"], $data["def"]["alias"]);
        }
        $table_main                                                                         = (
            isset($data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]])
                                                                                                ? self::$data["main"]["def"]["mainTable"]
                                                                                                : $data["def"]["mainTable"]
                                                                                            );
        if (isset($data["def"]["relationship"][$table_main]) && isset(self::$data["exts"])) {
            $field_ext                                                                      = $data["def"]["relationship"][$table_main]["external"];
            $field_key                                                                      = $data["def"]["relationship"][$table_main]["primary"];

            if (isset($data["def"]["struct"][$field_ext])) {
                $field_ext                                                                  = $data["def"]["relationship"][$table_main]["primary"];
                $field_key                                                                  = $data["def"]["relationship"][$table_main]["external"];

                $table_rel                                                                  = (
                    isset(self::$data["exts"][$table]) && isset(self::$data["exts"][$table][$field_ext])
                                                                                                ? $table
                                                                                                : $table_main
                                                                                            );
            }

            $ids                                                                            = (
                isset(self::$data["exts"][$table_main][$field_ext]) && is_array(self::$data["exts"][$table_main][$field_ext])
                                                                                                ? array_keys(self::$data["exts"][$table_main][$field_ext])
                                                                                                : null
                                                                                            );
            if ($ids) {
                $where[self::getFieldAlias($field_key, $data["def"]["alias"])]             = (
                    count($ids) == 1
                                                                                                ? $ids[0]
                                                                                                : $ids
                                                                                            );

                self::$data["sub"][$controller][$table]["where"]                            = $where;
            }
        }

        if ($where) {
            $sub_ids                                                                        = null;
            $indexes                                                                        = $data["def"]["indexes"];
            $select                                                                         = self::getFields(
                (
                    isset($data["select"])
                                                                                                    ? $data["select"]
                                                                                                    : null
                                                                                                ),
                $data["def"]["alias"],
                $indexes,
                array_search(DatabaseAdapter::FTYPE_PRIMARY, $data["def"]["struct"])
                                                                                            );
            $ormModel                                                                       = self::getModel(
                isset($data["service"])
                                                                                                ? $data["service"]
                                                                                                : $controller
                                                                                            );
            $regs                                                                           = $ormModel
                                                                                                ->setStorage($data["def"], true, false)
                                                                                                ->read(
                                                                                                    (
                                                                                                        $where === true
                                                                                                    ? null
                                                                                                    : $where
                                                                                                ),
                                                                                                    $select,
                                                                                                    $sort,
                                                                                                    $limit
                                                                                            );

            if (is_array($regs)) {
                $field_key                                                                  = null;
                if (isset($regs["rawdata"])) {
                    self::$result                                                           = $regs["rawdata"];
                    $regs["keys"]                                                           = array_keys($regs["rawdata"]);
                }

                if (isset($regs["exts"]) && is_array($regs["exts"])) {
                    if (isset(self::$data["exts"]) && isset(self::$data["exts"][$data["def"]["mainTable"]])) {
                        self::$data["exts"][$data["def"]["mainTable"]]                          = self::$data["exts"][$data["def"]["mainTable"]] + $regs["exts"];
                    } else {
                        self::$data["exts"][$data["def"]["mainTable"]]                          = $regs["exts"];
                    }

                    if (isset(self::$data["main"]["select"]) && isset($data["def"]["relationship"][$table_main]) /*&& !self::$data["main"]["where"]*/) {
                        $field_ext                                                          = $data["def"]["relationship"][$table_main]["external"];
                        $field_key                                                          = $data["def"]["relationship"][$table_main]["primary"];

                        if ($field_key) {
                            $ids                                                            = (
                                isset($regs["exts"][$field_ext])
                                                                                                ? array_keys($regs["exts"][$field_ext])
                                                                                                : null
                                                                                            );
                            if ($ids) {
                                self::$data["main"]["where"][$field_key]                    = (
                                    count($ids) == 1
                                                                                                ? $ids[0]
                                                                                                : $ids
                                                                                            );

                                if (!isset(self::$data["main"]["runned"]) && self::$result) { //fix per il permalink. viene inserito il dato in tutti i nodi duplicand i valori
                                    $sub_ids                                                = $ids;
                                }
                            } elseif ($regs === false) {
                                self::$data["main"]["where"][$field_key]                    = "0";
                            }
                        }
                    }
                }


                if (isset($regs["keys"]) && is_array($regs["keys"]) && count($regs["keys"])) {
                    $field_ext                                                              = null;
                    $counter                                                                = count($regs["keys"]);
                    $table_name                                                             = $data["def"]["table"]["alias"];

                    if (!$table_rel && isset($data["def"]["relationship"][$table_main])) {         //se è una maintable ma non anagraph reimposta l'external base es: doctors -> anagraph -> external
                        $field_ext                                                          = $data["def"]["relationship"][$table_main]["external"];
                    }

                    foreach ($regs["keys"] as $i => $id) {
                        $result                                                             = null;
                        $keys                                                               = null;
                        if (isset($data["select"]["*"])) {
                            $result                                                         = (
                                !$controller && !$table
                                                                                                ? $regs["result"][$i][$table_name]
                                                                                                : $regs["result"][$i]
                                                                                            );
                        } elseif (isset($data["select_is_empty"])) {
                            $result                                                         = array();
                        } else {
                            $result                                                         = (
                                $indexes
                                                                                                ? array_intersect_key($regs["result"][$i], array_flip($data["select"]))
                                                                                                : $regs["result"][$i]
                                                                                            );
                        }

                        if ($result) {
                            //triggera quando avviene la seguente casistica: anagraph --> anagraph_person dove anagraph_person.ID_anagraph = anagraph.ID
                            if ($table_main && isset($data["def"]["relationship"][$table_main]) && $data["def"]["relationship"][$table_main]["external"]) {
                                $field_ext                                                  = $data["def"]["relationship"][$table_main]["external"];

                                if (isset($regs["exts"][$field_ext]) && isset($regs["exts"][$field_ext][$regs["result"][$i][$field_ext]])) {
                                    $keys                                                   = array($regs["result"][$i][$field_ext]);
                                    $table_rel                                              = null;
                                }
                            }

                            if (!$keys && $field_ext) {
                                $keys                                                       = (
                                    $table_rel
                                                                                                ? array_keys(self::$data["exts"][$table_rel][$field_ext])
                                                                                                : self::$data["exts"][$table_main][$field_ext][$id]
                                                                                            );
                            }

                            $ids                                                            = (
                                $sub_ids
                                                                                                ? $sub_ids
                                                                                                : $keys
                                                                                            );

                            if (is_array($ids) && count($ids)) {
                                foreach ($ids as $id_primary) {
                                    $id_primary                                             = self::idsTraversing($id_primary, $id);

                                    if ($table_rel) { //discende fino ad anagraph per fondere i risultati annidati esempio anagraph -> users -> tokens
                                        $root_ids = self::$data["exts"][$ormModel->getMainModel()->getMainTable()][$field_key][$id_primary];
                                        if (is_array($root_ids) && count($root_ids) == 1) {
                                            $id_primary                                     = $root_ids[0];
                                        }
                                    }
                                    self::setResult(self::$result[$id_primary][$table_name], $result, (!$controller && !$table /* is main */));
                                }
                            } elseif (isset($data["select"])) {
                                self::setResult(self::$result[$id], $result, (!$controller && !$table /* is main */));
                            }
                        }
                    }
                }
            }
        } else {
            $counter = 0;
        }

        return $counter;
    }

    /**
     * @param null|string $controller
     * @param null|string $table
     * @param null|array $limit
     * @return int|null
     */
    private static function getDataNew(string $controller = null, string $table = null, array $limit = null) : ?int
    {
        $counter                                                                            = false;
        $data                                                                               = self::getCurrentData($controller, $table);
        $where                                                                              = self::getCurrentWhere($data);

        $sort = (
            isset($data["sort"])
            ? self::getFields($data["sort"], $data["def"]["alias"])
            : null
        );

        if ($where) {
            $indexes                                                                        = $data["def"]["indexes"];
            $key_name = (string) array_search(DatabaseAdapter::FTYPE_PRIMARY, $data["def"]["struct"]);
            $service = (
                isset($data["service"])
                ? $data["service"]
                : $controller
            );
            $select                                                                         = self::getFields(
                (
                    isset($data["select"])
                    ? $data["select"]
                    : null
                ),
                $data["def"]["alias"],
                $indexes,
                $key_name
            );

            $ormModel                                                                       = self::getModel($service);
            $regs                                                                           = $ormModel
                                                                                                ->setStorage($data["def"], false, false)
                                                                                                ->read(
                                                                                                    (
                                                                                                        $where === true
                                                                                                        ? null
                                                                                                        : $where
                                                                                                    ),
                                                                                                    $select,
                                                                                                    $sort,
                                                                                                    $limit
                                                                                                );
            $dataType = "result";
            if (is_array($regs)) {
                //print_r(json_encode($regs));
                $field_key                                                                  = null;
                if (isset($regs[$dataType])) {
                    $thisTable  = $data["def"]["table"]["name"];

                    self::$result[$thisTable] = array_combine(array_column($regs[$dataType], $key_name), $regs[$dataType]);

                    //self::$result[$thisTable] = $regs[$dataType];
                    if (is_array($data["def"]["relationship"]) && count($data["def"]["relationship"])) {
                        foreach ($data["def"]["relationship"] as $ref => $relation) {
                            $thisKey    = null;

                            $relType    = null;
                            $relTable   = null;
                            $relField   = null;
                            if (isset($data["def"]["struct"][$ref])) {
                                $thisKey    = $ref;

                                $relTable   = $relation["tbl"];
                                $relKey     = $relation["key"];

                                if ($data["def"]["mainTable"] == $relTable) {
                                    $relType = "main";
                                } elseif (isset(self::$data["sub"][$service][$relTable])) {
                                    $relType = "sub";
                                }
                            } else {
                                $thisKey    = $relation["primary"];

                                $relTable   = $ref;
                                $relKey     = $relation["external"];

                                if ($data["def"]["mainTable"] == $relTable) {
                                    $relType = "main";
                                } elseif (isset(self::$data["sub"][$service][$relTable])) {
                                    $relType = "sub";
                                }
                            }

                            if (!$relType && isset(self::$services_by_data["tables"][$service . "." . $relTable])) {
                                Error::register("Relationship not found: " . $thisTable . "." . $thisKey . " => " . $relTable . "." . $relKey);
                            }

                            $keyValue = array_unique(array_column($regs[$dataType], $thisKey));
                            if (count($keyValue)) {
                                self::checkmiowhere($keyValue, $relType, $service, $relTable, $relKey);
                            } else {
                                Error::register("Relationship found but missing keyValue in result: " . $thisTable . " => " . $thisKey);
                            }


                            echo $thisTable . "." . $thisKey . " => " . $relTable . "." . $relKey;
                            print_r($keyValue);

                            if (isset(self::$result[$relTable])) {
                                //foreach ($keyValue as $keyCounter => $keyID) {
                                foreach (array_combine(array_keys($keyValue), $keyValue) as $keyCounter => $keyID) {
                                    if (isset($regs[$dataType][$keyCounter][$thisKey]) && $regs[$dataType][$keyCounter][$thisKey] == $keyID) {
                                        self::$result[$relTable][$keyCounter][$thisTable][] =& self::$result[$thisTable][$keyCounter];
                                    }
                                }
                            }
                        }
                    }




                    $counter                                                                = $regs["count"];
                }
            }
        } else {
            $counter = 0;
        }

        return $counter;
    }


    private static function getDataSingle($controller, $table, $limit = null)
    {
        $data                                                                               = (
            isset(self::$data["sub"][$controller]) && isset(self::$data["sub"][$controller][$table])
                                                                                                ? self::$data["sub"][$controller][$table]
                                                                                                : self::$data["main"]
                                                                                            );
        if ($data) {
            if ($limit && !is_array($limit)) {
                $limit                                                                      = array(
                                                                                                "skip"  => 0,
                                                                                                "limit" => $limit
                                                                                            );
            }

            $regs                                                                           = self::getModel(
                isset($data["service"])
                                                                                                    ? $data["service"]
                                                                                                    : $controller
                                                                                                )
                                                                                                ->setStorage($data["def"], false, true)
                                                                                                ->read(
                                                                                                    (
                                                                                                        !isset($data["where"]) || $data["where"] === true
                                                                                                        ? null
                                                                                                        : self::getFields($data["where"], $data["def"]["alias"])
                                                                                                    ),
                                                                                                    (
                                                                                                        isset($data["select"])
                                                                                                        ? self::getFields($data["select"], $data["def"]["alias"])
                                                                                                        : null
                                                                                                    ),
                                                                                                    (
                                                                                                        isset($data["sort"])
                                                                                                        ? self::getFields($data["sort"], $data["def"]["alias"])
                                                                                                        : null
                                                                                                    ),
                                                                                                    $limit
                                                                                                );
            if (is_array($regs) && $regs["rawdata"]) {
                self::$result                                                               = $regs["rawdata"];
            }
        } else {
            Error::register("normalize data is empty", static::ERROR_BUCKET);
        }
    }

    private static function idsTraversing($id_primary, $id)
    {
        if (isset(self::$data["traversing"][$id_primary])) {
            $res                                                                            = self::$data["traversing"][$id_primary];
        } elseif (!isset(self::$data["traversing"][$id])) {
            self::$data["traversing"][$id]                                                  = $id_primary;
            $res = self::$data["traversing"][$id];
        } else {
            $res                                                                            = $id_primary;
        }

        return $res;
    }

    /**
     * @param array $insert
     * @param null|OrmModel $ormModel
     * @return array|bool|null
     */
    public static function insertUnique($insert, $ormModel = null)
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
     * @param array $insert
     * @param null|OrmModel $ormModel
     * @return array|bool|null
     */
    public static function insert($insert, $ormModel = null)
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
     * @param array $set
     * @param array $where
     * @param OrmModel $ormModel
     * @return array|bool|null
     */
    public static function update($set, $where, $ormModel = null)
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
     * @param array $where
     * @param null|array $set
     * @param null|array $insert
     * @param OrmModel $ormModel
     * @return array|bool|null
     */
    public static function write($where, $set = null, $insert = null, $ormModel = null)
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
     * @param $where
     * @param OrmModel $ormModel
     * @return array|bool|null
     * @todo: da fare
     */
    public static function delete($where, $ormModel = null)
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
     * @param null|array $where
     * @param null|array $set
     * @param null|array $insert
     * @param OrmModel $ormModel
     * @return array|bool|null
     */
    private static function set($where = null, $set = null, $insert = null, $ormModel = null)
    {
        self::clearResult($ormModel);

        self::resolveFieldsByScopes(array(
            "insert"                                                                        => $insert
            , "set"                                                                         => $set
            , "where"                                                                       => $where
        ));

        self::execSub();

        //main table
        self::setData();

        if (isset(self::$data["rev"]) && is_array(self::$data["rev"]) && count(self::$data["rev"])) {
            foreach (self::$data["rev"] as $table => $controller) {
                self::setData($controller, $table);
            }
        }

        return self::getResult(true);
    }

    /**
     * @param null $controller
     * @param null $table
     *
     */
    private static function setData($controller = null, $table = null)
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
        $key_name                                                                           = self::getFieldAlias(array_search("primary", $data["def"]["struct"]), $data["def"]["alias"]);

        if (isset($data["insert"]) && !isset($data["set"]) && isset($data["where"])) {
            $data["insert"]                                                                 = self::getFields($data["insert"], $data["def"]["alias"]);
            $regs                                                                           = $storage->read($data["insert"], array($key_name => true));

            if (is_array($regs)) {
                $key                                                                        = false;

                self::setKeyRelationship($regs["keys"][0], $key_name, $data, $controller);
            }
            if ($key === null) {
                $regs                                                                       = $storage->insert($data["insert"], $data["def"]["table"]["name"]);
                if (is_array($regs)) {
                    $key                                                                    = $regs["keys"][0];
                }
            }
        } elseif (isset($data["insert"]) && !isset($data["set"]) && !isset($data["where"])) {
            $data["insert"]                                                                 = self::getFields($data["insert"], $data["def"]["alias"]);
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
                                                                                                ->read(self::$data["main"]["where"], array($key_main_primary => true), null, null, self::$data["main"]["def"]["table"]["name"]);
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
            $regs                                                                           = $storage->read($data["where"], array($key_name => true), null, null, $data["def"]["table"]["name"]);
            if (is_array($regs)) {
                $key                                                                        = $regs["keys"][0];
            }
        } elseif (isset($data["insert"]) && isset($data["set"]) && isset($data["where"])) {
            $regs                                                                           = $storage->write(
                $data["insert"],
                array(
                                                                                                    "set"       => $data["set"]
                                                                                                    , "where"   => $data["where"]
                                                                                                ),
                $data["def"]["table"]["name"]
                                                                                            );
            if (is_array($regs)) {
                $key                                                                        = $regs["keys"][0];
            }
        }

        self::setKeyRelationship($key, $key_name, $data, $controller);

        if ($key !== null) {
            self::$result["keys"][$data["def"]["table"]["alias"]] = $key;
        }
    }

    private static function setKeyRelationship($key, $key_name, $data, $controller)
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
                            $field_alias                                                    = self::getFieldAlias($field_ext, self::$data["sub"][$controller][$tbl]["def"]["alias"]);
                            $rev_controller                                                 = self::$data["rev"][$tbl];

                            if (isset(self::$data["sub"][$rev_controller][$tbl]["insert"])) {
                                self::$data["sub"][$rev_controller][$tbl]["insert"][$field_alias]   = $key;
                            }
                            if (isset(self::$data["sub"][$rev_controller][$tbl]["set"])) {
                                self::$data["sub"][$rev_controller][$tbl]["where"][$field_alias]    = $key;
                            }
                        } else {
                            $field_alias                                                    = self::getFieldAlias($field_ext, self::$data["main"]["def"]["alias"]);
                            if (isset(self::$data["main"]["insert"])) {
                                self::$data["main"]["insert"][$field_alias]                 = $key;
                            }
                            if (isset(self::$data["main"]["set"])) {
                                self::$data["main"]["where"][$field_alias]                  = $key;
                            }
                        }
                    }
                }
            }
        }
    }

    private static function execSub($cmd = null)
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
    public static function cmd($action, $where = null, $fields = null, $ormModel = null)
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

    private static function getCurrentWhere(array $data)
    {
        if (!isset($data["where"])) {
            return null;
        }

        return ($data["where"] === true
            ? true
            : self::getFields($data["where"], $data["def"]["alias"])
        );
    }
    private static function cmdData($command, $controller = null, $table = null)
    {
        $data                                                                               = self::getCurrentData($controller, $table);
        $where                                                                              = self::getCurrentWhere($data);

        if ($where) {
            $ormModel                                                                       = self::getModel(
                $data["service"]
                                                                                                ? $data["service"]
                                                                                                : $controller
                                                                                            );
            $storage                                                                        = $ormModel->setStorage($data["def"]);
            $regs                                                                           = $storage->cmd(
                $command,
                (
                    $where === true
                                                                                                    ? null
                                                                                                    : $where
                                                                                                )
                                                                                            );
            self::$result["cmd"][$data["def"]["table"]["alias"]]                            = $regs;
        }
    }


    /**
     * @param OrmModel $ormModel
     */
    private static function setMainIndexes($ormModel)
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
     * @param array $result
     * @param array $entry
     * @param bool $replace
     */
    private static function setResult(&$result, $entry, $replace = false)
    {
        if ($result) {
            if ($replace) {
                $result                                                                     = array_replace($result, $entry);
            } else {
                if (Database::isAssocArray($result)) {
                    $result = array("0" => $result);
                }

                $result[]                                                                   = $entry;
            }
        } else {
            $result                                                                         = $entry;
        }
    }

    /**
     * @param $data
     * @return null
     */
    private static function resolveFieldsByScopes($data)
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
            $key_name                                                                       = array_search("primary", self::$data["main"]["def"]["struct"]);
            self::$data["main"]["select"][$key_name]                                        = $key_name;
            self::$data["main"]["select_is_empty"]                                          = true;
        }

        if (isset(self::$data["main"]["select"]) && isset(self::$data["main"]["select"]["*"])) {
            self::$data["main"]["select"] = array_combine(array_keys(self::$data["main"]["def"]["struct"]), array_keys(self::$data["main"]["def"]["struct"]));
        }


        return (!isset(self::$services_by_data["use_alias"]) && is_array(self::$services_by_data["tables"]) && count(self::$services_by_data["tables"]) === 1);
    }

    /**
     * @param $fields
     * @param string $scope
     * @return null
     */
    private static function resolveFields($fields, $scope = "fields")
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
                            self::$data["sub"][$service][$table][$scope] = array_combine(array_keys(self::$data["sub"][$service][$table]["def"]["struct"]), array_keys(self::$data["sub"][$service][$table]["def"]["struct"]));
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

        return true;
    }

    /**
     * @param array $fields
     * @param array|null $alias
     * @param array|null $indexes
     * @param string|null $primary_key
     * @return array
     */
    private static function getFields(array $fields = null, array $alias = null, array &$indexes = null, string $primary_key = null) : array
    {
        $res                                                                                = null;
        if (is_array($fields) && count($fields)) {
            $res                                                                            = $fields;
            if (!isset($res["*"])) {
                if (is_array($indexes) && count($indexes)) {
                    $res                                                                    = $res + array_fill_keys(array_keys($indexes), true);

                    if (is_array($alias) && count($alias)) {
                        $indexes = array_diff_key($indexes, $alias);
                    }
                    foreach ($fields as $field_key => $field_ext) {
                        if (isset($indexes[$field_key])) {
                            unset($indexes[$field_key]);
                        }
                        if (isset($indexes[$field_ext])) {
                            unset($indexes[$field_ext]);
                        }
                    }
                }

                if (is_array($alias) && count($alias)) {
                    foreach ($alias as $old => $new) {
                        if (array_key_exists($new, $res)) {
                            $res[$old]                                                      = $res[$new];
                            unset($res[$new]);
                        }

                        if (isset($fields[$old]) && isset($indexes[$new])) {
                            unset($indexes[$new]);
                        }
                    }
                }
            }
        }

        if (!$res) {
            if (is_array($indexes) && count($indexes)) {
                $res = array_fill_keys(array_keys($indexes), true);
            }
            if ($primary_key) {
                $res[$primary_key] = true;
            }
        }

        return $res;
    }

    private static function getFieldAlias($field, $alias)
    {
        if (is_array($alias) && count($alias)) {
            $alias_rev = array_flip($alias);
            return($alias_rev[$field]
                ? $alias_rev[$field]
                : $field
            );
        } else {
            return $field;
        }
    }

    /**
     * @param OrmModel $ormModel
     */
    private static function clearResult($ormModel)
    {
        self::$data                                                         = array();
        self::$result                                                       = array();
        self::$services_by_data                                             = array();
        self::$service                                                      = $ormModel;

        Error::clear(static::ERROR_BUCKET);
    }

    /**
     * @param bool $rawdata
     * @return array|bool|null
     */
    private static function resolveResult($rawdata = false)
    {
        if (is_array(self::$result)) {
            if ($rawdata || count(self::$result) > 1) {
                if (isset(self::$result["keys"])) {
                    $res                                                    = self::$result["keys"];
                } elseif (isset(self::$result["update"])) {
                    $res                                                    = self::$result["update"];
                } elseif (isset(self::$result["cmd"])) {
                    $res                                                    = self::$result["cmd"];
                } else {
                    $res                                                    = array_values(self::$result);
                }
            } else {
                $res                                                        = current(self::$result);
                if (isset(self::$data["main"]["service"]) && isset(self::$data["sub"]) && isset(self::$data["sub"][self::$data["main"]["service"]]) && is_array(self::$data["sub"][self::$data["main"]["service"]]) && count(self::$data["sub"][self::$data["main"]["service"]]) == 1 && is_array($res) && count($res) == 1) {
                    $res                                                    = current($res);
                }
                //todo: da veridicare prima era $this->service
                $service_name                                               = self::getModel()->getName();
                if (is_array($res) && count($res) == 1 && isset($res[$service_name])) {
                    $res                                                    = $res[$service_name];
                }
            }
        } else {
            $res                                                            = self::$result; // non deve mai entrare qui
        }

        return $res;
    }
    /**
     * @param bool $rawdata
     * @return array|bool|null
     */
    private static function getResult($rawdata = false)
    {
        //return self::$result;
        return self::resolveResult($rawdata);
    }
}
