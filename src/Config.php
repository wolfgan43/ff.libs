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
namespace phpformsframework\libs;

use phpformsframework\libs\storage\Filemanager;

class Config  implements Dumpable {
    const APP_BASE_NAME                                             = "app";
    const LIBS_BASE_NAME                                            = "libs-base";
    const LIBS_NAME                                                 = "libs";

    private static $config                                          = null;
    private static $maps                                            = null;
    private static $schema                                          = null;
    private static $engine                                          = null;
    private static $rules                                           = null;
    private static $scans                                           = null;
    private static $autoloads                                       = array();
    private static $webroot                                         = null;
    private static $dirstruct                                       = array(
                                                                        "libs"              => array(
                                                                            "path"          => Constant::LIBS_PATH
                                                                            , "permission"  => ""
                                                                        )
                                                                        , "conf"            => array(
                                                                            "path"          => Constant::CONF_PATH
                                                                            , "permission"  => ""
                                                                        )
                                                                    );
    public static function dump() {
        return array(
            "config"        => self::$config
            , "schema"      => self::$schema
            , "engine"      => self::$engine
            , "rules"       => self::$rules
            , "scans"       => self::$scans
            , "dirstruct"   => self::$dirstruct
            , "autoloads"   => self::$autoloads
        );
    }

    public static function loadDirStruct() {
        $config                                                     = self::rawData("dirs", true);
        $scans                                                      = [self::LIBS_BASE_NAME => [], self::LIBS_NAME => [], self::APP_BASE_NAME => []];

        if(is_array($config["dir"]) && count($config["dir"])) {
            if(Constant::DEBUG) {
                foreach (self::$dirstruct as $dir) {
                    if(!is_dir(DirStruct::$disk_path . $dir["path"])) {
                        Error::registerWarning("Dir not exist: " . $dir["path"], "dirstruct");
                    }
                }

            }

            foreach ($config["dir"] AS $dir) {
                $dir_attr                                           = DirStruct::getXmlAttr($dir);
                $dir_key                                            = (isset($dir_attr["type"]) && self::APP_BASE_NAME != $dir_attr["type"]
                                                                        ? $dir_attr["type"] . "-"
                                                                        : ""
                                                                    ) . basename($dir_attr["path"]);

                if(isset($dir_attr["scan"])) {
                    $scan_type                                      = self::APP_BASE_NAME;
                    $scan_path                                      = str_replace("[LIBS_PATH]", Constant::LIBS_PATH, $dir_attr["path"]);
                    if($scan_path != $dir_attr["path"]) {
                        $scan_type                                  = (strpos($scan_path, Constant::LIBS_FF_PATH) === false
                                                                        ? self::LIBS_NAME
                                                                        : self::LIBS_BASE_NAME
                                                                    );
                    }

                    $scans[$scan_type][$scan_path]                  = $dir_attr["scan"];

                    continue;
                }

                if(Constant::DEBUG && isset($dir_attr["path"]) && !is_dir(DirStruct::$disk_path . $dir_attr["path"]) && !Filemanager::makeDir($dir_attr["path"])) {
                    Error::registerWarning("Faild to Write " . $dir_attr["path"] . " Check permissions", "dirstruct");
                }

                if(isset($dir_attr["webroot"])) {
                    self::$webroot                                  = DirStruct::$disk_path . $dir_attr["path"];

                    continue;
                }

                self::$dirstruct[$dir_key]                          = $dir_attr;
                if(isset(self::$dirstruct[$dir_key]["autoload"]))   {
                    $autoload_path = DirStruct::getDiskPath($dir_key);
                    if($autoload_path)                              { self::$autoloads[] = DirStruct::getDiskPath($dir_key) . "/autoload." . Constant::PHP_EXT; }
                }
            }

            self::$scans = $scans[self::LIBS_BASE_NAME] + $scans[self::LIBS_NAME] + $scans[self::APP_BASE_NAME];
        }
    }

    public static function webRoot() {
        return self::$webroot;
    }
    public static function getDir($name) {
        return (isset(self::$dirstruct[$name])
            ? self::$dirstruct[$name]["path"]
            : ""
        );
    }
    public static function getAutoloads() {
        return self::$autoloads;
    }
    public static function mapping($bucket, $name = null) {
        $extension                                                  = (isset(self::$maps[$bucket])
                                                                        ? self::$maps[$bucket]
                                                                        : null
                                                                    );
        if($name && !isset($extension[$name]))                      { $extension[$name] = null; }

        return ($name
            ? $extension[$name]
            : $extension
        );
    }
    public static function addEngine($bucket, $callback) {
        self::$engine[$bucket]                                      = $callback;

    }
    public static function addRule($key, $method, $callback = null) {
        self::$rules[$key]                                          = array(
                                                                        "method"        => $method
                                                                        , "callback"    => $callback
                                                                    );

    }

