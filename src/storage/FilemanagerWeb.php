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
namespace phpformsframework\libs\storage;

use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Request;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\util\AdapterManager;
use phpformsframework\libs\util\Normalize;
use phpformsframework\libs\Exception;
use stdClass;

/**
 * Class FilemanagerWeb
 * @package phpformsframework\libs\storage
 */
class FilemanagerWeb implements Dumpable
{
    use AdapterManager;

    private const MAX_REQUEST_SIZE                                      = 255;

    private const ERROR_FILE_FORBIDDEN                                  = "failed to open stream: check verify ssl connection also";
    private const ERROR_FILE_EMPTY                                      = "response is empty";

    private static $cache                                               = null;

    /**
     * @return array
     */
    public static function dump() : array
    {
        return array(
            "loaded"           => self::$cache["request"]
        );
    }

    /**
     * @param string $url
     * @param array $params
     * @param string $method
     * @param int $timeout
     * @param string|null $user_agent
     * @param array|null $cookie
     * @param string|null $username
     * @param string|null $password
     * @param array $headers
     * @return string
     * @throws Exception
     */
    public static function fileGetContents(string $url, array $params = [], string $method = Request::METHOD_POST, int $timeout = 10, string $user_agent = null, array $cookie = null, string $username = null, string $password = null, array $headers = []) : string
    {
        $key                                        = self::normalizeUrlAndParams($method, $url, $params);
        if (($length = strlen($url)) > self::MAX_REQUEST_SIZE) {
            throw new Exception("Request GET too large: " . $length . " (limit is " . self::MAX_REQUEST_SIZE . " chars) url: " . $url, 500);
        }
        $context                                    = self::streamContext($params, $method, $timeout, $user_agent, $cookie, $username, $password, $headers);
        $location                                   = self::getUrlLocation($url);

        self::$cache["request"][$key]               = $location;
        self::$cache["response"][$location][$key]   = self::loadFile($url, $context);

        return self::$cache["response"][$location][$key];
    }

    /**
     * @param string $url
     * @param array $params
     * @param string $method
     * @param int $timeout
     * @param string|null $user_agent
     * @param array|null $cookie
     * @param string|null $username
     * @param string|null $password
     * @param array $headers
     * @return stdClass
     * @throws Exception
     * @todo da tipizzare
     */
    public static function fileGetContentsJson(string $url, array $params = [], string $method = Request::METHOD_POST, int $timeout = 10, string $user_agent = null, array $cookie = null, string $username = null, string $password = null, array $headers = []) : stdClass
    {
        $rawdata                                    = self::fileGetContents($url, $params, $method, $timeout, $user_agent, $cookie, $username, $password, $headers);
        if ($rawdata === "") {
            return new stdClass();
        }

        $res                                        = json_decode($rawdata);
        if (json_last_error() != JSON_ERROR_NONE) {
            Debug::set($rawdata, $method . "::response " . $url . "::rawdata");
            throw new Exception("Response is not a valid JSON (detail in response::rawdata): " . json_last_error_msg(), 406);
        }

        return $res;
    }

    /**
     * @param string $url
     * @param array $params
     * @param string $method
     * @param int $timeout
     * @param string|null $user_agent
     * @param array|null $cookie
     * @param string|null $username
     * @param string|null $password
     * @param array $headers
     * @return array|null
     * @throws Exception
     */
    public static function fileGetContentsWithHeaders(string $url, array $params = [], string $method = Request::METHOD_POST, int $timeout = 10, string $user_agent = null, array $cookie = null, string $username = null, string $password = null, array $headers = []) : ?array
    {
        $response_headers                           = array();
        $key                                        = self::normalizeUrlAndParams($method, $url, $params);
        $context                                    = self::streamContext($params, $method, $timeout, $user_agent, $cookie, $username, $password, $headers);
        $location                                   = self::getUrlLocation($url);

        self::$cache["request"][$key]               = $location;
        self::$cache["response"][$location][$key]   = self::loadFile($url, $context, $response_headers);

        return array(
            "headers" => self::parseResponseHeaders($response_headers),
            "content" => self::$cache["response"][$location][$key]
        );
    }

    /**
     * @param string $filename
     * @param string $data
     * @return false
     */
    public static function filePutContents(string $filename, string $data) : bool
    {
        if (!Dir::checkDiskPath(dirname($filename))) {
            return false;
        }

        return file_put_contents($filename, $data);
    }

