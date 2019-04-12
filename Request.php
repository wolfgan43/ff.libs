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

use phpformsframework\libs\international\Translator;

class Request {
    const TYPE                                                                                  = "request";
    const MAX_SIZE                                                                              = array(
                                                                                                    "GET"       => 256
                                                                                                    , "POST"    => 10240
                                                                                                    , "HEAD"    => 1024
                                                                                                    , "DEFAULT" => 128
                                                                                                    , "FILES"   => 1024000
                                                                                                );
    private static $request                                                                     = null;
    private static $rules                                                                       = null;
    private static $headers                                                                     = null;

    public static function loadSchema() {
        $config                                                                                 = Config::rawData("request", true);
        $schema                                                                                 = Config::getSchema("request");
        if(is_array($config["page"]) && count($config["page"])) {
            foreach ($config["page"] AS $request) {
                $page_attr                                                                      = DirStruct::getXmlAttr($request);

                $schema = self::setSchema($request, $page_attr["path"], $schema);
                /*if(is_array($request["header"]) && count($request["header"])) {
                    foreach($request["header"] AS $header) {
                        self::setRequestMapping($schema, DirStruct::getXmlAttr($header), $page_attr["path"], true);
                    }
                }
                if(is_array($request["get"]) && count($request["get"])) {
                    foreach($request["get"] AS $get) {
                        self::setRequestMapping($schema, DirStruct::getXmlAttr($get), $page_attr["path"]);
                    }

                }*/
                 /*else if($page_attr["get"]) {
                    $schema["request"][$page_attr["path"]] = true;
                }*/

            }
        }
        $schema = self::setSchema($config, "/", $schema);

       /* if(is_array($config["header"]) && count($config["header"])) {
            foreach ($config["header"] AS $header) {
                self::setRequestMapping($schema, DirStruct::getXmlAttr($header), "/", true);
            }
        }
        if(is_array($config["get"]) && count($config["get"])) {
            foreach ($config["get"] AS $get) {
                self::setRequestMapping($schema, DirStruct::getXmlAttr($get), "/");
            }
        }*/
        self::loadPatterns($config["pattern"]);
        self::loadAccessControl($config["accesscontrol"]);

        Config::setSchema($schema, "request");
    }

    private static function loadAccessControl($config) {
        if(is_array($config) && count($config)) {
            $schema                                             = Config::getSchema("accesscontrol");
            if(is_array($config) && count($config)) {
                foreach ($config AS $accesscontrol) {
                    $attr                                       = DirStruct::getXmlAttr($accesscontrol);
                    $key                                        = $attr["origin"];
                    if(!$key)                                   { continue; }
                    unset($attr["origin"]);
                    if(is_array($attr) && count($attr))         { $schema[$key] = $attr; }
                }
            }

            Config::setSchema($schema, "accesscontrol");
        }
    }
    private static function loadPatterns($config) {
        if(is_array($config) && count($config)) {
            $schema                                             = Config::getSchema("patterns");
            foreach ($config AS $pattern) {
                $attr                                           = DirStruct::getXmlAttr($pattern);
                $key                                            = ($attr["path"]
                                                                    ? $attr["path"]
                                                                    : $attr["source"]
                                                                );
                if(!$key)                                       { continue; }
                unset($attr["source"]);
                unset($attr["path"]);
                if(is_array($attr) && count($attr))             { $schema[$key] = $attr; }
            }

            Config::setSchema($schema, "patterns");
        }
    }
    public static function setSchema($rawdata, $path, $request = array()) {
        if(is_array($rawdata["header"]) && count($rawdata["header"])) {
            foreach ($rawdata["header"] AS $header) {
                self::setRequestMapping($request, DirStruct::getXmlAttr($header), $path, true);
            }
        }
        if(is_array($rawdata["get"]) && count($rawdata["get"])) {
            foreach ($rawdata["get"] AS $get) {
                self::setRequestMapping($request, DirStruct::getXmlAttr($get), $path);
            }
        }
        return $request;
    }
    private static function setRequestMapping(&$request, $attr, $path, $isHeader = false) {
        $bucket                                     = ($isHeader
                                                        ? "header"
                                                        : "body"
                                                    );
        $key                                        = (isset($attr["scope"])
                                                        ? $attr["scope"] . "."
                                                        : ""
                                                    ) . $attr["name"];
        $request[$path][$bucket][$key]    = $attr;
    }