    public static function rawData($key = null, $remove = false, $sub_key = null) {
        if($key && !isset( self::$config[$key]))                    { self::$config[$key] = null; }

        $res                                                        = ($key
                                                                        ? self::$config[$key]
                                                                        : self::$config
                                                                    );
        if($remove && $key)                                         { unset(self::$config[$key]); }

        return ($sub_key
            ? $res[$sub_key]
            : $res
        );
    }

    public static function loadRawData($paths = array()) {
        $config_path                                                = DirStruct::$disk_path . "/conf";
        $config                                                     = ($config_path
                                                                        ? array($config_path    => array("filter" => array("xml", "json"), "flag" => Filemanager::SCAN_FILE))
                                                                        : array()
                                                                    );

        $paths                                                      = array_replace(array(Constant::CONFIG_PATH => array("filter" => array("xml", "json"))), $paths, $config);

        //$cache = Mem::getInstance();
 //       $cache->set("test", "test", "mytest/ciao");
        Filemanager::scan($paths, function ($file) {
            switch (pathinfo($file, PATHINFO_EXTENSION)) {
                case "xml":
                    self::loadXml($file);
                    break;
                case "json":
                    self::loadJson($file);
                    break;
                default:
                    Error::registerWarning("Config file Extension not supported", "config");
            }
        });
    }
    private static function loadJson($file) {
        $arrExt = explode("_", pathinfo($file, PATHINFO_FILENAME), 2);
        if(count($arrExt) === 2) {
            self::$maps[$arrExt[0]][$arrExt[1]] = Filemanager::getInstance("json")->read($file);
        }
    }
    private static function loadXml($file) {
        $configs                                                = Filemanager::getInstance("xml")->read($file);
        foreach($configs AS $key => $config) {
            if(!isset(self::$config[$key]))                     { self::$config[$key] = array(); }
            $method                                             = (isset(self::$rules[$key]["method"])
                                                                    ? self::$rules[$key]["method"]
                                                                    : null
                                                                );

            switch ($method) {
                case "replace":
                    self::loadXmlReplace($key, $config);
                    break;
                case "merge":
                    self::loadXmlMerge($key, $config);
                    break;
                case "mergesub":
                    self::loadXmlMergeSub($key, $config);
                    break;
                default:
                    self::$config[$key]                         = $config;
            }
        }
    }
    private static function loadXmlReplace($key, $config) {
        self::$config[$key]                                         = array_replace(self::$config[$key], (array)$config);
    }
    private static function loadXmlMerge($key, $config) {
        if (is_array($config) && count($config)) {
            if (!isset($config[0]))                                 { $config = array($config); }
            self::$config[$key]                                     = array_merge(self::$config[$key], $config);
        }
    }
    private static function loadXmlMergeSub($key, $config) {
        if (is_array($config) && count($config)) {
            foreach ($config AS $sub_key => $sub_config) {
                if (!isset($sub_config[0]))                         { $sub_config = array($sub_config); }
                if(isset(self::$config[$key][$sub_key]))   {
                    self::$config[$key][$sub_key]                   = array_merge(self::$config[$key][$sub_key], $sub_config);
                } else {
                    self::$config[$key][$sub_key]                   = $sub_config;
                }

            }
        }
    }

    public static function setSchema($data, $bucket = null) {
        if(is_array($data)) {
            if($bucket) {
                if(isset(self::$schema[$bucket])) {
                    self::$schema[$bucket]                          = array_replace(self::$schema[$bucket], $data);
                } else {
                    self::$schema[$bucket]                          = $data;
                }
            } else {
                self::$schema                                       = $data;
            }
        }
    }
    public static function getSchema($bucket = null) {
        if($bucket && !isset(self::$schema[$bucket])) {
            self::$schema[$bucket]                                  = array();
            if(isset(self::$config[$bucket])) {
                $callback                                           = (isset(self::$rules[$bucket]["callback"])
                                                                        ? self::$rules[$bucket]["callback"]
                                                                        : null
                                                                    );
                if(is_callable($callback))                          { $callback(); }
            }
        }

        return (array) ($bucket
            ? self::$schema[$bucket]
            : self::$schema
        );
    }
}

