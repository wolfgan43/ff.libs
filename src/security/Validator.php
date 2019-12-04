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

namespace phpformsframework\libs\security;

use phpformsframework\libs\Constant;
use phpformsframework\libs\dto\DataError;
use phpformsframework\libs\Kernel;

/**
 * Class Validator
 * @package phpformsframework\libs\security
 */
class Validator
{
    const RULES                                             = array(
                                                                "bool"              => array(
                                                                    "filter"        => FILTER_VALIDATE_BOOLEAN,
                                                                    "flags"         => FILTER_NULL_ON_FAILURE,
                                                                    "options"       => array("default" => false),
                                                                ),
                                                                "domain"            => array(
                                                                    "filter"        => FILTER_VALIDATE_DOMAIN,
                                                                    "flags"         => null, //FILTER_VALIDATE_DOMAIN
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 24
                                                                ),
                                                                "email"             => array(
                                                                    "filter"        => FILTER_VALIDATE_EMAIL,
                                                                    "flags"         => null, //FILTER_FLAG_EMAIL_UNICODE
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 64
                                                                ),
                                                                "float"             => array(
                                                                    "filter"        => FILTER_VALIDATE_FLOAT,
                                                                    "flags"         => null, //FILTER_FLAG_ALLOW_THOUSAND
                                                                    "options"       => array("default" => 0),
                                                                    "length"        => 32
                                                                ),
                                                                "int"               => array(
                                                                    "filter"        => FILTER_VALIDATE_INT,
                                                                    "flags"         => null, //FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX
                                                                    "options"       => array("default" => 0),
                                                                    "length"        => 16
                                                                ),
                                                                "timestamp"         => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\phpformsframework\libs\security\Validator::isValidTimeStamp',
                                                                    "length"        => 10
                                                                ),
                                                                "ip"                => array(
                                                                    "filter"        => FILTER_VALIDATE_IP,
                                                                    "flags"         => null, //FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 15
                                                                ),
                                                                "mac"               => array(
                                                                    "filter"        => FILTER_VALIDATE_MAC,
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 24
                                                                ),
                                                                /*"regexp"          => array(
                                                                    "filter"        => FILTER_VALIDATE_REGEXP,
                                                                    "options"       => array("default" => null, "regexp" => '0')
                                                                ),*/
                                                                "url"               => array(
                                                                    "filter"        => FILTER_VALIDATE_URL,
                                                                    "flags"         => FILTER_FLAG_PATH_REQUIRED, // | FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_QUERY_REQUIRED
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 256
                                                                ),
                                                                "username"          => array(
                                                                    "filter"        => FILTER_SANITIZE_STRING,
                                                                    "flags"         => FILTER_FLAG_STRIP_LOW,
                                                                    "options"       => null,
                                                                    "callback"      => "\phpformsframework\libs\security\Validator::checkSpecialChars",
                                                                    "length"        => 48
                                                                ),
                                                                "string"            => array(
                                                                    "filter"        => FILTER_SANITIZE_STRING,
                                                                    "flags"         => FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW, //| FILTER_FLAG_STRIP_HIGH
                                                                    "options"       => null,
                                                                    "callback"      => "\phpformsframework\libs\security\Validator::checkSpecialChars",
                                                                    "length"        => 128
                                                                ),
                                                                "array"             => array(
                                                                    "filter"        => FILTER_SANITIZE_STRING,
                                                                    "flags"         => FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW, //| FILTER_FLAG_STRIP_HIGH
                                                                    "options"       => null,
                                                                    "callback"      => "\phpformsframework\libs\security\Validator::checkSpecialChars",
                                                                    "length"        => 128
                                                                ),
                                                                "arrayint"          => array(
                                                                    "filter"        => FILTER_VALIDATE_INT,
                                                                    "flags"         => FILTER_REQUIRE_ARRAY,
                                                                    "options"       => null,
                                                                    "length"        => 16
                                                                ),
                                                                "json"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\phpformsframework\libs\security\Validator::isJson',
                                                                    "length"        => 10240
                                                                ),
                                                                "password"          => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\phpformsframework\libs\security\Validator::isPassword',
                                                                    "length"        => 24
                                                                ),
                                                                "tel"               => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\phpformsframework\libs\security\Validator::isTel',
                                                                    "length"        => 16
                                                                ),
                                                                "file"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\phpformsframework\libs\security\Validator::isFile',
                                                                    "length"        => 1024000
                                                                ),
                                                                "encode"            => array(
                                                                    "filter"        => FILTER_SANITIZE_ENCODED,
                                                                    "flags"         => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH,
                                                                    "options"       => null,
                                                                    "normalize"     => true,
                                                                    "length"        => 192
                                                                ),
                                                                "slug"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\phpformsframework\libs\security\Validator::urlRewrite',
                                                                    "normalize"     => true,
                                                                    "length"        => 128
                                                                ),
                                                                "uuid"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\phpformsframework\libs\security\Validator::isUUID',
                                                                    "normalize"     => true,
                                                                    "length"        => 128
                                                                ),
                                                                "totp"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\phpformsframework\libs\security\Validator::isTotp',
                                                                    "normalize"     => true,
                                                                    "length"        => 7
                                                                ),
                                                                "text"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => "nl2br",
                                                                    "normalize"     => true,
                                                                    "length"        => 128000
                                                                )

                                                            );
    const SIGNATURES                                        = array(
                                                                'image/jpeg'        => array(
                                                                                        'FFD8FFDB',
                                                                                        'FFD8FFE000104A4649460001',
                                                                                        'FFD8FFEE',
                                                                                        'FFD8FFE1....457869660000'
                                                                                    ),
                                                                'image/png'         => array(
                                                                                        '89504E470D0A1A0A'
                                                                                    ),
                                                                'application/pdf'   => array(
                                                                                        '255044462D'
                                                                                    )
                                                            );
    private static $errors                                  = array();
    private static $errorName                               = null;

    /**
     * @param $what
     * @param string $fakename
     * @param string|null $type
     * @param null $range
     * @return DataError
     */
    public static function is(&$what, string $fakename, string $type = null, $range = null) : DataError
    {
        if (!array_key_exists($type, self::RULES)) {
            $type                                       = (
                is_array($what)
                                                            ? "array"
                                                            : "string"
                                                        );
        }
        $rule                                           = self::RULES[$type];

        self::setErrorName($fakename);
        self::setRuleOptions($rule, $range);

        $dataError                                      = self::isAllowed((array) $what, $type, $rule["length"]);
        if (!$dataError->isError()) {
            $validation                                 = filter_var($what, $rule["filter"], array(
                                                            "flags"         => $rule["flags"],
                                                            "options"       => $rule["options"]
                                                        ));

            if ($validation === null) {
                $dataError                              = self::isError(self::getErrorName() . " is not a valid " . $type . ($range ? ". The permitted values are [" . $range . "]" : ""), $type);
            } elseif (is_array($validation)) {
                if (is_array($what)) {
                    $diff                               = array_diff_key($what, array_filter($validation));
                    if (count($diff)) {
                        $dataError                      = self::isError(self::getErrorName() . "[" . implode(", ", array_keys($diff)) . "] is not valid " . $type, $type);
                    }
                } else {
                    $dataError                          = self::isError(self::getErrorName() . " is malformed");
                }
            } elseif ($validation != $what) {
                if (isset($rule["normalize"])) {
                    $what                               = $validation;
                } else {
                    $dataError                          = self::isError(self::getErrorName() . " is not a valid " . $type . ($validation && $validation !== true ? ". (" . $validation . " is valid!)" : ""), $type);
                }
            }

            if (isset($rule["callback"])) {
                $error                                  = call_user_func($rule["callback"], $what);
                if ($error) {
                    $dataError                          = self::isError($error, $type);
                }
            }
        }

        self::transform($what, $type);

        return $dataError;
    }

    /**
     * @todo da tipizzare
     * @param string $type
     * @return mixed|null
     */
    public static function getDefault(string $type)
    {
        return (isset(self::RULES[$type]["options"]["default"])
            ? self::RULES[$type]["options"]["default"]
            : null
        );
    }

    /**
     * @todo da tipizzare
     * @param $what
     * @param string|null $in
     */
    public static function transform(&$what, string $in = null) : void
    {
        if ($in) {
            if (is_array($what)) {
                foreach ($what as &$who) {
                    self::transform($who, $in);
                }
            } else {
                self::cast($what, $in);
            }
        }
    }

    /**
     * @todo da tipizzare
     * @param $value
     * @param string|null $type
     */
    private static function cast(&$value, string $type = null) : void
    {
        switch ($type) {
            case "json":
                $value = json_decode($value, true);
                break;
            case "boolean":
            case "bool":
            case "integer":
            case "int":
            case "float":
            case "double":
            case "string":
                settype($value, $type);
                break;
            default:
        }

        if (!is_array($value) && !is_object($value)) {
            $value = urldecode($value);
        }
    }
    /**
     * @param string|null $value
     * @return bool
     */
    public static function isJson(string $value = null) : bool
    {
        return $value && (bool) self::json2Array($value);
    }

    /**
     * @param string $string
     * @return array|null
     */
    public static function json2Array(string $string) : ?array
    {
        $res                                                            = null;
        if (substr($string, 0, 1) == "{" || substr($string, 0, 1) == "[") {
            $json                                                       = json_decode($string, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                $res                                                    = $json;
            }
        }

        return $res;
    }


    /**
     * @todo da tipizzare
     * @param mixed $value
     * @param array|null $spellcheck
     * @return string|null
     */
    public static function checkSpecialChars($value, array $spellcheck = null) : ?string
    {
        $errors                                         = array();
        if (!$spellcheck) {
            $spellcheck = array("''", '""', '\\"', '\\', '../', './');
        }

        if (!is_array($value)) {
            $value = array($value);
        }
        foreach ($value as $item) {
            $check                                      = str_replace($spellcheck, "", $item);
            if ($item != $check) {
                $errorName                              = self::getErrorName();
                $errors[$errorName]                     = $errorName . " is not a valid. " . "You can't use [" . implode(" ", $spellcheck) . "]";
            }
        }

        return (count($errors)
            ? implode(", ", $errors)
            : null
        );
    }

    /**
     * @param int $timestamp
     * @return bool
     */
    public static function isValidTimeStamp(int $timestamp) : bool
    {
        return (is_numeric($timestamp)
            && $timestamp > 0
            && $timestamp < pow(2, 31));
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isFile(string $value) : bool
    {
        return !self::invalidFile($value);
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function invalidFile(string $value) : bool
    {
        $res                                                                = false;
        $error                                                              = array();

        unset(self::$errors["file"]);
        if (isset($_FILES[$value])) {
            $names                                                          = (array) $_FILES[$value]["name"];
            if (is_array($names) && count($names)) {
                foreach ($names as $index => $name) {
                    if (!self::isFilePath($name)) {
                        $error[]                                            = $name . " is not valid path";
                    }
                }
            }

            $sizes                                                          = (array) $_FILES[$value]["size"];
            if (is_array($sizes) && count($sizes)) {
                foreach ($sizes as $index => $size) {
                    if ($size > self::RULES["file"]["length"]) {
                        $error[]                                            = $names[$index] . ": Upload Limit Exeeded";
                    }
                }
            }

            $types                                                          = (array) $_FILES[$value]["type"];
            if (is_array($types) && count($types)) {
                $files                                                      = (array) $_FILES[$value]["tmp_name"];
                foreach ($types as $index => $type) {
                    if (!self::checkMagicBytes($files[$index], $type)) {
                        $error[]                                            = $names[$index] . " File type mismatch";
                    }
                }
            }
        } elseif (!self::isFilePath($value)) {
            $error[]                                                        = $value . " is not valid path";
        }

        if (count($error)) {
            $res = implode(", ", $error);
            self::$errors["file"] = $res;
        }

        return $res;
    }

    /**
     * @param string $type
     * @return array|null
     */
    private static function getSignature(string $type) : ?array
    {
        return (isset(self::SIGNATURES[$type])
            ? self::SIGNATURES[$type]
            : null
        );
    }

    /**
     * @param string $file
     * @param string $type
     * @return bool
     */
    private static function checkMagicBytes(string $file, string $type) : bool
    {
        $checks                                                             = self::getSignature($type);
        $isValid                                                            = false;
        if (is_array($checks) && count($checks)) {
            foreach ($checks as $check) {
                $byteCount                                                  = strlen($check) / 2;
                $handle = @fopen($file, 'rb');
                if ($handle !== false && flock($handle, LOCK_EX)) {
                    $contents                                               = fread($handle, $byteCount);
                    $byteArray                                              = bin2hex($contents);
                    $regex                                                  = '#' . $check . '#i';
                    $isValid                                                = (bool)preg_match($regex, $byteArray);
                    flock($handle, LOCK_UN);
                    if ($isValid) {
                        break;
                    }
                }
                @fclose($handle);
            }
        } else {
            $isValid                                                        = true;
        }

        return $isValid;
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isFilePath(string $value) : bool
    {
        if (strpos($value, Constant::DISK_PATH) === 0) {
            $res = false;
        } else {
            $res = !self::checkSpecialChars($value) && !preg_match('/[^A-Za-z0-9.\/\-_\\$]/', $value);
        }
        return (bool) $res;
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isEmail(string $value) : bool
    {
        $regex                                                              = (
            Kernel::$Environment::DEBUG
                                                                                ? '/^([\.0-9a-z_\-\+]+)@(([0-9a-z\-]+\.)+[0-9a-z]{2,12})$/i'
                                                                                : '/^([\.0-9a-z_\-]+)@(([0-9a-z\-]+\.)+[0-9a-z]{2,12})$/i'
                                                                            );
        return (bool) preg_match($regex, $value);
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isTel(string $value) : bool
    {
        return strlen($value) > 9 && is_numeric(ltrim(str_replace(array(" ", ".", ",", "-"), array(""), $value), "+"));
    }

    /**
     * @param string $value
     * @param string|null $rule
     * @return bool
     */
    public static function isPassword(string $value, string $rule = null) : bool
    {
        return !(bool) self::invalidPassword($value, $rule);
    }

    /**
     * @param string $uuid
     * @return bool
     */
    public static function isUUID(string $uuid) : bool
    {
        return is_string($uuid) && (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) === 1);
    }

    /**
     * @param string $totp
     * @return bool
     */
    public static function isTotp(string $totp) : bool
    {
        return ctype_digit($totp);
    }


    /**
     * @param string $value
     * @return bool
     */
    public static function invalidUsername(string $value) : bool
    {
        $dataError = self::is($value, "username");

        return $dataError->isError();
    }

    /**
     * @param string $value
     * @param string|null $rule
     * @return array|null
     */
    public static function invalidPassword(string $value, string $rule = null) : ?string
    {
        $res                                                                = null;
        $error                                                              = array();

        unset(self::$errors["password"]);
        if ($value) {
            switch ($rule) {
                case "kerberos":
                    if (strlen($value) < 8) {
                        $error[] = "Password too short!";
                    }
                    if (!preg_match("#[0-9]+#", $value)) {
                        $error[] = "Password must include at least one number!";
                    }
                    if (!preg_match("#[a-z]+#", $value)) {
                        $error[] = "Password must include at least one letter!";
                    }
                    if (!preg_match("#[A-Z]+#", $value)) {
                        $error[] = "Password must include at least one upper letter!";
                    }
                    if (!preg_match("#[^a-zA-Z0-9]+#", $value)) {
                        $error[] = "Password must include at least one Special Character!";
                    }

                    /*$pspell_link                                            = pspell_new(vgCommon::LANG_CODE_TINY); //todo: non funziona il controllo
                    $word                                                   = preg_replace("#[^a-zA-Z]+#", "", $value);

                    if (!pspell_check($pspell_link, $word))                 $error[] = "Password must be impersonal!";
                    */
                    if (count($error)) {
                        $res                                                = implode(", ", $error);
                        self::$errors["password"]                           = $res;
                    }
                    break;
                case "pin":
                    if (strlen($value) < 5) {
                        $error[] = "Password too short!";
                    }
                    if (!preg_match("#[0-9]+#", $value)) {
                        $error[] = "Password must include at least one number!";
                    }

                    if (count($error)) {
                        $res                                                = implode(", ", $error);
                        self::$errors["password"]                           = $res;
                    }
                    break;
                default:
                    if (strlen($value) < 8) {
                        $error[] = "Password too short!";
                    }
                    if (!preg_match("#[0-9]+#", $value)) {
                        $error[] = "Password must include at least one number!";
                    }
                    if (!preg_match("#[a-z]+#", $value)) {
                        $error[] = "Password must include at least one letter!";
                    }
                    if (!preg_match("#[A-Z]+#", $value)) {
                        $error[] = "Password must include at least one upper letter!";
                    }

                    if (count($error)) {
                        $res                                                = implode(", ", $error);
                        self::$errors["password"]                           = $res;
                    }
            }
        }

        return $res;
    }

    /**
     * @param string $testo
     * @param string $char_sep
     * @return string|null
     */
    public static function urlRewrite(string $testo, string $char_sep = '-') : ?string
    {
        $testo = self::removeAccents($testo);
        $testo = strtolower($testo);

        $testo = preg_replace('/[^\p{L}0-9\-]+/u', ' ', $testo);
        $testo = trim($testo);
        $testo = preg_replace('/ +/', $char_sep, $testo);
        return preg_replace('/-+/', $char_sep, $testo);
    }

    /**
     * @param string $str
     * @return bool
     */
    private static function seemsUtf8(string $str) : bool
    {
        $length = strlen($str);
        for ($i=0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) {
                $n = 0;  # 0bbbbbbb
            } elseif (($c & 0xE0) == 0xC0) {
                $n = 1; # 110bbbbb
            } elseif (($c & 0xF0) == 0xE0) {
                $n=2; # 1110bbbb
            } elseif (($c & 0xF8) == 0xF0) {
                $n = 3; # 11110bbb
            } elseif (($c & 0xFC) == 0xF8) {
                $n = 4; # 111110bb
            } elseif (($c & 0xFE) == 0xFC) {
                $n = 5; # 1111110b
            } else {
                return false;
            } # Does not match any model

            for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Converts all accent characters to ASCII characters.
     *
     * If there are no accent characters, then the string given is just returned.
     *
     * @since 1.2.1
     *
     * @param string $string Text that might have accent characters
     * @return string Filtered string with replaced "nice" characters.
     */
    private static function removeAccents(string $string) : string
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }
        if (self::seemsUtf8($string)) {
            $chars = array(
                // Decompositions for Latin-1 Supplement
                chr(194).chr(170) => 'a', chr(194).chr(186) => 'o',
                chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
                chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
                chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
                chr(195).chr(134) => 'AE',chr(195).chr(135) => 'C',
                chr(195).chr(136) => 'E', chr(195).chr(137) => 'E',
                chr(195).chr(138) => 'E', chr(195).chr(139) => 'E',
                chr(195).chr(140) => 'I', chr(195).chr(141) => 'I',
                chr(195).chr(142) => 'I', chr(195).chr(143) => 'I',
                chr(195).chr(144) => 'D', chr(195).chr(145) => 'N',
                chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
                chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
                chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
                chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
                chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
                chr(195).chr(158) => 'TH',chr(195).chr(159) => 's',
                chr(195).chr(160) => 'a', chr(195).chr(161) => 'a',
                chr(195).chr(162) => 'a', chr(195).chr(163) => 'a',
                chr(195).chr(164) => 'a', chr(195).chr(165) => 'a',
                chr(195).chr(166) => 'ae',chr(195).chr(167) => 'c',
                chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
                chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
                chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
                chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
                chr(195).chr(176) => 'd', chr(195).chr(177) => 'n',
                chr(195).chr(178) => 'o', chr(195).chr(179) => 'o',
                chr(195).chr(180) => 'o', chr(195).chr(181) => 'o',
                chr(195).chr(182) => 'o', chr(195).chr(184) => 'o',
                chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
                chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
                chr(195).chr(189) => 'y', chr(195).chr(190) => 'th',
                chr(195).chr(191) => 'y', chr(195).chr(152) => 'O',
                // Decompositions for Latin Extended-A
                chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
                chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
                chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
                chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
                chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
                chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
                chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
                chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
                chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
                chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
                chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
                chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
                chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
                chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
                chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
                chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
                chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
                chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
                chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
                chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
                chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
                chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
                chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
                chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
                chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
                chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
                chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
                chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
                chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
                chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
                chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
                chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
                chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
                chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
                chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
                chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
                chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
                chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
                chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
                chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
                chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
                chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
                chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
                chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
                chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
                chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
                chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
                chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
                chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
                chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
                chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
                chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
                chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
                chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
                chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
                chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
                chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
                chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
                chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
                chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
                chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
                chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
                chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
                chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
                // Decompositions for Latin Extended-B
                chr(200).chr(152) => 'S', chr(200).chr(153) => 's',
                chr(200).chr(154) => 'T', chr(200).chr(155) => 't',
                // Euro Sign
                chr(226).chr(130).chr(172) => 'E',
                // GBP (Pound) Sign
                chr(194).chr(163) => '',
                // Vowels with diacritic (Vietnamese)
                // unmarked
                chr(198).chr(160) => 'O', chr(198).chr(161) => 'o',
                chr(198).chr(175) => 'U', chr(198).chr(176) => 'u',
                // grave accent
                chr(225).chr(186).chr(166) => 'A', chr(225).chr(186).chr(167) => 'a',
                chr(225).chr(186).chr(176) => 'A', chr(225).chr(186).chr(177) => 'a',
                chr(225).chr(187).chr(128) => 'E', chr(225).chr(187).chr(129) => 'e',
                chr(225).chr(187).chr(146) => 'O', chr(225).chr(187).chr(147) => 'o',
                chr(225).chr(187).chr(156) => 'O', chr(225).chr(187).chr(157) => 'o',
                chr(225).chr(187).chr(170) => 'U', chr(225).chr(187).chr(171) => 'u',
                chr(225).chr(187).chr(178) => 'Y', chr(225).chr(187).chr(179) => 'y',
                // hook
                chr(225).chr(186).chr(162) => 'A', chr(225).chr(186).chr(163) => 'a',
                chr(225).chr(186).chr(168) => 'A', chr(225).chr(186).chr(169) => 'a',
                chr(225).chr(186).chr(178) => 'A', chr(225).chr(186).chr(179) => 'a',
                chr(225).chr(186).chr(186) => 'E', chr(225).chr(186).chr(187) => 'e',
                chr(225).chr(187).chr(130) => 'E', chr(225).chr(187).chr(131) => 'e',
                chr(225).chr(187).chr(136) => 'I', chr(225).chr(187).chr(137) => 'i',
                chr(225).chr(187).chr(142) => 'O', chr(225).chr(187).chr(143) => 'o',
                chr(225).chr(187).chr(148) => 'O', chr(225).chr(187).chr(149) => 'o',
                chr(225).chr(187).chr(158) => 'O', chr(225).chr(187).chr(159) => 'o',
                chr(225).chr(187).chr(166) => 'U', chr(225).chr(187).chr(167) => 'u',
                chr(225).chr(187).chr(172) => 'U', chr(225).chr(187).chr(173) => 'u',
                chr(225).chr(187).chr(182) => 'Y', chr(225).chr(187).chr(183) => 'y',
                // tilde
                chr(225).chr(186).chr(170) => 'A', chr(225).chr(186).chr(171) => 'a',
                chr(225).chr(186).chr(180) => 'A', chr(225).chr(186).chr(181) => 'a',
                chr(225).chr(186).chr(188) => 'E', chr(225).chr(186).chr(189) => 'e',
                chr(225).chr(187).chr(132) => 'E', chr(225).chr(187).chr(133) => 'e',
                chr(225).chr(187).chr(150) => 'O', chr(225).chr(187).chr(151) => 'o',
                chr(225).chr(187).chr(160) => 'O', chr(225).chr(187).chr(161) => 'o',
                chr(225).chr(187).chr(174) => 'U', chr(225).chr(187).chr(175) => 'u',
                chr(225).chr(187).chr(184) => 'Y', chr(225).chr(187).chr(185) => 'y',
                // acute accent
                chr(225).chr(186).chr(164) => 'A', chr(225).chr(186).chr(165) => 'a',
                chr(225).chr(186).chr(174) => 'A', chr(225).chr(186).chr(175) => 'a',
                chr(225).chr(186).chr(190) => 'E', chr(225).chr(186).chr(191) => 'e',
                chr(225).chr(187).chr(144) => 'O', chr(225).chr(187).chr(145) => 'o',
                chr(225).chr(187).chr(154) => 'O', chr(225).chr(187).chr(155) => 'o',
                chr(225).chr(187).chr(168) => 'U', chr(225).chr(187).chr(169) => 'u',
                // dot below
                chr(225).chr(186).chr(160) => 'A', chr(225).chr(186).chr(161) => 'a',
                chr(225).chr(186).chr(172) => 'A', chr(225).chr(186).chr(173) => 'a',
                chr(225).chr(186).chr(182) => 'A', chr(225).chr(186).chr(183) => 'a',
                chr(225).chr(186).chr(184) => 'E', chr(225).chr(186).chr(185) => 'e',
                chr(225).chr(187).chr(134) => 'E', chr(225).chr(187).chr(135) => 'e',
                chr(225).chr(187).chr(138) => 'I', chr(225).chr(187).chr(139) => 'i',
                chr(225).chr(187).chr(140) => 'O', chr(225).chr(187).chr(141) => 'o',
                chr(225).chr(187).chr(152) => 'O', chr(225).chr(187).chr(153) => 'o',
                chr(225).chr(187).chr(162) => 'O', chr(225).chr(187).chr(163) => 'o',
                chr(225).chr(187).chr(164) => 'U', chr(225).chr(187).chr(165) => 'u',
                chr(225).chr(187).chr(176) => 'U', chr(225).chr(187).chr(177) => 'u',
                chr(225).chr(187).chr(180) => 'Y', chr(225).chr(187).chr(181) => 'y',
                // Vowels with diacritic (Chinese, Hanyu Pinyin)
                chr(201).chr(145) => 'a',
                // macron
                chr(199).chr(149) => 'U', chr(199).chr(150) => 'u',
                // acute accent
                chr(199).chr(151) => 'U', chr(199).chr(152) => 'u',
                // caron
                chr(199).chr(141) => 'A', chr(199).chr(142) => 'a',
                chr(199).chr(143) => 'I', chr(199).chr(144) => 'i',
                chr(199).chr(145) => 'O', chr(199).chr(146) => 'o',
                chr(199).chr(147) => 'U', chr(199).chr(148) => 'u',
                chr(199).chr(153) => 'U', chr(199).chr(154) => 'u',
                // grave accent
                chr(199).chr(155) => 'U', chr(199).chr(156) => 'u',
            );

            $string = strtr($string, $chars);
        } else {
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
                .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
                .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
                .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
                .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
                .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
                .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
                .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
                .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
                .chr(252).chr(253).chr(255);

            $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

            $string = strtr($string, $chars['in'], $chars['out']);
            $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
            $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
            $string = str_replace($double_chars['in'], $double_chars['out'], $string);
        }

        return $string;
    }

    /**
     * @param array $value
     * @param string $type
     * @param int $length
     * @return DataError
     */
    private static function isAllowed(array $value, string $type, int $length) : DataError
    {
        $dataError                                          = new DataError();
        foreach ($value as $item) {
            if (is_array($item)) {
                $dataError                                  = self::isError(self::getErrorName() . " Multidimensional Array not supported", $type, 501);
                break;
            } elseif (strlen($item) > $length) {
                $dataError                                  = self::isError(self::getErrorName() . " Max Length Exeeded: " . $type, $type, 413);
                break;
            }
        }

        return $dataError;
    }

    /**
     * @param array $rule
     * @param string|null $range
     */
    private static function setRuleOptions(array &$rule, string $range = null) : void
    {
        if ($range) {
            if ($rule["filter"] == FILTER_VALIDATE_INT || $rule["filter"] == FILTER_VALIDATE_FLOAT) {
                if (strpos($range, ":") !== false) {
                    $arrOpt                                 = explode(":", $range);
                    if (is_numeric($arrOpt[0])) {
                        $rule["options"]["min_range"]       = $arrOpt[0];
                    }
                    if (is_numeric($arrOpt[1])) {
                        $rule["options"]["max_range"]       = $arrOpt[1];
                    }
                } elseif (is_numeric($range)) {
                    $rule["options"]["decimal"]             = $range;
                }
            } else {
                self::setRuleOptionsString($rule, $range);
            }
        }
    }

    /**
     * @param array $rule
     * @param string $option
     */
    private static function setRuleOptionsString(array &$rule, string $option) : void
    {
        if (strpos($option, ":") !== false) {
            $arrOpt                                 = explode(":", $option);
            if (is_numeric($arrOpt[1])) {
                $rule["length"]                     = $arrOpt[1];
            }
        } elseif (is_numeric($option)) {
            $rule["length"]                         = $option;
        }
    }

    /**
     * @param string $name
     */
    private static function setErrorName(string $name) : void
    {
        self::$errorName = $name;
    }

    /**
     * @return string
     */
    private static function getErrorName() : string
    {
        return self::$errorName;
    }

    /**
     * @param string $error
     * @param null|string $type
     * @param int $status
     * @return DataError
     */
    private static function isError(string $error, string $type = null, int $status = 400) : DataError
    {
        $dataError = new DataError();
        $dataError->error($status, (
            $type && isset(self::$errors[$type])
            ? self::$errors[$type]
            : $error
        ));

        return $dataError;
    }
}
