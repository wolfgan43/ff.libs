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
namespace phpformsframework\libs\storage;

use phpformsframework\libs\storage\dto\OrmDef;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Exception;
use phpformsframework\libs\international\Data;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\util\Normalize;
use stdClass;

/**
 * Class DatabaseConverter
 * @package phpformsframework\libs\storage
 */
class DatabaseConverter
{
    private const ENCODING              = Constant::ENCODING;
    private const IMAGE_RESIZE          = [
                                            "crop"          => "x",
                                            "proportional"  => "-",
                                            "stretch"       => "|"
                                        ];
    private const DEFAULT_IMAGE_RESIZE  = "x";

    protected $prototype                = [
                                            "image"     => ["width", "height", "resize"],
                                            "encrypt"   => ["password", "algorithm", "cost"],
                                            "dencrypt"  => ["password", "algorithm", "cost"]
                                        ];
    /**
     * @var OrmDef
     */
    private $def                         = null;

    private $to                         = [];
    private $in                         = [];

    public function __construct(OrmDef $def)
    {
        $this->def = $def;
    }

    /**
     * @param string $field_output
     * @param string|null $field_db
     * @return string
     * @throws Exception
     */
    public function set(string &$field_output, string $field_db = null) : string
    {
        $casts                                      = $this->converterCasts($field_output);
        if (!$field_db) {
            $field_db                               = $field_output;
        }

        $this->converterCallback($casts, $field_db, $field_output);

        return $this->converterStruct($field_db, $field_output);
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @param mixed|null $value
     * @return string
     */
    public function in(string $name, $value = null): string
    {
        if ($value && isset($this->in[$name])) {
            foreach ($this->in[$name] as $func => $properties) {
                $value = $this->$func($value, $properties);
            }
        }

        return $value;
    }

    /**
     * @param array $record
     * @return array
     */
    public function to(array $record) : array
    {
        foreach (array_intersect_key($this->to, $record) as $field => $funcs) {
            foreach ($funcs as $func => $properties) {
                $record[$field] = $this->$func($record[$field], $properties);
            }
        }

        return $record;
    }

    /**
     * @return bool
     */
    public function issetTo() : bool
    {
        return !empty($this->to);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function encode(string $value) : string
    {
        return htmlspecialchars($value, ENT_QUOTES, self::ENCODING, true);
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string
     * @throws Exception
     */
    protected function image(string $value, stdClass $properties = null) : string
    {
        $value = $this->encode($value);

        $mode   = null;
        $width  = null;
        $height = null;
        if (!empty($properties->width) && !empty($properties->height)) {
            $mode   = $properties->width . (self::IMAGE_RESIZE[$properties->resize] ?? self::DEFAULT_IMAGE_RESIZE) . $properties->height;
            $width  = ' width="' . $properties->width . '"';
            $height = ' height="' . $properties->height . '"';
        }

        return '<img src="' . Media::getUrl($value, $mode) . '" alt="' . basename($value) . '"' . $width . $height . ' />';
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string
     * @throws Exception
     */
    protected function imageUrl(string $value, stdClass $properties = null) : string
    {
        $value = $this->encode($value);

        $mode   = null;
        if (!empty($properties->width) && !empty($properties->height)) {
            $mode   = $properties->width . (self::IMAGE_RESIZE[$properties->resize] ?? self::DEFAULT_IMAGE_RESIZE) . $properties->height;
        }

        return Media::getUrl($value, $mode);
    }

    /**
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    protected function timeElapsed(string $value) : ?string
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
    protected function dateElapsed(string $value): string
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
     * @return string|null
     * @throws Exception
     */
    protected function dateTime(string $value) : ?string
    {
        return (new Data($value, "Timestamp"))->getValue("DateTime", Locale::getCodeLang());
    }

    /**
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    protected function date(string $value) : ?string
    {
        return (new Data($value, "Timestamp"))->getValue("Date", Locale::getCodeLang());
    }

    /**
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    protected function time(string $value) : ?string
    {
        return (new Data($value, "Timestamp"))->getValue("Time", Locale::getCodeLang());
    }

    /**
     * @param string $value
     * @return string|null
     */
    protected function slug(string $value) : ?string
    {
        return Normalize::urlRewrite($value);
    }

    /**
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    protected function translate(string $value) : ?string
    {
        return Translator::getWordByCodeCached($value);
    }

    /**
     * @param string $value
     * @return int
     */
    protected function ascii(string $value) : int
    {
        return ord($value);
    }

    /**
     * @param string $value
     * @return int
     */
    protected function length(string $value) : int
    {
        return strlen($value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function lower(string $value) : string
    {
        return strtolower($value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function upper(string $value) : string
    {
        return strtoupper($value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function password(string $value) : string
    {
        return "*" . strtoupper(sha1(sha1($value, true)));
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string|null
     */
    protected function decrypt(string $value, stdClass $properties = null) : ?string
    {
        return (($params = $this->getEncryptParams($properties->password, $properties->algorithm, $properties->cost))
            ? openssl_decrypt(base64_decode($value), $params["method"], $params["key"], OPENSSL_RAW_DATA, $params["key"])
            : null
        );
    }

    /**
     * @param string $value
     * @param stdClass|null $properties
     * @return string|null
     */
    protected function encrypt(string $value, stdClass $properties = null) : ?string
    {
        return (($params = $this->getEncryptParams($properties->password, $properties->algorithm, $properties->cost))
            ? base64_encode(openssl_encrypt($value, $params["method"], $params["key"], OPENSSL_RAW_DATA, $params["iv"]))
            : null
        );
    }

    /*********************+
     * SET
     */

    /**
     * @param string $subject
     * @return array|null
     */
    private function converterCasts(string &$subject) : ?array
    {
        $casts                                      = null;
        if (strpos($subject, ":") !== false) {
            $casts                                  = explode(":", $subject);
            $subject                                = array_shift($casts);
        }

        return $casts;
    }

    /**
     * @param string $field_db
     * @param string|null $field_output
     * @return string
     * @throws Exception
     */
    private function converterStruct(string $field_db, string $field_output = null) : string
    {
        $struct_type                                = $this->getStructField($field_db);
        $casts                                      = $this->converterCasts($struct_type);

        $this->converterCallback($casts, $field_db, $field_output);

        return $struct_type;
    }

    /**
     * @param array|null $casts
     * @param string|null $field_db
     * @param string|null $field_output
     * @throws Exception
     */
    private function converterCallback(array $casts = null, string $field_db = null, string $field_output  = null) : void
    {
        if ($casts) {
            foreach ($casts as $cast) {
                $params                                 = [];
                $op                                     = strtolower(substr($cast, 0, 2));
                if (strpos($cast, "(") !== false) {
                    $func = explode("(", $cast, 2);
                    $cast = $func[0];
                    $params = explode(",", rtrim($func[1], ")"));
                }

                if ($op === "to" && $field_output) {
                    $this->add($this->to, $field_output, substr($cast, 2), $params);
                } elseif ($op === "in" && $field_db) {
                    $this->add($this->in, $field_db, substr($cast, 2), $params);
                } else {
                    throw new Exception($cast . " is not a valid function", 500);
                }
            }
        }
    }

    /**
     * @param array $ref
     * @param string $field_name
     * @param string $func
     * @param array|null $params
     * @throws Exception
     */
    private function add(array &$ref, string $field_name, string $func, array $params = null) : void
    {
        if (!method_exists($this, $func)) {
            throw new Exception("Function " . $func . " not implemented in " . __CLASS__, "501");
        }

        $properties                                                         = [];
        if (isset($this->prototype[strtolower($func)])) {
            foreach ($this->prototype[strtolower($func)] as $i => $key) {
                $properties[$key]                                           = trim($params[$key] ?? $params[$i]) ?: null;
            }
        } else {
            $properties                                                     = $params;
        }

        $ref[$field_name][$func]                                            = (object) $properties;
    }
    /**
     * @param string $key
     * @return string
     * @throws Exception
     */
    private function getStructField(string $key) : string
    {
        if (!isset($this->def->struct[$key])) {
            throw new Exception("Field: '" . $key . "' not found in struct on table: " . $this->def->table["name"], 500);
        }

        return $this->def->struct[$key];
    }

    /**
     * @param string $password
     * @param string $algorithm
     * @param int $cost
     * @return array|null
     */
    private function getEncryptParams(string $password, string $algorithm, int $cost = 12) : ?array
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
}
