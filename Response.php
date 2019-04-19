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

class Response {
     public static function error($status = 404, $response = null, $headers = null, $type = null) {
        self::send($response, $headers, $type, $status);
    }
    public static function send($response = null, $headers = null, $type = null, $status = null) {
        if(isset($response["data"]) && is_array($response["data"])) {
            $size                                   = strlen(http_build_query($response["data"], '', ''));
        } elseif(isset($response["data"])) {
            $size                                   = strlen($response["data"]);
        } else {
            $size                                   = 0;
        }

        Log::request($response["error"], $response["status"], $size);

        if(!$status) {
            $status                                 = (isset($response["status"])
                                                        ? $response["status"]
                                                        : 200
                                                    );
        }

        Response::code($status);
        if (is_array($headers) && count($headers)) {
            foreach ($headers AS $header) {
                header($header);
            }
        }

        if(!$type) {
            switch ($_SERVER["HTTP_ACCEPT"]) {
                case "application/xml":
                case "text/xml":
                    $type                           = "xml";
                    break;
                case "application/json":
                case "text/json":
                    $type                           = "json";
                    break;
                case "application/soap+xml":
                    $type                           = "soap";
                    break;
                case "text/html":
                    $type                           = "html";
                    break;
                default:
            }
            if(!$type) {
                if (is_array($response)) {
                    if (isset($response["html"])) {
                        $type                       = "html";
                    } else {
                        $type                       = "json";
                    }
                } else {
                    $type                           = "text";
                }
            }
        }

        if(isset($response["error"]))               { $response["error"] = Translator::get_word_by_code($response["error"]); }
        switch($type) {
            case "xml":
                header("Content-type: application/xml");
                echo $response;
                break;
            case "soap":
                header("Content-type: application/soap+xml");
                //todo: self::soap_client($response["url"], $response["headers"], $response["action"], $response["data"], $response["auth"]);
                break;
            case "html":
                header("Content-type: text/html");
                echo $response["html"];
                break;
            case "text":
                header("Content-type: text/plain");
                echo $response;
                break;
            case "json":
            default:
                header("Content-type: application/json");
                echo json_encode((array) $response);
        }

        exit;
    }

    public static function redirect($destination, $http_response_code = null, $headers = null)
    {
        if($http_response_code === null)            { $http_response_code = 301; }
        Log::write("REFERER: " . $_SERVER["HTTP_REFERER"], "redirect", $http_response_code, $destination);

        self::sendHeaders(array(
            "cache" => "must-revalidate"
        ));

        if(strpos($destination, "/") !== 0 && strpos($destination, "http") !== 0) {
            $destination                            = "http" . ($_SERVER["HTTPS"] ? "s" : "") . "://" . $destination;
        }
        if("http" . ($_SERVER["HTTPS"] ? "s": "") . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] != $destination) {
            header("Location: " . $destination, true, $http_response_code);
            if(is_array($headers) && count($headers)) {
                foreach ($headers AS $key => $value) {
                    header(ucfirst(str_replace(array(" ", "_"), "-", $key)) . ": " . $value);
                }
            }
        } else {
            Response::code(400);
        }

        exit;
    }

    public static function code($code = null) {
         return ($code
             ? http_response_code($code)
             : http_response_code()
         );

    }
    public static function sendHeaders($params = null) {
        //header_remove();
        $days                       = 7;

        $keep_alive			        = $params["keep_alive"]		    ? $params["keep_alive"]			: false;
        $max_age				    = $params["max_age"]            ? $params["max_age"]            : null;
        $expires				    = $params["expires"]            ? $params["expires"]            : null;
        $compress			        = $params["compress"]           ? $params["compress"]           : false;
        $cache					    = $params["cache"]			    ? $params["cache"]				: "public";
        $disposition			    = $params["disposition"]		? $params["disposition"]		: "inline";
        $filename			        = $params["filename"]           ? $params["filename"]           : null;
        $mtime			            = $params["mtime"]              ? $params["mtime"]              : null;
        $mimetype			        = $params["mimetype"]		    ? $params["mimetype"]			: null;
        $size				        = $params["size"]               ? $params["size"]               : null;
        $etag				        = $params["etag"]				? $params["etag"]				: true;

        if($size)                   { header("Content-Length: " . $size); }
        if(strlen($etag))           { header("ETag: " . $etag); }

        if($mimetype) {
            $content_type = $mimetype;
            if ($mimetype == "text/css" || $mimetype == "application/x-javascript") {
                header("Vary: Accept-Encoding");
            } elseif ($mimetype == "text/html") {
                $content_type .= "; charset=UTF-8";
                header("Vary: Accept-Encoding");
            } elseif (strpos($mimetype, "image/") === 0) {
                $days = 365;
            }

            header("Content-type: $content_type");
        }

        if($disposition) {
            $content_disposition = $disposition;
            if ($filename) {
                $content_disposition .= "; filename=" . rawurlencode($filename);
            }
            header("Content-Disposition: " . $content_disposition);
        }


        if($keep_alive) {
            header("Connection: Keep-Alive");
        }
        if($compress) {
            header("Content-encoding: " . ($compress === true ? "gzip" : $compress));
        }

        switch($cache) {
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
                if($expires === false && $max_age === false) {
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
                        if($mtime) {
                            $mod_gmt = gmdate("D, d M Y H:i:s", $mtime) . " GMT";
                            header("Last-Modified: $mod_gmt");
                        }
                        if ($max_age === null) {
                            //$max_age = 60 * 60 * $hours;
                            $max_age = 60 * 60 * 24 * $days;
                            header("Cache-Control: public, max-age=$max_age");
                        }
                        else {
                            header("Cache-Control: public, max-age=$max_age");
                        }
                    }
                }

                header("Pragma: !invalid");
        }
    }
}