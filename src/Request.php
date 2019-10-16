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

/**
 * Class Request
 * @package phpformsframework\libs
 */
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

    /**
     * @return array
     */
    public static function dump() : array
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
    public static function loadConfig(array $config)
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
    public static function loadSchema(array $rawdata) : array
    {
        self::loadParams($rawdata, self::$params);

        if (isset($rawdata["accesscontrol"]) && is_array($rawdata["accesscontrol"])) {
            self::loadAccessControl($rawdata["accesscontrol"]);
        }
        if (isset($rawdata["pages"]) && is_array($rawdata["pages"])) {
            self::loadPages($rawdata["pages"]);
        }
        if (isset($rawdata["domain"]) && is_array($rawdata["domain"])) {
            self::loadDomain($rawdata["domain"]);
        }
        if (isset($rawdata["gateway"]) && is_array($rawdata["gateway"])) {
            self::loadGateway($rawdata["gateway"]);
        }
        if (isset($rawdata["pattern"]) && is_array($rawdata["pattern"])) {
            self::loadPatterns($rawdata["pattern"]);
        }

        return self::dump();
    }

    /**
     * @param array $rawdata
     * @param array $obj
     */
    private static function loadParams(array $rawdata, &$obj) : void
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

    /**
     * @param array $obj
     * @param array $attr
     * @param string $bucket
     */
    private static function loadRequestMapping(&$obj, array $attr, string $bucket = "body") : void
    {
        $key                                                    = (
            isset($attr["scope"])
                ? $attr["scope"] . "."
                : ""
            ) . $attr["name"];

        $obj[$bucket][$key]                                     = $attr;
    }

    /**
     * @param array $config
     */
    private static function loadAccessControl(array $config) : void
    {
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

    /**
     * @param array $config
     */
    private static function loadPages(array $config) : void
    {
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

    /**
     * @param array $config
     */
    private static function loadDomain(array $config) : void
    {
        $schema                                             = array();
        foreach ($config as $domain) {
            $attr                                           = Dir::getXmlAttr($domain);
            $schema[$attr["name"]]                          = $attr["path"];
        }
        self::$alias                                        = $schema;
    }

    /**
     * @param array $config
     */
    private static function loadGateway(array $config) : void
    {
        $schema                                             = array();
        foreach ($config as $gateway) {
            $attr                                           = Dir::getXmlAttr($gateway);
            $schema[$attr["name"]]                          = $attr["proxy"];
        }

        self::$gateway                                        = $schema;
    }

    /**
     * @param array $config
     */
    private static function loadPatterns(array $config) : void
    {
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

    /**
     *
     */
    private static function findPageByPathInfo()
    {
        $page                                                   = array();
        $router                                                 = Router::find(self::$orig_path_info);
        $page_path                                              = rtrim(self::$orig_path_info, "/");
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

    /**
     *
     */
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
    public static function &pageConfiguration() : RequestPage
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

    /**
     *
     */
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

        if (!self::isCli() && self::remoteAddr() == self::serverAddr()) {
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

    public static function rawdata(bool $toObj = false)
    {
        return (array) self::body(null, null, $toObj, "rawdata");
    }
    public static function valid(bool $toObj = false)
    {
        return (array) self::body(null, null, $toObj, "valid");
    }
    public static function unknown(bool $toObj = false)
    {
        return (array) self::body(null, null, $toObj, "unknown");
    }
    public static function get(string $key, bool $toObj = false)
    {
        return self::body($key, "GET", $toObj, "get");
    }
    public static function post(string $key, bool $toObj = false)
    {
        return self::body($key, "POST", $toObj, "post");
    }
    public static function patch(string $key, bool $toObj = false)
    {
        return self::body($key, "PATCH", $toObj, "post");
    }
    public static function delete(string $key, bool $toObj = false)
    {
        return self::body($key, "DELETE", $toObj, "post");
    }
    public static function put(string $key, bool $toObj = false)
    {
        return self::body($key, "PUT", $toObj, "rawdata");
    }

    public static function cookie(string $key, bool $toObj = false)
    {
        return self::body($key, "COOKIE", $toObj);
    }
    public static function session(string $key, bool $toObj = false)
    {
        return self::body($key, "SESSION", $toObj);
    }
    public static function getModel(string $scope, bool $toObj = true)
    {
        return self::body(null, null, $toObj, $scope);
    }
    /**
     * @param bool $with_unknown
     * @return string
     */
    public static function getQuery(bool $with_unknown = false) : string
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

    /**
     *
     */
    private static function capture()
    {
        self::captureServer();

        self::security();

        self::captureHeaders();
        self::captureBody();
    }

    public static function headers(string $key = null)
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
    private static function body(string $key = null, string $method = null, bool $toObj = false, string $scope = "rawdata")
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
     * @param string $path_info
     * @return RequestPageRules
     */
    private static function setRulesByPage(string $path_info) : RequestPageRules
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

    /**
     * @param string|null $hostname
     * @return string
     */
    public static function proxy(string $hostname = null) : string
    {
        if (!$hostname) {
            $hostname                                           = self::hostname();
        }
        return (isset(self::$gateway[$hostname])
            ? self::$gateway[$hostname]
            : $hostname
        );
    }

    /**
     * @param string|null $hostname
     * @return string|null
     */
    public static function alias(string $hostname = null) : ?string
    {
        if (!$hostname) {
            $hostname                                           = self::hostname();
        }
        return (isset(self::$alias[$hostname])
            ? self::$alias[$hostname]
            : null
        );
    }

    /**
     * @return bool
     */
    public static function isHTTPS() : bool
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER["HTTP_ORIGIN"]) && strpos($_SERVER["HTTP_ORIGIN"], "https://") === 0);
    }

    /**
     * @return string
     */
    public static function protocol() : string
    {
        return (self::isHTTPS() ? "https" : "http") . "://";
    }

    /**
     * @return string|null
     */
    public static function hostname() : ?string
    {
        return (isset($_SERVER["HTTP_HOST"])
            ? $_SERVER["HTTP_HOST"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function protocol_host() : ?string
    {
        return (self::hostname()
            ?  self::protocol() . self::hostname()
            : null
        );
    }

    /**
     * @return string
     */
    public static function pathinfo() : string
    {
        return (isset($_SERVER["PATH_INFO"])
            ? $_SERVER["PATH_INFO"]
            : DIRECTORY_SEPARATOR
        );
    }

    /**
     * @return string|null
     */
    public static function requestURI() : ?string
    {
        return (isset($_SERVER["REQUEST_URI"])
            ? $_SERVER["REQUEST_URI"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function rawQuery() : ?string
    {
        return (empty($_SERVER["QUERY_STRING"])
            ? null
            : $_SERVER["QUERY_STRING"]
        );
    }

    /**
     * @return string
     */
    public static function protocol_host_pathinfo() : string
    {
        return self::protocol_host() . Constant::SITE_PATH . self::pathinfo();
    }

    /**
     * @param string|null $phpurl_part
     * @return string
     */
    public static function url(string $phpurl_part = null) : string
    {
        $url                                                    = self::protocol_host_pathinfo() . self::getQuery(false);

        return ($phpurl_part && $url
            ? parse_url($url, $phpurl_part)
            : $url
        );
    }

    /**
     * @param string|null $phpurl_part
     * @return string|null
     */
    public static function referer(string $phpurl_part = null) : ?string
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

    /**
     * @return string|null
     */
    public static function userAgent() : ?string
    {
        return (isset($_SERVER["HTTP_USER_AGENT"])
            ? $_SERVER["HTTP_USER_AGENT"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function remoteAddr() : ?string
    {
        return (isset($_SERVER["REMOTE_ADDR"])
            ? $_SERVER["REMOTE_ADDR"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function serverAddr() : ?string
    {
        return (isset($_SERVER["SERVER_ADDR"])
            ? $_SERVER["SERVER_ADDR"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function rawAccept() : ?string
    {
        return (isset($_SERVER["HTTP_ACCEPT"])
            ? $_SERVER["HTTP_ACCEPT"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function method() : ?string
    {
        return (isset($_SERVER["REQUEST_METHOD"])
            ? strtoupper($_SERVER["REQUEST_METHOD"])
            : null
        );
    }

    /**
     * @return bool
     */
    public static function isAjax() : bool
    {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    }

    /**
     * @return bool
     */
    public static function isCli() : bool
    {
        return (php_sapi_name() === 'cli');
    }

    /**
     *
     */
    private static function captureServer()
    {
        self::$server = (
            self::isCli()
            ? null
            : $_SERVER
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    private static function server(string $key) : ?string
    {
        return (isset(self::$server[$key])
            ? self::$server[$key]
            : null
        );
    }

    private static function getAccessControl(string $origin, string $key = null)
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

    /**
     * return only the headers and not the content
     * @param string $origin
     */
    private static function corsPreflight(string $origin) : void
    {
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

    /**
     * @return bool
     */
    private static function security() : bool
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
                self::corsPreflight($origin);
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

    /**
     * @param string|null $origin
     */
    private static function securityHeaders(string $origin = null) : void
    {
        if ($origin) {
            $allow_origin = self::getAccessControl($origin, "allow-origin");
            if ($allow_origin) {
                header('Access-Control-Allow-Origin: ' . $allow_origin);
            }
        }

        self::verifyInvalidRequest();
    }

    /**
     * @return array
     */
    private static function getRequestHeaders() : array
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

    /**
     * @return array
     */
    private static function securityHeaderParams() : array
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

    /**
     * @param array $request
     * @param string $method
     * @return array
     */
    private static function securityParams(array $request, string $method) : array
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

    /**
     * @param $value
     * @param null $type
     * @param string|null $fakename
     * @param string|null $range
     * @return array
     */
    private static function securityValidation(&$value, $type = null, string $fakename = null, string $range = null) : array
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

    /**
     * @return array
     */
    private static function securityFileParams() : array
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

    /**
     * @param string|null $method
     * @return array
     */
    private static function getReq(string $method = null) : array
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

    /**
     * @param array $req
     * @param string $method
     * @return bool
     */
    private static function isAllowedSize(array $req, string $method) : bool
    {
        $request_size                                                                           = strlen(http_build_query($req, '', ''));

        $max_size                                                                               = static::MAX_SIZE;
        $request_max_size                                                                       = (
            isset($max_size[$method])
                                                                                                    ? $max_size[$method]
                                                                                                    : $max_size["DEFAULT"]
                                                                                                );
        return $request_size < $request_max_size;
    }

    /**
     * @return array
     */
    private static function captureHeaders() : array
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

    /**
     * @return string|null
     */
    private static function getRequestMethod() : ?string
    {
        return self::$page->method;
    }
    /**
     * @param null|string $key
     * @param null|string $method
     * @return null|array
     */
    private static function captureBody(string $key = null, string $method = null) : ?array
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

    /**
     * @return string|null
     */
    private static function verifyInvalidHTTPS() : ?string
    {
        return (self::$page->https && !isset($_SERVER["HTTPS"])
            ? "Request Method Must Be In HTTPS"
            : null
        );
    }

    /**
     *
     */
    private static function verifyInvalidRequest()
    {
        $error                                                                                  = self::verifyInvalidHTTPS();
        if (!$error && self::method() != self::$page->method) {
            $error                                                                              = "Request Method Must Be " . self::$page->method
                                                                                                    . (
                                                                                                        self::$page->https && self::referer(PHP_URL_HOST) == self::hostname()
                                                                                                        ? " (Redirect Https may Change Request Method)"
                                                                                                        : ""
                                                                                                    );
        }

        if ($error) {
            self::isError($error, 405);
        }
    }

    /**
     * @param string $error
     * @param int $status
     */
    private static function isError(string $error, int $status = 400)
    {
        Response::sendError($status, $error);
    }

    /**
     * @return string
     */
    public static function accept() : string
    {
        $accept = self::rawAccept();
        return ($accept
            ? explode(",", $accept)[0]
            : '*/*'
        );

    }
}
