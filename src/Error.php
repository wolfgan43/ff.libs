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

use Exception;
use phpformsframework\libs\util\ServerManager;

/**
 * Class Error
 * @package phpformsframework\libs
 */
class Error
{
    use ServerManager;

    private const ERROR_UNKNOWN                                 = "Unknown";
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
    private static $errors                                      = array();
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

    /**
     * @param array $rules
     */
    public static function addRules(array $rules) : void
    {
        if (!empty($rules)) {
            if (isset($rules["hosts"])) {
                foreach ($rules["hosts"] as $source => $engine) {
                    self::addRule($source, $engine, "host");
                }
            }
            if (isset($rules["paths"])) {
                foreach ($rules["paths"] as $source => $engine) {
                    self::addRule($source, $engine, "host");
                }
            }
        }
    }

    /**
     * @param string $source
     * @param array $engine
     * @param string $type
     */
    public static function addRule(string $source, array $engine, string $type = "path") : void
    {
        self::$rules[$type][$source]                            = $engine;
    }

    /**
     * @param int $code
     * @return string|null
     */
    public static function getErrorMessage(int $code) : ?string
    {
        $status_code                                            = self::STATUS_CODE;

        return (isset($status_code[$code])
            ? $status_code[$code]
            : self::ERROR_UNKNOWN
        );
    }

    /**
     * @param string $path_info
     * @return array|null
     */
    private static function find(string $path_info) : ?array
    {
        $type                                           = self::findByHost();
        if ($type) {
            $rule                                       = array(
                                                            "type"      => $type
                                                            , "path"    => $path_info
                                                        );
        } else {
            $rule                                       = self::findByPath($path_info);
        }

        return $rule;
    }

    /**
     * @param string|null $host_name
     * @return string|null
     */
    private static function findByHost(string $host_name = null) : ?string
    {
        $res                                            = null;
        if (!empty(self::$rules["host"])) {
            $arrHost                                    = explode(".", (
                $host_name
                                                            ? $host_name
                                                            : self::hostname()
                                                        ));
            if (isset(self::$rules["host"][$arrHost[0]])) {
                $res                                    = self::$rules["host"][$arrHost[0]];
            }
        }
        return $res;
    }

    /**
     * @param string $path_info
     * @return array|null
     */
    private static function findByPath(string $path_info) : ?array
    {
        $rule                                           = null;
        $res                                            = null;
        if (!empty(self::$rules["path"])) {
            $base_path                                  = $path_info;
            if ($base_path) {
                do {
                    $base_path                          = dirname($base_path);
                    if (isset(self::$rules["path"][$base_path])) {
                        $rule                           = self::$rules["path"][$base_path];
                        break;
                    }
                } while ($base_path != DIRECTORY_SEPARATOR);

                if ($rule) {
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

    /**
     * @param string $path_info
     * @throws Exception
     */
    public static function run(string $path_info) : void
    {
        $rule                                           = self::find($path_info);

        Hook::handle("on_error_process", $path_info);

        if ($rule) {
            switch ($rule["type"]) {
                case "media":
                    Response::sendError("404");
                    break;
                case "html":
                    Response::sendError("403");
                    break;
                default:
                    Response::sendError("404");
            }
        }

        exit;
    }

    /**
     * @param string|null $bucket
     * @return bool
     */
    public static function check(string $bucket = null) : bool
    {
        return (bool) self::raise($bucket);
    }

    /**
     * @param string $bucket
     * @return string|null
     */
    public static function raise(string $bucket) : ?string
    {
        return (isset(self::$errors[$bucket])
            ? implode(", ", self::$errors[$bucket])
            : null
        );
    }

    /**
     * @param string|null $bucket
     */
    public static function clear(string $bucket = null) : void
    {
        if ($bucket === false) {
            self::$errors                               = null;
        } else {
            self::$errors[$bucket]                      = null;
        }
    }

    /**
     * @param string $error
     * @param string|null $bucket
     * @throws Exception
     */
    public static function register(string $error, string $bucket = null) : void
    {
        if ($error) {
            self::$errors[$bucket][]                    = $error;

            Log::alert($error);
            Response::sendError(500, $error);
        }
    }

    /**
     * @param string $error
     * @param string|null $bucket
     */
    public static function registerWarning(string $error, string $bucket = null) : void
    {
        self::$errors[$bucket][]                        = (
            is_array($error)
                                                            ? print_r($error, true)
                                                            : $error
                                                        );
    }

    /**
     * @param string $from_bucket
     * @param string $to_bucket
     * @throws Exception
     */
    public static function transfer(string $from_bucket, string $to_bucket) : void
    {
        if (self::check($from_bucket)) {
            self::register(self::raise($from_bucket), $to_bucket);
        }
    }

    /**
     * @return array|null
     */
    public static function dump() : array
    {
        return self::$errors;
    }
}
