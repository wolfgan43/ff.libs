<?php
namespace phpformsframework\libs\util;

use phpformsframework\libs\Constant;
use stdClass;

/**
 * Trait SecureManager
 * @package phpformsframework\libs\util
 */
trait ServerManager
{
    /**
     * @return bool
     */
    private static function isHTTPS(): bool
    {
        $isHttps =
            $_SERVER['HTTPS']
            ?? $_SERVER['REQUEST_SCHEME']
            ?? $_SERVER['HTTP_X_FORWARDED_PROTO']
            ?? null
        ;
        return $isHttps && (
            strcasecmp('on', $isHttps) == 0
                || strcasecmp('https', $isHttps) == 0
            );
    }

    /**
     * @return string
     */
    private static function protocol(): string
    {
        return (self::isHTTPS() ? "https" : "http") . "://";
    }
    /**
     * @return string|null
     */
    private static function hostname(): ?string
    {
        return $_SERVER["HTTP_HOST"] ?? null;
    }

    /**
     * @return string|null
     */
    private static function protocolHost(): ?string
    {
        return (self::hostname()
            ? self::protocol() . self::hostname()
            : null
        );
    }

    /**
     * @return string
     */
    private static function pathinfo(): string
    {
        return $_SERVER["PATH_INFO"] ?? DIRECTORY_SEPARATOR;
    }

    /**
     * @return string|null
     */
    private static function requestURI(): ?string
    {
        return $_SERVER["REQUEST_URI"] ?? null;
    }

    /**
     * @return string|null
     */
    private static function queryString(): ?string
    {
        return (empty($_SERVER["QUERY_STRING"])
            ? null
            : $_SERVER["QUERY_STRING"]
        );
    }

    /**
     * @return string
     */
    private static function protocolHostPathinfo(): string
    {
        return self::protocolHost() . Constant::SITE_PATH . self::pathinfo();
    }

    /**
     * @param string|null $phpurl_part
     * @return string|null
     */
    private static function url(string $phpurl_part = null): ?string
    {
        $url = self::requestURI();

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
    private static function referer(string $phpurl_part = null, string $key = "referer"): ?string
    {
        $referer = $_SERVER["HTTP_" . strtoupper($key)] ?? null;

        $res = (
            $phpurl_part && $referer
            ? parse_url($referer, $phpurl_part)
            : $referer
        );

        return empty($res) ? null : $res;
    }

    /**
     * @return string|null
     */
    private static function userAgent(): ?string
    {
        return $_SERVER["HTTP_USER_AGENT"] ?? null;
    }

    /**
     * @return string|null
     */
    private static function remoteAddr(): ?string
    {
        return $_SERVER["REMOTE_ADDR"] ?? null;
    }
    /**
     * @return string|null
     */
    private static function remotePort(): ?string
    {
        return $_SERVER["REMOTE_PORT"] ?? null;
    }
    /**
     * @return string|null
     */
    private static function serverAddr(): ?string
    {
        return $_SERVER["SERVER_ADDR"] ?? null;
    }

    /**
     * @return string|null
     */
    private static function serverProtocol(): ?string
    {
        return $_SERVER["SERVER_PROTOCOL"] ?? null;
    }
    /**
     * @return string|null
     */
    private static function rawAccept() : ?string
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
    private static function requestMethod(bool $toLower = false) : ?string
    {
        return (isset($_SERVER["REQUEST_METHOD"])
            ? ($toLower ? strtolower($_SERVER["REQUEST_METHOD"]) : strtoupper($_SERVER["REQUEST_METHOD"]))
            : null
        );
    }

    /**
     * @return bool
     */
    private static function isAjax() : bool
    {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    }

    /**
     * @return bool
     */
    private static function isCli() : bool
    {
        return (php_sapi_name() === 'cli');
    }

    /**
     * @return stdClass
     */
    private static function cookie() : stdClass
    {
        return (object) ($_COOKIE ?? []);
    }

    /**
     * @return stdClass
     */
    private static function session() : stdClass
    {
        return (object) ($_SESSION ?? []);
    }

    /**
     * @return array
     */
    private static function server() : ?array
    {
        return $_SERVER ?? null;
    }
}
