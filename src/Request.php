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

use phpformsframework\libs\dto\RequestPage;
use phpformsframework\libs\dto\RequestPageRules;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\security\Buckler;
use phpformsframework\libs\security\Validator;

class Request implements Configurable, Dumpable
{
    const MAX_SIZE                                                  = array(
                                                                        "GET"       => 256,
                                                                        "PUT"       => 10240,
                                                                        "POST"      => 10240,
                                                                        "HEAD"      => 2048,
                                                                        "DEFAULT"   => 128,
                                                                        "FILES"     => 1024000
                                                                    );
    private static $params                                          = null;
    private static $access_control                                  = null;
    private static $pages                                           = null;
    private static $alias                                           = null;
    private static $gateway                                         = null;
    private static $patterns                                        = null;
    private static $server                                          = null;

    /**
     * @var RequestPage $page
     */
    private static $page                                            = null;

    private static $orig_path_info                                  = null;
    private static $root_path                                       = null;
    private static $path_info                                       = null;

    public static function dump()
    {
        return array(
            "params"            => self::$params,
            "access_control"    => self::$access_control,
            "pages"             => self::$pages,
            "alias"             => self::$alias,
            "gateway"           => self::$gateway,
            "patterns"          => self::$patterns,
        );
    }

    /**
     * @access private
     * @param dto\ConfigRules $configRules
     * @return dto\ConfigRules
     */
    public static function loadConfigRules($configRules)
    {
        return $configRules
            ->add("request")
            ->add("alias")
            ->add("patterns");
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig($config)
    {
        self::$params                                               = $config["params"];
        self::$access_control                                       = $config["access_control"];
        self::$pages                                                = $config["pages"];
        self::$alias                                                = $config["alias"];
        self::$gateway                                              = $config["gateway"];
        self::$patterns                                             = $config["patterns"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema($rawdata)
    {
        self::loadParams($rawdata, self::$params);

        if (isset($rawdata["accesscontrol"])) {
            self::loadAccessControl($rawdata["accesscontrol"]);
        }
        if (isset($rawdata["pages"])) {
            self::loadPages($rawdata["pages"]);
        }
        if (isset($rawdata["domain"])) {
            self::loadDomain($rawdata["domain"]);
        }
        if (isset($rawdata["gateway"])) {
            self::loadGateway($rawdata["gateway"]);
        }
        if (isset($rawdata["pattern"])) {
            self::loadPatterns($rawdata["pattern"]);
        }

        return array(
            "params"                => self::$params,
            "access_control"        => self::$access_control,
            "pages"                 => self::$pages,
            "alias"                 => self::$alias,
            "gateway"               => self::$gateway,
            "patterns"              => self::$patterns
        );
    }

    private static function loadParams($rawdata, &$obj)
    {
        if (isset($rawdata["header"]) && is_array($rawdata["header"]) && count($rawdata["header"])) {
            foreach ($rawdata["header"] as $header) {
                self::loadRequestMapping($obj, Dir::getXmlAttr($header), "header");
            }
        }
        if (isset($rawdata["get"]) && is_array($rawdata["get"]) && count($rawdata["get"])) {
            foreach ($rawdata["get"] as $get) {
                self::loadRequestMapping($obj, Dir::getXmlAttr($get), "query");
            }
        }
        if (isset($rawdata["post"]) && is_array($rawdata["post"]) && count($rawdata["post"])) {
            foreach ($rawdata["post"] as $post) {
                self::loadRequestMapping($obj, Dir::getXmlAttr($post), "body");
            }
        }
    }

    private static function loadRequestMapping(&$obj, $attr, $bucket = "body")
    {
        $key                                                    = (
            isset($attr["scope"])
                ? $attr["scope"] . "."
                : ""
            ) . $attr["name"];

        $obj[$bucket][$key]                                     = $attr;
    }
    private static function loadAccessControl($config)
    {
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            if (is_array($config) && count($config)) {
                foreach ($config as $access_control) {
                    $attr                                       = Dir::getXmlAttr($access_control);
                    $key                                        = $attr["origin"];
                    if (!$key) {
                        continue;
                    }
                    unset($attr["origin"]);
                    if (is_array($attr) && count($attr)) {
                        $schema[$key] = $attr;
                    }
                }
            }

            self::$access_control                                = $schema;
        }
    }
    private static function loadPages($config)
    {
        if (is_array($config) && count($config)) {
            foreach ($config as $page) {
                $attr                                           = Dir::getXmlAttr($page);
                if (isset($attr["path"])) {
                    $key                                        = $attr["path"];
                    self::$pages[$key]                          = null;
                    self::loadParams($page, self::$pages[$key]);
                    self::$pages[$key]["config"]                = $page["config"];
                }
            }
        }
    }

    private static function loadDomain($config)
    {
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            foreach ($config as $domain) {
                $attr                                           = Dir::getXmlAttr($domain);
                $schema[$attr["name"]]                          = $attr["path"];
            }
            self::$alias                                        = $schema;
        }
    }
    private static function loadGateway($config)
    {
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            foreach ($config as $gateway) {
                $attr                                           = Dir::getXmlAttr($gateway);
                $schema[$attr["name"]]                          = $attr["proxy"];
            }

            self::$gateway                                        = $schema;
        }
    }
    private static function loadPatterns($config)
    {
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            foreach ($config as $pattern) {
                $attr                                           = Dir::getXmlAttr($pattern);
                $key                                            = (
                    $attr["path"]
                                                                    ? $attr["path"]
                                                                    : $attr["source"]
                                                                );
                if (!$key) {
                    continue;
                }
                unset($attr["source"]);
                unset($attr["path"]);
                if (is_array($attr) && count($attr)) {
                    $schema[$key]                               = $attr;
                }
            }

            self::$patterns                                     = $schema;
        }
    }

    private static function findPageByPathInfo()
    {
        $page                                                   = array();
        $router                                                 = Router::find(self::$orig_path_info);
        $page_path                                              = rtrim($router["path"], "/");
        if (!$page_path) {
            $page_path = DIRECTORY_SEPARATOR;
        }

        do {
            if (isset(self::$pages[$page_path])) {
                $page                                           = array_replace(self::$pages[$page_path]["config"], $page);
            }
            $page_path                                          = dirname($page_path);
        } while ($page_path != DIRECTORY_SEPARATOR);

        $page["path_info"] = (
            isset($page["strip_path"]) && strpos(self::$path_info, $page["strip_path"]) === 0
            ? substr(self::$path_info, strlen($page["strip_path"]))
            : self::$path_info
        );
        if (!$page["path_info"]) {
            $page["path_info"] = DIRECTORY_SEPARATOR;
        }

        if (is_array(self::$patterns) && count(self::$patterns)) {
            $matches = null;
            foreach (self::$patterns as $pattern => $rule) {
                if (preg_match(Router::regexp($pattern), $page["path_info"], $matches)) {
                    $page = (
                        $router["path"] == $page["path_info"]
                        ? array_replace($rule, $page)
                        : array_replace($page, $rule)
                    );
                }
            }
        }

        $page["rules"]                                      = self::setRulesByPage($page["path_info"]);

        self::$page                                         = new RequestPage($page);
    }

    private static function urlVerify()
    {
        $redirect                                           = null;
        //necessario XHR perche le request a servizi esterni path del domain alias non triggerano piu
        if (self::method() == "GET" && !self::isAjax() && count(self::unknown())) {
            // Evita pagine duplicate quando i link vengono gestiti dagli alias o altro
            $redirect                                       = self::url();
        }

        if ($redirect) {
            Response::redirect($redirect);
        }
    }

    /**
     * @access private
     * @return RequestPage
     */
    public static function &pageConfiguration()
    {
        self::rewritePathInfo();

        self::findPageByPathInfo();

        self::capture();

        Kernel::useCache(!self::$page->nocache);

        if (isset(self::$page->root_path) && self::$page->root_path == self::$root_path) {
            $_SERVER["PATH_INFO"]                           = self::$orig_path_info;
        }

        //@todo: da sistemare
        if (Env::get("REQUEST_SECURITY_LEVEL")) {
            Buckler::protectMyAss();
        }

        if (self::$page->validation) {
            self::urlVerify();
        }
        if (self::$page->log) {
            Log::write(self::rawdata(), self::$page->log);
        }

        return self::$page;
    }


    private static function rewritePathInfo()
    {
        $hostname                                           = self::hostname();
        $aliasname                                          = (
            $hostname && isset(self::$alias[$hostname])
            ? self::$alias[$hostname]
            : null
        );
        $requestURI                                         = self::requestURI();
        $rawQuery                                           = self::rawQuery();
        if ($requestURI) {
            self::$orig_path_info                           = rtrim(rtrim($rawQuery
                ? rtrim($requestURI, $rawQuery)
                : $requestURI, "?"), "/");

            if (Constant::SITE_PATH) {
                self::$orig_path_info                       = str_replace(Constant::SITE_PATH, "", self::$orig_path_info);
            }
        }
        if (!self::$orig_path_info) {
            self::$orig_path_info = "/";
        }

        self::$orig_path_info                               = Locale::setByPath(self::$orig_path_info);

        if ($aliasname) {
            if (strpos(self::$orig_path_info, $aliasname . "/") === 0
                || self::$orig_path_info == $aliasname
            ) {
                $query                                      = (
                    is_array($_GET) && count($_GET)
                    ? "?" . http_build_query($_GET)
                    : ""
                );
                Response::redirect($hostname . substr(self::$orig_path_info, strlen($aliasname)) . $query);
            }

            self::$root_path                                = $aliasname;
        }


        $path_info                                          = rtrim(self::$root_path . self::$orig_path_info, "/");
        if (!$path_info) {
            $path_info = "/";
        }

        $_SERVER["XHR_PATH_INFO"]                           = null;
        $_SERVER["ORIG_PATH_INFO"]                          = self::$orig_path_info;
        $_SERVER["PATH_INFO"]                               = $path_info;


        if (self::isAjax()) {
            $_SERVER["XHR_PATH_INFO"]                       = rtrim(self::$root_path . self::referer(PHP_URL_PATH), "/");
        }

        if (!self::isCommandLineInterface() && self::remoteAddr() == self::serverAddr()) {
            if (isset($_POST["pathinfo"])) {
                $_SERVER["PATH_INFO"]                       = rtrim($_POST["pathinfo"], "/");
                if (!$_SERVER["PATH_INFO"]) {
                    $_SERVER["PATH_INFO"] = "/";
                }

                unset($_POST["pathinfo"]);
            }
            if (isset($_POST["referer"])) {
                $_SERVER["HTTP_REFERER"]                    = $_POST["referer"];
                unset($_POST["referer"]);
            }
            if (isset($_POST["agent"])) {
                $_SERVER["HTTP_USER_AGENT"]                 = $_POST["agent"];
                unset($_POST["agent"]);
            }
            if (isset($_POST["cookie"])) {
                $_COOKIE                                    = $_POST["cookie"];
                unset($_POST["cookie"]);
            }

            if (Kernel::$Environment::DEBUG) {
                register_shutdown_function(function () {
                    $data["pathinfo"] = $_SERVER["PATH_INFO"];
                    $data["error"] = error_get_last();
                    $data["pid"] = getmypid();
                    $data["exTime"] = Debug::exTimeApp();

                    Log::debugging($data, "request", "async");
                });
            }
        }

        self::$path_info                                    = $path_info;
    }

    public static function rawdata($toObj = false)
    {
        return (array) self::body(null, null, $toObj, "rawdata");
    }
    public static function valid($toObj = false)
    {
        return (array) self::body(null, null, $toObj, "valid");
    }
    public static function unknown($toObj = false)
    {
        return (array) self::body(null, null, $toObj, "unknown");
    }
    public static function get($key, $toObj = false)
    {
        return self::body($key, "GET", $toObj, "get");
    }
    public static function post($key, $toObj = false)
    {
        return self::body($key, "POST", $toObj, "post");
    }
    public static function patch($key, $toObj = false)
    {
        return self::body($key, "PATCH", $toObj, "post");
    }
    public static function delete($key, $toObj = false)
    {
        return self::body($key, "DELETE", $toObj, "post");
    }
    public static function put($key, $toObj = false)
    {
        return self::body($key, "PUT", $toObj, "rawdata");
    }

    public static function cookie($key, $toObj = false)
    {
        return self::body($key, "COOKIE", $toObj);
    }
    public static function session($key, $toObj = false)
    {
        return self::body($key, "SESSION", $toObj);
    }
    public static function getModel($scope, $toObj = true)
    {
        return self::body(null, null, $toObj, $scope);
    }
    /**
     * @param bool $with_unknown
     * @return string
     */
    public static function getQuery($with_unknown = false)
    {
        if (!self::$page->request) {
            self::captureBody();
        }

        $res                                                    = array_filter(
            $with_unknown
                                                                    ? self::$page->request["rawdata"]
                                                                    : self::$page->request["valid"]
                                                                );

        return (is_array($res) && count($res)
            ? "?" . http_build_query($res)
            : ""
        );
    }

    private static function capture()
    {
        self::captureServer();

        self::security();

        self::captureHeaders();
        self::captureBody();
    }
    public static function headers($key = null)
    {
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
    private static function body($key = null, $method = null, $toObj = false, $scope = "rawdata")
    {
        $rawdata                                                = self::captureBody($scope, $method);
        if ($key && !isset($rawdata[$key])) {
            $rawdata[$key] = null;
        }

        $res                                                    = (
            $key
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

    /**
     * @param $path_info
     * @return RequestPageRules
     */
    private static function setRulesByPage($path_info)
    {
        $rules                                                  = new RequestPageRules();
        $request_path                                           = $path_info;

        do {
            if (isset(self::$pages[$request_path])) {
                $rules->set(self::$pages[$request_path]);
            }
        } while ($request_path != DIRECTORY_SEPARATOR && $request_path = dirname($request_path));

        return $rules;
    }

    public static function proxy($hostname = null)
    {
        if (!$hostname) {
            $hostname                                           = self::hostname();
        }
        return (isset(self::$gateway[$hostname])
            ? self::$gateway[$hostname]
            : $hostname
        );
    }
    public static function alias($hostname = null)
    {
        if (!$hostname) {
            $hostname                                           = self::hostname();
        }
        return (isset(self::$alias[$hostname])
            ? self::$alias[$hostname]
            : null
        );
    }

    public static function isHTTPS()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER["HTTP_ORIGIN"]) && strpos($_SERVER["HTTP_ORIGIN"], "https://") === 0);
    }
    public static function protocol()
    {
        return (self::isHTTPS() ? "https" : "http") . "://";
    }
    public static function hostname()
    {
        return (isset($_SERVER["HTTP_HOST"])
            ? $_SERVER["HTTP_HOST"]
            : null
        );
    }
    public static function protocol_host()
    {
        return (self::hostname()
            ?  self::protocol() . self::hostname()
            : ""
        );
    }
    public static function pathinfo()
    {
        return (isset($_SERVER["PATH_INFO"])
            ? $_SERVER["PATH_INFO"]
            : DIRECTORY_SEPARATOR
        );
    }
    public static function requestURI()
    {
        return (isset($_SERVER["REQUEST_URI"])
            ? $_SERVER["REQUEST_URI"]
            : null
        );
    }
    public static function rawQuery()
    {
        return (empty($_SERVER["QUERY_STRING"])
            ? null
            : $_SERVER["QUERY_STRING"]
        );
    }
    public static function protocol_host_pathinfo()
    {
        return self::protocol_host() . Constant::SITE_PATH . self::pathinfo();
    }
    public static function url($phpurl_part = null)
    {
        $url                                                    = self::protocol_host_pathinfo() . self::getQuery(false);

        return ($phpurl_part && $url
            ? parse_url($url, $phpurl_part)
            : $url
        );
    }

    public static function referer($phpurl_part = null)
    {
        $referer                                                = (
            isset($_SERVER["HTTP_REFERER"])
                                                                    ? $_SERVER["HTTP_REFERER"]
                                                                    : null
                                                                );

        return ($phpurl_part && $referer
            ? parse_url($referer, $phpurl_part)
            : $referer
        );
    }
    public static function userAgent()
    {
        return (isset($_SERVER["HTTP_USER_AGENT"])
            ? $_SERVER["HTTP_USER_AGENT"]
            : null
        );
    }
    public static function remoteAddr()
    {
        return (isset($_SERVER["REMOTE_ADDR"])
            ? $_SERVER["REMOTE_ADDR"]
            : null
        );
    }
    public static function serverAddr()
    {
        return (isset($_SERVER["SERVER_ADDR"])
            ? $_SERVER["SERVER_ADDR"]
            : null
        );
    }
    public static function method()
    {
        return (isset($_SERVER["REQUEST_METHOD"])
            ? strtoupper($_SERVER["REQUEST_METHOD"])
            : null
        );
    }
    public static function isAjax()
    {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    }

    private static function isCommandLineInterface()
    {
        return (php_sapi_name() === 'cli');
    }

    private static function captureServer()
    {
        self::$server = (
            self::isCommandLineInterface()
            ? null
            : $_SERVER
        );
    }
    private static function server($key)
    {
        return (isset(self::$server[$key])
            ? self::$server[$key]
            : null
        );
    }

    private static function getAccessControl($origin, $key = null)
    {
        $access_control                                         = false;
        if (isset(self::$access_control)) {
            if (isset(self::$access_control[$origin])) {
                $access_control                                 = self::$access_control[$origin];
            } elseif (isset(self::$access_control["*"])) {
                $access_control                                 = self::$access_control["*"];
            }
        }
        return ($key && $access_control
            ? $access_control[$key]
            : $access_control
        );
    }

    private static function CORS_preflight($origin)
    {
        // return only the headers and not the content
        // only allow CORS
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $origin) {
            $access_control = self::getAccessControl($origin);

            if ($access_control) {
                if (!$access_control["allow-methods"]) {
                    $access_control["allow-methods"] = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] . ", OPTIONS";
                }
                if (!$access_control["allow-headers"]) {
                    $access_control["allow-headers"] = (
                        isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])
                        ? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']
                        : "*"
                    );
                }
                if (!$access_control["allow-origin"]) {
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
    private static function security()
    {
        if (headers_sent()) {
            return false;
        }
        if (isset($_SERVER["HTTP_ORIGIN"]) && filter_var($_SERVER["HTTP_ORIGIN"], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $origin = str_replace("https://", "http://", "", $_SERVER["HTTP_ORIGIN"]);
        } else {
            $origin = self::referer(PHP_URL_HOST);
        }

        //todo: remove TRACE request method
        //todo: remove serverSignature
        header_remove("X-Powered-By");
        header("Vary: Accept-Encoding" . ($origin ? ", Origin" : ""));

        switch (self::method()) {
            case "OPTIONS":
                self::CORS_preflight($origin);
                break;
            case "TRACE": //todo: to manage
                Response::sendError(405);
                break;
            case "CONNECT": //todo: to manage
                Response::sendError(405);
                break;
            case "HEAD": //todo: to manage
                self::securityHeaders($origin);
                exit;
                break;
            case "PROPFIND": //todo: to manage
                Response::sendError(405);
                break;
            case "GET":
            case "POST":
            case "PUT":
                self::securityHeaders($origin);
                break;
            case "DELETE": //todo: to manage
                Response::sendError(405);
                break;
            default:
                Response::sendError(405);
        }

        return true;
    }
    private static function securityHeaders($origin)
    {
        $allow_origin = self::getAccessControl($origin, "allow-origin");
        if ($allow_origin) {
            header('Access-Control-Allow-Origin: ' . $allow_origin);
        }
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header("Strict-Transport-Security: max-age=31536000");
        /**
         * @todo: da verificare e implementare
         *
         * header("Content-Security-Policy: default-src 'self' https://cdnjs.cloudflare.com; script-src 'self' https://cdnjs.cloudflare.com; connect-src 'self'; img-src 'self'; style-src 'self' https://cdnjs.cloudflare.com; ");
         * header('Public-Key-Pins: pin-sha256="d6qzRu9zOECb90Uez27xWltNsj0e1Md7GkYYkVoZWmM="; pin-sha256="E9CZ9INDbd+2eRQozYqqbQ2yXLVKB9+xcprMF+44U1g="; max-age=604800; includeSubDomains; report-uri="https://example.net/pkp-report"');
         * header("Referrer-Policy: origin-when-cross-origin");
         * header("Expect-CT: max-age=7776000, enforce");
         */
        self::isInvalidReqMethod(true);
    }
    private static function getRequestHeaders()
    {
        if (function_exists("getallheaders")) {
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

    private static function securityHeaderParams()
    {
        $errors                                                                         = array();
        if (self::isAllowedSize(self::getRequestHeaders(), "HEAD")) {
            foreach (self::$page->rules->header as $rule) {
                $header_key                                                             = str_replace("-", "_", $rule["name"]);
                if ($rule["name"] == "Authorization") {
                    $header_name                                                        = "Authorization";
                } else {
                    $header_name                                                        = "HTTP_" . strtoupper($header_key);
                }

                if (isset($rule["required"]) && !isset($_SERVER[$header_name])) {
                    $errors[400][]                                                      = $rule["name"] . " is required";
                } elseif (isset($rule["required_ifnot"]) && !isset($_SERVER["HTTP_" . strtoupper($rule["required_ifnot"])]) && !isset($_SERVER[$header_name])) {
                    $errors[400][]                                                      = $rule["name"] . " is required";
                } elseif (isset($_SERVER[$header_name])) {
                    $validator_rule                                                     = (
                        isset($rule["validator"])
                        ? $rule["validator"]
                        : null
                    );
                    $validator_range                                                    = (
                        isset($rule["validator_range"])
                        ? $rule["validator_range"]
                        : null
                    );
                    $validator                                                          = Validator::is($_SERVER[$header_name], $validator_rule, array("fakename" => $header_key . " (in header)", "range" => $validator_range));
                    if ($validator->isError()) {
                        $errors[$validator->status][] = $validator->error;
                    }

                    self::$page->headers[$header_key]                                   = $_SERVER[$header_name];
                }
            }
        } else {
            $errors[413][]                                                              = "Headers Max Size Exeeded";
        }

        return $errors;
    }

    private static function securityParams($request, $method)
    {
        $errors                                                                         = array();
        $bucket                                                                         = (
            $method == "GET" || $method == "PUT"
                                                                                            ? "query"
                                                                                            : "body"
                                                                                        );
        if (self::isAllowedSize($request, $method) && self::isAllowedSize(self::getRequestHeaders(), "HEAD")) {
            self::$page->request["valid"]                                               = array();

            if (is_array(self::$page->rules->$bucket) && count(self::$page->rules->$bucket) && is_array($request)) {
                foreach (self::$page->rules->$bucket as $rule) {
                    if (isset($rule["required"]) && $rule["required"] === true && !isset($request[$rule["name"]])) {
                        $errors[400][]                                                  = $rule["name"] . " is required";
                    } elseif (isset($rule["required_ifnot"]) && !isset($_SERVER[$rule["required_ifnot"]]) && !isset($request[$rule["name"]])) {
                        $errors[400][]                                                  = $rule["name"] . " is required";
                    } elseif (isset($request[$rule["name"]])) {
                        $validator_rule                                                 = (
                            isset($rule["validator"])
                                                                                            ? $rule["validator"]
                                                                                            : null
                                                                                        );
                        $validator_range                                                = (
                            isset($rule["validator_range"])
                                                                                            ? $rule["validator_range"]
                                                                                            : null
                                                                                        );

                        $errors                                                         = $errors + self::securityValidation($request[$rule["name"]], $validator_rule, $rule["name"], $validator_range);


                        if (isset($rule["scope"])) {
                            self::$page->request[$rule["scope"]][$rule["name"]]         = $request[$rule["name"]];
                        }
                        if (!isset($rule["hide"]) || $rule["hide"] === false) {
                            self::$page->request["valid"][$rule["name"]]                = $request[$rule["name"]];
                        } else {
                            unset($request[$rule["name"]]);
                        }
                    } else {
                        $request[$rule["name"]]                                         = (
                            isset($rule["default"])
                                                                                            ? $rule["default"]
                                                                                            : null
                                                                                        );
                        self::$page->request["valid"][$rule["name"]]                    = $request[$rule["name"]];
                        if (isset($rule["scope"])) {
                            self::$page->request[$rule["scope"]][$rule["name"]]         = $request[$rule["name"]];
                        }
                    }
                }

                self::$page->request["rawdata"]                                         = $request;
                if ($method == "GET") {
                    self::$page->request["get"]                                         = $request;
                } elseif ($method == "POST" || $method == "PATCH" || $method == "DELETE") {
                    self::$page->request["post"]                                        = $request;
                }
                self::$page->request["unknown"]                                         = array_diff_key($request, self::$page->request["valid"]);
                if (isset(self::$page->request["unknown"]) && is_array(self::$page->request["unknown"]) && count(self::$page->request["unknown"])) {
                    foreach (self::$page->request["unknown"] as $unknown_key => $unknown) {
                        $errors                                                         = $errors + self::securityValidation($unknown, null, $unknown_key);
                    }
                }
            }
        } else {
            $errors[413][]                                                              = "Request Max Size Exeeded";
        }

        return $errors;
    }

    private static function securityValidation(&$value, $type = null, $fakename = null, $range = null)
    {
        $errors                                                                         = array();
        $params                                                                         = array();
        if ($fakename) {
            $params["fakename"]   = $fakename;
        }
        if ($range) {
            $params["range"]      = $range;
        }

        $validator                                                                      = Validator::is($value, $type, $params);
        if ($validator->isError()) {
            $errors[$validator->status][] = $validator->error;
        }


        return $errors;
    }

    private static function securityFileParams()
    {
        $errors                                                                         = array();
        if (is_array($_FILES) && count($_FILES)) {
            foreach ($_FILES as $file_name => $file) {
                if (isset(self::$page->rules->body[$file_name]) && self::$page->rules->body[$file_name]["validator"] != "file") {
                    $errors[400][]                                                      = $file_name . " must be type " . self::$page->rules->body[$file_name]["validator"];
                } else {
                    $validator                                                          = Validator::is($file_name, "file", array("fakename" => $file_name));
                    if ($validator->isError()) {
                        $errors[$validator->status][] = $validator->error;
                    }
                }
            }
        }

        return $errors;
    }

    private static function getReq($method = null)
    {
        switch ($method) {
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

    private static function isAllowedSize($req, $method)
    {
        $request_size                                                                           = strlen((
            is_array($req)
                                                                                                    ? http_build_query($req, '', '')
                                                                                                    : $req
                                                                                                ));

        $max_size                                                                               = static::MAX_SIZE;
        $request_max_size                                                                       = (
            isset($max_size[$method])
                                                                                                    ? $max_size[$method]
                                                                                                    : $max_size["DEFAULT"]
                                                                                                );
        return $request_size < $request_max_size;
    }

    private static function captureHeaders()
    {
        static $last_update                                                                     = 0;

        if ($last_update < self::$page->rules->last_update
            && is_array(self::$page->rules->header) && count(self::$page->rules->header)
        ) {
            $last_update                                                                        = self::$page->rules->last_update;

            $errors                                                                             = self::securityHeaderParams();
            if (is_array($errors) && count($errors)) {
                asort($errors);
                $status = key($errors);

                self::isError(implode(", ", $errors[$status]), $status);
            }
        }

        return self::$page->headers;
    }

    private static function getRequestMethod()
    {
        return self::$page->method;

    }
    /**
     * @param null|string $key
     * @param null|string $method
     * @return null|array
     */
    private static function captureBody($key = null, $method = null)
    {
        static $last_update                                                                     = 0;

        if ($last_update < self::$page->rules->last_update) {
            $last_update                                                                    = self::$page->rules->last_update;

            if (!$method) {
                $method = self::getRequestMethod();
            }

            $request                                                                        = self::getReq($method);
            $errors                                                                         = self::securityParams($request, $method) + self::securityFileParams();

            if (is_array($errors) && count($errors)) {
                asort($errors);
                $status = key($errors);

                self::isError(implode(", ", $errors[$status]), $status);
            } else {
                unset($_REQUEST);
                unset($_GET);
                unset($_POST);
            }
        }

        if ($key && !isset(self::$page->request[$key])) {
            self::$page->request[$key] = null;
        }

        return ($key
            ? self::$page->request[$key]
            : self::$page->request
        );
    }

    private static function isInvalidHTTPS()
    {
        return (self::$page->https && !isset($_SERVER["HTTPS"])
            ? "Request Method Must Be In HTTPS"
            : null
        );
    }
    private static function isInvalidReqMethod($exit = false)
    {
        $error                                                                                  = self::isInvalidHTTPS();

        if (!$error && self::method() != self::$page->method) {
            $error                                                                              = "Request Method Must Be " . self::$page->method
                                                                                                    . (
                                                                                                        self::$page->https && self::referer(PHP_URL_HOST) == self::hostname()
                                                                                                        ? " (Redirect Https may Change Request Method)"
                                                                                                        : ""
                                                                                                    );
        }

        if ($exit && $error) {
            self::isError($error, 405);
        }

        return $error;
    }

    private static function isError($error, $status = 400)
    {
        Response::sendError($status, $error);
    }

    public static function accept()
    {
        $arrAccept = array('*/*');
        if (isset($_SERVER["HTTP_ACCEPT"])) {
            $arrAccept = explode(",", $_SERVER["HTTP_ACCEPT"]);
        }

        return $arrAccept[0];
    }
}
