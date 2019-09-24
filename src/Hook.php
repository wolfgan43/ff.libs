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

class Hook implements Configurable, Dumpable
{
    const HOOK_PRIORITY_HIGH                                                        = 1000;
    const HOOK_PRIORITY_NORMAL                                                      = 100;
    const HOOK_PRIORITY_LOW                                                         = 10;

    private static $events                                                          = null;

    /**
     * @access private
     * @param dto\ConfigRules $configRules
     * @return dto\ConfigRules
     */
    public static function loadConfigRules($configRules)
    {
        return $configRules
            ->add("hooks");
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig($config)
    {
        self::$events = $config["events"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema($rawdata)
    {
        if (isset($rawdata["hook"]) && is_array($rawdata["hook"]) && count($rawdata["hook"])) {
            foreach ($rawdata["hook"] as $hook) {
                $attr                                                               = Dir::getXmlAttr($hook);
                $func                                                               = $attr["obj"] . "::" . $attr["method"];



                self::register($attr["name"], $func);
            }
        }

        return [
            "events" => self::$events
        ];
    }
    public static function dump()
    {
        return self::$events;
    }

    /**
     * AddHook
     * @param $name
     * @param $func
     * @param int $priority
     */
    public static function register($name, $func, $priority = self::HOOK_PRIORITY_NORMAL)
    {
        if (is_callable($func)) {
            Debug::dumpCaller("addHook::" . $name);
            if (!isset(self::$events[$name])) {
                self::$events[$name]                                                = array();
            }
            self::$events[$name][$priority + count((array)self::$events[$name])]    = $func;
        }
    }

    /**
     * DoHook
     * @param $name
     * @param null|$ref
     * @param null $params
     * @return array|null
     */
    public static function handle($name, &$ref = null, $params = null)
    {
        $res                                                                        = null;
        if (isset(self::$events[$name]) && is_array(self::$events[$name])) {
            krsort(self::$events[$name], SORT_NUMERIC);
            foreach (self::$events[$name] as $func) {
                $res[]                                                              = $func($ref, $params);
            }
        }

        return $res;
    }
}
