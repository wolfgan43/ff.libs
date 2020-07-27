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
use phpformsframework\libs\international\Locale;
use stdClass;
use Exception;

/**
 * Class Request
 * @package phpformsframework\libs
 */
class Request implements Configurable, Dumpable
{
    public const METHOD_GET         = "GET";
    public const METHOD_POST        = "POST";
    public const METHOD_PUT         = "PUT";
    public const METHOD_DELETE      = "DELETE";
    public const METHOD_PATCH       = "PATCH";

    public const METHOD_PROPFIND    = "PROPFIND";
    public const METHOD_HEAD        = "HEAD";
    public const METHOD_CONNECT     = "CONNECT";
    public const METHOD_TRACE       = "TRACE";
    public const METHOD_OPTIONS     = "OPTIONS";

    private const REQUEST_VALID     = "valid";

    private static $params          = null;
    private static $access_control  = null;
    private static $pages           = array();
    private static $alias           = null;
    private static $gateway         = null;
    private static $patterns        = null;
    private static $server          = null;
    private static $path2params     = null;

    /**
     * @var RequestPage $page
     */
    private static $page            = null;

    private static $orig_path_info  = null;
    private static $root_path       = null;
    private static $path_info       = null;

    /**
     * @return array
     */
    public static function dump(): array
    {
        return array(
            "params"            => self::$params,
            "access_control"    => self::$access_control,
            "pages"             => self::$pages,
            "alias"             => self::$alias,
            "gateway"           => self::$gateway,
            "patterns"          => self::$patterns,
            "path2params"       => self::$path2params,
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
        self::$params           = $config["params"];
        self::$access_control   = $config["access_control"];
        self::$pages            = $config["pages"];
        self::$alias            = $config["alias"];
        self::$gateway          = $config["gateway"];
        self::$patterns         = $config["patterns"];
        self::$path2params      = $config["path2params"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema(array $rawdata): array
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
        if (isset($rawdata["path2params"])) {
            self::$path2params = $rawdata["path2params"];
        }

        return self::dump();
    }

    /**
     * @param array $rawdata
     * @param array $obj
     */
    private static function loadParams(array $rawdata, &$obj): void
    {
        if (!empty($rawdata["header"])) {
            foreach ($rawdata["header"] as $header) {
                self::loadRequestMapping($obj, Dir::getXmlAttr($header), "header");
            }
        }
        if (!empty($rawdata["get"])) {
            foreach ($rawdata["get"] as $get) {
                self::loadRequestMapping($obj, Dir::getXmlAttr($get), "query");
            }
        }
        if (!empty($rawdata["post"])) {
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
    private static function loadRequestMapping(&$obj, array $attr, string $bucket = "body"): void
    {
        $key                    = $attr["name"];
        $attr["name"]           = str_replace("-", "_", $attr["name"]);

        $obj[$bucket][$key]     = $attr;
    }

    /**
     * @param array $config
     */
    private static function loadAccessControl(array $config): void
    {
        $schema = array();
        if (!empty($config)) {
            foreach ($config as $access_control) {
                $attr = Dir::getXmlAttr($access_control);
                if (!isset($attr["origin"])) {
                    continue;
                }
                $schema[$attr["origin"]] = $attr;
            }
        }

        self::$access_control = $schema;
    }

    /**
     * @param array $config
     */
    private static function loadPages(array $config): void
    {
        foreach ($config as $key => $page) {
            self::$pages[$key] = null;
            self::loadParams($page, self::$pages[$key]);
            self::$pages[$key]["config"] = $page["config"];
        }
    }

    /**
     * @param array $config
     */
    private static function loadDomain(array $config): void
    {
        $schema = array();
        foreach ($config as $domain) {
            $attr = Dir::getXmlAttr($domain);
            $schema[$attr["name"]] = $attr["path"];
        }
        self::$alias = $schema;
    }

    /**
     * @param array $config
     */
    private static function loadGateway(array $config): void
    {
        $schema = array();
        foreach ($config as $gateway) {
            $attr = Dir::getXmlAttr($gateway);
            $schema[$attr["name"]] = $attr["proxy"];
        }

        self::$gateway = $schema;
    }

    /**
     * @param array $config
     */
    private static function loadPatterns(array $config): void
    {
        $schema = array();
        foreach ($config as $pattern) {
            $attr = Dir::getXmlAttr($pattern);
            $key = (
                $attr["path"]
                ? $attr["path"]
                : $attr["source"]
            );
            if (!$key) {
                continue;
            }
            unset($attr["source"]);
            unset($attr["path"]);
            if (!empty($attr)) {
                $schema[$key] = $attr;
            }
        }

        self::$patterns = $schema;
    }

    /**
     * @access private
     * @return RequestPage
     * @throws Exception
     */
    public static function &pageConfiguration(): RequestPage
    {
        self::rewritePathInfo();

        self::$page = self::getPage(self::$orig_path_info);

        Log::setRoutine(self::$page->log);

        self::capture();

        return self::$page;
    }

    /**
     *
     */
    private static function rewritePathInfo()
    {
        $hostname                       = self::hostname();
        $aliasname                      = (
            $hostname && isset(self::$alias[$hostname])
            ? self::$alias[$hostname]
            : null
        );
        $requestURI                     = self::requestURI();
        if ($requestURI) {
            self::$orig_path_info       = rtrim(explode("?", $requestURI)[0], "/");
            if (Constant::SITE_PATH) {
                self::$orig_path_info   = str_replace(Constant::SITE_PATH, "", self::$orig_path_info);
            }
        }
        if (!self::$orig_path_info) {
            self::$orig_path_info       = "/";
        } else {
            self::$orig_path_info       = str_replace("/index." . Constant::PHP_EXT, "", self::$orig_path_info);
        }

        self::$orig_path_info           = Locale::setByPath(self::$orig_path_info);

        if ($aliasname) {
            if (strpos(self::$orig_path_info, $aliasname . "/") === 0
                || self::$orig_path_info == $aliasname
            ) {
                $query = (
                    !empty($_GET)
                    ? "?" . http_build_query($_GET)
                    : ""
                );
                Response::redirect($hostname . substr(self::$orig_path_info, strlen($aliasname)) . $query);
            }

            self::$root_path            = $aliasname;
        }


        $path_info = rtrim(self::$root_path . self::$orig_path_info, "/");
        if (!$path_info) {
            $path_info = "/";
        }

        $_SERVER["XHR_PATH_INFO"] = null;
        $_SERVER["ORIG_PATH_INFO"] = self::$orig_path_info;
        $_SERVER["PATH_INFO"] = $path_info;


        if (self::isAjax()) {
            $_SERVER["XHR_PATH_INFO"] = rtrim(self::$root_path . self::referer(PHP_URL_PATH), "/");
        }

        if (!self::isCli() && self::remoteAddr() == self::serverAddr()) {
            if (isset($_POST["pathinfo"])) {
                $_SERVER["PATH_INFO"] = rtrim($_POST["pathinfo"], "/");
                if (!$_SERVER["PATH_INFO"]) {
                    $_SERVER["PATH_INFO"] = "/";
                }

                unset($_POST["pathinfo"]);
            }
            if (isset($_POST["referer"])) {
                $_SERVER["HTTP_REFERER"] = $_POST["referer"];
                unset($_POST["referer"]);
            }
            if (isset($_POST["agent"])) {
                $_SERVER["HTTP_USER_AGENT"] = $_POST["agent"];
                unset($_POST["agent"]);
            }
            if (isset($_POST["cookie"])) {
                $_COOKIE = $_POST["cookie"];
                unset($_POST["cookie"]);
            }
        }

        self::$path_info = $path_info;
    }

    /**
     * @return stdClass
     * @throws Exception
     */
    public static function headers() : stdClass
    {
        return (object) self::captureHeaders();
    }

    /**
     * @param bool $toArray
     * @return stdClass|array
     * @throws Exception
     */
    public static function rawdata(bool $toArray = false)
    {
        return ($toArray
            ? self::body(RequestPage::REQUEST_RAWDATA)
            : (object) self::body(RequestPage::REQUEST_RAWDATA)
        );
    }

    /**
     * @return array
     * @throws Exception
     */
    public static function valid(): array
    {
        return self::body(RequestPage::REQUEST_VALID);
    }

    /**
     * @return stdClass
     * @throws Exception
     */
    public static function cookie() : stdClass
    {
        return (object) ($_COOKIE ?? []);
    }

    /**
     * @return stdClass
     * @throws Exception
     */
    public static function session() : stdClass
    {
        return (object) ($_SESSION ?? []);
    }

    /**
     * @param string $scope
     * @return stdClass
     * @throws Exception
     */
    public static function getModel(string $scope) : stdClass
    {
        return (object) self::body($scope);
    }

    /**
     * @param bool $with_unknown
     * @return string
     * @throws Exception
     */
    public static function getQuery(bool $with_unknown = false): string
    {
        if (!self::$page->issetRequest()) {
            self::captureBody();
        }

        $res = array_filter(
            $with_unknown
                ? self::$page->getRequest()
                : self::$page->getRequestValid()
        );

        return (empty($res)
            ? ""
            : "?" . http_build_query($res)
        );
    }

    /**
     * @return string|null
     */
    public static function getAuthorizationHeader(): ?string
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * @return string|null
     */
    public static function getBearerToken() : ?string
    {
        $headers                                                    = self::getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        if (isset($_SERVER["HTTP_BEARER"])) {
            return $_SERVER["HTTP_BEARER"];
        }
        return null;
    }
    /**
     * @throws Exception
     */
    private static function capture()
    {
        $error = error_get_last();
        if ($error) {
            self::sendError(500, $error["message"]);
        } else {
            self::captureServer();

            self::security();

            self::captureHeaders();
            self::captureBody();

            unset($_REQUEST);
            unset($_GET);
            unset($_POST);
        }
    }


    /**
     * @param string $scope
     * @param null|string $method
     * @return array
     * @throws Exception
     */
    private static function body(string $scope = RequestPage::REQUEST_RAWDATA, string $method = null) : array
    {
        return (
            0 && self::$page->issetRequest()
            ? self::$page->getRequest($scope)
            : self::captureBody($scope, $method)
        );
    }

    /**
     * @param string|null $hostname
     * @return string|null
     */
    public static function proxy(string $hostname = null): ?string
    {
        if (!$hostname) {
            $hostname = self::hostname();
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
    public static function alias(string $hostname = null): ?string
    {
        if (!$hostname) {
            $hostname = self::hostname();
        }
        return (isset(self::$alias[$hostname])
            ? self::$alias[$hostname]
            : null
        );
    }

    /**
     * @return bool
     */
    public static function isHTTPS(): bool
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || self::referer(PHP_URL_SCHEME, "origin") == "https";
    }

    /**
     * @return string
     */
    public static function protocol(): string
    {
        return (self::isHTTPS() ? "https" : "http") . "://";
    }

    /**
     * @return string|null
     */
    public static function hostname(): ?string
    {
        return (isset($_SERVER["HTTP_HOST"])
            ? $_SERVER["HTTP_HOST"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function protocolHost(): ?string
    {
        return (self::hostname()
            ? self::protocol() . self::hostname()
            : null
        );
    }

    /**
     * @return string
     */
    public static function pathinfo(): string
    {
        return (isset($_SERVER["PATH_INFO"])
            ? $_SERVER["PATH_INFO"]
            : DIRECTORY_SEPARATOR
        );
    }

    /**
     * @return string|null
     */
    public static function requestURI(): ?string
    {
        return (isset($_SERVER["REQUEST_URI"])
            ? $_SERVER["REQUEST_URI"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function queryString(): ?string
    {
        return (empty($_SERVER["QUERY_STRING"])
            ? null
            : $_SERVER["QUERY_STRING"]
        );
    }

    /**
     * @return string
     */
    public static function protocolHostPathinfo(): string
    {
        return self::protocolHost() . Constant::SITE_PATH . self::pathinfo();
    }

    /**
     * @param string|null $phpurl_part
     * @return string
     * @throws Exception
     */
    public static function url(string $phpurl_part = null): string
    {
        $url = self::protocolHostPathinfo() . self::getQuery(false);

        return ($phpurl_part && $url
            ? parse_url($url, $phpurl_part)
            : $url
        );
    }

    /**
     * @param string|null $phpurl_part
     * @param string $key
     * @return string|null
     */
    public static function referer(string $phpurl_part = null, string $key = "referer"): ?string
    {
        $referer = (
            isset($_SERVER["HTTP_" . strtoupper($key)])
            ? $_SERVER["HTTP_" . strtoupper($key)]
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
    public static function userAgent(): ?string
    {
        return (isset($_SERVER["HTTP_USER_AGENT"])
            ? $_SERVER["HTTP_USER_AGENT"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function remoteAddr(): ?string
    {
        return (isset($_SERVER["REMOTE_ADDR"])
            ? $_SERVER["REMOTE_ADDR"]
            : null
        );
    }
    /**
     * @return string|null
     */
    public static function remotePort(): ?string
    {
        return (isset($_SERVER["REMOTE_PORT"])
            ? $_SERVER["REMOTE_PORT"]
            : null
        );
    }
    /**
     * @return string|null
     */
    public static function serverAddr(): ?string
    {
        return (isset($_SERVER["SERVER_ADDR"])
            ? $_SERVER["SERVER_ADDR"]
            : null
        );
    }

    /**
     * @return string|null
     */
    public static function serverProtocol(): ?string
    {
        return (isset($_SERVER["SERVER_PROTOCOL"])
            ? $_SERVER["SERVER_PROTOCOL"]
            : null
        );
    }
    /**
     * @return string|null
     */
    public static function rawAccept() : ?string
    {
        return (isset($_SERVER["HTTP_ACCEPT"]) && $_SERVER["HTTP_ACCEPT"] != '*/*'
            ? $_SERVER["HTTP_ACCEPT"]
            : null
        );
    }

    /**
     * @param bool $toLower
     * @return string|null
     */
    public static function method(bool $toLower = false) : ?string
    {
        return (isset($_SERVER["REQUEST_METHOD"])
            ? ($toLower ? strtolower($_SERVER["REQUEST_METHOD"]) : strtoupper($_SERVER["REQUEST_METHOD"]))
            : null
        );
    }

    /**
     * @param string|null $method
     * @param array $exclude
     * @return string|null
     */
    public static function methodValid(string $method, array $exclude = array()) : ?string
    {
        return (in_array($method, array_diff([
                self::METHOD_GET,
                self::METHOD_POST,
                self::METHOD_PUT,
                self::METHOD_PATCH,
                self::METHOD_DELETE
            ], $exclude))
            ? $method
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

    /**
     * @param string $origin
     * @return array|null
     */
    private static function getAccessControl(string $origin) : ?array
    {
        $access_control                                         = null;
        if (isset(self::$access_control)) {
            $key                                                = parse_url($origin, PHP_URL_HOST);
            if (isset(self::$access_control[$key])) {
                $access_control                                 = self::$access_control[$key];
            }
        }

        return $access_control;
    }

    /**
     * return only the headers and not the content
     * @param string|null $origin
     */
    private static function corsPreflight(string $origin = null) : void
    {
        if ($origin) {
            $access_control = self::getAccessControl($origin);
            if ($access_control) {
                if (isset($access_control["allow-credentials"]) && $access_control["origin"] != "*") {
                    header('Access-Control-Allow-Credentials: true');
                }

                header('Access-Control-Allow-Origin: ' . $access_control["origin"]);
                if (isset($access_control["allow-header"])) {
                    header("Access-Control-Allow-Headers: {$access_control["allow-header"]}");
                } elseif (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                header('Access-Control-Max-Age: ' . (
                    isset($access_control["max-age"])
                    ? $access_control["max-age"]
                    : 3600
                ));
                header("Content-Type: text/plain");
            } elseif (!isset(self::$access_control)) {
                self::corsFree($origin);
            }
        }
    }

    /**
     * @param string $origin
     */
    private static function corsFree(string $origin) : void
    {
        if (strpos($origin, self::protocol()) !== 0) {
            $origin = "*";
        }

        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Credentials: true');
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
        header("Content-Type: text/plain");
    }

    /**
     * @return bool
     * @throws Exception
     */
    private static function security() : bool
    {
        if (headers_sent()) {
            return false;
        }
        $origin = self::referer(null, "origin");
        if (!$origin) {
            $origin = self::referer();
        }

        //todo: remove TRACE request method
        //todo: remove serverSignature
        header_remove("X-Powered-By");
        header("Vary: Accept-Encoding" . ($origin ? ", Origin" : ""));


        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Access-Control-Allow-Headers: DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,content-type');

        if (self::isHTTPS()) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }

        switch (self::method()) {
            case self::METHOD_OPTIONS:
            case self::METHOD_HEAD: //todo: to manage
                header('Access-Control-Allow-Methods: ' . self::$page->method);

                self::corsPreflight($origin);
                exit;
                break;
            case self::METHOD_GET:
            case self::METHOD_POST:
            case self::METHOD_PUT:
            case self::METHOD_PATCH:
            case self::METHOD_DELETE:
                self::securityHeaders($origin);
                break;
            case self::METHOD_TRACE: //todo: to manage
            case self::METHOD_CONNECT: //todo: to manage
            case self::METHOD_PROPFIND: //todo: to manage
            default:
                self::sendError(405);
        }

        return true;
    }

    /**
     * @param string|null $origin
     * @throws Exception
     */
    private static function securityHeaders(string $origin = null) : void
    {
        header('Access-Control-Allow-Methods: ' . self::$page->method . ',' . self::METHOD_OPTIONS . ',' . self::METHOD_HEAD);

        self::corsPreflight($origin);


        self::verifyInvalidRequest();
    }

    /**
     * @param string|null $method
     * @return array
     */
    private static function getReq(string $method = null) : array
    {
        switch ($method) {
            case self::METHOD_POST:
                $req                                                                            = (
                    self::isFormData()
                    ? $_POST
                    : json_decode(file_get_contents('php://input'), true)
                );
                break;
            case self::METHOD_GET:
            case self::METHOD_PUT:
            case self::METHOD_PATCH:
            case self::METHOD_DELETE:
                $req                                                                            = $_GET;
                break;
            default:
                $req                                                                            = $_REQUEST;
        }

        return array_filter((array)$req);
    }

    /**
     * @return bool
     */
    private static function isFormData() : bool
    {
        return (isset($_SERVER["CONTENT_TYPE"])
            && (
                stripos($_SERVER["CONTENT_TYPE"], "/x-www-form-urlencoded") !== false
                || stripos($_SERVER["CONTENT_TYPE"], "/form-data") !== false
            ));
    }

    /**
     * @return array
     * @throws Exception
     */
    private static function captureHeaders() : array
    {
        static $last_update                                                                     = 0;

        if ($last_update < self::$page->rules->last_update) {
            $last_update                                                                        = self::$page->rules->last_update;

            if (self::$page->loadHeaders($_SERVER)) {
                self::sendError(self::$page->status, self::$page->error);
            }
        }

        return self::$page->getHeaders();
    }

    /**
     * @return string|null
     */
    private static function getRequestMethod() : ?string
    {
        return self::$page->method;
    }

    /**
     * @param null|string $scope
     * @param null|string $method
     * @return null|array
     * @throws Exception
     */
    private static function captureBody(string $scope = null, string $method = null) : ?array
    {
        static $last_update                                                                     = 0;

        if ($last_update < self::$page->rules->last_update) {
            $last_update                                                                    = self::$page->rules->last_update;

            if (!$method) {
                $method = self::getRequestMethod();
            }

            $request                                                                        = self::getReq($method);

            if (self::$page->loadRequest($request) || self::$page->loadRequestFile()) {
                self::sendError(self::$page->status, self::$page->error);
            }
        }

        return ($scope
            ? self::$page->getRequest($scope)
            : null
        );
    }

    /**
     * @param string $path_info
     * @param array|null $request
     * @param array|null $headers
     * @return RequestPage
     */
    public static function getPage(string $path_info, array $request = null, array $headers = null) : RequestPage
    {
        $page           = new RequestPage($path_info, self::$pages, self::$path2params, self::$patterns);

        $page->loadRequest($request);
        $page->loadHeaders($headers, true);

        return $page;
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
     * @throws Exception
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
            self::sendError(405, $error);
        }
    }

    /**
     * @param int $status
     * @param string|null $msg
     * @throws Exception
     */
    private static function sendError(int $status, string $msg = null) : void
    {
        Response::sendError($status, $msg);
    }

    /**
     * @return string
     */
    private static function pageAccept() : string
    {
        if (self::isCli()) {
            return "php/cli";
        }

        return (self::isAjax() && self::$page->accept == "*/*"
            ? "application/json"
            : self::$page->accept
        );
    }
    /**
     * @return string
     */
    public static function accept() : string
    {
        $accept = self::rawAccept();
        return ($accept
            ? explode(",", $accept)[0]
            : self::pageAccept()
        );
    }
}
