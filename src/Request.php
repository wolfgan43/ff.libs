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

use hcore\libs\Env;
use phpformsframework\libs\dto\RequestPage;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\util\ServerManager;
use phpformsframework\libs\util\TypesConverter;

/**
 * Class Page
 * @package phpformsframework\libs
 */
class Request implements Configurable, Dumpable
{
    use TypesConverter;
    use ServerManager;

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

    private static $params          = null;
    private static $access_control  = null;
    private static $pages           = [];
    private static $alias           = null;
    private static $gateway         = null;
    private static $patterns        = null;
    private static $path2params     = null;

    /**
     * @var RequestPage[]
     */
    private static $pageLoaded      = [];

    /**
     * @var RequestPage
     */
    private $page                   = null;
    private $path_info              = null;

    private $orig_path_info         = null;
    private $root_path              = null;


    /**
     * @param $page
     * @return Request
     */
    public static function set(&$page) : self
    {
        return new static($page);
    }

    public static function &load(string $path_info, array $request = null, array $headers = null) : RequestPage
    {
        if (!isset(self::$pageLoaded[$path_info . self::checkSumArray($request)])) {
            $page = new RequestPage($path_info, self::$pages, self::$path2params, self::$patterns);
            $page->loadRequest($request ?? []);
            $page->loadHeaders($headers, true);
            $page->loadAuthorization($headers["Authorization"] ?? self::getAuthorizationHeader());

            self::$pageLoaded[$path_info] = $page;
        }

        return self::$pageLoaded[$path_info];
    }

