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
namespace phpformsframework\libs\security;

use phpformsframework\libs\Configurable;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Error;
use phpformsframework\libs\Log;
use phpformsframework\libs\Request;
use phpformsframework\libs\Response;
use phpformsframework\libs\Config;
use phpformsframework\libs\Debug;

class Buckler implements Configurable
{
    const SCHEMA_BUCKET                                         = "badpath";

    public static function loadSchema()
    {
        Debug::stopWatch("load/buckler");

        $config                                                 = Config::rawData(static::SCHEMA_BUCKET, true, "rule");

        if (is_array($config) && count($config)) {
            $schema                                             = array();
            foreach ($config as $badpath) {
                $attr                                           = Dir::getXmlAttr($badpath);
                $key                                            = $attr["source"];
                unset($attr["source"]);
                $schema[$key]                                   = $attr;
            }

            Config::setSchema($schema, static::SCHEMA_BUCKET);
        }

        Debug::stopWatch("load/buckler");
    }
    public static function protectMyAss()
    {
        self::checkLoadAvg();
        self::checkAllowedPath();
    }
    private static function checkLoadAvg()
    {
        if (function_exists("sys_getloadavg")) {
            $load = sys_getloadavg();
            if ($load[0] > 80) {
                Error::send(503);
                Log::emergency("server busy");
                exit;
            }
        }
    }

    private static function path_info()
    {
        $path_info = null;
        if (isset($_SERVER["REQUEST_URI"])) {
            $path_info =  rtrim(rtrim(isset($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"]
                ? rtrim($_SERVER["REQUEST_URI"], $_SERVER["QUERY_STRING"])
                : $_SERVER["REQUEST_URI"], "?"), "/");
        }

        return $path_info;
    }

    private static function checkAllowedPath()
    {
        $rules                                                  = Config::getSchema(static::SCHEMA_BUCKET);

        $path_info                                              = self::path_info();
        if ($path_info) {
            $matches                                            = array();

            if (is_array($rules) && count($rules)) {
                foreach ($rules as $source => $rule) {
                    $src                                        = self::regexp($source);
                    if (preg_match($src, $path_info, $matches)
                        && (is_numeric($rule["destination"]) || ctype_digit($rule["destination"]))
                    ) {
                        Response::code($rule["destination"]);

                        if (isset($rule["log"])) {
                            Log::write(
                                array(
                                    "RULE"          => $source
                                    , "ACTION"      => $rule["destination"]
                                    , "URL"         => Request::url()
                                    , "REFERER"     => Request::referer()
                                ),
                                "shield",
                                $rule["destination"],
                                "BadPath"
                            );
                        }
                        exit;
                    }
                }
            }
        }
    }

    private static function regexp($rule)
    {
        return "#" . (
            strpos($rule, "[") === false && strpos($rule, "(") === false && strpos($rule, '$') === false
                ? str_replace("\*", "(.*)", preg_quote($rule, "#"))
                : $rule
            ) . "#i";
    }

    /**
     * @todo da fare
     * private static function antiFlood()
     * {
     * }
     */
}
