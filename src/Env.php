<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs;

use ff\libs\cache\Buffer;

/**
 * Class Env
 * @package ff\libs
 */
class Env implements Configurable
{
    const CACHE_BUCKET                                              = "env";

    private static $dotenv                                          = array();
    private static $vars                                            = array();
    private static $packages                                        = null;
    private static $permanent                                       = array();


    /**
     * @return array
     */
    public static function &getAll() : array
    {
        return self::$vars;
    }
    /**
     * @param string $key
     * @return mixed|null
     */
    public static function get(string $key)
    {
        return self::$vars[$key] ?? null;
    }

    /**
     * @param string $key
     * @param $value
     * @param bool $permanent
     * @return bool|null
     */
    public static function set(string $key, $value, bool $permanent = false)
    {
        self::$vars[$key]                                            = $value;

        if ($permanent) {
            self::$permanent[$key]                                  = self::$vars[$key];
            $cache                                                  = Buffer::cache(static::CACHE_BUCKET, true);
            $cache->set("permanent", self::$permanent);
        }

        return null;
    }

    /**
     * @param array $values
     * @return array
     */
    public static function fill(array $values) : array
    {
        self::$vars                                                  = array_replace(self::$vars, $values);

        return self::$vars;
    }

    /**
     * @return array|null
     */
    private static function loadPermanent() : array
    {
        $cache                                                      = Buffer::cache(static::CACHE_BUCKET, true);
        $permanent                                                  = $cache->get("permanent");
        if ($permanent) {
            self::$permanent                                        = $permanent;
        }

        return self::$permanent;
    }

    /**
     * @param array $vars
     */
    private static function setGlobalVars(array $vars) : void
    {
        self::$vars                                                 = array_replace($vars, self::loadPermanent());
    }

    /**
     * @access private
     * @param dto\ConfigRules $configRules
     * @return dto\ConfigRules
     */
    public static function loadConfigRules(dto\ConfigRules $configRules) : dto\ConfigRules
    {
        return $configRules
            ->add("env", self::METHOD_REPLACE);
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig(array $config)
    {
        self::$dotenv                                               = $config["dotenv"];
        self::$packages                                             = $config["packages"];

        self::setGlobalVars($config["vars"]);
    }

    /**
     * @access private
     * @param array $rawdata
     * @param string $bucket
     * @return array
     */
    public static function loadSchema(array $rawdata, string $bucket = "default") : array
    {
        if (!empty($rawdata)) {
            if (isset($rawdata[0])) {
                throw new Exception("double tag env declared in config.xml", 500);
            }
            foreach ($rawdata as $key => $value) {
                self::$packages[$bucket][$key]                      = Dir::getXmlAttr($value);

                self::$vars[$key]                                   = self::$packages[$bucket][$key]["value"] ?? null;
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