    public static function get($key = null, $toArray = false) {
        return self::body($key, $toArray);
    }
    public static function post($key = null, $toArray = false) {
        return self::body($key, $toArray, "post");
    }
    public static function cookie($key = null, $toArray = false) {
        return self::body($key, $toArray, "cookie");
    }
    public static function session($key = null, $toArray = false) {
        return self::body($key, $toArray, "session");
    }
    public static function headers($key = null) {
        $res                                                                                    = self::captureHeaders();
        return ($key
            ? $res[$key]
            :  $res
        );
    }

    /**
     * @param null|string $key
     * @param bool $toArray
     * @param null|string $method
     * @return array|null|object
     */
    private static function body($key = null, $toArray = false, $method = null) {
        if(!$key)                                                                               { $toArray = true; }

        $res                                                                                    = self::captureBody($key, $method);
        return ($toArray
            ? $res
            : (object) $res
        );
    }
    /**
     * @param
     *
     * $rules = array(
                        "header"            => ""
                        , "body"            => ""
                        , "last_update"     => ""
                        , "method"          => ""
                        , "exts"            => ""
                        , "navigation"      => ""
                        , "select"          => ""
                        , "default"         => ""
                        , "order"           => ""
                     );
     */

    public static function setRulesByPage($page) {
        $rules                                              = array();
        $request                                            = Config::getSchema("request");
        $request_path                                       = rtrim($page["alias"] . $page["user_path"], "/");
        if(!$request_path)                                  { $request_path = "/"; }

        $rules["body"]                                      = array();
        $rules["header"]                                    = array();
        $rules["access_control"]                            = Config::getSchema("accesscontrol");

        do {
            if(isset($request[$request_path])) {
                $rules["body"]                              = array_replace((array) $request[$request_path]["body"], $rules["body"]);
                $rules["header"]                            = array_replace((array) $request[$request_path]["header"], $rules["header"]);
            }
        } while($request_path != DIRECTORY_SEPARATOR && $request_path = dirname($request_path));

        $rules["https"]                                     = $page["https"];
        $rules["method"]                                    = ($page["method"]
                                                                ? strtoupper($page["method"])
                                                                : $_SERVER["REQUEST_METHOD"]
                                                            );
        self::$rules                                                                            = $rules;

        self::$rules["last_update"]                                                             = microtime(true);
    }

    public static function isAjax() {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    }

    /**
     * @param bool $with_unknown
     * @return string
     */
    public static function getQuery($with_unknown = false) {
        if(!self::$request)                                                                     { self::captureBody(); }

        $res                                                                                    = ($with_unknown
                                                                                                    ? self::$request["rawdata"]
                                                                                                    : self::$request["valid"]
                                                                                                );

        return http_build_query($res);
    }

    private static function getAccessControl($origin, $key = null) {
        $access_control = false;
        if(isset(self::$rules["access_control"])) {
            $access_control = (isset(self::$rules["access_control"][$origin])
                ? self::$rules["access_control"][$origin]
                : self::$rules["access_control"]["*"]
            );
        }
        return ($key && $access_control
            ? $access_control[$key]
            : $access_control
        );
    }

