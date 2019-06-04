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
use phpformsframework\libs\Log;
use phpformsframework\libs\Error;
use phpformsframework\libs\storage\models\Model;

class Orm implements Dumpable {
    const NAME_SPACE                                                                        = 'phpformsframework\\libs\\storage\\models\\';

    private static $singleton                                                               = null;
    private static $data                                                                    = array();
    private static $services_by_data                                                        = array();
    private static $result                                                                  = array();

    private static $service                                                                 = null;

    /**
     * @param string $ormModel
     * @return Model
     */
    public static function getInstance($ormModel) {
        return self::setSingleton($ormModel);
    }
    public static function dump()
    {
        return self::$singleton;
    }

    private static function setSingleton($ormModel) {
        if(!isset(self::$singleton[$ormModel])) {
            $class_name                                                                     = static::NAME_SPACE . ucfirst($ormModel);
            self::$singleton[$ormModel]                                                     = new $class_name();
        }

        return self::$singleton[$ormModel];
    }

    /**
     * @param string $model
     * @return Model
     */
    private static function getModel($model = null) {
        return ($model
            ? self::setSingleton($model)
            :  self::$service
        );
    }

    /**
     * @param Model $ormModel
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

        if(Debug::ACTIVE) {
            Log::debugging(array(
                "action"    => "readRawData"
                , "data"    => self::$data
                , "exTime"  => Debug::stopWatch()
            ));
        }

        return $res;
    }

    /**
     * @param Model $ormModel
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|array $limit
     * @return array|bool|null
     */
    public static function read($where = null, $fields = null, $sort = null, $limit = null, $ormModel = null)
    {
        Debug::dumpCaller("Read RawData: " . print_r($fields, true) . " Where: " . print_r($where, true) . " Sort: " . print_r($sort, true) . " Limit: " . print_r($limit, true));
        if(Debug::ACTIVE)                                                                   { Debug::startWatch(); }

        $res                                                                                = self::get($where, $fields, $sort, $limit, $ormModel, false);

        if(Debug::ACTIVE) {
            Log::debugging(array(
                "action"    => "read"
                , "data"    => self::$data
                , "exTime"  => Debug::stopWatch()
            ));
        }
        return $res;
    }