    /**
     * @param string $url
     * @param array $params
     * @return array
     */
    public static function getQueryByUrl(string &$url, array $params = []) : array
    {
        $url_params                         = array();
        if (strpos($url, "?") !== false) {
            $query                          = explode("?", $url, 2);
            $url                            = $query[0];
            if (!empty($query[1])) {
                parse_str($query[1], $url_params);
            }
        }
        return array_replace($params, $url_params);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @return string
     */
    private static function normalizeUrlAndParams(string $method, string &$url, array &$params = []) : string
    {
        $params                             = self::getQueryByUrl($url, $params);
        $key                                = $url;
        if (!empty($params)) {
            $key                            .= "?" . http_build_query($params);
            if ($method != Request::METHOD_POST) {
                $url                        = $key;
                $params                     = [];
            }
        }
        $location                           = (
            strpos($url, "http") === 0
            ? strtoupper($method) . ":"
            : ""
        );
        return $location . $key;
    }

    /**
     * @param string $url
     * @return string
     */
    private static function getUrlLocation(string $url) : string
    {
        return (
            strpos($url, "http") === 0
            ? "remote"
            : "local"
        );
    }

    /**
     * @param array $headers
     * @return array
     */
    private static function parseResponseHeaders(array $headers) : array
    {
        $head                               = array();
        foreach ($headers as $v) {
            $t                              = explode(':', $v, 2);
            if (isset($t[1])) {
                $head[trim($t[0])]          = trim($t[1]);
            } else {
                $head[]                     = $v;
                if (preg_match("#HTTP/[0-9.]+\s+([0-9]+)#", $v, $out)) {
                    $head['response_code']  = intval($out[1]);
                }
            }
        }

        return $head;
    }

    /**
     * @param string $path
     * @param resource|null $context
     * @param array|null $headers
     * @return string|null
     * @throws Exception
     */
    private static function loadFile(string $path, $context = null, array &$headers = null) : ?string
    {
        if (Validator::is($path, $path, "url")->isError() && !Dir::checkDiskPath($path)) {
            return "";
        }

        $content                            = @file_get_contents($path, false, $context);
        if ($content === false) {
            throw new Exception(error_get_last()["message"] ?? self::ERROR_FILE_FORBIDDEN, 406);
        } elseif(empty($content) && strpos($http_response_header[0] ?? "", " 204 ") === false) {
            throw new Exception(self::ERROR_FILE_EMPTY . " " . $http_response_header[0], 406);
        }

        Normalize::removeBom($content);
        if (isset($http_response_header) && isset($headers)) {
            $headers                        = $http_response_header;
        }


        return $content;
    }

    /**
     * @param array $params
     * @param string $method
     * @param int $timeout
     * @param string|null $user_agent
     * @param array|null $cookie
     * @param string|null $username
     * @param string|null $password
     * @param array $headers
     * @return resource
     */
    private static function streamContext(array $params = [], string $method = Request::METHOD_POST, int $timeout = 60, string $user_agent = null, array $cookie = null, string $username = null, string $password = null, array $headers = [])
    {
        if (!$username) {
            $username                       = Kernel::$Environment::HTTP_AUTH_USERNAME;
        }
        if (!$password) {
            $password                       = Kernel::$Environment::HTTP_AUTH_SECRET;
        }
        if (!$method) {
            $method                         = Request::METHOD_POST;
        }

        $headers                            = (
            !empty($headers)
            ? array_combine(array_keys($headers), explode("&", str_replace("=", ": ", urldecode(http_build_query($headers)))))
            : []
        );

        if ($username) {
            $headers["Authorization"]       = "Authorization: Basic " . base64_encode($username . ":" . $password);
        }

        if ($cookie) {
            $headers["Cookie"]              = "Cookie: " . http_build_query($cookie, '', '; ');
        }

        $opts = array(
            'ssl'                           => array(
                "verify_peer" 		        => Kernel::$Environment::SSL_VERIFYPEER,
                "verify_peer_name" 	        => Kernel::$Environment::SSL_VERIFYPEER
            ),
            'http'                          => array(
                'method'  			        => $method,
                'timeout'  			        => $timeout,
                'ignore_errors'             => true
            )
        );

        if ($method == Request::METHOD_POST) {
            $content_type                   = "Content-Type: application/x-www-form-urlencoded";
            foreach ($headers as $header_key => $header_value) {
                if (strtolower($header_key) == "content-type") {
                    $content_type           = $header_value;
                    unset($headers[$header_key]);
                    break;
                }
            }

            $headers["Content-Type"]        = $content_type;
            $opts["http"]["content"]        = (
                strpos($headers["Content-Type"], "json") === false
                ? http_build_query($params)
                : json_encode($params)
            );
        }

        if ($user_agent) {
            $opts['http']['user_agent']     = $user_agent;
        }

        if (!empty($headers)) {
            $opts['http']['header']         = implode("\r\n", $headers);
        }

        /** @todo da implementare per gestire il trasgerimento dei file

               define('MULTIPART_BOUNDARY', '--------------------------'.microtime(true));

               $header = 'Content-Type: multipart/form-data; boundary='.MULTIPART_BOUNDARY;
               // equivalent to <input type="file" name="uploaded_file"/>
               define('FORM_FIELD', 'uploaded_file');

               $filename = "/path/to/uploaded/file.zip";
               $file_contents = file_get_contents($filename);

               $content =  "--".MULTIPART_BOUNDARY."\r\n".
                   "Content-Disposition: form-data; name=\"".FORM_FIELD."\"; filename=\"".basename($filename)."\"\r\n".
                   "Content-Type: application/zip\r\n\r\n".
                   $file_contents."\r\n";

               // add some POST fields to the request too: $_POST['foo'] = 'bar'
               $content .= "--".MULTIPART_BOUNDARY."\r\n".
                   "Content-Disposition: form-data; name=\"foo\"\r\n\r\n".
                   "bar\r\n";

               // signal end of request (note the trailing "--")
               $content .= "--".MULTIPART_BOUNDARY."--\r\n";

               $context = stream_context_create(array(
                   'http' => array(
                       'method' => 'POST',
                       'header' => $header,
                       'content' => $content,
                   )
               ));


               file_get_contents('http://url/to/upload/handler', false, $context);
        */

        return stream_context_create($opts);
    }
}
