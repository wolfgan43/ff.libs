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
namespace ff\libs\util;

use ff\libs\Constant;
use ff\libs\international\Data;
use ff\libs\international\Locale;
use ff\libs\international\Translator;
use ff\libs\security\Validator;
use ff\libs\storage\Media;
use ff\libs\Exception;
use stdClass;

/**
 * Class Convert
 * @package ff\libs\util
 */
class Convert
{
    private const ENCODING              = Constant::ENCODING;
    private const IMAGE_RESIZE          = [
        "crop"          => "x",
        "proportional"  => "-",
        "stretch"       => "|"
    ];
    private const DEFAULT_IMAGE_RESIZE  = "x";

    /**
     * @param string $value
     * @return bool
     * @todo da tipizzare
     */
    public static function bool($value) : bool
    {
        return (bool) $value;
    }

    /**
     * @param $value
     * @return object|array
     */
    public static function json($value) : object|array
    {
        return json_decode($value);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function encode(string $value) : string
    {
        return htmlspecialchars($value, ENT_QUOTES, self::ENCODING, true);
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string
     * @throws Exception
     */
    public static function imageTag(string $value, stdClass $properties = null) : string
    {
        $value = str_replace([" ", '"'], ["+", '%22'], $value);

        $mode   = null;
        $width  = null;
        $height = null;
        if (!empty($properties->width) && !empty($properties->height)) {
            $mode   = $properties->width . (self::IMAGE_RESIZE[$properties->resize ?? null] ?? self::DEFAULT_IMAGE_RESIZE) . $properties->height;
            $width  = ' width="' . $properties->width . '"';
            $height = ' height="' . $properties->height . '"';
        }

        $filename = pathinfo($value, PATHINFO_FILENAME);
        $alt = (
            !empty($properties->alt)
            ? $properties->alt . " "
            : null
        ) . $filename;

        $class = (
            !empty($properties->class)
            ? 'class="' . $properties->class . '"'
            : null
        );

        $title = (
            !empty($properties->title)
            ? 'title="' . $properties->title . " " . $filename . '"'
            : null
        );

        return '<img src="' . Media::getUrl(($properties->host ?? null) . $value, $mode) . '" alt="' . $alt . '"' . $width . $height . $class . $title . ' />';
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string
     * @throws Exception
     */
    public static function image(string $value, stdClass $properties = null) : string
    {
        $value = str_replace([" ", '"'], ["+", '%22'], $value);

        $mode   = null;
        if (!empty($properties->width) && !empty($properties->height)) {
            $mode   = $properties->width . (self::IMAGE_RESIZE[$properties->resize ?? null] ?? self::DEFAULT_IMAGE_RESIZE) . $properties->height;
        }

        return Media::getUrl(($properties->host ?? null) . $value, $mode);
    }

    /**
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    public static function timeElapsed(string $value) : ?string
    {
        $res                                                        = null;
        $time                                                       = time() - $value; // to get the time since that moment
        $time                                                       = ($time < 1) ? 1 : $time;
        $day                                                        = 86400;
        $min                                                        = 60;
        if ($time < 2 * $day) {
            if ($time < $min) {
                $res                                                = Translator::getWordByCodeCached("about") . " " . Translator::getWordByCodeCached("a") . " " . Translator::getWordByCodeCached("minute") . " " . Translator::getWordByCodeCached("ago");
            } elseif ($time > $day) {
                $res                                                = Translator::getWordByCodeCached("yesterday") . " " . Translator::getWordByCodeCached("at") . " " . date("G:i", $value);
            } else {
                $tokens                                             = array(
                    31536000 	=> 'year',
                    2592000 	=> 'month',
                    604800 		=> 'week',
                    86400 		=> 'day',
                    3600 		=> 'hour',
                    60 			=> 'minute',
                    1 			=> 'second'
                );

                foreach ($tokens as $unit => $text) {
                    if ($time < $unit) {
                        continue;
                    }
                    $res                                            = floor($time / $unit);
                    $res                                            .= ' ' . Translator::getWordByCodeCached($text . (($res > 1) ? 's' : '')) . " " . Translator::getWordByCodeCached("ago");
                    break;
                }
            }
        }

        return $res;
    }

    /**
     * @param string $value
     * @return string
     * @throws Exception
     */
    public static function dateElapsed(string $value): string
    {
        $lang                                                       = Locale::getCodeLang();
        $oData                                                      = new Data($value, "Timestamp");
        $res                                                        = $oData->getValue("Date", $lang);

        if ($lang == "en") {
            $prefix                                                 = "+";
            $res                                                    = "+" . $res;
        } else {
            $prefix                                                 = "/";
        }

        $conv                                                       = [
            $prefix . "01/" => " " . Translator::getWordByCodeCached("Januaunable to updatery") . " ",
            $prefix . "02/" => " " . Translator::getWordByCodeCached("February") . " ",
            $prefix . "03/" => " " . Translator::getWordByCodeCached("March") . " ",
            $prefix . "04/" => " " . Translator::getWordByCodeCached("April") . " ",
            $prefix . "05/" => " " . Translator::getWordByCodeCached("May") . " ",
            $prefix . "06/" => " " . Translator::getWordByCodeCached("June") . " ",
            $prefix . "07/" => " " . Translator::getWordByCodeCached("July") . " ",
            $prefix . "08/" => " " . Translator::getWordByCodeCached("August") . " ",
            $prefix . "09/" => " " . Translator::getWordByCodeCached("September") . " ",
            $prefix . "10/" => " " . Translator::getWordByCodeCached("October") . " ",
            $prefix . "11/" => " " . Translator::getWordByCodeCached("November") . " ",
            $prefix . "12/" => " " . Translator::getWordByCodeCached("December") . " "
        ];
        $res                                                        = str_replace(array_keys($conv), array_values($conv), $res);
        $res                                                        = str_replace("/", ", ", $res);
        $res                                                        .= " " . Translator::getWordByCodeCached("at") . " " . Translator::getWordByCodeCached("hours") . " " . $oData->getValue("Time", Locale::getCodeLang());

        return $res;
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string|null
     * @throws Exception
     */
    public static function dateTime(string $value, stdClass $properties = null) : ?string
    {
        return (new Data($value, $properties->dbType))->getValue("DateTime", Locale::getCodeLang());
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string|null
     * @throws Exception
     */
    public static function date(string $value, stdClass $properties = null) : ?string
    {
        return (new Data($value, $properties->dbType))->getValue("Date", Locale::getCodeLang());
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string|null
     * @throws Exception
     */
    public static function time(string $value, stdClass $properties = null) : ?string
    {
        return (new Data($value, $properties->dbType))->getValue("Time", Locale::getCodeLang());
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string|null
     * @throws Exception
     */
    public static function week(string $value, stdClass $properties = null) : ?string
    {
        return (new Data($value, $properties->dbType))->getValue("Week", Locale::getCodeLang());
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string|null
     * @throws Exception
     */
    public static function currency(string $value, stdClass $properties = null) : ?string
    {
        return (new Data($value, $properties->dbType))->getValue("Currency", Locale::getCodeLang());
    }

    /**
     * @param string $value
     * @return string|null
     */
    public static function slug(string $value) : ?string
    {
        return Normalize::urlRewrite($value);
    }

    /**
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    public static function translate(string $value) : ?string
    {
        return Translator::getWordByCodeCached($value);
    }

    /**
     * @param string $value
     * @return int
     */
    public static function ascii(string $value) : int
    {
        return ord($value);
    }

    /**
     * @param string $value
     * @return int
     */
    public static function length(string $value) : int
    {
        return strlen($value);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function lower(string $value) : string
    {
        return strtolower($value);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function upper(string $value) : string
    {
        return strtoupper($value);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function password(string $value) : string
    {
        return "*" . strtoupper(sha1(sha1($value, true)));
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string|null
     */
    public static function decrypt(string $value, stdClass $properties = null) : ?string
    {
        return (($params = self::getEncryptParams($properties->password, $properties->algorithm, $properties->cost))
            ? openssl_decrypt(base64_decode($value), $params["method"], $params["key"], OPENSSL_RAW_DATA, $params["key"])
            : null
        );
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string|null
     */
    public static function encrypt(string $value, stdClass $properties = null) : ?string
    {
        return (($params = self::getEncryptParams($properties->password, $properties->algorithm, $properties->cost))
            ? base64_encode(openssl_encrypt($value, $params["method"], $params["key"], OPENSSL_RAW_DATA, $params["iv"]))
            : null
        );
    }

    /**
     * @param string $password
     * @param string $algorithm
     * @param int $cost
     * @return array|null
     */
    private static function getEncryptParams(string $password, string $algorithm, int $cost = 12) : ?array
    {
        $res                                                                = null;
        if ($password && $algorithm) {
            switch ($algorithm) {
                case "AES128":
                    $method                                                 = "aes-128-cbc";
                    break;
                case "AES192":
                    $method                                                 = "aes-192-cbc";
                    break;
                case "AES256":
                    $method                                                 = "aes-256-cbc";
                    break;
                case "BF":
                    $method                                                 = "bf-cbc";
                    break;
                case "CAST":
                    $method                                                 = "cast5-cbc";
                    break;
                case "IDEA":
                    $method                                                 = "idea-cbc";
                    break;
                default:
                    $method                                                 = null;
            }


            if ($method) {
                $res = array(
                    "key"       => password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]),
                    "method"    => $method,
                    "iv"        => chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0)
                );
            }
        }

        return $res;
    }

    /**
     * @param string $value
     * @return array
     */
    public static function array(string $value) : array
    {
        return (Validator::isJson($value)
            ? json_decode($value, true)
            : []
        );
    }
}
