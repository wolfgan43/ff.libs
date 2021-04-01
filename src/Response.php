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

use phpformsframework\libs\dto\DataAdapter;
use phpformsframework\libs\dto\DataError;
use phpformsframework\libs\gui\controllers\ErrorController;
use phpformsframework\libs\storage\drivers\Array2XML;
use Exception;
use phpformsframework\libs\util\Normalize;
use phpformsframework\libs\util\ServerManager;

/**
 * Class Response
 * @package phpformsframework\libs
 */
class Response
{
    use ServerManager;

    public const CONTINUE                           = 100;
    public const SWITCHING_PROTOCOLS                = 101;
    public const PROCESSING                         = 102;
    public const EARLY_HINTS                        = 103;
    public const OK                                 = 200;
    public const CREATED                            = 201;
    public const ACCEPTED                           = 202;
    public const NON_AUTHORITATIVE_INFORMATION      = 203;
    public const NO_CONTENT                         = 204;
    public const RESET_CONTENT                      = 205;
    public const PARTIAL_CONTENT                    = 206;
    public const MULTI_STATUS                       = 207;
    public const ALREADY_REPORTED                   = 208;
    public const IM_USED                            = 226;
    public const MULTIPLE_CHOICES                   = 300;
    public const MOVED_PERMANENTLY                  = 301;
    public const FFOUND                             = 302;
    public const SEE_OTHER                          = 303;
    public const NOT_MODIFIED                       = 304;
    public const USE_PROXY                          = 305;
    public const SWITCH_PROXY                       = 306;
    public const TEMPORARY_REDIRECT                 = 307;
    public const PERMANENT_REDIRECT                 = 308;
    public const BAD_REQUEST                        = 400;
    public const UNAUTHORIZED                       = 401;
    public const PAYMENT_REQUIRED                   = 402;
    public const FORBIDDEN                          = 403;
    public const NOT_FOUND                          = 404;
    public const METHOD_NOT_ALLOWED                 = 405;
    public const NOT_ACCEPTABLE                     = 406;
    public const PROXY_AUTHENTICATION_REQUIRED      = 407;
    public const REQUEST_TIMEOUT                    = 408;
    public const CONFLICT                           = 409;
    public const GONE                               = 410;
    public const LENGTH_REQUIRED                    = 411;
    public const PRECONDITION_FAILED                = 412;
    public const PAYLOAD_TOO_LARGE                  = 413;
    public const URI_TOO_LONG                       = 414;
    public const UNSUPPORTED_MEDIA_TYPE             = 415;
    public const RANGE_NOT_SATISFIABLE              = 416;
    public const EXPECTATION_FAILED                 = 417;
    public const IM_A_TEAPOT                        = 418;
    public const MISDIRECTED_REQUEST                = 421;
    public const UNPROCESSABLE_ENTITY               = 422;
    public const LOCKED                             = 423;
    public const FAILED_DEPENDENCY                  = 424;
    public const TOO_EARLY                          = 425;
    public const UPGRADE_REQUIRED                   = 426;
    public const PRECONDITION_REQUIRED              = 428;
    public const TOO_MANY_REQUESTS                  = 429;
    public const REQUEST_HEADER_FIELDS_TOO_LARGE    = 431;
    public const UNAVAILABLE_FOR_LEGAL_REASONS      = 451;
    public const INTERNAL_SERVER_ERROR              = 500;
    public const NOT_IMPLEMENTED                    = 501;
    public const BAD_GATEWAY                        = 502;
    public const SERVICE_UNAVAILABLE                = 503;
    public const GATEWAY_TIMEOUT                    = 504;
    public const HTTP_VERSION_NOT_SUPPORTED         = 505;
    public const VARIANT_ALSO_NEGOTIATES            = 506;
    public const INSUFFICIENT_STORAGE               = 507;
    public const LOOP_DETECTED                      = 508;
    public const NOT_EXTENDED                       = 510;
    public const NETWORK_AUTHENTICATION_REQUIRED    = 511;

    private const ERROR_BUCKET                      = "response";

    private const HOOK_ON_BEFORE_SEND               = "Response::onBeforeSend";

    private static $content_type                    = null;

    /**
     * @param callable $func
     */
    public static function onBeforeSend(callable $func) : void
    {
        Hook::register(self::HOOK_ON_BEFORE_SEND, $func);
    }

    /**
     * @param string $content_type
     */
    public static function setContentType(string $content_type) : void
    {
        self::$content_type                         = $content_type;
    }

