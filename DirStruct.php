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

if(!defined("DOCUMENT_ROOT"))                               { define("DOCUMENT_ROOT", $_SERVER["DOCUMENT_ROOT"]); }
if(!defined("SITE_PATH"))                                   { define("SITE_PATH", str_replace(array(DOCUMENT_ROOT, "/vendor/phpformsframework/libs"), "", __DIR__)); }
if(!defined("CONF_PATH"))                                   { define("CONF_PATH", "/conf"); }
if(!defined("LIBS_PATH"))                                   { define("LIBS_PATH", "/vendor"); }

abstract class DirStruct implements Configurable {
    const PHP_EXT                                                   = "php";
    const SITE_PATH                                                 = SITE_PATH;

    public static $disk_path                                        = DOCUMENT_ROOT . SITE_PATH;
    protected static $dirstruct                                     = array(
                                                                        "libs"              => array(
                                                                            "path"          => LIBS_PATH
                                                                            , "permission"  => ""
                                                                        )
                                                                        , "conf"            => array(
                                                                            "path"          => CONF_PATH
                                                                            , "permission"  => ""
                                                                        )
                                                                    );
    protected static $autoload                                      = array();

    public static function getDiskPath($what = null, $relative = false) {
        $path                                                       = (isset(self::$dirstruct[$what])
                                                                        ? self::$dirstruct[$what]["path"]
                                                                        : $what
                                                                    );
		return ($relative
            ? $path
            : realpath(self::documentRoot() . $path)
        );
	}
	public static function checkDiskPath($abs_path) {
        return strpos(realpath($abs_path), self::$disk_path) === 0;
    }
    public static function getPathInfo($user_path = null) {
        $path_info                                                  = $_SERVER["PATH_INFO"];
        return ($user_path
            ? (strpos($path_info, $user_path) === 0
                ? substr($path_info, strlen($user_path))
                : false
            )
            : $path_info
        );
    }

    public static function loadSchema() {
        $config                                                     = Config::rawData("dirstruct", true);
        if(is_array($config) && count($config)) {
            foreach ($config AS $dir_key => $dir) {
                $dir_attr                                           = self::getXmlAttr($dir);
                self::$dirstruct[$dir_key]                          = $dir_attr;
                if(isset(self::$dirstruct[$dir_key]["autoload"]))   { self::$autoload[] = self::getDiskPath($dir_key) . "/autoload." . self::PHP_EXT; }
            }
        }
    }

    /**
     * @param $path
     * @param bool $once
     * @return bool|mixed
     */
    protected static function autoload($path, $once = false) {
        $rc                                                         = false;
        if(self::checkDiskPath($path)) {
            $rc                                                     = ($once
                                                                        ? require_once ($path)
                                                                        : include($path)
                                                                    );
        }

        return $rc;
    }
    protected static function documentRoot() {
        return self::$disk_path;
    }

    public static function getXmlAttr($item, $key = null) {
        $res = (isset($item["@attributes"])
            ? $item["@attributes"]
            : $item
        );

        return ($key
            ? $res[$key]
            : $res
        );
    }
}