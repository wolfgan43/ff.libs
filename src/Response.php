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
use phpformsframework\libs\storage\Media;

class Response
{
    private static $content_type = null;

    public static function setContentType($content_type)
    {
        self::$content_type                         = $content_type;
    }

    public static function error($status = 404, $response = null, $headers = null, $type = null)
    {
        self::send($response, $headers, $type, $status);
    }
    public static function send($response = null, $headers = null, $type = null, $status = null)
    {
        if ($status) {
            Response::code($status);
        }

        if (!$type) {
            $type = self::$content_type;
        }

        if (is_array($headers) && count($headers)) {
            foreach ($headers as $header) {
                header($header);
            }
        }

        if (!$type) {
            $type                                   = "text";
            if (is_array($response)) {
                if (isset($response["html"])) {
                    $type                           = "html";
                } elseif (Request::isAjax() || Request::method() != "GET") {
                    $type                           = "json";
                }
            }
        }

        if (0 &&  self::invalidAccept($type)) {
            Response::code(501);
            echo "content type " . $type . "is different to http_accept";
        } else {
            if (isset($response["error"]) && $response["error"]) {
                $response["error"] = Translator::get_word_by_code($response["error"]);
            }

            self::sendHeadersByType($type);
            switch ($type) {
                case "js":
                    echo $response;
                    break;
                case "css":
                    echo $response;
                    break;
                case "html":
                    if (isset($response["error"]) && $response["error"]) {
                        echo $response["error"];
                    } elseif (isset($response["html"])) {
                        echo $response["html"];
                    }
                    break;
                case "xml":
                    echo $response;
                    break;
                case "soap":
                    //todo: da fare
                    break;
                case "json":
                    echo json_encode((array) $response);
                    break;
                case "text":
                default:
                    if (isset($response["error"]) && $response["error"]) {
                        echo $response["error"];
                    } elseif (isset($response["data"])) {
                        if (is_array($response["data"])) {
                            echo implode(" ", $response["data"]);
                        } else {
                            echo $response["data"];
                        }
                    }
            }
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

    private static function sendHeadersByType($type)
    {
        if (!headers_sent()) {
            $mimetype = Media::MIMETYPE[$type];
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

    private static function invalidAccept($ext)
    {
        return isset($_SERVER["HTTP_ACCEPT"]) && $_SERVER["HTTP_ACCEPT"] && strpos($_SERVER["HTTP_ACCEPT"], Media::getMimeTypeByExtension($ext)) === false;
    }
}