    /**
     * @param int $code
     * @param string|null $msg
     * @return void
     * @todo da tipizzare
     */
    public static function sendError(int $code = 404, string $msg = null) : void
    {
        switch (Kernel::$Page->accept()) {
            case "application/json":
            case "text/json":
                Log::registerProcedure("Request", "validator" . Log::CLASS_SEP . "error");

                $response = new DataError();
                $response->error(self::errorStatus($code), $msg);
                self::send($response, ["cache" => "no-cache"]);
                break;
            case "text/html":
                Log::registerProcedure("Router", "page" . Log::CLASS_SEP . "error");
                try {
                    self::send((new ErrorController())
                        ->error(self::errorStatus($code), $msg)
                        ->display());
                } catch (Exception $e) {
                    header("Content-Type: text/html");
                    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                    header("Pragma: no-cache");

                    $status = $e->getCode();
                    $status_message = Error::getErrorMessage($status);
                    Debug::setBackTrace($e->getTrace());
                    self::httpCode($status);
                    echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>' . $status_message . '</title>
</head><body>
<h1>' . $e->getMessage() . '</h1>
<p>The requested URL ' . self::pathinfo() . ' was not found on this server.</p>
<p>Additionally, a ' . $status_message . '
error was encountered while trying to use an ErrorDocument to handle the request.</p>
' . Debug::dump($e->getMessage(), true) . '
</body></html>';
                    exit;
                }
                break;
            case "php/cli":
                Debug::dump($msg);
                break;
            default:
                self::sendErrorPlain($msg, $code);
        }
    }

    public static function sendErrorPlain(string $msg = null, int $code = null) : void
    {
        self::httpCode(self::errorStatus($code));
        die($msg ?? "Unknown Error");
    }

    /**
     * @param int|null $code
     * @return int
     */
    private static function errorStatus(int $code = null) : int
    {
        return ($code < 400
            ? 500
            : $code
        );
    }
    /**
     * @param mixed $data
     * @param string $content_type
     * @param array|null $headers
     * @param null|int $status
     * @return void
     * @throws Exception
     * @todo da tipizzare
     */
    public static function sendRawData($data, string $content_type, array $headers = [], $status = null)
    {
        if (self::isValidContentType($content_type)) {
            self::sendHeadersByMimeType($content_type, $headers);
            if ($status) {
                self::httpCode($status);
            }
            switch ($content_type) {
                case "application/json":
                case "text/json":
                    echo self::toJson($data);
                    break;
                case "application/xml":
                case "text/xml":
                    echo self::toXml($data);
                    break;
                default:
                    echo self::toPlainText($data);
            }
        }

        self::endScript($data);
    }

    /**
     * @param mixed $data
     * @return string
     * @throws Exception
     * @todo da tipizzare
     */
    private static function toXml($data) : string
    {
        if (is_object($data)) {
            $data                                   = get_object_vars($data);
        } elseif (!$data) {
            $data                                   = null;
        }

        try {
            $data                                   = Array2XML::createXML("root", $data);
        } catch (Exception $e) {
            Error::register($e, static::ERROR_BUCKET);
        }

        return $data->saveXML();
    }

    /**
     * @todo da tipizzare
     * @param mixed $data
     * @return string
     */
    private static function toPlainText($data) : string
    {
        if (is_array($data)) {
            $data                                   = implode(" ", $data);
        } elseif (is_object($data)) {
            $data                                   = implode(" ", get_object_vars($data));
        }

        return $data;
    }

    /**
     * @todo da tipizzare
     * @param mixed $data
     * @return string
     */
    private static function toJson($data) : string
    {
        return (!is_array($data) && !is_object($data)
            ? '[]'
            : json_encode($data)
        );
    }

    /**
     * @param string $content_type
     * @return bool
     */
    private static function isValidContentType(string $content_type) : bool
    {
        if (self::invalidAccept($content_type)) {
            /**
             * @todo da gestire i tipi accepted self::sendHeadersByMimeType(...)
             */
            self::httpCode(501);
            $message = "content type " . $content_type . " is different to http_accept: " . self::rawAccept();
            echo $message;
            self::endScript($message);
        }

        return true;
    }

    /**
     * @param DataAdapter $response
     * @param array|null $headers
     * @param null|int $status
     * @return void
     */
    public static function send(DataAdapter $response, array $headers = [], int $status = null) : void
    {
        if (self::isValidContentType($response::CONTENT_TYPE)) {
            self::httpCode($status ?? $response->status);

            self::sendHeadersByMimeType($response::CONTENT_TYPE, $headers);
            echo $response->output();
        }

        self::endScript($response->toLog());
    }

    /**
     * @todo da tipizzare
     * @param null $message
     */
    private static function endScript($message = null) : void
    {
        Hook::handle(self::HOOK_ON_BEFORE_SEND);

        Log::write($message);
        exit;
    }

    /**
     * @param string $response
     * @param array|null $headers
     * @return void
     * @throws Exception
     */
    public static function sendHtml(string $response, array $headers = []) : void
    {
        self::sendRawData($response, "text/html", $headers);
    }

