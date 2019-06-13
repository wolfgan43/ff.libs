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

use phpformsframework\libs\security\Validator;

class Request implements Configurable {
    const TYPE                                                                                  = "request";
    const MAX_SIZE                                                                              = array(
                                                                                                    "GET"       => 256
                                                                                                    , "POST"    => 10240
                                                                                                    , "HEAD"    => 2048
                                                                                                    , "DEFAULT" => 128
                                                                                                    , "FILES"   => 1024000
                                                                                                );
    private static $request                                                                     = null;
    private static $rules                                                                       = null;
    private static $headers                                                                     = null;

    public static function loadSchema() {
        $config                                                                                 = Config::rawData("request", true);
        $schema                                                                                 = array();
        if(is_array($config["page"]) && count($config["page"])) {
            foreach ($config["page"] AS $request) {
                $page_attr                                                                      = DirStruct::getXmlAttr($request);
                $schema                                                                         = self::setSchema($request, $page_attr["path"], $schema);
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
        $schema                                                 = self::setSchema($config, "/", $schema);

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
        if(isset($config["pattern"]))                           { self::loadPatterns($config["pattern"]); }
        if(isset($config["accesscontrol"]))                     { self::loadAccessControl($config["accesscontrol"]); }

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
        if(isset($rawdata["header"]) && is_array($rawdata["header"]) && count($rawdata["header"])) {
            foreach ($rawdata["header"] AS $header) {
                self::setRequestMapping($request, DirStruct::getXmlAttr($header), $path, "header");
            }
        }
        if(isset($rawdata["get"]) && is_array($rawdata["get"]) && count($rawdata["get"])) {
            foreach ($rawdata["get"] AS $get) {
                self::setRequestMapping($request, DirStruct::getXmlAttr($get), $path, "query");
            }
        }
        if(isset($rawdata["post"]) && is_array($rawdata["post"]) && count($rawdata["post"])) {
            foreach ($rawdata["post"] AS $post) {
                self::setRequestMapping($request, DirStruct::getXmlAttr($post), $path, "body");
            }
        }
        return $request;
    }
    private static function setRequestMapping(&$request, $attr, $path, $type = "body") {
        $bucket                                                 = $type;
        $key                                                    = (isset($attr["scope"])
                                                                    ? $attr["scope"] . "."
                                                                    : ""
                                                                ) . $attr["name"];
        $request[$path][$bucket][$key]                          = $attr;
    }

    public static function rawdata($toObj = false) {
        return (array) self::body(null, null, $toObj, "rawdata");
    }
    public static function valid($toObj = false) {
        return (array) self::body(null, null, $toObj, "valid");
    }
    public static function unknown($toObj = false) {
        return (array) self::body(null, null, $toObj, "unknown");
    }
    public static function get($key, $toObj = false) {
        return self::body($key, "GET", $toObj, "get");
    }
    public static function post($key, $toObj = false) {
        return self::body($key, "POST", $toObj, "post");
    }
    public static function patch($key, $toObj = false) {
        return self::body($key, "PATCH", $toObj, "post");
    }
    public static function delete($key, $toObj = false) {
        return self::body($key, "DELETE", $toObj, "post");
    }
    public static function put($key, $toObj = false) {
        return self::body($key, "PUT", $toObj, "rawdata");
    }

    public static function cookie($key, $toObj = false) {
        return self::body($key, "COOKIE", $toObj);
    }
    public static function session($key, $toObj = false) {
        return self::body($key, "SESSION", $toObj);
    }
    public static function getModel($scope, $toObj = true) {
        $res                                                    = self::body(null, null, $toObj, $scope);

        return $res;
    }
    /**
     * @param bool $with_unknown
     * @return string
     */
    public static function getQuery($with_unknown = false) {
        if(!self::$request)                                     { self::captureBody(); }

        $res                                                    = ($with_unknown
                                                                    ? self::$request["rawdata"]
                                                                    : self::$request["valid"]
                                                                );

        return (is_array($res) && count($res)
            ? "?" . http_build_query($res)
            : ""
        );
    }

    public static function capture() {
        self::captureHeaders();
        self::captureBody();
    }
    public static function headers($key = null) {
        $res                                                    = self::captureHeaders();
        return ($key
            ? $res[$key]
            :  $res
        );
    }


    /**
     * @param null|string $key
     * @param null|string $method
     * @param bool $toObj
     * @param string $scope
     * @return array|object|null
     */
    private static function body($key = null, $method = null, $toObj = false, $scope = "rawdata") {
        $rawdata                                                = self::captureBody($scope, $method);
        if($key && !isset($rawdata[$key]))                      { $rawdata[$key] = null; }

        $res                                                    = ($key
                                                                    ? $rawdata[$key]
                                                                    : $rawdata
                                                                );
        return ($toObj && $res
            ? (object) $res
            : $res
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
        $rules                                                  = array();
        $request                                                = Config::getSchema("request");
        $request_path                                           = (isset($page["alias"])
                                                                    ? rtrim($page["alias"] . $page["user_path"], "/")
                                                                    : $page["user_path"]
                                                                );
        if(!$request_path)                                      { $request_path = "/"; }

        $rules["query"]                                         = array();
        $rules["body"]                                          = array();
        $rules["header"]                                        = array();
        $rules["access_control"]                                = Config::getSchema("accesscontrol");

        do {
            if(isset($request[$request_path])) {
                if(isset($request[$request_path]["query"]) && is_array($request[$request_path]["query"])) {
                    $rules["query"]                            = array_replace($request[$request_path]["query"], $rules["query"]);
                }
                if(isset($request[$request_path]["body"]) && is_array($request[$request_path]["body"])) {
                    $rules["body"]                              = array_replace($request[$request_path]["body"], $rules["body"]);
                }
                if(isset($request[$request_path]["header"]) && is_array($request[$request_path]["header"])) {
                    $rules["header"]                            = array_replace((array) $request[$request_path]["header"], $rules["header"]);
                }
            }
        } while($request_path != DIRECTORY_SEPARATOR && $request_path = dirname($request_path));

        $rules["https"]                                         = (isset($page["https"])
                                                                    ? $page["https"]
                                                                    : null
                                                                );
        $rules["method"]                                        = (isset($page["method"])
                                                                    ? strtoupper($page["method"])
                                                                    : self::method()
                                                                );
        self::$rules                                            = $rules;

        self::$rules["last_update"]                             = microtime(true);
    }

    public static function url($pathinfo_part = null) {
        $url                                                    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . DirStruct::SITE_PATH . $_SERVER["PATH_INFO"] . self::getQuery(false);

        return ($pathinfo_part && $url
            ? pathinfo($url, $pathinfo_part)
            : $url
        );
    }

    public static function referer($pathinfo_part = null) {
        $referer                                                = (isset($_SERVER["HTTP_REFERER"])
                                                                    ? $_SERVER["HTTP_REFERER"]
                                                                    : null
                                                                );

        return ($pathinfo_part && $referer
            ? pathinfo($referer, $pathinfo_part)
            : $referer
        );
    }

    public static function method() {
        return strtoupper($_SERVER["REQUEST_METHOD"]);
    }
    public static function isAjax() {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    }



    private static function getAccessControl($origin, $key = null) {
        $access_control = false;
        if(isset(self::$rules["access_control"])) {
            if(isset(self::$rules["access_control"][$origin])) {
                $access_control                                 = self::$rules["access_control"][$origin];
            } elseif (isset(self::$rules["access_control"]["*"])) {
                $access_control                                 = self::$rules["access_control"]["*"];
            }
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
        if (headers_sent()) {
            return false;
        }
        if(isset($_SERVER["HTTP_ORIGIN"]) && filter_var($_SERVER["HTTP_ORIGIN"], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $origin = str_replace("https://", "http://", "", $_SERVER["HTTP_ORIGIN"]);
        } elseif (isset($_SERVER["HTTP_REFERER"])) {
            $origin = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST);
        } else {
            $origin = null;
        }

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
        //header("Content-Security-Policy: default-src 'self' https://cdnjs.cloudflare.com; script-src 'self' https://cdnjs.cloudflare.com; connect-src 'self'; img-src 'self'; style-src 'self' https://cdnjs.cloudflare.com; ");
        //header('Public-Key-Pins: pin-sha256="d6qzRu9zOECb90Uez27xWltNsj0e1Md7GkYYkVoZWmM="; pin-sha256="E9CZ9INDbd+2eRQozYqqbQ2yXLVKB9+xcprMF+44U1g="; max-age=604800; includeSubDomains; report-uri="https://example.net/pkp-report"');
        //header("Referrer-Policy: origin-when-cross-origin");
        //header("Expect-CT: max-age=7776000, enforce");
        self::isInvalidReqMethod(true);
    }
    private static function getRequestHeaders() {
        if(function_exists("getallheaders")) {
            $headers = getallheaders();
        } else {
            $headers = array();
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) <> 'HTTP_') {
                    continue;
                }
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    private static function securityHeaderParams() {
        $errors                                                                         = array();
        if(self::isAllowedSize(self::getRequestHeaders(), "HEAD")) {
            foreach(self::$rules["header"] AS $rule_key => $rule) {
                $header_key                                                             = str_replace("-", "_", $rule["name"]);
                switch($rule["name"]) {
                    case "Authorization":
                        $header_name                                                    = "Authorization";
                        break;
                    default:
                        $header_name                                                    = "HTTP_" . strtoupper($header_key);
                }
                if(isset($rule["required"]) && !isset($_SERVER[$header_name])) {
                    $errors[400][]                                                      = $rule["name"] . " is required";
                } elseif(isset($rule["required_ifnot"]) && !isset($_SERVER["HTTP_" . strtoupper($rule["required_ifnot"])]) && !isset($_SERVER[$header_name])) {
                    $errors[400][]                                                      = $rule["name"] . " is required";
                } elseif(isset($_SERVER[$header_name])) {
                    $validator_rule                                                     = (isset($rule["validator"])
                        ? $rule["validator"]
                        : null
                    );
                    $validator_range                                                    = (isset($rule["validator_range"])
                        ? $rule["validator_range"]
                        : null
                    );
                    $validator                                                          = Validator::is($_SERVER[$header_name], $validator_rule, array("fakename" => $header_key . " (in header)", "range" => $validator_range));
                    if ($validator["status"] !== 0)                                     { $errors[400][] = $validator["error"]; }

                    self::$headers[$header_key]                                         = $_SERVER[$header_name];
                }
            }
        } else {
            $errors[413][]                                                              = "Headers Max Size Exeeded";
        }

        return $errors;
    }

    private static function securityParams($request, $method) {
        $errors                                                                         = array();
        $bucket                                                                         = ($method == "GET" || $method == "PUT"
                                                                                            ? "query"
                                                                                            : "body"
                                                                                        );

        if(self::isAllowedSize($request, $method) && self::isAllowedSize(self::getRequestHeaders(), "HEAD")) {
            //Mapping Request by Rules
            if(is_array(self::$rules[$bucket]) && count(self::$rules[$bucket]) && is_array($request)) {
                self::$request["valid"]                                                 = array();
                foreach(self::$rules[$bucket] AS $rule) {
                    if(isset($rule["required"]) && $rule["required"] === true && !isset($request[$rule["name"]])) {
                        $errors[400][]                                                  = $rule["name"] . " is required";
                    } elseif(isset($rule["required_ifnot"]) && !isset($_SERVER[$rule["required_ifnot"]]) && !isset($request[$rule["name"]])) {
                        $errors[400][]                                                  = $rule["name"] . " is required";
                    } elseif(isset($request[$rule["name"]])) {
                        $validator_rule                                                 = (isset($rule["validator"])
                                                                                            ? $rule["validator"]
                                                                                            : null
                                                                                        );
                        $validator_range                                                = (isset($rule["validator_range"])
                                                                                            ? $rule["validator_range"]
                                                                                            : null
                                                                                        );

                        $errors                                                         = $errors + self::securityValidation($request[$rule["name"]], $validator_rule, $rule["name"], $validator_range);


                        if(isset($rule["scope"])) {
                            self::$request[$rule["scope"]][$rule["name"]]               = $request[$rule["name"]];
                        }
                        if(!isset($rule["hide"]) || $rule["hide"] === false) {
                            self::$request["valid"][$rule["name"]]                      = $request[$rule["name"]];
                        } else {
                            unset($request[$rule["name"]]);
                        }
                    } else {
                        $request[$rule["name"]]                                         = (isset($rule["default"])
                                                                                            ? $rule["default"]
                                                                                            : null
                                                                                        );
                        self::$request["valid"][$rule["name"]]                          = $request[$rule["name"]];
                        if(isset($rule["scope"])) {
                            self::$request[$rule["scope"]][$rule["name"]]               = $request[$rule["name"]];
                        }
                    }
                }

                self::$request["rawdata"]                                               = $request;
                if($method == "GET") {
                    self::$request["get"]                                               = $request;
                } elseif($method == "POST" || $method == "PATCH" || $method == "DELETE") {
                    self::$request["post"]                                              = $request;
                }
                self::$request["unknown"]                                               = array_diff_key($request, self::$request["valid"]);
                if(isset(self::$request["unknown"]) && is_array(self::$request["unknown"]) && count(self::$request["unknown"])) {
                    foreach (self::$request["unknown"] as $unknown_key => $unknown) {
                        $errors                                                         = $errors + self::securityValidation($unknown, null, $unknown_key);
                    }
                }
            }
        } else {
            $errors[413][]                                                              = "Request Max Size Exeeded";
        }

        return $errors;
    }

    private static function securityValidation(&$value, $type = null, $fakename = null, $range = null) {
        $errors                                                                         = array();
        $params                                                                         = array();
        if($fakename)                                                                   { $params["fakename"]   = $fakename; }
        if($range)                                                                      { $params["range"]      = $range; }

        $validator                                                                      = Validator::is($value, $type, $params);
        if(isset($validator["status"]) && $validator["status"] !== 0)                   { $errors[$validator["status"]][] = $validator["error"]; }


        return $errors;
    }

    private static function securityFileParams() {
        $errors                                                                         = array();
        if(is_array($_FILES) && count($_FILES)) {
            foreach ($_FILES as $file_name => $file) {
                if(isset(self::$rules["body"][$file_name]) && self::$rules["body"][$file_name]["validator"] != "file") {
                    $errors[400][]                                                      = $file_name . " must be type " . self::$rules["body"][$file_name]["validator"];
                } else {
                    $validator                                                          = Validator::is($file_name, "file", array("fakename" => $file_name));
                    if($validator["status"] !== 0)                                      { $errors[$validator["status"]][] = $validator["error"]; }
                }
            }
        }

        return $errors;
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
        $request_size                                                                           = strlen((is_array($req)
                                                                                                    ? http_build_query($req, '', '')
                                                                                                    : $req
                                                                                                ));

        $max_size                                                                               = static::MAX_SIZE;
        $request_max_size                                                                       = (isset($max_size[$method])
                                                                                                    ? $max_size[$method]
                                                                                                    : $max_size["DEFAULT"]
                                                                                                );
        return $request_size < $request_max_size;
    }

    private static function captureHeaders() {
        static $last_update                                                                     = 0;

        if(self::$headers === null || $last_update < self::$rules["last_update"]) {
            self::$headers                                                                      = array();
            if(is_array(self::$rules["header"]) && count(self::$rules["header"])) {
                $last_update                                                                    = self::$rules["last_update"];

                $errors                                                                         = self::securityHeaderParams();
                if(is_array($errors) && count($errors)) {
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
            if(self::$request === null || $last_update < self::$rules["last_update"]) {
                self::$request                                                                  = array();
                $last_update                                                                    = self::$rules["last_update"];

                if(!$method)                                                                    { $method = self::$rules["method"]; }
                $request                                                                        = self::getReq($method);
                $errors                                                                         = self::securityParams($request, $method) + self::securityFileParams();

                if(is_array($errors) && count($errors)) {
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
        if($key && !isset(self::$request[$key])) {
            self::$request[$key] = null;
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
            if(self::$rules["method"] && self::method() != self::$rules["method"]) {
                $error                                                                          = "Request Method Must Be " . self::$rules["method"]
                                                                                                    . (self::$rules["https"] && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]) === 0
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