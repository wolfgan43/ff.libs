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
namespace phpformsframework\libs;

use Throwable;

/**
 * Class Exception
 * @package phpformsframework\libs
 */
class Exception extends \Exception
{
    private const SEP                                   = ", ";
    private const ERROR_UNKNOWN                         = "Unknown";
    private const STATUS_CODE                           = array(
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
    private static $errors                              = [];


    /**
     * @param int $code
     * @return string
     */
    public static function getErrorMessage(int $code) : string
    {
        $status_code                                    = self::STATUS_CODE;

        return $status_code[$code] ?? self::ERROR_UNKNOWN;
    }

    /**
     * @param string $error
     * @param string|null $bucket
     */
    public static function warning(string $error, string $bucket = null) : void
    {
        self::$errors[$bucket][]                        = $error;
    }

    /**
     * @param string $bucket
     * @return string|null
     */
    public static function raise(string $bucket) : ?string
    {
        return (isset(self::$errors[$bucket])
            ? implode(self::SEP, self::$errors[$bucket])
            : null
        );
    }
    /**
     * @return array|null
     */
    public static function dump() : array
    {
        return self::$errors;
    }

    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        Debug::setBackTrace(debug_backtrace());
        
        parent::__construct($message, $code, $previous);
    }
}