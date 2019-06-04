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

class Error {
    const TYPE                                                  = "Error";
    const STATUS_CODE                                           = array(
                                                                      100 => "100 Continue"
                                                                    , 101 => "101 Switching Protocols"
                                                                    , 102 => "102 Processing"
                                                                    , 103 => "103 Early Hints"
                                                                    , 200 => "200 OK"
                                                                    , 201 => "201 Created"
                                                                    , 202 => "202 Accepted"
                                                                    , 203 => "203 Non-Authoritative Information"
                                                                    , 204 => "204 No Content"
                                                                    , 205 => "205 Reset Content"
                                                                    , 206 => "206 Partial Content"
                                                                    , 207 => "207 Multi-Status"
                                                                    , 208 => "208 Already Reported"
                                                                    , 226 => "226 IM Used"
                                                                    , 300 => "300 Multiple Choices"
                                                                    , 301 => "301 Moved Permanently"
                                                                    , 302 => "302 Found"
                                                                    , 303 => "303 See Other"
                                                                    , 304 => "304 Not Modified"
                                                                    , 305 => "305 Use Proxy"
                                                                    , 306 => "306 Switch Proxy"
                                                                    , 307 => "307 Temporary Redirect"
                                                                    , 308 => "308 Permanent Redirect"
                                                                    , 400 => "400 Bad Request"
                                                                    , 401 => "401 Unauthorized"
                                                                    , 402 => "402 Payment Required"
                                                                    , 403 => "403 Forbidden"
                                                                    , 404 => "404 Not Found"
                                                                    , 405 => "405 Method Not Allowed"
                                                                    , 406 => "406 Not Acceptable"
                                                                    , 407 => "407 Proxy Authentication Required"
                                                                    , 408 => "408 Request Timeout"
                                                                    , 409 => "409 Conflict"
                                                                    , 410 => "410 Gone"
                                                                    , 411 => "411 Length Required"
                                                                    , 412 => "412 Precondition Failed"
                                                                    , 413 => "413 Payload Too Large"
                                                                    , 414 => "414 URI Too Long"
                                                                    , 415 => "415 Unsupported Media Type"
                                                                    , 416 => "416 Range Not Satisfiable"
                                                                    , 417 => "417 Expectation Failed"
                                                                    , 418 => "418 I'm a teapot"
                                                                    , 421 => "421 Misdirected Request"
                                                                    , 422 => "422 Unprocessable Entity"
                                                                    , 423 => "423 Locked"
                                                                    , 424 => "424 Failed Dependency"
                                                                    , 425 => "425 Too Early"
                                                                    , 426 => "426 Upgrade Required"
                                                                    , 428 => "428 Precondition Required"
                                                                    , 429 => "429 Too Many Requests"
                                                                    , 431 => "431 Request Header Fields Too Large"
                                                                    , 451 => "451 Unavailable For Legal Reasons"
                                                                    , 500 => "500 Internal Server Error"
                                                                    , 501 => "501 Not Implemented"
                                                                    , 502 => "502 Bad Gateway"
                                                                    , 503 => "503 Service Unavailable"
                                                                    , 504 => "504 Gateway Timeout"
                                                                    , 505 => "505 HTTP Version Not Supported"
                                                                    , 506 => "506 Variant Also Negotiates"
                                                                    , 507 => "507 Insufficient Storage"
                                                                    , 508 => "508 Loop Detected"
                                                                    , 510 => "510 Not Extended"
                                                                    , 511 => "511 Network Authentication Required"
                                                                );
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
    public static function getErrorMessage($code) {
        $status_code = self::STATUS_CODE;

        return (isset($status_code[$code])
            ? $status_code[$code]
            : null
        );
    }
    public static function send($code = 404, $template = null) {
        Response::error($code, $template);
    }

    private static function find($path_info) {
        $type                                           = self::findByHost();
        if($type) {
            $rule                                       = array(
                                                            "type"      => $type
                                                            , "path"    => $path_info
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
                                                            "type"          => $rule
                                                            , "base_path"   => $base_path
                                                            , "path"        => substr($path_info, strlen($base_path))
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
        if($bucket && !isset(self::$errors[$bucket])) {
            return null;
        }

        return ($bucket
            ? implode(", ", self::$errors[$bucket])
            : self::$errors
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
            self::$errors[$bucket][]                    = $error;
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

    public static function registerWarning($error, $bucket = null) {
        self::$errors[$bucket][]                        = $error;
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