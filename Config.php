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

abstract class Config  {
    private static $config = null;
    private static $schema = null;
    private static $engine = null;

    public static function addEngine($bucket, $callback) {
        self::$engine[$bucket]                                      = $callback;

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

    public static function loadRawData($paths, $actions = null) {
        Filemanager::scan($paths, function ($file) use ($actions) {
            $configs                                                = Filemanager::getInstance("xml")->read($file);
            foreach($configs AS $key => $config) {
                switch ($actions[$key]["method"]) {
                    case "replace":
                        self::$config[$key]                        = array_replace((array)self::$config[$key], (array)$config);
                        break;
                    case "merge":
                        if (is_array($config) && count($config)) {
                            if (!isset($config[0]))                 { $config = array($config); }
                            self::$config[$key]                    = array_merge((array)self::$config[$key], $config);
                        }
                        break;
                    case "mergesub":
                        if (is_array($config) && count($config)) {
                            foreach ($config AS $sub_key => $sub_config) {
                                if (!isset($sub_config[0]))         { $sub_config = array($sub_config); }
                                self::$config[$key][$sub_key]      = array_merge((array)self::$config[$key][$sub_key], $sub_config);
                            }
                        }
                        break;
                    default:
                        self::$config[$key]                        = $config;
                }

                self::addEngine($key, $actions[$key]["callback"]);
            }
        });
    }


    public static function setSchema($data, $bucket = null) {
        if(is_array($data)) {
            if($bucket) {
                self::$schema[$bucket]                              = array_replace((array) self::$schema[$bucket], $data);
            } else {
                self::$schema                                       = $data;
            }
        }
    }
    public static function getSchema($bucket) {
        if($bucket && !isset(self::$schema[$bucket])) {
            self::$schema[$bucket]                                  = array();

            $callback                                               = self::$engine[$bucket]["callback"];
            if(is_callable($callback))                              { $callback(); }
        }

        return (array) ($bucket
            ? self::$schema[$bucket]
            : self::$schema
        );
    }
}

