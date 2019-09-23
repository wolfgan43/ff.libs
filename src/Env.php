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

use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\storage\Filemanager;
use DirectoryIterator;

class Env implements Configurable
{
    const CACHE_BUCKET                                              = "env";

    private static $dotenv                                          = array();
    private static $vars                                            = array();
    private static $packages                                        = null;
    private static $permanent                                       = array();

    private static function loadDotEnv()
    {
        $file                                                       = file_get_contents(
            Constant::DISK_PATH . '/.env' .
            (
                isset(self::$vars["ENVIRONMENT"])
                ? "." . self::$vars["ENVIRONMENT"]
                : ""
            )
        );
        $arrEnv                                                     = array();

        $envString                                                  = str_replace(array("\r\n", "\n\n", "\n", '"true"', '"false"', '"null"'), array("\n", "&", "&", 'true', 'false', 'null'), $file);
        parse_str($envString, $arrEnv);

        self::$dotenv                                               = $arrEnv;

        return self::$dotenv;
    }

    /**
     * @param null|string $key
     * @return mixed|null
     */
    public static function get($key = null)
    {
        if ($key && !isset(self::$vars[$key])) {
            self::$vars[$key]                                       = null;
        }

        return ($key
            ? self::$vars[$key]
            : self::$vars
        );
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param bool $permanent
     * @return mixed
     */
    public static function set($key, $value, $permanent = false)
    {
        self::$vars[$key]                                            = $value;

        if ($permanent) {
            self::$permanent[$key]                                  = self::$vars[$key];
            $cache                                                  = Mem::getInstance(static::CACHE_BUCKET, true);
            $cache->set("permanent", self::$permanent);
        }

        return self::$vars[$key];
    }

    /**
     * @param array $values
     * @return array
     */
    public static function fill($values)
    {
        self::$vars                                                  = array_replace(self::$vars, $values);

        return self::$vars;
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
            ? self::$vars["packages"][$key]
            : self::$packages
        );
    }

    private static function loadPermanent()
    {
        $cache                                                      = Mem::getInstance(static::CACHE_BUCKET, true);
        $permanent                                                  = $cache->get("permanent");
        if ($permanent) {
            self::$permanent                                        = $permanent;
        }

        return self::$permanent;
    }

    private static function setGlobalVars($vars)
    {
        self::$vars                                                 = $vars + self::loadPermanent();
    }

    /**
     * @param dto\ConfigRules $configRules
     * @return dto\ConfigRules
     */
    public static function loadConfigRules($configRules)
    {
        return $configRules
            ->add("env", self::METHOD_REPLACE);
    }
    public static function loadConfig($config)
    {
        self::$dotenv                                               = $config["dotenv"];
        self::$packages                                             = $config["packages"];

        self::setGlobalVars($config["vars"]);
    }

    public static function loadSchema($rawdata, $bucket = "default")
    {
        //self::loadDotEnv();

        if (is_array($rawdata) && count($rawdata)) {
            foreach ($rawdata as $key => $value) {
                self::$packages[$bucket][$key]                      = Dir::getXmlAttr($value);

                self::$vars[$key]                                   = self::$packages[$bucket][$key]["value"];
            }
        }
        $vars                                                       = self::$dotenv + self::$vars;

        self::setGlobalVars($vars);

        return array(
            "dotenv"                => self::$dotenv,
            "vars"                  => $vars,
            "packages"              => self::$packages
        );
    }
}
