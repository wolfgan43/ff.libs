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
use stdClass;

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
    public const METHOD_PROPFIND    = "PROPFIND";
    public const METHOD_HEAD        = "HEAD";
    public const METHOD_CONNECT     = "CONNECT";
    public const METHOD_TRACE       = "TRACE";
    public const METHOD_OPTIONS     = "OPTIONS";
    public const METHOD_PATCH       = "PATCH";

    private const REQUEST_VALID     = "valid";


    protected const MAX_SIZE        = array(
                                        self::METHOD_GET        => 256,
                                        self::METHOD_PUT        => 10240,
                                        self::METHOD_POST       => 10240,
                                        self::METHOD_HEAD       => 2048,
                                        "DEFAULT"               => 128,
                                        "FILES"                 => 1024000
                                    );
    private static $params          = null;
    private static $access_control  = null;
    private static $pages           = null;
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
            "params" => self::$params,
            "access_control" => self::$access_control,
            "pages" => self::$pages,
            "alias" => self::$alias,
            "gateway" => self::$gateway,
            "patterns" => self::$patterns,
            "path2params" => self::$path2params,
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
        self::$params = $config["params"];
        self::$access_control = $config["access_control"];
        self::$pages = $config["pages"];
        self::$alias = $config["alias"];
        self::$gateway = $config["gateway"];
        self::$patterns = $config["patterns"];
        self::$path2params = $config["path2params"];
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
    private static function loadRequestMapping(&$obj, array $attr, string $bucket = "body"): void
    {
        /*$key = (
            isset($attr["scope"])
                ? $attr["scope"] . "."
                : ""
            ) . $attr["name"];*/
        $key                    = $attr["name"];
        $obj[$bucket][$key]     = $attr;
    }

    /**
     * @param array $config
     */
    private static function loadAccessControl(array $config): void
    {
        $schema = array();
        if (is_array($config) && count($config)) {
            foreach ($config as $access_control) {
                $attr = Dir::getXmlAttr($access_control);
                $key = $attr["origin"];
                if (!$key) {
                    continue;
                }
                unset($attr["origin"]);
                if (is_array($attr) && count($attr)) {
                    $schema[$key] = $attr;
                }
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
            if (is_array($attr) && count($attr)) {
                $schema[$key] = $attr;
            }
        }

        self::$patterns = $schema;
    }

    /**
     * @param string $path_info
     * @return string
     */
    private static function findEnvByPathInfo(string $path_info): string
    {
        $path_info = rtrim($path_info, "/");
        if (!$path_info) {
            $path_info = DIRECTORY_SEPARATOR;
        }


        if (is_array(self::$path2params) && count(self::$path2params)) {
            foreach (self::$path2params as $page_path => $params) {
                if (preg_match_all($params["regexp"], $path_info, $matches)) {
                    if (self::method() == self::METHOD_GET) {
                        $_GET = array_merge($_GET, array_combine($params["matches"], $matches[1]));
                    } else {
                        $_POST = array_merge($_POST, array_combine($params["matches"], $matches[1]));
                    }

                    $path_info = $page_path;
                    break;
                }
            }
        }

        return $path_info;
    }

    /**
     * @param string $path_info
     * @param array $page
     * @return array|null
     */
    private static function findPageByRouter(string $path_info, array &$page): ?array
    {
        $router = Router::find($path_info);
        do {
            if (isset(self::$pages[$path_info])) {
                $page = array_replace(self::$pages[$path_info]["config"], $page);
            }
            $path_info = dirname($path_info);
        } while ($path_info != DIRECTORY_SEPARATOR);

        return $router;
    }

    /**
     * @param string $path_info
     * @return RequestPage
     */
    private static function findPageByPathInfo(string $path_info) : RequestPage
    {
        $page = array();
        $page_path = self::findEnvByPathInfo($path_info);
        $router = self::findPageByRouter($page_path, $page);
        //@todo da verificare se e corretto il self::$path_info e la differenza tra self::$path_info
        //@todo e se ha senso la diff tra self::$orig_path_info e self::$path_info
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

        return new RequestPage($page, self::setRulesByPage($page_path));
    }

    /**
     *
     */
    private static function urlVerify()
    {
        $redirect = null;
        //necessario XHR perche le request a servizi esterni path del domain alias non triggerano piu
        if (self::method() == self::METHOD_GET && !self::isAjax() && count(self::$page->getRequestUnknown())) {
            // Evita pagine duplicate quando i link vengono gestiti dagli alias o altro
            $redirect = self::url();
        }

        if ($redirect) {
            Response::redirect($redirect);
        }
    }

    /**
     * @access private
     * @return RequestPage
     */
    public static function &pageConfiguration(): RequestPage
    {
        self::rewritePathInfo();

        self::$page = self::findPageByPathInfo(self::$orig_path_info);

        Log::setRoutine(self::$page->log);

        self::capture();

        Kernel::useCache(!self::$page->nocache);

        if (isset(self::$page->root_path) && self::$page->root_path == self::$root_path) {
            $_SERVER["PATH_INFO"] = self::$orig_path_info;
        }

        //@todo: da sistemare
        if (Env::get("REQUEST_SECURITY_LEVEL")) {
            Buckler::protectMyAss();
        }

        if (self::$page->validation) {
            self::urlVerify();
        }

        return self::$page;
    }

    /**
     *
     */
    private static function rewritePathInfo()
    {
        $hostname = self::hostname();
        $aliasname = (
            $hostname && isset(self::$alias[$hostname])
            ? self::$alias[$hostname]
            : null
        );
        $requestURI = self::requestURI();
        $queryString = self::queryString();
        if ($requestURI) {
            self::$orig_path_info = rtrim(rtrim($queryString
                ? rtrim($requestURI, $queryString)
                : $requestURI, "?"), "/");

            if (Constant::SITE_PATH) {
                self::$orig_path_info = str_replace(Constant::SITE_PATH, "", self::$orig_path_info);
            }
        }
        if (!self::$orig_path_info) {
            self::$orig_path_info = "/";
        }

        self::$orig_path_info = Locale::setByPath(self::$orig_path_info);

        if ($aliasname) {
            if (strpos(self::$orig_path_info, $aliasname . "/") === 0
                || self::$orig_path_info == $aliasname
            ) {
                $query = (
                    is_array($_GET) && count($_GET)
                    ? "?" . http_build_query($_GET)
                    : ""
                );
                Response::redirect($hostname . substr(self::$orig_path_info, strlen($aliasname)) . $query);
            }

            self::$root_path = $aliasname;
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
     */
    public static function headers() : stdClass
    {
        return (object) self::captureHeaders();
    }
    /**
     * @return stdClass
     */
    public static function rawdata(): stdClass
    {
        return (object) self::body(RequestPage::REQUEST_RAWDATA);
    }

    /**
     * @return array
     */
    public static function valid(): array
    {
        return self::body(RequestPage::REQUEST_VALID);
    }

    /**
     * @return stdClass
     */
    public static function cookie() : stdClass
    {
        return (object) self::body("COOKIE");
    }

    /**
     * @return stdClass
     */
    public static function session() : stdClass
    {
        return (object) self::body("SESSION");
    }

    /**
     * @param string $scope
     * @return stdClass
     */
    public static function getModel(string $scope) : stdClass
    {
        return (object) self::body($scope);
    }

    /**
     * @param bool $with_unknown
     * @return string
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

        return (is_array($res) && count($res)
            ? "?" . http_build_query($res)
            : ""
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
     *
     */
    private static function capture()
    {
        $error = error_get_last();
        if ($error) {
            self::sendError($error["message"], 500);
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
     * @param
     *
     * $rules = array(
     * "header"            => ""
     * , "body"            => ""
     * , "last_update"     => ""
     * , "method"          => ""
     * , "exts"            => ""
     * , "navigation"      => ""
     * , "select"          => ""
     * , "default"         => ""
     * , "order"           => ""
     * );
     */

    /**
     * @param string $path_info
     * @return RequestPageRules
     */
    private static function setRulesByPage(string $path_info): RequestPageRules
    {
        $rules = new RequestPageRules();
        $request_path = $path_info;

        do {
            if (isset(self::$pages[$request_path])) {
                $rules->set(self::$pages[$request_path]);
            }
        } while ($request_path != DIRECTORY_SEPARATOR && $request_path = dirname($request_path));

        return $rules;
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

    /**
     * @param string $origin
     * @return array|null
     */
    private static function getAccessControl(string $origin) : ?array
    {
        $access_control                                         = null;
        if (isset(self::$access_control)) {
            if (isset(self::$access_control[$origin])) {
                $access_control                                 = self::$access_control[$origin];
            } elseif (isset(self::$access_control["*"])) {
                $access_control                                 = self::$access_control["*"];
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
        header('Access-Control-Allow-Methods: ' . self::$page->method);

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
        $origin = self::referer(PHP_URL_HOST, "origin");
        if (!$origin) {
            $origin = self::referer(PHP_URL_HOST);
        }

        //todo: remove TRACE request method
        //todo: remove serverSignature
        header_remove("X-Powered-By");
        header("Vary: Accept-Encoding" . ($origin ? ", Origin" : ""));

        switch (self::method()) {
            case self::METHOD_OPTIONS:
                self::corsPreflight($origin);
                break;
            case self::METHOD_TRACE: //todo: to manage
                self::sendError(405);
                break;
            case self::METHOD_CONNECT: //todo: to manage
                self::sendError(405);
                break;
            case self::METHOD_HEAD: //todo: to manage
                self::corsPreflight();
                exit;
                break;
            case self::METHOD_PROPFIND: //todo: to manage
                self::sendError(405);
                break;
            case self::METHOD_GET:
            case self::METHOD_POST:
            case self::METHOD_PUT:
                self::securityHeaders($origin);
                break;
            case self::METHOD_PATCH: //todo: to manage
                self::sendError(405);
                break;
            case self::METHOD_DELETE: //todo: to manage
                self::sendError(405);
                break;
            default:
                self::sendError(405);
        }

        return true;
    }

    /**
     * @param string|null $origin
     */
    private static function securityHeaders(string $origin = null) : void
    {
        if ($origin) {
            $access_control = self::getAccessControl($origin);
            if (isset($access_control["allow-origin"])) {
                header('Access-Control-Allow-Origin: ' . $access_control["allow-origin"]);
            }
        }
        header('Access-Control-Allow-Methods: ' . self::$page->method . ',' . self::METHOD_OPTIONS . ',' . self::METHOD_HEAD);

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
     * @param RequestPage $page
     * @return bool
     */
    private static function securityHeaderParams(RequestPage &$page) : bool
    {
        $errors                                                                         = null;
        if (self::isAllowedSize(self::getRequestHeaders(), self::METHOD_HEAD)) {
            foreach ($page->rules->header as $rule) {
                $header_key                                                             = str_replace("-", "_", $rule["name"]);
                if ($rule["name"] == "Authorization") {
                    $header_name                                                        = "Authorization";
                } else {
                    $header_name                                                        = "HTTP_" . strtoupper($header_key);
                }

                $page->setHeader($header_key);
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
                    $validator                                                          = Validator::is($_SERVER[$header_name], $header_key . " (in header)", $validator_rule, $validator_range);
                    if ($validator->isError()) {
                        $errors[$validator->status][]                                   = $validator->error;
                    }

                    $page->setHeader($header_key, $_SERVER[$header_name]);
                }
            }
        } else {
            $errors[413][]                                                              = "Headers Max Size Exeeded";
        }

        $page->error($errors);

        return $page->isError();
    }

    /**
     * @param string $method
     * @return string
     */
    private static function bucketByMethod(string $method) : string
    {
        return ($method == self::METHOD_GET || $method == self::METHOD_PUT
            ? "query"
            : "body"
        );
    }

    /**
     * @param array $request
     * @param string $method
     * @param RequestPage $page
     * @return bool
     */
    private static function securityParams(array $request, string $method, RequestPage &$page) : bool
    {
        $errors                                                                         = array();
        $bucket                                                                         = self::bucketByMethod($method);
        if (self::isAllowedSize($request, $method) && self::isAllowedSize(self::getRequestHeaders(), self::METHOD_HEAD)) {
            if (is_array($page->rules->$bucket) && count($page->rules->$bucket) && is_array($request)) {
                foreach ($page->rules->$bucket as $rule) {
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

                        $errors                                                         = $errors + self::securityValidation($request[$rule["name"]], $rule["name"], $validator_rule, $validator_range);


                        if (isset($rule["scope"])) {
                            $page->setRequest($rule["scope"], $request[$rule["name"]], $rule["name"]);
                        }
                        if (!isset($rule["hide"]) || $rule["hide"] === false) {
                            $page->setRequest(RequestPage::REQUEST_VALID, $request[$rule["name"]], $rule["name"]);
                        } else {
                            unset($request[$rule["name"]]);
                        }
                    } else {
                        $request[$rule["name"]]                                         = self::getDefault($rule);
                        $page->setRequest(RequestPage::REQUEST_VALID, $request[$rule["name"]], $rule["name"]);
                        if (isset($rule["scope"])) {
                            $page->setRequest($rule["scope"], $request[$rule["name"]], $rule["name"]);
                        }
                    }
                }

                $page->setRequest($page::REQUEST_RAWDATA, $request);
                $page->setUnknown($request);
                foreach ($page->getRequestUnknown() as $unknown_key => $unknown) {
                    $errors                                                             = $errors + self::securityValidation($unknown, $unknown_key);
                }
            }
        } else {
            $errors[413][]                                                              = "Request Max Size Exeeded";
        }

        $page->error($errors);

        return $page->isError();
    }

    /**
     * @todo da tipizzare
     * @param array $rule
     * @return mixed|null
     */
    private static function getDefault(array $rule)
    {
        $res = null;
        if (isset($rule["default"])) {
            $res = $rule["default"];
        } elseif (isset($rule["validator"])) {
            $res = Validator::getDefault($rule["validator"]);
        }
        return $res;
    }

    /**
     * @todo da tipizzare
     * @param $value
     * @param string $fakename
     * @param string|null $type
     * @param string|null $range
     * @return array
     */
    private static function securityValidation(&$value, string $fakename, string $type = null, string $range = null) : array
    {
        $errors                                                                         = array();

        $validator                                                                      = Validator::is($value, $fakename, $type, $range);
        if ($validator->isError()) {
            $errors[$validator->status][]                                               = $validator->error;
        }


        return $errors;
    }

    /**
     * @param RequestPage $page
     * @return bool
     */
    private static function securityFileParams(RequestPage &$page) : bool
    {
        $errors                                                                         = array();
        if (is_array($_FILES) && count($_FILES)) {
            foreach ($_FILES as $file_name => $file) {
                if (isset($page->rules->body[$file_name]["mime"]) && strpos($page->rules->body[$file_name]["mime"], $file["type"]) === false) {
                    $errors[400][]                                                      = $file_name . " must be type " . $page->rules->body[$file_name]["mime"];
                } else {
                    $validator                                                          = Validator::is($file_name, $file_name, "file");
                    if ($validator->isError()) {
                        $errors[$validator->status][] = $validator->error;
                    }
                }
            }
        }

        $page->error($errors);

        return $page->isError();
    }

    /**
     * @param string|null $method
     * @return array
     */
    private static function getReq(string $method = null) : array
    {
        switch ($method) {
            case self::METHOD_POST:
            case self::METHOD_PATCH:
            case self::METHOD_DELETE:
                $req                                                                            = $_POST;
                break;
            case self::METHOD_GET:
                $req                                                                            = $_GET;
                break;
            case "COOKIE":
                $req                                                                            = $_COOKIE;
                break;
            case "SESSION":
                $req                                                                            = $_SESSION;
                break;
            case self::METHOD_PUT:
            default:
                $req                                                                            = $_REQUEST;

        }

        return array_filter($req);
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

            if (self::securityHeaderParams(self::$page)) {
                self::sendError(self::$page->error, self::$page->status);
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

            if (self::securityParams($request, $method, self::$page) || self::securityFileParams(self::$page)) {
                self::sendError(self::$page->error, self::$page->status);
            }
        }

        return ($scope
            ? self::$page->getRequest($scope)
            : null
        );
    }

    /**
     * @param string $path_info
     * @param array $request
     * @return RequestPage
     */
    public static function getPage(string $path_info, array $request) : RequestPage
    {
        $config = [
            "path_info" => $path_info,
            "method" => (
                isset(self::$pages[$path_info]["config"]["method"])
                ? self::$pages[$path_info]["config"]["method"]
                : self::METHOD_POST
            )
        ];

        $page = new RequestPage($config, self::setRulesByPage($path_info));

        self::securityParams($request, $page->method, $page);

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
            self::sendError($error, 405);
        }
    }

    /**
     * @param string $error
     * @param int $status
     */
    private static function sendError(string $error, int $status = 400)
    {
        Response::sendError($status, $error);
    }

    /**
     * @return string
     */
    private static function pageAccept() : string
    {
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
