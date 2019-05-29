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
use DirectoryIterator;

class Env implements Configurable {
    private static $env                                             = array();
    private static $packages                                        = null;

    public static function get($key = null, $value = null) {
        if($key) {
            $ref                                                    = &self::$env[$key];
        } else {
            $ref                                                    = &self::$env;
        }
        if($value !== null) {
            $ref                                                    = $value;
        }

        return $ref;
    }

    public static function getPackage($key = null, $path = null) {
        if(!$path)                                                  { $path = DirStruct::getDiskPath("packages", true); }
        if(!self::$packages && $key === null) {
            $fs                                                     = Filemanager::getInstance("xml");
            $packages                                               = new DirectoryIterator(DirStruct::$disk_path . $path);

            foreach ($packages as $package) {
                if ($package->isDot())                              { continue; }

                $name                                               = $package->getBasename(".xml");
                $xml                                                = $fs->read($package->getPathname());
                self::loadSchema($name, $xml);
            }
        } elseif($key && self::$packages[$key] === null) {
            self::$packages[$key]                                   = false;
            if(is_file(DirStruct::$disk_path . $path . "/" . $key . ".xml")) {
                $xml                                                = Filemanager::getInstance("xml")->read(DirStruct::$disk_path . $path . "/" . $key . ".xml");
                self::loadSchema($key, $xml);
            }
        }

        return ($key
            ? self::$env["packages"][$key]
            : self::$packages
        );
    }

    public static function loadSchema() {
        $config                                                     = Config::rawData("env", true);

        if(is_array($config) && count($config)) {
            foreach ($config as $key => $value) {
                self::$packages["default"][$key]                    = DirStruct::getXmlAttr($value);

                self::$env[$key]                                    = self::$packages["default"][$key]["value"];
            }
        }
    }
}