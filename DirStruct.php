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

define("VENDOR_LIBS_DIR", DIRECTORY_SEPARATOR .  "vendor" . DIRECTORY_SEPARATOR . "phpformsframework" . DIRECTORY_SEPARATOR . "libs");

if(!defined("DOCUMENT_ROOT")) {
    define("DOCUMENT_ROOT", (isset($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"]
        ? $_SERVER["DOCUMENT_ROOT"]
        : str_replace(VENDOR_LIBS_DIR, "", __DIR__))
    );
}

if(!defined("SITE_PATH"))                                   { define("SITE_PATH", str_replace(array(DOCUMENT_ROOT, VENDOR_LIBS_DIR), "", __DIR__)); }
if(!defined("CONF_PATH"))                                   { define("CONF_PATH", "/conf"); }
if(!defined("LIBS_PATH"))                                   { define("LIBS_PATH", "/vendor"); }

abstract class DirStruct {
    const PHP_EXT                                                   = "php";
    const SITE_PATH                                                 = SITE_PATH;

    public static $disk_path                                        = DOCUMENT_ROOT . SITE_PATH;


    public static function getDiskPath($what = null, $relative = false) {
        $path                                                       = Config::getDir($what);
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