    private static function CORS_prefight($origin) {
        // return only the headers and not the content
        // only allow CORS
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $origin) {
            $access_control = self::getAccessControl($origin);

            if($access_control) {
                if(!$access_control["allow-methods"]) {
                    $access_control["allow-methods"] = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] . ", OPTIONS";
                }
                if(!$access_control["allow-headers"]) {
                    $access_control["allow-headers"] = (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])
                        ? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']
                        : "*"
                    );
                }
                if(!$access_control["allow-origin"]) {
                    $access_control["allow-origin"] = "*";
                }
                if (isset($access_control["allow-credentials"]) && $access_control["allow-origin"] != "*") {
                    header('Access-Control-Allow-Credentials: true');
                }

                header('Access-Control-Allow-Methods: ' . $access_control["allow-methods"]); //... GET, HEAD, POST, PUT, DELETE, CONNECT, OPTIONS, TRACE, PATCH
                header('Access-Control-Allow-Origin: ' . $access_control["allow-origin"]);
                header('Access-Control-Allow-Headers: ' . $access_control["allow-headers"]);
                header('Access-Control-Max-Age: 3600');
                header("Content-Type: text/plain");

            }
        }
        exit;
    }
    private static function security() {
        /*if (!headers_sent()) {
            foreach (headers_list() as $header)
                header_remove(explode(":", $header)[0]);
        }*/
        $origin = (isset($_SERVER["HTTP_ORIGIN"]) && filter_var($_SERVER["HTTP_ORIGIN"], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            ? str_replace("https://", "http://", "", $_SERVER["HTTP_ORIGIN"])
            : parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST)
        );
        //todo: remove TRACE request method
        //todo: remove serverSignature
        header_remove("X-Powered-By");
        header("Vary: Accept-Encoding" . ($origin ? ", Origin" : ""));

        switch ($_SERVER['REQUEST_METHOD']) {
            case "OPTIONS":
                self::CORS_prefight($origin);
                break;
            case "TRACE": //todo: to manage
                Response::error(405);
                break;
            case "CONNECT": //todo: to manage
                Response::error(405);
                break;
            case "HEAD": //todo: to manage
                self::securityHeaders($origin);
                exit;
                break;
            case "PROPFIND": //todo: to manage
                Response::error(405);
                break;
            case "GET":
            case "POST":
            case "PUT":
                self::securityHeaders($origin);
                break;
            case "DELETE": //todo: to manage
                Response::error(405);
                break;
            default:
                Response::error(405);
        }

        return true;
    }
    private static function securityHeaders($origin) {
        $allow_origin = self::getAccessControl($origin, "allow-origin");
        if($allow_origin) {
            header('Access-Control-Allow-Origin: ' . $allow_origin);
        }
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header("Strict-Transport-Security: max-age=31536000");
        header("Content-Security-Policy: default-src 'none'; script-src 'self'; connect-src 'self'; img-src 'self'; style-src 'self';");
        //header('Public-Key-Pins: pin-sha256="d6qzRu9zOECb90Uez27xWltNsj0e1Md7GkYYkVoZWmM="; pin-sha256="E9CZ9INDbd+2eRQozYqqbQ2yXLVKB9+xcprMF+44U1g="; max-age=604800; includeSubDomains; report-uri="https://example.net/pkp-report"');
        //header("Referrer-Policy: origin-when-cross-origin");
        //header("Expect-CT: max-age=7776000, enforce");
        self::isInvalidReqMethod(true);
    }
    private static function getReq($method = null) {
        switch($method) {
            case "POST":
            case "PATCH":
            case "DELETE":
                $req                                                                            = $_POST;
                break;
            case "GET":
                $req                                                                            = $_GET;
                break;
            case "COOKIE":
                $req                                                                            = $_COOKIE;
                break;
            case "SESSION":
                $req                                                                            = $_SESSION;
                break;
            case "PUT":
            default:
                $req                                                                            = $_REQUEST;

        }

        return $req;
    }

    private static function isAllowedSize($req, $method) {
        $request_size                                                                           = strlen(implode("", array_keys($req))
                                                                                                    . implode("", $req)
                                                                                                );
        $request_max_size                                                                       = (isset(self::MAX_SIZE[$method])
                                                                                                    ? self::MAX_SIZE[$method]
                                                                                                    : self::MAX_SIZE["DEFAULT"]
                                                                                                );

        return $request_size < $request_max_size;
    }

    private static function captureHeaders() {
        static $last_update                                                                     = null;

        if(!self::$headers || $last_update < self::$rules["last_update"]) {
            if(is_array(self::$rules["header"]) && count(self::$rules["header"])) {
                $errors                                                                         = null;
                if(self::isAllowedSize(getallheaders(), "HEAD")) {
                    foreach(self::$rules["header"] AS $rule_key => $rule) {
                        $header_key                                                                 = str_replace("-", "_", $rule["name"]);
                        switch($rule["name"]) {
                            case "Authorization":
                                $header_name                                                        = "Authorization";
                                break;
                            default:
                                $header_name                                                        = "HTTP_" . strtoupper($header_key);
                        }
                        if($rule["required"] && !$_SERVER[$header_name]) {
                            $errors[400][]                                                               = $rule["name"] . " is required";
                        } elseif(isset($_SERVER[$header_name])) {
                            $validator                                                              = Validator::is($_SERVER[$header_name], $rule["validator"], array("fakename" => $header_key . " (in header)", "range" => $rule["validator_range"]));
                            if ($validator["status"] !== 0)                                         { $errors[400][] = $validator["error"]; }

                            self::$headers[$header_key]                                             = $_SERVER[$header_name];
                        }
                    }
                } else {
                    $errors[413][]                                                                  = "Headers Max Size Exeeded";
                }

                if($errors) {
                    asort($errors);
                    $status = key ($errors);

                    self::isError(implode(", ", $errors[$status]), $status);
                }
            }
        }

        return self::$headers;
    }

    /**
     * @param null|string $key
     * @param null|string $method
     * @return null|array
     */
    private static function captureBody($key = null, $method = null) {
        static $last_update                                                                     = 0;

        if(self::security()) {
            if(!self::$request || $last_update < self::$rules["last_update"]) {
                $errors                                                                         = null;

                if(!$method)                                                                    { $method = self::$rules["method"]; }
                $request                                                                        = self::getReq($method);

                if(self::isAllowedSize($request, $method) && self::isAllowedSize(getallheaders(), "HEAD")) {
                    //Mapping Request by Rules
                    if(is_array(self::$rules["body"]) && count(self::$rules["body"]) && is_array($request) && count($request)) {
                        foreach(self::$rules["body"] AS $rule) {
                            if($rule["required"] && !isset($request[$rule["name"]])) {
                                $errors[400][]                                                  = $rule["name"] . " is required";
                            } elseif(isset($request[$rule["name"]])) {
                                $validator                                                      = Validator::is($request[$rule["name"]], $rule["validator"], array("fakename" => $rule["name"], "range" => $rule["validator_range"]));
                                if($validator["status"] !== 0)                                  { $errors[$validator["status"]][] = $validator["error"]; }

                                if($rule["scope"]) {
                                    self::$request[$rule["scope"]][$rule["name"]]               = $request[$rule["name"]];
                                }
                                if(!$rule["hide"]) {
                                    self::$request["valid"][$rule["name"]]                      = $request[$rule["name"]];
                                } else {
                                    unset($request[$rule["name"]]);
                                }
                            }
                        }

                        self::$request["rawdata"]                                               = $request;
                        self::$request["unknown"]                                               = array_diff_key($request, self::$request["valid"]);

                        if(is_array(self::$request["unknown"]) && count(self::$request["unknown"])) {
                            foreach (self::$request["unknown"] as $unknown_key => $unknown) {
                                $validator                                                      = Validator::is($unknown, null, array("fakename" => $unknown_key));
                                if($validator["status"] !== 0)                                  { $errors[$validator["status"]][] = $validator["error"]; }
                            }
                        }
                    }

                    if(is_array($_FILES) && count($_FILES)) {
                        foreach ($_FILES as $file_name => $file) {
                            if(isset(self::$rules["body"][$file_name]) && self::$rules["body"][$file_name]["validator"] != "file") {
                                $errors[400][]                                                  = $file_name . " must be type " . self::$rules["body"][$file_name]["validator"];
                            } else {
                                $validator                                                      = Validator::is($file_name, "file", array("fakename" => $file_name));
                                if($validator["status"] !== 0)                                  { $errors[$validator["status"]][] = $validator["error"]; }
                            }
                        }
                    }
                } else {
                    $errors[413][]                                                              = "Request Max Size Exeeded";
                }

                if($errors) {
                    asort($errors);
                    $status = key ($errors);

                    self::isError(implode(", ", $errors[$status]), $status);
                } else {
                    unset($_REQUEST);
                    unset($_GET);
                    unset($_POST);
                }
            }
        }

        return ($key
            ? self::$request[$key]
            : self::$request
        );
    }

    private static function isInvalidHTTPS() {
        return (self::$rules["https"] && !$_SERVER["HTTPS"]
            ? "Request Method Must Be In HTTPS"
            : null
        );
    }
    private static function isInvalidReqMethod($exit = false) {

        $error                                                                                  = self::isInvalidHTTPS();
        if(!$error) {
            if(self::$rules["method"] && $_SERVER["REQUEST_METHOD"] != self::$rules["method"]) {
                $error                                                                          = "Request Method Must Be " . self::$rules["method"]
                                                                                                    . (self::$rules["https"] && strpos($_SERVER["HTTP_REFERER"], "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]) === 0
                                                                                                        ? " (Redirect Https may Change Request Method)"
                                                                                                        : ""
                                                                                                    );
            }
        }

        if($exit && $error)                                                                     { self::isError($error, 405); }

        return $error;
    }

    private static function isError($error, $status = 400) {
        Response::send(array(
            "status" => $status
            , "error" => $error
        ));
    }
}

