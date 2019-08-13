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

class Dir
{
    public static function getDiskPath($what = null, $relative = false)
    {
        $path                                                       = Config::getDir($what);
        if ($path) {
            return ($relative
                ? $path
                : realpath(Constant::DISK_PATH . $path)
            );
        } else {
            return null;
        }
    }
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

    public static function loadFile($path, $context = null)
    {
        return file_get_contents($path, false, $context);
    }


    /**
     * @param array $item
     * @param null|string $key
     * @return mixed
     */
    public static function getXmlAttr($item, $key = null)
    {
        $res = (
            isset($item["@attributes"])
            ? $item["@attributes"]
            : $item
        );

        return ($key
            ? $res[$key]
            : $res
        );
    }
}
