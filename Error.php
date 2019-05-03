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

use phpformsframework\libs\storage\Media;

class Error {
    const TYPE                                                  = "Error";

    private static $errors                                      = null;
    private static $rules                                       = array(
                                                                    "path" => array(
                                                                        "/static"   => "media"
                                                                        , "/media"  => "media"
                                                                    )
                                                                    , "host" => array(
                                                                        "static."   => "media"
                                                                        , "media."  => "media"
                                                                    )
                                                                );
    //todo: da finalizzare
    public static function addRules($rules) {
        if(is_array($rules) && count($rules)) {
            foreach($rules AS $rule => $params) {
                self::addRule($rule, $params);

            }
        }

    }
    public static function addRule($source, $destination, $priority = null, $redirect = false) {

    }

    public static function send($code = 404, $template = null) {
        Response::error($code, $template);
    }

    private static function find($path_info) {
        $type                                           = self::findByHost();
        if($type) {
            $rule                                       = array(
                                                            "type" => $type
                                                            , "path" => $path_info
                                                        );
        } else {
            $rule                                       = self::findByPath($path_info);
        }

        return $rule;
    }

    private static function findByHost($host_name = null) {
        $res                                            = false;
        if(is_array(self::$rules["host"]) && count(self::$rules["host"])) {
            $arrHost                                    = explode(".", ($host_name
                                                            ? $host_name
                                                            : $_SERVER["HTTP_HOST"]
                                                        ));

            $res                                        = isset(self::$rules["host"][$arrHost[0]]);
        }
        return $res;

    }

    private static function findByPath($path_info) {
        $rule                                           = null;
        $res                                            = false;
        if(is_array(self::$rules["path"]) && count(self::$rules["path"])) {
            $base_path                                  = $path_info;
            if($base_path) {
                do {
                    $base_path                          = dirname($base_path);
                    if(isset(self::$rules["path"][$base_path])) {
                        $rule                           = self::$rules["path"][$base_path];
                        break;
                    }
                } while($base_path != DIRECTORY_SEPARATOR);

                if($rule) {
                    $res                                = array(
                                                            "type" => $rule
                                                            , "base_path" => $base_path
                                                            , "path" => substr($path_info, strlen($base_path))
                                                        );
                }
            }
        }


        return $res;
    }

    public static function run($path_info) {
        $rule                                           = self::find($path_info);

        if($rule) { //todo:: da finire la gestione con hook e inizializzazione delle casistiche
            //self::doHook("on_error_" . $rule["type"], $path_info);

            switch ($rule["type"]) {
                case "media":
                    self::send("404");
                    break;
                    break;
                case "html":
                    self::send("403");
                    break;
                default:
                    self::send("404");
            }
        }

        exit;
    }

    public static function check($bucket = null) {
        return (bool) self::raise($bucket);
    }
    public static function raise($bucket = null) {
        return ($bucket === false
            ? self::$errors
            : self::$errors[$bucket]
        );
    }
    public static function clear($bucket = null) {
        if($bucket === false) {
            self::$errors                               = null;
        } else {
            self::$errors[$bucket]                      = null;
        }
    }
    public static function register($error, $bucket = null) {
        if($error) {
            self::$errors[$bucket]                      = $error;
            if(Debug::ACTIVE) {
                Debug::dump($error);
                exit;
            } else {
                Log::critical($error);
            }
        }

        return ($bucket
            ? self::$errors[$bucket]
            : self::$errors
        );
    }
    public static function transfer($from_bucket, $to_bucket) {
        Error::register(Error::raise($from_bucket), $to_bucket);
    }
    public static function dump($errdes, $errno = E_USER_ERROR, $context = NULL, $variables = NULL) {
        ErrorHandler::raise($errdes, $errno, $context, $variables);
    }

    public static function handler($errno, $errstr, $errfile, $errline)
    {
//todo: da portare l'handler del framework qui
    }
}