class Response {
     public static function error($status = 404, $response = null, $headers = null, $type = null) {
        self::send($response, $headers, $type, $status);
    }
    public static function send($response = null, $headers = null, $type = null, $status = null) {
        Log::request($response["error"], $response["status"], strlen($response["data"]));

        if(!$status) {
            $status = (isset($response["status"])
                ? $response["status"]
                : 200
            );
        }

        Response::code($status);
        if (is_array($headers) && count($headers)) {
            foreach ($headers AS $header) {
                header($header);
            }
        }

        if(!$type) {
            switch ($_SERVER["HTTP_ACCEPT"]) {
                case "application/xml":
                case "text/xml":
                    $type = "xml";
                    break;
                case "application/json":
                case "text/json":
                    $type = "json";
                    break;
                case "application/soap+xml":
                    $type = "soap";
                    break;
                case "text/html":
                    $type = "html";
                    break;
                default:
            }
            if(!$type) {
                if (is_array($response)) {
                    if (isset($response["html"])) {
                        $type = "html";
                    } else {
                        $type = "json";
                    }
                } else {
                    $type = "text";
                }
            }
        }

        if(isset($response["status"]))                                                      { $response["status"] = (int) $response["status"]; }
        if($response["error"])                                                              { $response["error"] = Translator::get_word_by_code($response["error"]); }
        switch($type) {
            case "xml":
                header("Content-type: application/xml");
                echo $response;
                break;
            case "soap":
                header("Content-type: application/soap+xml");
                //todo: self::soap_client($response["url"], $response["headers"], $response["action"], $response["data"], $response["auth"]);
                break;
            case "html":
                header("Content-type: text/html");
                echo '<!DOCTYPE html>
                    <html>
                    <head>
                        <script defer async>' . $response["data"]["js"] . '</script>
                        <style type="text/css">' . $response["data"]["css"] . '</style>
                    </head>
                    <body>
                    ' . $response["data"]["html"] . '
                    </body>
                    </html>';
                break;
            case "text":
                header("Content-type: text/plain");
                echo $response;
                break;
            case "json":
            default:
                header("Content-type: application/json");
                echo json_encode((array) $response);
        }

        exit;
    }

