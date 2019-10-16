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

use phpformsframework\libs\dto\DataResponse;

/**
 * Class Dir
 * @package phpformsframework\libs
 */
class Dir
{
    /**
     * @param string $what
     * @param bool $relative
     * @return string|null
     */
    public static function findAssetPath(string $what, $relative = false) : ?string
    {
        return self::getDiskPath($what, "assets", $relative);
    }
    /**
     * @param string $what
     * @param bool $relative
     * @return string|null
     */
    public static function findAppPath(string $what, $relative = false) : ?string
    {
        return self::getDiskPath($what, Config::APP_BASE_NAME, $relative);
    }

    /**
     * @param string $what
     * @param bool $relative
     * @return string|null
     */
    public static function findCachePath(string $what, $relative = false) : ?string
    {
        return self::getDiskPath($what, "cache", $relative);
    }

    /**
     * @param string $what
     * @param bool $relative
     * @return string|null
     */
    public static function findViewPath(string $what, $relative = false) : ?string
    {
        return self::getDiskPath($what, "views", $relative);
    }

    /**
     * @param string $what
     * @param string $bucket
     * @param bool $relative
     * @return string|null
     */
    private static function getDiskPath(string $what, string $bucket, $relative = false) : ?string
    {
        $path                                                       = Config::getDir($what, $bucket);
        if ($path) {
            return ($relative
                ? $path
                : realpath(Constant::DISK_PATH . $path)
            );
        } else {
            Error::registerWarning("path not found: " . $what);

            return false;
        }
    }

    /**
     * @param string $abs_path
     * @return bool
     */
    public static function checkDiskPath($abs_path)
    {
        return strpos(realpath($abs_path), Constant::DISK_PATH) === 0;
    }

    /**
     * @param $path
     * @param bool $once
     * @return bool|mixed
     */
    public static function autoload($path, $once = false)
    {
        $rc                                                         = false;
        if (self::checkDiskPath($path)) {
            $rc                                                     = (
                $once
                                                                        ? require_once($path)
                                                                        : include($path)
                                                                    );
        }

        return $rc;
    }

    /**
     * @param string $path
     * @param null|resource $context
     * @return false|string
     */
    public static function loadFile($path, $context = null)
    {
        $res = @file_get_contents($path, false, $context);
        if ($res === false) {
            Error::register("File inaccessible: " . ($path ? $path : "empty"));
        }

        return $res;
    }


    /**
     * @param array $item
     * @param null|string $key
     * @return mixed
     */
    public static function getXmlAttr(array $item, string $key = null)
    {
        Debug::stopWatch("XMLATTR");
        /*return new DataResponse(isset($item["@attributes"])
            ? $item["@attributes"]
            : $item);*/

        $res = (
            isset($item["@attributes"])
            ? $item["@attributes"]
            : $item
        );

        Debug::stopWatch("XMLATTR");

        return ($key
            ? $res[$key]
            : $res
        );
    }

    /**
     * @param array $item
     * @return \ArrayObject
     */
    public static function getXmlAttr2Object(array $item)
    {
        Debug::stopWatch("XMLATTR_OBJ");


        $obj = new \ArrayObject(isset($item["@attributes"])
        ? $item["@attributes"]
        : $item, \ArrayObject::ARRAY_AS_PROPS);

        Debug::stopWatch("XMLATTR_OBJ");

        return $obj;

    }
}
