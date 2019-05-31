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
    private static $config                                          = null;
    private static $schema                                          = null;
    private static $engine                                          = null;
    private static $rules                                           = null;
    private static $autoloads                                       = array();
    private static $dirstruct                                       = array(
                                                                        "libs"              => array(
                                                                            "path"          => LIBS_PATH
                                                                            , "permission"  => ""
                                                                        )
                                                                        , "conf"            => array(
                                                                            "path"          => CONF_PATH
                                                                            , "permission"  => ""
                                                                        )
                                                                    );
    public static function dump() {
        return array(
            "config"        => self::$config
            , "schema"      => self::$schema
            , "engine"      => self::$engine
            , "rules"       => self::$rules
            , "dirstruct"   => self::$dirstruct
            , "autoloads"   => self::$autoloads
        );
    }
    public static function loadDirStruct() {
        $config                                                     = self::rawData("dirstruct", true);
        if(is_array($config) && count($config)) {
            foreach ($config AS $dir_key => $dir) {
                $dir_attr                                           = DirStruct::getXmlAttr($dir);
                self::$dirstruct[$dir_key]                          = $dir_attr;
                if(isset(self::$dirstruct[$dir_key]["autoload"]))   {
                    $autoload_path = DirStruct::getDiskPath($dir_key);
                    if($autoload_path) {
                        self::$autoloads[] = DirStruct::getDiskPath($dir_key) . "/autoload." . DirStruct::PHP_EXT;
                    }
                }
            }
        }
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

    public static function loadRawData($paths) {
        $paths                                                      = array_replace(array(__DIR__ => array("filter" => array("xml"))), $paths);

        Filemanager::scan($paths, function ($file) {
            $configs                                                = Filemanager::getInstance("xml")->read($file);
            foreach($configs AS $key => $config) {
                if(!isset(self::$config[$key]))                     { self::$config[$key] = array(); }
                $method                                             = (isset(self::$rules[$key]["method"])
                                                                        ? self::$rules[$key]["method"]
                                                                        : null
                                                                    );

                switch ($method) {
                    case "replace":
                        self::$config[$key]                         = array_replace(self::$config[$key], (array)$config);
                        break;
                    case "merge":
                        if (is_array($config) && count($config)) {
                            if (!isset($config[0]))                 { $config = array($config); }
                            self::$config[$key]                     = array_merge(self::$config[$key], $config);
                        }
                        break;
                    case "mergesub":
                        if (is_array($config) && count($config)) {
                            foreach ($config AS $sub_key => $sub_config) {
                                if (!isset($sub_config[0]))         { $sub_config = array($sub_config); }
                                if(isset(self::$config[$key][$sub_key]))   {
                                    self::$config[$key][$sub_key]   = array_merge(self::$config[$key][$sub_key], $sub_config);
                                } else {
                                    self::$config[$key][$sub_key]   = $sub_config;
                                }

                            }
                        }
                        break;
                    default:
                        self::$config[$key]                         = $config;
                }
            }
        });
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
                                                                        : null //ucfirst($bucket) . "::loadSchema"
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

