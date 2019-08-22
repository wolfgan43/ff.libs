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
use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\storage\drivers\Array2XML;
use Exception;

class Response
{
    const ERROR_BUCKET                              = "response";

    private static $content_type                    = null;

    public static function setContentType($content_type)
    {
        self::$content_type                         = $content_type;
    }

    /**
     * @param int $status
     * @param null $msg
     * @todo da inserire la pagina html per la response DataHtml
     */
    public static function error($status = 404, $msg = null)
    {
        switch (Request::accept()) {
            case "application/json":
            case "text/json":
                $response = new DataResponse();
                $response->error($status, $msg);
                break;
            default:
                $response = new DataHtml();
                $response->error($status, $msg);
                $response->html = $msg;
        }

        self::send($response, $status);
    }

    public static function sendRawData($data, $content_type, $status = null)
    {
        if (self::isValidContentType($content_type)) {
            self::sendHeadersByMimeType($content_type);
            if ($status) {
                Response::code($status);
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

        exit;
    }

    private static function toXml($data)
    {
        if (is_object($data)) {
            $data                                   = get_object_vars($data);
        } elseif (!is_array($data)) {
            $data                                   = array($data);
        }

        try {
            $data                                   = Array2XML::createXML("root", $data);
        } catch (Exception $e) {
            Error::register($e, static::ERROR_BUCKET);
        }

        return $data;
    }

    private static function toPlainText($data)
    {
        if (is_array($data)) {
            $data                                   = implode(" ", $data);
        } elseif (is_object($data)) {
            $data                                   = implode(" ", get_object_vars($data));
        }

        return $data;
    }

    private static function toJson($data)
    {
        if (!is_array($data) && !is_object($data)) {
            $data                                   = array($data);
        }

        return json_encode($data);
    }

    /**
     * @param string $content_type
     * @return bool
     */
    private static function isValidContentType($content_type)
    {
        if (self::invalidAccept($content_type)) {
            /**
             * @todo da gestire i tipi accepted self::sendHeadersByMimeType(...)
             */
            Response::code(501);
            echo "content type " . $content_type . " is different to http_accept: " . Request::accept();
            exit;
        }

        return true;
    }

    /**
     * @param DataAdapter $response
     * @param null $status
     */
    public static function send($response, $status = null)
    {
        if (self::isValidContentType($response::CONTENT_TYPE)) {
            if ($response->status) {
                Response::code($status);
            }

            self::sendHeadersByMimeType($response::CONTENT_TYPE);
            echo $response->output();
        }
        exit;
    }

    public static function redirect($destination, $http_response_code = null, $headers = null)
    {
        if ($http_response_code === null) {
            $http_response_code = 301;
        }
        Log::write("REFERER: " . Request::referer(), "redirect", $http_response_code, $destination);

        self::sendHeaders(array(
            "cache" => "must-revalidate"
        ));

        if (strpos($destination, DIRECTORY_SEPARATOR) !== 0 && strpos($destination, "http") !== 0) {
            $destination                            = Request::protocol() . $destination;
        }
        if (Request::protocol_host() . $_SERVER["REQUEST_URI"] != $destination) {
            header("Location: " . $destination, true, $http_response_code);
            if (is_array($headers) && count($headers)) {
                foreach ($headers as $key => $value) {
                    header(ucfirst(str_replace(array(" ", "_"), "-", $key)) . ": " . $value);
                }
            }
        } else {
            Response::code(400);
        }

        exit;
    }

    public static function code($code = null)
    {
        return ($code
             ? http_response_code($code)
             : http_response_code()
         );
    }

    private static function sendHeadersByMimeType($mimetype)
    {
        if (!headers_sent()) {
            if (0) {
                self::sendHeaders(array("mimetype" => $mimetype));
            } else {
                header("Content-type: " . $mimetype);
            }
        }
    }

    public static function sendHeaders($params = null)
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
            header("Content-Length: " . $size);
        }
        if (strlen($etag)) {
            header("ETag: " . $etag);
        }

        if ($mimetype) {
            $content_type = $mimetype;
            if ($mimetype == "text/css" || $mimetype == "application/x-javascript") {
                header("Vary: Accept-Encoding");
            } elseif ($mimetype == "text/html") {
                $content_type .= "; charset=UTF-8";
                header("Vary: Accept-Encoding");
            }

            header("Content-type: $content_type");
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
                ? explode(",", str_replace(" ", "", $_SERVER["HTTP_ACCEPT_ENCODING"]))
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

    private static function invalidAccept($content_type)
    {
        $accept = Request::accept();

        return $accept != '*/*' && strpos($accept, $content_type) === false;
    }
}
