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

/**
 * Class Hook
 * @package ff\libs
 */
class Hook implements Configurable, Dumpable
{
    private const ERROR_BUCKET                                                      = "hook";

    public const HOOK_PRIORITY_HIGH                                                 = 1000;
    public const HOOK_PRIORITY_NORMAL                                               = 100;
    public const HOOK_PRIORITY_LOW                                                  = 10;

    private static $events                                                          = array();

    /**
     * @access private
     * @param dto\ConfigRules $configRules
     * @return dto\ConfigRules
     */
    public static function loadConfigRules(dto\ConfigRules $configRules) : dto\ConfigRules
    {
        return $configRules
            ->add("hooks");
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig(array $config)
    {
        self::$events = $config["events"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema(array $rawdata) : array
    {
        if (!empty($rawdata["hook"])) {
            foreach ($rawdata["hook"] as $hook) {
                $attr                                                               = Dir::getXmlAttr($hook);
                $func                                                               = $attr["obj"] . "::" . $attr["method"];

                if (empty($attr["override"])) {
                    self::register($attr["name"], $func);
                } else {
                    self::registerOnce($attr["name"], $func);
                }
            }
        }

        return [
            "events" => self::$events
        ];
    }

    /**
     * @return array
     */
    public static function dump() : array
    {
        return self::$events;
    }

    /**
     * AddHook
     * @param string $name
     * @param string|callable $func
     * @param int $priority
     */
    public static function register(string $name, $func, int $priority = self::HOOK_PRIORITY_NORMAL) : void
    {
        self::addCallback($name, $func, $priority);
    }

    /**
     * AddHook
     * @param string $name
     * @param string|callable $func
     * @param int $priority
     */
    public static function registerOnce(string $name, $func, int $priority = self::HOOK_PRIORITY_NORMAL) : void
    {
        self::addCallback($name, $func, $priority, true);
    }

    /**
     * @param $name
     * @param string|callable $func
     * @param int $priority
     * @param bool $override
     */
    private static function addCallback($name, $func, int $priority = self::HOOK_PRIORITY_NORMAL, bool $override = false) : void
    {
        if (!isset(self::$events[$name])) {
            self::$events[$name]                                                    = [];
        }

        if ($override) {
            self::$events[$name]                                                    = array($priority => $func);
        } else {
            self::$events[$name][$priority + count(self::$events[$name])]           = $func;
        }
    }

    /**
     * DoHook
     * @todo da tipizzare
     * @param string $name
     * @param mixed|null $ref
     * @param mixed|null $params
     * @return array|null
     */
    public static function handle(string $name, &$ref = null, &$params = null) : ?array
    {
        $res                                                                        = null;

        if (!empty(self::$events[$name])) {
            ksort(self::$events[$name], SORT_NUMERIC);
            foreach (self::$events[$name] as $func) {
                if (!is_callable($func)) {
                    Response::sendErrorPlain($func . " is not callable");
                }
                $res[]                                                              = $func($ref, $params);
            }
        }

        return $res;
    }
}