    /**
     * @param array $response
     * @param array|null $headers
     * @return void
     * @throws Exception
     */
    public static function sendJson(array $response, array $headers = []) : void
    {
        self::sendRawData($response, "application/json", $headers);
    }

    /**
     * @param string|null $destination
     * @param int|null $http_response_code
     * @param array|null $headers
     */
    public static function redirect(string $destination = null, int $http_response_code = null, array $headers = null) : void
    {
        if ($http_response_code === null) {
            $http_response_code = 301;
        }

        self::sendHeaders(array(
            "cache" => "must-revalidate"
        ));

        if (strpos($destination, DIRECTORY_SEPARATOR) !== 0 && strpos($destination, "http") !== 0) {
            $destination                            = self::protocol() . $destination;
        }
        if (self::protocolHost() . $_SERVER["REQUEST_URI"] != $destination) {
            header("Location: " . $destination, true, $http_response_code);
            if (!empty($headers)) {
                foreach ($headers as $key => $value) {
                    header(ucfirst(str_replace(array(" ", "_"), "-", $key)) . ": " . $value);
                }
            }
        } else {
            self::httpCode(400);
        }

        self::endScript("Redirect: " . self::referer() . " => " . $destination);
    }

    /**
     * @param null|int $code
     * @return int
     */
    public static function httpCode(int $code = null) : int
    {
        return ($code
             ? http_response_code($code)
             : http_response_code()
         );
    }

    /**
     * @param string $mimetype
     * @param array $headers
     */
    private static function sendHeadersByMimeType(string $mimetype, array $headers = []) : void
    {
        if (!headers_sent()) {
            self::sendHeaders(array_replace($headers, ["mimetype" => $mimetype]));
        }
    }

    /**
     * @param array|null $params
     */
    public static function sendHeaders(array $params = null) : void
    {
        $keep_alive			        = isset($params["keep_alive"])  ? $params["keep_alive"]			: null;
        $max_age				    = isset($params["max_age"])     ? $params["max_age"]            : null;
        $expires				    = isset($params["expires"])     ? $params["expires"]            : null;
        $compress			        = isset($params["compress"])    ? $params["compress"]           : null;
        $cache					    = isset($params["cache"])		? $params["cache"]				: "public";
        $disposition			    = isset($params["disposition"])	? $params["disposition"]		: "inline";
        $filename			        = isset($params["filename"])    ? $params["filename"]           : null;
        $mtime			            = isset($params["mtime"])       ? $params["mtime"]              : null;
        $mimetype			        = isset($params["mimetype"])	? $params["mimetype"]			: null;
        $size				        = isset($params["size"])        ? $params["size"]               : null;
        $etag				        = isset($params["etag"])		? $params["etag"]				: null;

        if ($size) {
            header("Accept-Ranges: bytes");
            header("Content-Length: " . $size);
        }

        if (strlen($etag)) {
            header("ETag: " . $etag);
        }

        if (!$mimetype) {
            $mimetype = Kernel::$Page->accept();
        }

        if ($mimetype) {
            $content_type = $mimetype;
            if ($mimetype == "text/css" || $mimetype == "application/x-javascript") {
                header("Vary: Accept-Encoding");
            } elseif ($mimetype == "text/html") {
                $content_type .= "; charset=UTF-8";
                header("Vary: Accept-Encoding");
            }

            header("Content-Type: $content_type");
        }

        if ($disposition && $filename) {
            $content_disposition = $disposition;
            $content_disposition .= "; filename=" . rawurlencode($filename);
            header("Content-Disposition: " . $content_disposition);
        }


        if ($keep_alive) {
            header("Connection: Keep-Alive");
        }
        if ($compress) {
            $accept_encoding = (
                isset($_SERVER["HTTP_ACCEPT_ENCODING"])
                ? Normalize::string2array($_SERVER["HTTP_ACCEPT_ENCODING"])
                : null
            );
            if ($accept_encoding) {
                if ($compress === true) {
                    $compress   = $accept_encoding[0];
                } elseif (array_search($compress, $accept_encoding) === false) {
                    $compress = false;
                }
                if ($compress) {
                    header("Content-encoding: " . $compress);
                }
            }
        }

        switch ($cache) {
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
                if ($expires === false && $max_age === false) {
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
                        if ($mtime) {
                            $mod_gmt = gmdate("D, d M Y H:i:s", $mtime) . " GMT";
                            header("Last-Modified: $mod_gmt");
                        }
                        if ($max_age === null) {
                            header("Cache-Control: public");
                        } else {
                            header("Cache-Control: public, max-age=$max_age");
                        }
                    }
                }

                header("Pragma: !invalid");
        }
    }

    /**
     * @param string $content_type
     * @return bool
     */
    private static function invalidAccept(string $content_type) : bool
    {
        $accept = self::rawAccept();
        return $accept != "" && strpos($accept, $content_type) === false && strpos($accept, "*/*") === false;
    }
}
