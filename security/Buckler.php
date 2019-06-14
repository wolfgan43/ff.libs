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
use phpformsframework\libs\DirStruct;
use phpformsframework\libs\Error;
use phpformsframework\libs\Log;
use phpformsframework\libs\Request;
use phpformsframework\libs\Response;
use phpformsframework\libs\Config;

class Buckler implements Configurable {
    public static function loadSchema() {
        $config                                                 = Config::rawData("badpath", true, "rule");

        if(is_array($config) && count($config)) {
            $schema                                             = array();
            foreach($config AS $badpath) {
                $attr                                           = DirStruct::getXmlAttr($badpath);
                $key                                            = $attr["source"];
                unset($attr["source"]);
                $schema[$key]                                   = $attr;
            }

            Config::setSchema($schema, "badpath");
        }
    }
    public static function protectMyAss() {
        self::checkLoadAvg();
        self::checkAllowedPath();
    }
    private static function checkLoadAvg() {
        if(function_exists("sys_getloadavg")) {
            $load = sys_getloadavg();
            if ($load[0] > 80) {
                Error::send(503);
                Log::emergency("server busy");
                //Logs::write($_SERVER, "error_server_busy");
                exit;
            }
        }
    }
    private static function checkAllowedPath($path_info = null, $do_redirect = true) {
        $rules                                              = Config::getSchema("badpath");

        $path_info                                          = ($path_info
                                                                ? $path_info
                                                                : Request::url(PHP_URL_PATH)
                                                            );
        $matches                                            = array();

        if(is_array($rules) && count($rules)) {
            foreach($rules AS $source => $rule) {
                $src                                        = self::regexp($source);
                if(preg_match($src, $path_info, $matches)) {
                    if(is_numeric($rule["destination"]) || ctype_digit($rule["destination"])) {
                        Response::code($rule["destination"]);

                        if(isset($rule["log"])) {
                            Log::write(
                                array(
                                    "RULE"          => $source
                                    , "ACTION"      => $rule["destination"]
                                    , "URL"         => Request::url()
                                    , "REFERER"     => Request::referer()
                                )
                                , "shield"
                                , $rule["destination"]
                                , "BadPath"
                            );
                        }
                        exit;
                    } elseif($do_redirect && $rule["destination"]) {
                        $redirect                           = $rule["destination"];
                        if(strpos($src, "(") !== false && strpos($rule["destination"], "$") !== false) {
                            $redirect                       = preg_replace($src, $rule["destination"], $path_info);
                        }

                        Response::redirect($_SERVER["HTTP_HOST"] . $redirect);
                    }
                }
            }
        }

        return $path_info;
    }
    private static function antiFlood() { //todo: da fare

    }
    private static function regexp($rule) {
        return "#" . (strpos($rule, "[") === false && strpos($rule, "(") === false && strpos($rule, '$') === false
                ? str_replace("\*", "(.*)", preg_quote($rule, "#"))
                : $rule
            ) . "#i";
    }
}