    public static function redirect($destination, $http_response_code = null, $headers = null)
    {
        if($http_response_code === null) {
            $http_response_code = 301;
        }
        Log::write("REFERER: " . $_SERVER["HTTP_REFERER"], "redirect", $http_response_code, $destination);

        self::sendHeaders(array(
            "cache" => "must-revalidate"
        ));

        if(strpos($destination, "/") !== 0 && strpos($destination, "http") !== 0) {
            $destination = "http" . ($_SERVER["HTTPS"] ? "s" : "") . "://" . $destination;
        }
        if("http" . ($_SERVER["HTTPS"] ? "s": "") . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] != $destination) {
            header("Location: " . $destination, true, $http_response_code);
            if(is_array($headers) && count($headers)) {
                foreach ($headers AS $key => $value) {
                    header(ucfirst(str_replace(array(" ", "_"), "-", $key)) . ": " . $value);
                }
            }
        } else {
            Response::code(400);
        }

        exit;
    }

    public static function code($code = null) {
         return ($code
             ? http_response_code($code)
             : http_response_code()
         );

    }
    public static function sendHeaders($params = null) {
        //header_remove();
        $days                       = 7;

        $keep_alive			        = $params["keep_alive"]		    ? $params["keep_alive"]			: false;
        $max_age				    = $params["max_age"]            ? $params["max_age"]            : null;
        $expires				    = $params["expires"]            ? $params["expires"]            : null;
        $compress			        = $params["compress"]           ? $params["compress"]           : false;
        $cache					    = $params["cache"]			    ? $params["cache"]				: "public";
        $disposition			    = $params["disposition"]		? $params["disposition"]		: "inline";
        $filename			        = $params["filename"]           ? $params["filename"]           : null;
        $mtime			            = $params["mtime"]              ? $params["mtime"]              : null;
        $mimetype			        = $params["mimetype"]		    ? $params["mimetype"]			: null;
        $size				        = $params["size"]               ? $params["size"]               : null;
        $etag				        = $params["etag"]				? $params["etag"]				: true;

        if($size)                   { header("Content-Length: " . $size); }
        if(strlen($etag))           { header("ETag: " . $etag); }

        if($mimetype) {
            $content_type = $mimetype;
            if ($mimetype == "text/css" || $mimetype == "application/x-javascript") {
                header("Vary: Accept-Encoding");
            } elseif ($mimetype == "text/html") {
                $content_type .= "; charset=UTF-8";
                header("Vary: Accept-Encoding");
            } elseif (strpos($mimetype, "image/") === 0) {
                $days = 365;
            }

            header("Content-type: $content_type");
        }

        if($disposition) {
            $content_disposition = $disposition;
            if ($filename) {
                $content_disposition .= "; filename=" . rawurlencode($filename);
            }
            header("Content-Disposition: " . $content_disposition);
        }


        if($keep_alive) {
            header("Connection: Keep-Alive");
        }
        if($compress) {
            header("Content-encoding: " . ($compress === true ? "gzip" : $compress));
        }

        switch($cache) {
            case "no-store":
                header('Cache-Control: no-store');
                header("Pragma: no-cache");
                break;
            case "no-cache":
                header('Cache-Control: no-cache');
                header("Pragma: no-cache");
                break;
            case "must-revalidate":
                $expires = time() - 1;
                $exp_gmt = gmdate("D, d M Y H:i:s", $expires) . " GMT";

                header("Expires: $exp_gmt");
                header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                header("Pragma: no-cache");
                break;
            case "public":
            default:
                if($expires === false && $max_age === false) {
                    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                    header("Pragma: no-cache");
                } else {
                    if ($expires !== false && $expires !== null) {
                        if ($expires < 0) {
                            $expires = time() - $expires;
                        }
                        $exp_gmt = gmdate("D, d M Y H:i:s", $expires) . " GMT";
                        header("Expires: $exp_gmt");
                    }

                    if ($max_age !== false) {
                        if($mtime) {
                            $mod_gmt = gmdate("D, d M Y H:i:s", $mtime) . " GMT";
                            header("Last-Modified: $mod_gmt");
                        }
                        if ($max_age === null) {
                            //$max_age = 60 * 60 * $hours;
                            $max_age = 60 * 60 * 24 * $days;
                            header("Cache-Control: public, max-age=$max_age");
                        }
                        else {
                            header("Cache-Control: public, max-age=$max_age");
                        }
                    }
                }

                header("Pragma: !invalid");
        }
    }
}