    /**
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|array $limit
     * @param Model $ormModel
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

        if($single_service) {
            self::getDataSingle(self::$services_by_data["last"], self::$services_by_data["last_table"], $limit);
        } else {
            if(is_array(self::$data["sub"]) && count(self::$data["sub"])) {
                foreach(self::$data["sub"] AS $controller => $tables) {
                    foreach($tables AS $table => $params) {
                        $keys_unique                                                        = (isset($params["def"]["indexes"]) && is_array($params["def"]["indexes"])
                                                                                                ? array_keys($params["def"]["indexes"], "unique")
                                                                                                : array()
                                                                                            );
                        if(count($keys_unique)) {
                            $where_unique                                                   = array_intersect($keys_unique, array_keys($params["where"]));
                            if ($where_unique == $keys_unique) {
                                foreach ($where_unique AS $where_unique_index => $where_unique_key) {
                                    if(isset($params["where"][$where_unique_key]['$regex'])) {
                                        unset($where_unique[$where_unique_index]);
                                    }
                                }

                                if(count($where_unique)) {
                                    self::$data["sub"][$controller][$table]["runned"]       = true;
                                    $counter                                                = self::getData($controller, $table);
                                    if($counter === false && $params["where"])              { return self::getResult($result_raw_data); }

                                    unset(self::$data["exts"]);
                                }
                            }
                        }
                    }
                }
            }
            if(isset(self::$data["main"]["where"])) {
                self::$data["main"]["runned"]                                               = true;
                $counter                                                                    = self::getData(null, null, $limit);       //try main table
                if($counter === false)                                                      { return false; }
            }

            if(isset(self::$data["sub"]) && is_array(self::$data["sub"]) && count(self::$data["sub"])) {
                foreach(self::$data["sub"] AS $controller => $tables) {
                    foreach($tables AS $table => $params) {
                        if(!isset($params["runned"]))                                       { $counter = self::getData($controller, $table); }
                        if($counter === false && $params["where"])                          { return self::getResult($result_raw_data); }

                    }
                }
            }

            if(!isset(self::$data["main"]["runned"]) && isset(self::$data["main"]["where"])) {
                self::getData(null, null, $limit);       //try main table
            }
        }

        return self::getResult($result_raw_data);
    }

    /**
     * @param null|string $controller
     * @param null|string $table
     * @param null|array $limit
     * @return bool|int
     */
    private static function getData($controller = null, $table = null, $limit = null) {
        $counter                                                                            = false;
        $table_rel                                                                          = false;
        $where                                                                              = null;
        $sort                                                                               = null;
        $data                                                                               = (!$controller && !$table
                                                                                                ? self::$data["main"]
                                                                                                : self::$data["sub"][$controller][($table
                                                                                                    ? $table
                                                                                                    : self::getModel($controller)->getMainTable()
                                                                                                )]
                                                                                            );
        if(isset($data["where"])) {
            $where                                                                          = ($data["where"] === true
                                                                                                ? true
                                                                                                : self::getFields($data["where"], $data["def"]["alias"])
                                                                                            );
        }

        if(isset($data["sort"])) {
            $sort                                                                           = self::getFields($data["sort"], $data["def"]["alias"]);
        }
        $table_main                                                                         = (isset($data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]])
                                                                                                ? self::$data["main"]["def"]["mainTable"]
                                                                                                : $data["def"]["mainTable"]
                                                                                            );

        if(isset($data["def"]["relationship"][$table_main]) && isset(self::$data["exts"])) {
            $field_ext                                                                      = $data["def"]["relationship"][$table_main]["external"];
            $field_key                                                                      = $data["def"]["relationship"][$table_main]["primary"];


            if(isset($data["def"]["struct"][$field_ext])) { //imposta la tabella di relazione se la chiave è esterna es:   mol.studi.def.struct.ID_anagraph o doctors.def.struct.ID_anagraph
//echo "tbl: " . $table . "\n";
//echo "tbl main: " . $table_main . "\n";
//echo "pre External: " . $field_ext . "\n";
//echo "post External: " . $field_key . "\n";
                //if(!$data["def"]["relationship"][$field_ext]) {
                $field_ext                                                                  = $data["def"]["relationship"][$table_main]["primary"];
                $field_key                                                                  = $data["def"]["relationship"][$table_main]["external"];
                //}
                $table_rel                                                                  = (isset(self::$data["exts"][$table]) && isset(self::$data["exts"][$table][$field_ext])
                                                                                                ? $table
                                                                                                : $table_main
                                                                                            );


//echo "tbl rel: " . $table_rel . "." . $field_ext . "\n";
//echo "--------------------------\n";
            }

            $ids                                                                            = (isset(self::$data["exts"][$table_main][$field_ext]) && is_array(self::$data["exts"][$table_main][$field_ext])
                                                                                                ? array_keys(self::$data["exts"][$table_main][$field_ext])
                                                                                                : null
                                                                                            );
            if($ids) {
                $where[self::getFieldAlias($field_key, $data["def"]["alias"])]             = (count($ids) == 1
                                                                                                ? $ids[0]
                                                                                                : $ids
                                                                                            );

                self::$data["sub"][$controller][$table]["where"]                            = $where; //for debug
            }

        }

        if($where) {
            $sub_ids                                                                        = null;
            $indexes                                                                        = $data["def"]["indexes"];
            $select                                                                         = (1
                                                                                                ? self::getFields(
                                                                                                    $data["select"]
                                                                                                    , $data["def"]["alias"]
                                                                                                    , $indexes
                                                                                                    , array_search("primary", $data["def"]["struct"])
                                                                                                )
                                                                                                : $data["select"]
                                                                                            );
            $ormModel                                                                       = self::getModel(isset($data["service"])
                                                                                                ? $data["service"]
                                                                                                : $controller
                                                                                            );
            $regs                                                                           = $ormModel
                                                                                                ->setStorage($data["def"], array("exts" => true, "rawdata" => false))
                                                                                                ->read(
                                                                                                ($where === true
                                                                                                    ? null
                                                                                                    : $where
                                                                                                )
                                                                                                , $select
                                                                                                , $sort
                                                                                                , $limit
                                                                                            );

            if(is_array($regs)) {
                $field_key                                                                  = null;
                if(isset($regs["rawdata"])) {
                    self::$result                                                           = $regs["rawdata"];
                    $regs["keys"]                                                           = array_keys($regs["rawdata"]);
                }

                if(isset($regs["exts"]) && is_array($regs["exts"])) {
                    if(isset(self::$data["exts"]) && isset(self::$data["exts"][$data["def"]["mainTable"]])) {
                        self::$data["exts"][$data["def"]["mainTable"]]                          = self::$data["exts"][$data["def"]["mainTable"]] + $regs["exts"];
                    } else {
                        self::$data["exts"][$data["def"]["mainTable"]]                          = $regs["exts"];
                    }

                    if(isset(self::$data["main"]["select"]) && isset($data["def"]["relationship"][$table_main]) /*&& !self::$data["main"]["where"]*/) {
                        $field_ext                                                          = $data["def"]["relationship"][$table_main]["external"];
                        $field_key                                                          = $data["def"]["relationship"][$table_main]["primary"];

                        if($field_key) {
                            $ids                                                            = (isset($regs["exts"][$field_ext])
                                                                                                ? array_keys($regs["exts"][$field_ext])
                                                                                                : false
                                                                                            );
                            if($ids) {
                                self::$data["main"]["where"][$field_key]                    = (count($ids) == 1
                                                                                                ? $ids[0]
                                                                                                : $ids
                                                                                            );
                                //if(!$data["runned"])                                            self::getData(); //try main table by sub
                                //$sub_ids                                                      = array_keys(self::$data["exts"][self::$data["main"]["def"]["mainTable"]][$field_ext]);
                                //
                                if(!isset(self::$data["main"]["runned"]) && self::$result) { //fix per il permalink. viene inserito il dato in tutti i nodi duplicand i valori
                                    $sub_ids                                                = $ids;
                                }
                            } elseif($regs === false) {
                                self::$data["main"]["where"][$field_key]                    = "0";
                            }
                        }
                    }
                }


                if(isset($regs["keys"]) && is_array($regs["keys"]) && count($regs["keys"])) {
                    $field_ext                                                              = null;
                    $counter                                                                = count($regs["keys"]);
                    $table_name                                                             = $data["def"]["table"]["alias"];

                    if(!$table_rel && isset($data["def"]["relationship"][$table_main])) {         //se è una maintable ma non anagraph reimposta l'external base es: doctors -> anagraph -> external
                        $field_ext                                                          = $data["def"]["relationship"][$table_main]["external"];
                    }

                    foreach($regs["keys"] AS $i => $id) {
                        $result                                                             = null;
                        $keys                                                               = null;
                        if(isset($data["select"]["*"])) {
                            $result                                                         = (!$controller && !$table
                                                                                                ? $regs["result"][$i][$table_name]
                                                                                                : $regs["result"][$i]
                                                                                            );
                        } elseif(isset($data["select_is_empty"])) {
                            $result                                                         = array();
                        } else {
                            $result                                                         = ($indexes
                                                                                                ? array_intersect_key($regs["result"][$i], array_flip($data["select"]))
                                                                                                : $regs["result"][$i]
                                                                                            );
                        }

                        if($result) {
                            //triggera quando avviene la seguente casistica: anagraph --> anagraph_person dove anagraph_person.ID_anagraph = anagraph.ID
                            if($table_main && isset($data["def"]["relationship"][$table_main]) && $data["def"]["relationship"][$table_main]["external"]) {
                                $field_ext                                                  = $data["def"]["relationship"][$table_main]["external"];

                                if(isset($regs["exts"][$field_ext]) && isset($regs["exts"][$field_ext][$regs["result"][$i][$field_ext]])) {
                                    $keys                                                   = array($regs["result"][$i][$field_ext]);
                                    $table_rel                                              = null;
                                }
                            }

                            if(!$keys && $field_ext) {
                                $keys                                                       = ($table_rel
                                                                                                ? array_keys(self::$data["exts"][$table_rel][$field_ext])
                                                                                                : self::$data["exts"][$table_main][$field_ext][$id]
                                                                                            );
                            }

                            $ids                                                            = ($sub_ids
                                                                                                ? $sub_ids
                                                                                                : $keys
                                                                                            );
//  print_r($ids);
                            if(is_array($ids) && count($ids)) {
                                foreach($ids AS $id_primary) {
                                    $id_primary                                             = self::ids_traversing($id_primary, $id);

                                    /*if(0 && $opt["limit"] == 1)
                                        self::setResult(self::$result[$id], $result);
                                    else*/
                                    if($table_rel) { //discende fino ad anagraph per fondere i risultati annidati esempio anagraph -> users -> tokens
                                        $root_ids = self::$data["exts"][$ormModel->getMainModel()->getMainTable()][$field_key][$id_primary];
                                        if(is_array($root_ids) && count($root_ids) == 1) {
                                            $id_primary                                     = $root_ids[0];
                                        }
                                    }
                                    self::setResult(self::$result[$id_primary][$table_name], $result, (!$controller && !$table /* is main */));

                                    /*if(self::$result[$id][$table_name]) {
                                        if($this->isAssocArray(self::$result[$id][$table_name]))
                                            self::$result[$id][$table_name]                     = array("0" => self::$result[$id][$table_name]);

                                        self::$result[$id][$table_name][]                       = $result;
                                    } else {
                                        self::$result[$id][$table_name]                         = $result;
                                    }*/
                                }
                            } else {
                                self::setResult(self::$result[$id], $result, (!$controller && !$table /* is main */));
                                //self::$result[$id]                                            = $result;
                            }
                        }
                    }
                }
            } else {
                Error::register($regs, "orm");
            }
        } else {
            $counter = null;
        }

        return $counter;
    }

    private static function getDataSingle($controller, $table, $limit = null) {
        $data                                                                               = (isset(self::$data["sub"][$controller]) && isset(self::$data["sub"][$controller][$table])
                                                                                                ? self::$data["sub"][$controller][$table]
                                                                                                : self::$data["main"]
                                                                                            );
        if($data) {
            $regs                                                                           = self::getModel(
                                                                                                    isset($data["service"])
                                                                                                        ? $data["service"]
                                                                                                        : $controller
                                                                                                    )
                                                                                                ->setStorage($data["def"], array("exts" => false, "rawdata" => true))
                                                                                                ->read(
                                                                                                    (!isset($data["where"]) || $data["where"] === true
                                                                                                        ? null
                                                                                                        : self::getFields($data["where"], $data["def"]["alias"])
                                                                                                    )
                                                                                                    , (isset($data["select"])
                                                                                                        ? self::getFields($data["select"], $data["def"]["alias"])
                                                                                                        : null
                                                                                                    )
                                                                                                    , (isset($data["sort"])
                                                                                                        ? self::getFields($data["sort"], $data["def"]["alias"])
                                                                                                        : null
                                                                                                    )
                                                                                                    , $limit
                                                                                                );
            if(is_array($regs)) {
                if($regs["rawdata"])                                                        { self::$result = $regs["rawdata"]; }
            } else {
                Error::register($regs, "orm");
            }
        } else {
            Error::register("normalize data is empty", "orm");
        }
    }

    private static function ids_traversing($id_primary, $id) {
        if(isset(self::$data["traversing"][$id_primary])) {
            $res                                                                            = self::$data["traversing"][$id_primary];
        } elseif(!isset(self::$data["traversing"][$id])) {
            self::$data["traversing"][$id]                                                  = $id_primary;
            $res = self::$data["traversing"][$id];
        } else {
            $res                                                                            = $id_primary;
        }

        return $res;
    }

    /**
     * @param array $insert
     * @param null|Model $ormModel
     * @return array|bool|null
     */
    public static function insert($insert, $ormModel = null)
    {
        Debug::dumpCaller("Insert: " . print_r($insert, true));
        if(Debug::ACTIVE)                                                                   { Debug::startWatch(); }

        $res                                                                                = self::set(null, null, $insert, $ormModel);

        if(Debug::ACTIVE) {
            Log::debugging(array(
                "action"    => "insert"
                , "data"    => self::$data
                , "exTime"  => Debug::stopWatch()
            ));
        }
        return $res;
    }

    /**
     * @param array $set
     * @param array $where
     * @param Model $ormModel
     * @return array|bool|null
     */
    public static function update($set, $where, $ormModel = null)
    {
        Debug::dumpCaller("Update: " . print_r($set, true) . " Where: " . print_r($where, true));
        if(Debug::ACTIVE)                                                                   { Debug::startWatch(); }

        $res                                                                                = self::set($where, $set, null, $ormModel);

        if(Debug::ACTIVE) {
            Log::debugging(array(
                "action"    => "insert"
            , "data"    => self::$data
            , "exTime"  => Debug::stopWatch()
            ));
        }
        return $res;
    }

    /**
     * @param array $where
     * @param null|array $set
     * @param null|array $insert
     * @param Model $ormModel
     * @return array|bool|null
     */
    public static function write($where, $set = null, $insert = null, $ormModel = null)
    {
        Debug::dumpCaller("Write: " . print_r($where, true) . " Set: " . print_r($set, true) . " Insert: " . print_r($insert, true));
        if(Debug::ACTIVE)                                                                   { Debug::startWatch(); }

        $res                                                                                = self::set($where, $set, $insert, $ormModel);

        if(Debug::ACTIVE) {
            Log::debugging(array(
                "action"    => "write"
                , "data"    => self::$data
                , "exTime"  => Debug::stopWatch()
            ));
        }
        return $res;
    }
    /**
     * @param $where
     * @param Model $ormModel
     * @return array|bool|null
     * @todo: da fare
     */
    public static function delete($where, $ormModel = null)
    {
        Debug::dumpCaller("Insert: " . print_r($where, true));
        if(Debug::ACTIVE)                                                                   { Debug::startWatch(); }

        $res = !(bool) $ormModel;

        if(Debug::ACTIVE) {
            Log::debugging(array(
                "action"    => "insert"
            , "data"    => self::$data
            , "exTime"  => Debug::stopWatch()
            ));
        }
        return $res;
    }


    /**
     * @param array $where
     * @param null|array $set
     * @param null|array $insert
     * @param Model $ormModel
     * @return array|bool|null
     */
    private static function set($where, $set = null, $insert = null, $ormModel = null)
    {
        self::clearResult($ormModel);

        if(!$set && !$insert) {
            $insert                                                                         = $where;
            $where                                                                          = null;
        }

        self::resolveFieldsByScopes(array(
            "insert"                                                                        => $insert
            , "set"                                                                         => $set
            , "where"                                                                       => $where
        ));

        if(isset(self::$data["sub"]) && is_array(self::$data["sub"]) && count(self::$data["sub"])) {
            foreach(self::$data["sub"] AS $controller => $tables) {
                foreach($tables AS $table => $params) {
                    if($params["def"]["struct"][$params["def"]["relationship"][$params["def"]["mainTable"]]["external"]]
                        || $params["def"]["struct"][$params["def"]["relationship"][self::$data["main"]["mainTable"]]["external"]]
                    ) {
                        self::$data["rev"][$table]                                          = $controller;
                    } else {
                        self::setData($controller, $table);
                    }
                }
            }
        }

        //main table
        self::setData();

        if(isset(self::$data["rev"]) && is_array(self::$data["rev"]) && count(self::$data["rev"])) {
            foreach(self::$data["rev"] AS $table => $controller) {
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
    private static function setData($controller = null, $table = null) {
        $key                                                                                = null;
        $data                                                                               = (!$controller && !$table
                                                                                                ? self::$data["main"]
                                                                                                : self::$data["sub"][$controller][($table
                                                                                                    ? $table
                                                                                                    : self::getModel($controller)->getMainTable()
                                                                                                )]
                                                                                            );
        $ormModel                                                                           = self::getModel(isset($data["service"])
                                                                                                ? $data["service"]
                                                                                                : $controller
                                                                                            );
        $storage                                                                            = $ormModel->setStorage($data["def"]);
        $key_name                                                                           = self::getFieldAlias(array_search("primary", $data["def"]["struct"]), $data["def"]["alias"]);

        if(isset($data["insert"])) {
            $data["insert"]                                                                 = self::getFields($data["insert"], $data["def"]["alias"]);
            if(isset($data["where"]))                                                       { $data["where"] = self::getFields($data["where"], $data["def"]["alias"]); }
            if(!isset($data["where"]))                                                      { $data["where"] = $data["insert"]; }

            $regs                                                                           = $storage->read($data["where"], array($key_name => true));
            if(is_array($regs)) {
                $key                                                                        = $regs["keys"][0];
            } else {
                Error::register($regs, "orm");
            }
            if(!$key && !Error::check("orm")) {
                $regs                                                                       = $storage->insert($data["insert"], $data["def"]["table"]["name"]);
                if(is_array($regs)) {
                    $key                                                                    = $regs["keys"][0];
                } else {
                    Error::register($regs, "orm");
                }
            }
        } elseif(isset($data["set"]) && !isset($data["where"])) {
            if(isset($data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]) && self::$data["main"]["where"][$data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]["primary"]])  {
                $external_name                                                              = $data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]["external"];
                $primary_name                                                               = $data["def"]["relationship"][self::$data["main"]["def"]["mainTable"]]["primary"];
                if(!isset($data["def"]["struct"][$external_name])) {
                    if(!isset(self::$data["main"]["where"][$external_name]))                { self::setMainIndexes($ormModel); }

                    $data["where"][$primary_name]                                           = self::$data["main"]["where"][$external_name];
                } else {
                    $data["where"][$external_name]                                          = self::$data["main"]["where"][$primary_name];
                }
            }

            if(isset($data["where"])) {
                $regs                                                                       = $storage->update($data["set"], $data["where"], $data["def"]["table"]["name"]);
                if($regs === true) {
                    $key                                                                    = null;
                } else {
                    Error::register($regs, "orm");
                }
            }
        } elseif(isset($data["set"]) && isset($data["where"])) {
            $regs                                                                           = $storage->update($data["set"], $data["where"], $data["def"]["table"]["name"]);
            if($regs === true) {
                $key                                                                        = null;
            } else {
                Error::register($regs, "orm");
            }
        } elseif(isset($data["where"]) && !isset($data["insert"]) && !isset($data["set"])) {
            $regs                                                                           = $storage->read($data["where"], array($key_name => true), null, null, $data["def"]["table"]["name"]);
            if(is_array($regs)) {
                $key                                                                        = $regs["keys"][0];

            } else {
                Error::register($regs, "orm");
            }
        } elseif(isset($data["insert"]) && isset($data["set"]) && isset($data["where"])) {
            $regs                                                                           = $storage->write(
                                                                                                $data["insert"]
                                                                                                , array(
                                                                                                    "set"       => $data["set"]
                                                                                                    , "where"   => $data["where"]
                                                                                                )
                                                                                                , $data["def"]["table"]["name"]
                                                                                            );
            if(is_array($regs)) {
                $key                                                                        = $regs["keys"][0];
            } else {
                Error::register($regs, "orm");
            }
        }

        if(is_array($data["def"]["relationship"]) && count($data["def"]["relationship"])) {
            foreach ($data["def"]["relationship"] AS $tbl => $rel) {
                if(isset($rel["external"]) && isset(self::$data["sub"][$controller][$tbl])) {
                    $field_ext                                                              = $rel["external"];
                    //$field_key                                                            = $rel["primary"];

                    if(isset($data["def"]["struct"][$field_ext])) {
                        $field_ext                                                          = $rel["primary"];
                        //$field_key                                                        = $rel["external"];
                    }
                    if($key && $field_ext && $field_ext != $key_name) {
                        if ($tbl != self::$data["main"]["def"]["mainTable"]) {
                            $field_alias                                                    = self::getFieldAlias($field_ext, self::$data["sub"][$controller][$tbl]["def"]["alias"]);
                            $rev_controller                                                 = self::$data["rev"][$tbl];

                            if (self::$data["sub"][$rev_controller][$tbl]["insert"]) {
                                self::$data["sub"][$rev_controller][$tbl]["insert"][$field_alias]   = $key;
                                //self::$data["sub"][$rev_controller][$tbl]["where"][$field_alias]  = $key;
                            }
                            if (self::$data["sub"][$rev_controller][$tbl]["set"]) {
                                //self::$data["sub"][$rev_controller][$tbl]["set"][$field_alias]    = $key;
                                self::$data["sub"][$rev_controller][$tbl]["where"][$field_alias]    = $key;
                            }
                        } else {
                            $field_alias                                                    = self::getFieldAlias($field_ext, self::$data["main"]["def"]["alias"]);
                            if (self::$data["main"]["insert"]) {
                                self::$data["main"]["insert"][$field_alias]                 = $key;
                            }
                            if (self::$data["main"]["set"]) {
                                self::$data["main"]["where"][$field_alias]                  = $key;
                            }
                        }
                    }
                }
            }
        }

        if($key)                                                                            { self::$result["keys"][$data["def"]["table"]["alias"]] = $key; }
    }

    public static function cmd($name, $where = null, $fields = null, $ormModel = null) {
        self::clearResult($ormModel);

        self::resolveFieldsByScopes(array(
            "select"                                                                        => $fields
            , "where"                                                                       => $where
        ));


        if(is_array(self::$data["sub"]) && count(self::$data["sub"])) {
            foreach(self::$data["sub"] AS $controller => $tables) {
                foreach($tables AS $table => $params) {
                    if($params["def"]["struct"][$params["def"]["relationship"][$params["def"]["mainTable"]]["external"]]
                        || $params["def"]["struct"][$params["def"]["relationship"][self::$data["main"]["mainTable"]]["external"]]
                    ) {
                        self::$data["rev"][$table]                                          = $controller;
                    } else {
                        self::cmdData($name, $controller, $table);
                    }
                }
            }
        }

        return self::getResult(false);
    }
    private static function cmdData($command, $controller = null, $table = null) {
        $data                                                                               = (!$controller && !$table
                                                                                                ? self::$data["main"]
                                                                                                : self::$data["sub"][$controller][($table
                                                                                                    ? $table
                                                                                                    : self::getModel($controller)->getMainTable()
                                                                                                )]
                                                                                            );
        $where                                                                              = ($data["where"] === true
                                                                                                ? true
                                                                                                : self::getFields($data["where"], $data["def"]["alias"])
                                                                                            );

        if($where) {
            $indexes                                                                        = $data["def"]["indexes"];
            $select                                                                         = self::getFields(
                                                                                                $data["select"]
                                                                                                , $data["def"]["alias"]
                                                                                                , $indexes
                                                                                                , array_search("primary", $data["def"]["struct"])
                                                                                            );
            $ormModel                                                                           = self::getModel($data["service"]
                                                                                                ? $data["service"]
                                                                                                : $controller
                                                                                            );
            $storage                                                                        = $ormModel->setStorage($data["def"]);
            $regs                                                                           = $storage->cmd(
                                                                                                $command
                                                                                                , ($where === true
                                                                                                    ? null
                                                                                                    : $where
                                                                                                )
                                                                                                , $select
                                                                                            );
            self::$result[$data["def"]["table"]["alias"]] = $regs;
        }

    }


    /**
     * @param Model $ormModel
     */
    private static function setMainIndexes($ormModel) {
        $res                                                                                = $ormModel
                                                                                                ->getMainModel()
                                                                                                ->setStorage(self::$data["main"]["def"])
                                                                                                ->read(
                                                                                                    self::$data["main"]["where"]
                                                                                                    , array_keys(self::$data["main"]["def"]["indexes"])
                                                                                                );
        if(is_array($res))                                                                  { self::$data["main"]["where"] = array_replace(self::$data["main"]["where"], $res); }
    }

    /**
     * @param array $result
     * @param array $entry
     * @param bool $replace
     */
    private static function setResult(&$result, $entry, $replace = false) {
        if($result) {
            if($replace) {
                $result                                                                     = array_replace($result, $entry);
            } else {
                if(Database::isAssocArray($result))                                         { $result = array("0" => $result); }

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
    private static function resolveFieldsByScopes($data) {
        foreach ($data as $scope => $fields) {
            self::resolveFields($fields, $scope);
        }
        //$this->service = "mol";
        $is_single_service                                                                  = (count(self::$services_by_data["services"]) == 1
                                                                                                ? true
                                                                                                : false
                                                                                            );

        if(isset(self::$services_by_data["last"]) && $is_single_service) {
            self::$service                                                                  = self::setSingleton(self::$services_by_data["last"]);
        }
        //cambia il service se nella query non viene usata anagraph
        /*if(self::$services_by_data["last"]) {
            if(count(self::$services_by_data["services"]) == 1) {
                $this->service                                                              = self::$services_by_data["last"];
            } else if(count((array) self::$services_by_data["services"] > 1)
                        && count((array) self::$services_by_data["select"]) == 1
                        && count((array) self::$services_by_data["where"]) == 1
            ) {


                $subService                                                                 = self::$services_by_data["last"];
                $subTable                                                                   = $this->getMainTable($subService);
                $key_external                                                               = self::$data["sub"][$subService][$subTable]["def"]["relationship"][Anagraph::MAIN_TABLE]["external"];
                $key_primary                                                                = self::$data["sub"][$subService][$subTable]["def"]["relationship"][Anagraph::MAIN_TABLE]["primary"];
                if(self::$data["sub"][$subService][$subTable]["def"]["struct"][$key_external]
                    && self::$data["main"]["where"][$key_primary]
                ) {
                    if(count(self::$data["main"]["where"]) == 1) {
                           $this->service                                                      = self::$services_by_data["last"];
                           self::$data["sub"][$subService][$subTable]["where"][$key_external]  = self::$data["main"]["where"][$key_primary];
                        unset(self::$data["main"]["where"]);
                    } else {
                        Error::register("Read: " . "unexpected Double Relationship in primary => secondary table", "orm");
                    }
                }
            }
        }*/

        if((!isset(self::$data["main"]) || !(isset(self::$data["main"]["where"]) || isset(self::$data["main"]["select"]) || isset(self::$data["main"]["insert"]))) && $is_single_service) {
            $subService                                                                     = key(self::$services_by_data["services"]);
            $ormModel                                                                       = self::getModel($subService);
            $subTable                                                                       = $ormModel->getMainTable();

            if(isset(self::$data["sub"][$subService]) && isset(self::$data["sub"][$subService][$subTable])) {
                self::$data["main"]                                                         = self::$data["sub"][$subService][$subTable];
            } else {
                self::$data["main"]["def"]                                                  = $ormModel->getStruct($subTable);
            }
            self::$data["main"]["service"]                                                  = $subService;

            unset(self::$data["sub"][$subService][$subTable]);
            if(!count(self::$data["sub"][$subService]))                                     { unset(self::$data["sub"][$subService]); }
            if(!count(self::$data["sub"]))                                                  { unset(self::$data["sub"]); }

            if($data["where"] === true)                                                     { self::$data["sub"][$subService]["state"]["where"] = true; }
        } else {
            $ormModel                                                                       = self::getModel();
            $mainTable                                                                      = $ormModel->getMainTable();

            self::$data["main"]["def"]                                                      = $ormModel->getStruct($mainTable);
            self::$data["main"]["service"]                                                  = $ormModel->getName();

            if($data["where"] === true)                                                     { self::$data["main"]["where"] = true; }
        }

        if(!isset(self::$data["main"]["select"]) && isset($data["select"]) && !$is_single_service) {
            $key_name                                                                       = array_search("primary", self::$data["main"]["def"]["struct"]);
            self::$data["main"]["select"][$key_name]                                        = $key_name;
            self::$data["main"]["select_is_empty"]                                          = true;
        }

        if(isset(self::$data["main"]["select"]) && isset(self::$data["main"]["select"]["*"])) {
            //self::$data["main"]["select"] = array_fill_keys(array_keys(self::$data["main"]["def"]["struct"]), true);
            self::$data["main"]["select"] = array_combine(array_keys(self::$data["main"]["def"]["struct"]), array_keys(self::$data["main"]["def"]["struct"]));
        }


        return (!isset(self::$services_by_data["use_alias"]) && is_array(self::$services_by_data["tables"]) && count(self::$services_by_data["tables"]) === 1
            ? true
            : false
        );
    }

    /**
     * @param $fields
     * @param string $scope
     * @return null
     */
    private static function resolveFields($fields, $scope = "fields") {
        if(is_array($fields) && count($fields)) {
            $ormModel                                                                       = self::getModel();
            $mainService                                                                    = $ormModel->getName(); // ($this->service ? $this->service : Anagraph::TYPE);
            $mainTable                                                                      = $ormModel->getMainTable(); // ($this->service ? $this->getMainTable($mainService) : Anagraph::MAIN_TABLE);
            if($scope == "select" || $scope == "where" || $scope == "sort") {
                self::$services_by_data["last"]                                             = $mainService;
                self::$services_by_data["last_table"]                                       = $mainTable;
            }
            $is_or                                                                          = false;
            if(isset($fields['$or'])) {
                $fields                                                                     = $fields['$or'];
                $is_or                                                                      = true;
            }

            foreach($fields AS $key => $alias) {
                $table                                                                      = null;
                $fIndex                                                                     = null;
                $service                                                                    = $mainService; //$this->service;
                if(is_numeric($key)) {
                    $key                                                                    = $alias;
                    if($scope != "insert" && $scope != "set")                               { $alias = true; }

                } elseif(is_null($alias)) {
                    $alias                                                                  = null;
                    /*$alias                                                                  = ($scope == "insert" || $scope == "set"
                                                                                                ? null
                                                                                                : null
                                                                                            );*/
                }

                if($scope == "select" && $alias && is_string($alias)) {
                    if(isset(self::$services_by_data["use_alias"])) {
                        self::$services_by_data["use_alias"]++;
                    } else {
                        self::$services_by_data["use_alias"] = 1;
                    }
                }

                $parts                                                                      = explode(".", $key);
                switch(count($parts)) {
                    case "4":
                        if(Debug::ACTIVE) {
                            Debug::dump("Wrong Format: " . $key);
                            exit;
                        }
                        break;
                    case "3":
                        $service                                                            = $parts[0];
                        $table                                                              = $parts[1];
                        $fIndex                                                             = ($service == $mainService && $table == $mainTable
                                                                                                ? -2
                                                                                                : 2
                                                                                            );
                        //$this->services[$service]                                           = null;
                        break;
                    case "2":
                        $table                                                              = $parts[0];
                        $fIndex                                                             = ($table == $mainTable
                                                                                                ? -1
                                                                                                : 1
                                                                                            );
                        break;
                    case "1":
                        $table                                                              = $mainTable;
                        $fIndex                                                             = null;

                    default:
                }

                self::$services_by_data["services"][$service]                               = true;
                self::$services_by_data["tables"][$service . "." . $table]                  = true;
                self::$services_by_data[$scope][$service]                                   = true;
                if($scope == "select" || $scope == "where" || $scope == "sort") {
                    self::$services_by_data["last"]                                         = $service;
                    self::$services_by_data["last_table"]                                   = $table;
                }
                if($fIndex === null || $fIndex < 0) {
                    if($is_or) {
                        self::$data["main"][$scope]['$or'][$parts[abs($fIndex)]]            = ($alias === true && $scope == "select"
                                                                                                ? $parts[abs($fIndex)]
                                                                                                : $alias
                                                                                            );
                    } else {
                        self::$data["main"][$scope][$parts[abs($fIndex)]]                   = ($alias === true && $scope == "select"
                                                                                                ? $parts[abs($fIndex)]
                                                                                                : $alias
                                                                                            );
                    }
                    continue;
                }

                if(!isset(self::$data["sub"]) || !isset(self::$data["sub"][$service][$table]["def"])) {
                    self::$data["sub"][$service][$table]["def"]                             = self::getModel($service)->getStruct($table);
                }

                if(!isset(self::$data["sub"][$service][$table]["def"]["struct"][$parts[$fIndex]])) {
                    if($scope == "select" && $parts[$fIndex] == "*") {
                        self::$data["sub"][$service][$table][$scope] = array_combine(array_keys(self::$data["sub"][$service][$table]["def"]["struct"]), array_keys(self::$data["sub"][$service][$table]["def"]["struct"]));
                    }
                    continue;
                }

                if($scope == "insert") {
                    self::$data["sub"][$service][$table]["insert"][$parts[$fIndex]]         = $alias;
                    self::$data["sub"][$service][$table]["where"][$parts[$fIndex]]          = $alias;
                } else {
                    if($is_or) {
                        self::$data["sub"][$service][$table][$scope]['$or'][$parts[$fIndex]]= ($alias === true && $scope == "select"
                            ? $parts[$fIndex]
                            : $alias
                        );
                    } else {
                        self::$data["sub"][$service][$table][$scope][$parts[$fIndex]]       = ($alias === true && $scope == "select"
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
     * @param null|array $alias
     * @param null|array $indexes
     * @param null|string $primary_key
     * @return array
     */
    private static function getFields($fields = array(), $alias = null, &$indexes = null, $primary_key = null) {
        $res                                                                                = null;

	    if(is_array($fields) && count($fields)) {
            $res                                                                            = $fields;
            if(!isset($res["*"])) {
                if(is_array($indexes) && count($indexes)) {
                    $res                                                                    = $res + array_fill_keys(array_keys($indexes), true);

                    if (is_array($alias) && count($alias))
                        $indexes                                                            = array_diff_key($indexes, $alias);

                    foreach ($fields AS $field_key => $field_ext) {
                        if (isset($indexes[$field_key]))                                    { unset($indexes[$field_key]); }
                        if (isset($indexes[$field_ext]))                                    { unset($indexes[$field_ext]); }
                    }
                }

                if (is_array($alias) && count($alias)) {
                    foreach ($alias AS $old => $new) {
                        if (array_key_exists($new, $res)) {
                            $res[$old]                                                      = $res[$new];
                                                                                            unset($res[$new]);

                        }

                        if(isset($fields[$old]) && isset($indexes[$new]))                   { unset($indexes[$new]); }
                    }
                }
            }
        }

        if(!$res) {
            if(is_array($indexes) && count($indexes))                                       { $res = array_fill_keys(array_keys($indexes), true); }
            if($primary_key)                                                                { $res[$primary_key] = true; }
        }

        return $res;
    }

    private static function getFieldAlias($field, $alias) {
        if(is_array($alias) && count($alias)) {
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
     * @param Model $ormModel
     */
    private static function clearResult($ormModel)
    {
        self::$data                                                         = array();
        self::$result                                                       = array();
        self::$services_by_data                                             = array();
        self::$service                                                      = $ormModel;

        Error::clear("orm");
    }

    /**
     * @param bool $rawdata
     * @return array|bool|null
     */
    private static function resolveResult($rawdata = false) {
        if(is_array(self::$result)) {
            if($rawdata || count(self::$result) > 1) {
                if(isset(self::$result["keys"])) {
                    $res                                                    = self::$result["keys"];
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
                if(is_array($res) && count($res) == 1 && isset($res[$service_name])) {
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
     * @return array|mixed|null
     */
    private static function getResult($rawdata = false)
    {
        return (Error::check("orm")
            ? Error::raise("orm")
            : self::resolveResult($rawdata)
        );
    }

}