    /**
     * @return string|null
     */
    private static function getAuthorizationHeader(): ?string
    {
        $headers = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
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
     * @param dto\ConfigRules $configRules
     * @return dto\ConfigRules
     */
    public static function loadConfigRules(dto\ConfigRules $configRules): dto\ConfigRules
    {
        return $configRules
            ->add("request")
            ->add("alias")
            ->add("patterns");
    }

    /**
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
        if (!empty($rawdata["put"])) {
            foreach ($rawdata["put"] as $put) {
                self::loadRequestMapping($obj, Dir::getXmlAttr($put), "query");
            }
        }
        if (!empty($rawdata["delete"])) {
            foreach ($rawdata["delete"] as $delete) {
                self::loadRequestMapping($obj, Dir::getXmlAttr($delete), "query");
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
            self::$pages[$key]["config"] = $page["config"] ?? [];
            if (isset($page["events"])) {
                self::$pages[$key]["events"] = $page["events"];
            }
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
     * Page constructor.
     * @param $page
     */
    public function __construct(&$page)
    {
        $this->path_info                    = $this->rewritePathInfo();
        self::$pageLoaded[$this->path_info] = new RequestPage($this->orig_path_info, self::$pages, self::$path2params, self::$patterns);
        $this->page                         =& self::$pageLoaded[$this->path_info];

        Log::setRoutine($this->page->log);

        $page                               = $this->page;
    }

    /**
     * @return string
     */
    private function rewritePathInfo() : string
    {
        $hostname                       = $this->hostname();
        $aliasname                      = (
            $hostname && isset(self::$alias[$hostname])
            ? self::$alias[$hostname]
            : null
        );
        $requestURI                     = $this->requestURI();
        if ($requestURI) {
            $this->orig_path_info       = rtrim(explode("?", $requestURI)[0], "/");
            if (Constant::SITE_PATH) {
                $this->orig_path_info   = str_replace(Constant::SITE_PATH, "", $this->orig_path_info);
            }
        }
        if (!$this->orig_path_info) {
            $this->orig_path_info       = "/";
        } else {
            $this->orig_path_info       = str_replace("/index." . Constant::PHP_EXT, "", $this->orig_path_info);
        }

        $this->orig_path_info           = Locale::setByPath($this->orig_path_info);

        if ($aliasname) {
            if (strpos($this->orig_path_info, $aliasname . "/") === 0
                || $this->orig_path_info == $aliasname
            ) {
                $query = (
                    !empty($_GET)
                    ? "?" . http_build_query($_GET)
                    : ""
                );
                Response::redirect($hostname . substr($this->orig_path_info, strlen($aliasname)) . $query);
            }

            $this->root_path            = $aliasname;
        }


        $path_info = rtrim($this->root_path . $this->orig_path_info, "/");
        if (!$path_info) {
            $path_info = "/";
        }

        $_SERVER["XHR_PATH_INFO"] = null;
        $_SERVER["ORIG_PATH_INFO"] = $this->orig_path_info;
        $_SERVER["PATH_INFO"] = $path_info;

        if ($this->isXhr()) {
            $_SERVER["XHR_PATH_INFO"] = rtrim($this->root_path . $this->referer(PHP_URL_PATH), "/");
        }

        if (!$this->isCli() && $this->remoteAddr() == $this->serverAddr()) {
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

        return $path_info;
    }

    /**
     * @throws Exception
     */
    public function capture()
    {
        $error = error_get_last();
        if ($error) {
            $this->sendError(500, $error["message"]);
        } else {
            $this->security();

            $this->captureAuthorization();
            $this->captureHeaders();
            $this->captureBody();

            unset($_REQUEST);
            unset($_GET);
            unset($_POST);
        }
    }

    /**
     * @param int $status
     * @param string|null $msg
     * @throws Exception
     */
    private function sendError(int $status, string $msg = null) : void
    {
        Response::sendError($status, $msg);
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function security() : bool
    {
        if (headers_sent()) {
            return false;
        }
        $origin = $this->referer(null, "origin");
        if (!$origin) {
            $origin = $this->referer();
        }

        //todo: remove TRACE request method
        //todo: remove serverSignature
        header_remove("X-Powered-By");
        header("Vary: Accept-Encoding"              . ($origin ? ", Origin" : ""));

        header('X-Content-Type-Options: '           . Env::get("CONTENT_TYPE_OPTIONS"));
        header('X-XSS-Protection: '                 . Env::get("XSS_PROTECTION"));
        header('Access-Control-Allow-Headers: '     . Env::get("ACCESS_CONTROL_ALLOW_HEADERS"));

        if ($this->isHTTPS()) {
            header('Strict-Transport-Security: '    . Env::get("STRICT_TRANSPORT_SECURITY"));
        }

        header('X-Frame-Options: '                  . Env::get("FRAME_OPTIONS"));
        header('Referrer-Policy: '                  . Env::get("REFERRER_POLICY"));
        header('Expect-CT: '                        . Env::get("EXPECT_CT"));

        //header('Content-Security-Policy: '                . Env::get("CONTENT_SECURITY_POLICY"));
        //header('Permissions-Policy: '                     . Env::get("PERMISSIONS_POLICY"));

        switch ($this->requestMethod()) {
            case self::METHOD_OPTIONS:
            case self::METHOD_HEAD: //todo: to manage
                header('Access-Control-Allow-Methods: ' . (
                    $this->page->method == self::METHOD_HEAD
                        ? self::METHOD_GET . ", " . self::METHOD_POST
                        : $this->page->method
                ));
                $this->corsPreflight($origin);
                http_response_code(204);
                exit;
            case self::METHOD_GET:
            case self::METHOD_POST:
            case self::METHOD_PUT:
            case self::METHOD_PATCH:
            case self::METHOD_DELETE:
                $this->securityHeaders($origin);
                break;
            case self::METHOD_TRACE: //todo: to manage
            case self::METHOD_CONNECT: //todo: to manage
            case self::METHOD_PROPFIND: //todo: to manage
            default:
                $this->sendError(405);
        }

        return true;
    }

    /**
     * return only the headers and not the content
     * @param string|null $origin
     */
    private function corsPreflight(string $origin = null) : void
    {
        if ($origin) {
            $access_control = $this->getAccessControl($origin);
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
                header('Access-Control-Max-Age: ' . $access_control["max-age"] ?? 3600);
                header("Content-Type: text/plain");
            } elseif (!isset(self::$access_control)) {
                $this->corsFree($origin);
            }
        }
    }

    /**
     * @param string $origin
     */
    private function corsFree(string $origin) : void
    {
        if (strpos($origin, $this->protocol()) !== 0) {
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
     * @param string $origin
     * @return array|null
     */
    private function getAccessControl(string $origin) : ?array
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
     * @param string|null $origin
     * @throws Exception
     */
    private function securityHeaders(string $origin = null) : void
    {
        header('Access-Control-Allow-Methods: ' . $this->page->method . ',' . self::METHOD_OPTIONS . ',' . self::METHOD_HEAD);

        $this->corsPreflight($origin);


        $this->verifyInvalidRequest();
    }

    /**
     * @throws Exception
     */
    private function verifyInvalidRequest()
    {
        $error                                                                                  = $this->verifyInvalidHTTPS();
        if (!$error && $this->requestMethod() != $this->page->method) {
            $error                                                                              = "Request Method Must Be " . $this->page->method
                . (
                    $this->page->https && $this->referer(PHP_URL_HOST) == $this->hostname()
                    ? " (Redirect Https may Change Request Method)"
                    : ""
                );
        }

        if ($error) {
            $this->sendError(405, $error);
        }
    }

    /**
     * @return string|null
     */
    private function verifyInvalidHTTPS() : ?string
    {
        return ($this->page->https && !isset($_SERVER["HTTPS"])
            ? "Request Method Must Be In HTTPS"
            : null
        );
    }

    /**
     *
     */
    private function captureAuthorization() : void
    {
        $this->page->loadAuthorization($this->getAuthorizationHeader());
    }

    /**
     * @return array
     * @throws Exception
     */
    private function captureHeaders() : array
    {
        if ($this->page->loadHeaders($_SERVER) && empty($this->page->controller)) {
            $this->sendError($this->page->status, $this->page->error);
        }

        return $this->page->getHeaders();
    }

    /**
     * @return null|array
     * @throws Exception
     */
    private function captureBody() : array
    {
        $request                                                                                = $this->getReq($this->getRequestMethod());

        if (($this->page->loadRequest($request) || $this->page->loadRequestFile()) && ($this->page->exception || empty($this->page->controller))) {
            $this->sendError($this->page->status, $this->page->error);
        }

        return $this->page->getRequest();
    }

    /**
     * @return string|null
     */
    private function getRequestMethod() : ?string
    {
        return $this->page->method;
    }

    /**
     * @return array
     */
    private function phpInput() : array
    {
        $input = file_get_contents('php://input');
        if ($this->contentEncoding() == "gzip") {
            $input = gzdecode($input);
        }

        return (array) json_decode($input, true);
    }

    /**
     * @param string|null $method
     * @return array
     */
    private function getReq(string $method = null) : array
    {
        switch ($method) {
            case self::METHOD_POST:
                $req                                                                            =  $_GET + (
                    $this->isFormData()
                        ? $_POST
                        : $this->phpInput()
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
        return $req;
    }

    /**
     * @return bool
     */
    private function isFormData() : bool
    {
        return (isset($_SERVER["CONTENT_TYPE"])
            && (
                stripos($_SERVER["CONTENT_TYPE"], "/x-www-form-urlencoded") !== false
                || stripos($_SERVER["CONTENT_TYPE"], "/form-data") !== false
            ));
    }
}
