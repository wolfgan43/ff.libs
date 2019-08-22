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

class Env implements Configurable, Dumpable
{
    private static $env                                             = array();
    private static $packages                                        = null;

    /**
     * @param null|string $key
     * @return mixed|null
     */
    public static function get($key = null)
    {
        if (!isset(self::$env[$key])) {
            self::$env[$key]                                        = null;
        }

        return ($key
            ? self::$env[$key]
            : null
        );
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function set($key, $value)
    {
        self::$env[$key]                                            = $value;

        return self::$env[$key];
    }

    /**
     * @param array $values
     * @return array
     */
    public static function fill($values)
    {
        self::$env                                                  = array_replace(self::$env, $values);

        return self::$env;
    }


    public static function getPackage($key = null)
    {
        $package_disk_path                                          = Dir::getDiskPath("config/packages");
        if (!self::$packages && $key === null) {
            $fs                                                     = Filemanager::getInstance("xml");
            $packages                                               = new DirectoryIterator($package_disk_path);

            foreach ($packages as $package) {
                if ($package->isDot()) {
                    continue;
                }

                $name                                               = $package->getBasename(".xml");
                $xml                                                = $fs->read($package->getPathname());
                self::loadSchema($xml, $name);
            }
        } elseif ($key && self::$packages[$key] === null) {
            self::$packages[$key]                                   = false;

            $xml                                                    = Filemanager::getInstance("xml")->read($package_disk_path . DIRECTORY_SEPARATOR . $key . ".xml");
            self::loadSchema($xml, $key);
        }

        return ($key
            ? self::$env["packages"][$key]
            : self::$packages
        );
    }

    public static function loadConfigRules($configRules)
    {
        return $configRules
            ->add("env", self::METHOD_REPLACE);
    }
    public static function loadConfig($config)
    {
        self::$env                                                  = $config["env"];
        self::$packages                                             = $config["packages"];
    }

    public static function loadSchema($rawdata, $bucket = "default")
    {
        if (is_array($rawdata) && count($rawdata)) {
            foreach ($rawdata as $key => $value) {
                self::$packages[$bucket][$key]                  = Dir::getXmlAttr($value);

                self::$env[$key]                                = self::$packages[$bucket][$key]["value"];
            }
        }

        return array(
            "env"       => self::$env,
            "packages"  => self::$packages,
        );
    }

    public static function dump()
    {
        return self::$env;
    }
}
