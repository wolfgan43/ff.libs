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
use phpformsframework\libs\Env;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Request;

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
                                                                    "length"        => 1
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
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 32
                                                                ),
                                                                "int"               => array(
                                                                    "filter"        => FILTER_VALIDATE_INT,
                                                                    "flags"         => null, //FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX
                                                                    "options"       => array("default" => null),
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
                                                                    "length"        => 0
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
                                                                "token"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\phpformsframework\libs\security\Validator::isToken',
                                                                    "normalize"     => true,
                                                                    "length"        => 2048
                                                                ),
                                                                "text"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => "nl2br",
                                                                    "normalize"     => true,
                                                                    "length"        => 128000
                                                                )

                                                            );
    /**
     * https://en.wikipedia.org/wiki/List_of_file_signatures
     */
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

    private const REQUEST_MAX_SIZE                          = array(
                                                                Request::METHOD_GET     => 256,
                                                                Request::METHOD_PUT     => 10240,
                                                                Request::METHOD_PATCH   => 10240,
                                                                Request::METHOD_POST    => 10240,
                                                                Request::METHOD_HEAD    => 2048,
                                                                "DEFAULT"               => 128,
                                                                "FILES"                 => 1024000
                                                            );
    private const PASSWORD_LENGTH                           = 8;
    private static $errors                                  = array();
    private static $errorName                               = null;

    /**
 * @param string $method
 * @return int
 */
    public static function getRequestMaxSize(string $method) : int
    {
        $method = strtoupper($method);
        return Env::get("VALIDATOR_REQUEST_MAX_SIZE_" . $method) ?? self::REQUEST_MAX_SIZE[$method] ?? self::REQUEST_MAX_SIZE["DEFAULT"];
    }

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

        $length                                         = (Env::get("VALIDATOR_" . strtoupper($type) . "_LENGTH") ?? $rule["length"]);
        $dataError                                      = self::isAllowed((array) $what, $type, $length);
        if (!$dataError->isError()) {
            $validation                                 = filter_var($what, $rule["filter"], array(
                                                            "flags"         => $rule["flags"],
                                                            "options"       => $rule["options"]
                                                        ));

            if ($validation === null) {
                $dataError                              = self::isError(self::getErrorName() . " is not a valid " . $type . ($range ? ". The permitted values are [" . $range . "]" : ""), $type);
            } elseif (is_array($validation)) {
                if (is_array($what) && ($type != "array" && $type != "arrayint")) {
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
     * Returns webserver max upload size in B/KB/MB/GB
     * @param string $return
     * @return int
     */
    public static function getMaxUploadSize(string $return = null) : int
    {
        $max_upload                                                         = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
        $max_upload                                                         = str_replace('M', '', $max_upload);
        switch ($return) {
            case "K":
                $res                                                        = $max_upload * 1024;
                break;
            case "M":
                $res                                                        = $max_upload;
                break;
            case "G":
                $res                                                        = (int) $max_upload / 1024;
                break;
            default:
                $res                                                        = $max_upload *1024 * 1024;
        }
        return $res;
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
            if (!empty($names)) {
                foreach ($names as $index => $name) {
                    if (!self::isFilePath($name)) {
                        $error[]                                            = $name . " is not valid path";
                    }
                }
            }

            $sizes                                                          = (array) $_FILES[$value]["size"];
            if (!empty($sizes)) {
                foreach ($sizes as $index => $size) {
                    if ($size > self::getMaxUploadSize()) {
                        $error[]                                            = $names[$index] . ": Upload Limit Exceeded";
                    }
                }
            }

            $types                                                          = (array) $_FILES[$value]["type"];
            if (!empty($types)) {
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
        if (!empty($checks)) {
            $handle                                                         = @fopen($file, 'rb');
            if ($handle !== false && flock($handle, LOCK_EX)) {
                foreach ($checks as $check) {
                    fseek($handle, 0);
                    $byteCount                                              = strlen($check) / 2;
                    $contents                                               = fread($handle, $byteCount);
                    $byteArray                                              = bin2hex($contents);
                    $regex                                                  = '#' . $check . '#i';
                    $isValid                                                = (bool)preg_match($regex, $byteArray);
                    if ($isValid) {
                        break;
                    }

                }
                flock($handle, LOCK_UN);
            }
            @fclose($handle);
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
            $res = !self::checkSpecialChars($value) && !preg_match('/[^A-Za-z0-9.\/\-_\s\$]/', $value);
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
     * @param string $token
     * @return bool
     */
    public static function isToken(string $token) : bool
    {
        return substr_count($token, ".") === 2 || substr_count($token, "-") === 0;
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

    private static function invalidPasswordAlphaNum(string $value, array &$error) : void
    {
        if (strlen($value) < (Env::get("VALIDATOR_PASSWORD_LENGTH") ?? self::PASSWORD_LENGTH)) {
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
                    self::invalidPasswordAlphaNum($value, $error);

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
                    self::invalidPasswordAlphaNum($value, $error);

                    if (count($error)) {
                        $res                                                = implode(", ", $error);
                        self::$errors["password"]                           = $res;
                    }
            }
        }

        return $res;
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
        if ($length > 0) {
            foreach ($value as $item) {
                if ((is_array($item) && strlen(serialize($item)) > $length)
                    || (is_string($item) && strlen($item) > $length)
                ) {
                    $dataError                              = self::isError(self::getErrorName() . " Max Length Exceeded: " . $type, $type, 413);
                    break;
                }
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
