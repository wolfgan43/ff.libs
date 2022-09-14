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
namespace ff\libs\security;

use ff\libs\dto\DataError;
use ff\libs\Env;
use ff\libs\Kernel;
use ff\libs\Request;
use ff\libs\Exception;
use stdClass;

/**
 * Class Validator
 * @package ff\libs\security
 */
class Validator
{
    public const REQUEST_UPLOAD_PARAM_NAME                  = "files";
    private const ERROR_NO_BASE64                           = ' is not a valid base64.';

    private const MEMORY_LIMIT_REQUEST                      = 11;
    private const MEMORY_LIMIT_BASE64                       = 4;
    private const REQUEST_LIMIT                             = 1024000000;    //1024MB
    private const SPELL_CHECK                               = array("''", '""', '\\"', '\\\\', '../', './', 'file://');
    private const SPELL_CHECK_TEXT                          = array('\\"', '\\\\', '../', './', 'file://');
    private const EXCEPTIONS                                = [
                                                                "int"   => [
                                                                    ''  => 0,
                                                                ],
                                                                "float" => [
                                                                    ''  => 0
                                                                ],
                                                                "bool"  => [
                                                                    ''  => false,
                                                                    0   => false,
                                                                    '0' => false,
                                                                ],
                                                                "boolean"  => [
                                                                    ''  => false,
                                                                    0   => false,
                                                                    '0' => false,
                                                                ]
                                                            ];
    private const RULES                                     = array(
                                                                "bool"              => array(
                                                                    "filter"        => FILTER_VALIDATE_BOOLEAN,
                                                                    "flags"         => FILTER_NULL_ON_FAILURE,
                                                                    "options"       => array("default" => false),
                                                                    "normalize"     => true,
                                                                    "length"        => 1
                                                                ),
                                                                "boolean"           => array(
                                                                    "filter"        => FILTER_VALIDATE_BOOLEAN,
                                                                    "flags"         => FILTER_NULL_ON_FAILURE,
                                                                    "options"       => array("default" => false),
                                                                    "normalize"     => true,
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
                                                                "number"            => array(
                                                                    "filter"        => FILTER_VALIDATE_FLOAT,
                                                                    "flags"         => null, //FILTER_FLAG_ALLOW_THOUSAND
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 32
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
                                                                "ip"                => array(
                                                                    "filter"        => FILTER_VALIDATE_IP,
                                                                    "flags"         => null, //FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 15
                                                                ),
                                                                "mac"               => array(
                                                                    "filter"        => FILTER_VALIDATE_MAC,
                                                                    "flags"         => null,
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 24
                                                                ),
                                                                "url"               => array(
                                                                    "filter"        => FILTER_VALIDATE_URL,
                                                                    "flags"         => FILTER_FLAG_PATH_REQUIRED, // | FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_QUERY_REQUIRED
                                                                    "options"       => array("default" => null),
                                                                    "length"        => 256
                                                                ),
                                                                "username"          => array(
                                                                    "filter"        => FILTER_UNSAFE_RAW,
                                                                    "flags"         => FILTER_FLAG_STRIP_LOW,
                                                                    "options"       => null,
                                                                    "callback"      => '\ff\libs\security\Validator::checkSpecialChars',
                                                                    "length"        => 48
                                                                ),
                                                                "string"            => array(
                                                                    "filter"        => FILTER_UNSAFE_RAW,
                                                                    "flags"         => FILTER_FLAG_STRIP_LOW,
                                                                    "options"       => null,
                                                                    "callback"      => '\ff\libs\security\Validator::checkSpecialChars',
                                                                    "length"        => 256
                                                                ),
                                                                "array"             => array(
                                                                    "filter"        => FILTER_UNSAFE_RAW,
                                                                    "flags"         => FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE | FILTER_FLAG_STRIP_LOW,
                                                                    "options"       => null,
                                                                    "callback"      => '\ff\libs\security\Validator::checkSpecialChars',
                                                                    "length"        => 10240
                                                                ),
                                                                "arrayint"          => array(
                                                                    "filter"        => FILTER_VALIDATE_INT,
                                                                    "flags"         => FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE,
                                                                    "options"       => null,
                                                                    "length"        => 16
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
                                                                    "options"       => '\ff\libs\util\Normalize::urlRewrite',
                                                                    "normalize"     => true,
                                                                    "length"        => 128
                                                                ),
                                                                "text"              => array(
                                                                    "filter"        => FILTER_UNSAFE_RAW,
                                                                    "flags"         => FILTER_FLAG_STRIP_LOW,
                                                                    "options"       => null,
                                                                    "callback"      => '\ff\libs\security\Validator::checkSpecialCharsText',
                                                                    "length"        => 128000
                                                                ),
                                                                "texthtml"          => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => "nl2br",
                                                                    "normalize"     => true,
                                                                    "length"        => 128000
                                                                ),
                                                                "timestamp"         => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\ff\libs\security\Validator::isTimeStamp',
                                                                    "length"        => 10
                                                                ),
                                                                "timestampmicro"    => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\ff\libs\security\Validator::isTimeStampMicro',
                                                                    "length"        => 13
                                                                ),
                                                                "json"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\ff\libs\security\Validator::isJson',
                                                                    "length"        => 10240
                                                                ),
                                                                "password"          => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\ff\libs\security\Validator::isPassword',
                                                                    "length"        => 24
                                                                ),
                                                                "tel"               => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\ff\libs\security\Validator::isTel',
                                                                    "length"        => 16
                                                                ),
                                                                "uuid"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\ff\libs\security\Validator::isUUID',
                                                                    "length"        => 128
                                                                ),
                                                                "totp"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\ff\libs\security\Validator::isTotp',
                                                                    "length"        => 7
                                                                ),
                                                                "token"             => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\ff\libs\security\Validator::isToken',
                                                                    "length"        => 2048
                                                                ),
                                                                "file"              => array(
                                                                    "filter"        => FILTER_CALLBACK,
                                                                    "flags"         => null,
                                                                    "options"       => '\ff\libs\security\ValidatorFile::check',
                                                                    "length"        => 0
                                                                ),
                                                                "base64"            => array(
                                                                    "filter"        => null,
                                                                    "flags"         => null,
                                                                    "options"       => null,
                                                                    "callback"      => '\ff\libs\security\Validator::checkBase64',
                                                                    "length"        => 0
                                                                ),
                                                                "base64json"        => array(
                                                                    "filter"        => null,
                                                                    "flags"         => null,
                                                                    "options"       => null,
                                                                    "callback"      => '\ff\libs\security\Validator::checkJsonBase64',
                                                                    "length"        => 0
                                                                ),
                                                                "base64file"        => array(
                                                                    "filter"        => null,
                                                                    "flags"         => null,
                                                                    "options"       => null,
                                                                    "callback"      => '\ff\libs\security\Validator::checkBase64File',
                                                                    "length"        => 0
                                                                ),
                                                                "base64url"        => array(
                                                                    "filter"        => null,
                                                                    "flags"         => null,
                                                                    "options"       => null,
                                                                    "callback"      => '\ff\libs\security\Validator::checkBase64Url',
                                                                    "length"        => 0
                                                                )
                                                            );

    private const REQUEST_MAX_SIZE                          = array(
                                                                Request::METHOD_GET     => 256,
                                                                Request::METHOD_PUT     => 10240,
                                                                Request::METHOD_PATCH   => 10240,
                                                                Request::METHOD_POST    => 102400,
                                                                Request::METHOD_HEAD    => 2048,
                                                                "DEFAULT"               => 128,
                                                                "FILES"                 => 1024000
                                                            );
    private const PASSWORD_LENGTH                           = 8;

    private static $contextName                             = null;
    private static $conversion                              = null;

    /**
 * @param string $method
 * @return int
 */
    public static function getRequestMaxSize(string $method) : int
    {
        $method                                             = strtoupper($method);
        $size                                               = Env::get("VALIDATOR_REQUEST_MAX_SIZE_" . $method)
                                                                ?? self::REQUEST_MAX_SIZE[$method]
                                                                ?? self::REQUEST_MAX_SIZE["DEFAULT"];
        return ($size < self::REQUEST_LIMIT
            ? $size
            : self::REQUEST_LIMIT
        );
    }

    /**
     * @param $what
     * @param string $context
     * @param string|null $type
     * @param null $range
     * @return DataError
     */
    public static function is(&$what, string $context, string $type = null, $range = null) : DataError
    {
        if ($what === null) {
            return new DataError();
        } elseif (isset(self::EXCEPTIONS[$type][$what])) {
            $what = self::EXCEPTIONS[$type][$what];
        }

        if (!array_key_exists($type, self::RULES)) {
            //@todo da gestire il default in modo dinamico. Da togliere text in virtu di string
            $type                                       = (
                is_array($what)
                                                            ? "array"
                                                            : "text"
            );
        }
        $rule                                           = (object) self::RULES[$type];

        self::setContextName($context);
        self::setRuleOptions($rule, $range);

        $length                                         = (Env::get("VALIDATOR_" . strtoupper($type) . "_LENGTH") ?? $rule->length);
        $dataError                                      = self::isAllowed((array) $what, $length, $context . ": Max Length Exceeded. Validator is " . $type);
        if ($dataError->isError()) {
            return $dataError;
        }

        if (!empty($rule->filter)) {
            if ($dataError->get("size") > (self::size2Bytes(Kernel::$Environment::MEMORY_LIMIT) / self::MEMORY_LIMIT_REQUEST)) {
                return $dataError->error(413, $context . ": Memory Limit Reached. Validator is " . $type);
            }

            if ($type === "json" && is_array($what)) {
                $type = "array";
            }
            $validation                                 = filter_var($what, $rule->filter, array(
                                                            "flags"     => $rule->flags         ?? null,
                                                            "options"   => $rule->options       ?? null
                                                        ));

            if ($rule->filter === FILTER_CALLBACK) {
                $error                                  = !$validation;
            } elseif ($type == "array"
                || $type == "string"
                || $type == "text"
                || $rule->filter === FILTER_VALIDATE_BOOLEAN
                || $rule->filter === FILTER_VALIDATE_INT
                || $rule->filter === FILTER_VALIDATE_FLOAT
            ) {
                $error                                  = $validation != $what;
            } else {
                $error                                  = $validation !== $what;
            }

            if ($validation === null || $error) {
                $dataError->error(
                    400,
                    $context . " is not a valid " . $type
                    . ($range           ? ". The permitted values are [" . $range . "]" : "")
                );
            } elseif (is_array($validation) && ($error  = self::isArrayAllowed($what, $type))) {
                $dataError->error(400, $error);
            } elseif (!empty($rule->normalize)) {
                $what                                   = $validation;
            }
        }

        if (isset($rule->callback) && (($error = self::isArrayAllowed($what, $type)) || ($error = ($rule->callback)($what, $rule->limit ?? null)))) {
            $dataError->error(400, $error);
        }

        if (!$dataError->isError()) {
            self::transform($what, $type);
        }

        self::$conversion                               = null;

        return $dataError;
    }

    /**
     * @param string|array $what
     * @param string $type
     * @return string|null
     * @todo da tipizzare
     */
    private static function isArrayAllowed($what, string $type) : ?string
    {
        return (is_array($what) && ($type != "array" && $type != "arrayint")
            ? self::getContextName() . " Cannot be an Array."
            : null
        );
    }

    /**
     * @todo da tipizzare
     * @param string $type
     * @return mixed|null
     */
    public static function getDefault(string $type)
    {
        return self::RULES[$type]["options"]["default"] ?? null;
    }

    /**
     * @todo da tipizzare
     * @param $what
     * @param string $in
     */
    private static function transform(&$what, string $in) : void
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
     * @param string $type
     */
    private static function cast(&$value, string $type) : void
    {
        switch ($type) {
            case "json":
            case "base64":
            case "base64file":
            case "base64url":
                $value      = self::$conversion;
                break;
            case "boolean":
            case "bool":
            case "integer":
            case "int":
            case "float":
            case "double":
            case "string":
                settype($value, $type);
                // no break
            default:
                if (!is_array($value) && !is_object($value)) {
                    $value          = urldecode($value);
                }
        }
    }

    /**
     * @param string|null $value
     * @return bool
     */
    public static function isJson(string $value = null) : bool
    {
        return $value === "" || $value == "[]" || self::convertJson($value);
    }

    /**
     * @param string $string
     * @param bool $toArray
     * @return array|stdClass|null
     * @todo da tipizzare
     */
    public static function jsonDecode(string $string, bool $toArray = true)
    {
        $res                                                            = null;
        if (substr($string, 0, 1) == "{" || substr($string, 0, 1) == "[") {
            $json                                                       = json_decode($string, $toArray);
            if (json_last_error() == JSON_ERROR_NONE) {
                $res                                                    = $json;
            }
        }

        return $res;
    }

    /**
     * @param $value
     * @return string|null
     */
    public static function checkSpecialCharsText($value) : ?string
    {
        return self::checkSpecialChars($value, self::SPELL_CHECK_TEXT);
    }

    /**
     * @param $value
     * @param array|null $spell_check
     * @return string|null
     */
    public static function checkSpecialChars($value, array $spell_check = null) : ?string
    {
        return self::findSpecialChars((array) $value, $spell_check ?? self::SPELL_CHECK);
    }

    /**
     * @param array $value
     * @param array $spell_check
     * @param string|null $label
     * @return string|null
     */
    private static function findSpecialChars(array $value, array $spell_check, string $label = null) : ?string
    {
        $error = null;
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $error  = self::findSpecialChars($item, $spell_check, "[...]");
                break;
            } elseif ($item != str_replace($spell_check, "", $item)) {
                $error  = Buckler::encodeEntity(self::getContextName() . $label . "[" . $key . "]") . (
                    is_float($item)
                    ? " is not a valid float. Max precision must be: " . ini_get("precision")
                    : " is not a valid. " . "You can't use [" . implode(" ", $spell_check) . "]"
                );
                break;
            } elseif ($item != preg_replace('/<?[a-z\s]*>/i', "", $item)) {
                $error  = Buckler::encodeEntity(self::getContextName() . $label . "[" . $key . "]") . " HTML entity not allowed";
                break;
            }
        }

        return $error;
    }

    /**
     * @param mixed $timestamp
     * @return bool
     */
    public static function isTimeStamp($timestamp) : bool
    {
        return (empty($timestamp) || (is_numeric($timestamp)
                && $timestamp > 0
                && $timestamp < pow(2, 31))
        );
    }

    /**
     * @param $timestampMicro
     * @return bool
     */
    public static function isTimeStampMicro($timestampMicro) : bool
    {
        return (empty($timestampMicro) || (is_numeric($timestampMicro)
            && $timestampMicro > 0
            && $timestampMicro < pow(2, 63))
        );
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isFile(string $value) : bool
    {
        return !((bool) self::checkSpecialChars($value));
    }

    /**
     * @param string $value
     * @return bool
     * @throws Exception
     */
    public static function isBase64(string $value) : bool
    {
        return !((bool) self::checkBase64($value));
    }





    /**
     * @param string $value
     * @return array|null
     */
    private static function &convertJson(string $value) : ?array
    {
        if (!isset(self::$conversion)) {
            self::$conversion                                               = self::jsonDecode($value);
        }

        return self::$conversion;
    }

    /**
     * @param string $value
     * @return string
     * @throws Exception
     */
    private static function base64Decode(string $value) : string
    {
        if (strlen($value) > (self::size2Bytes(Kernel::$Environment::MEMORY_LIMIT) / self::MEMORY_LIMIT_BASE64)) {
            throw new Exception(self::getContextName() . " base64 Memory Limit Reached.", 413);
        }

        $value = urldecode($value);
        $value = str_replace(' ', '+', $value);
        return (string) base64_decode($value, true);
    }

    /**
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    private static function &convertBase64(string $value) : ?string
    {
        if (!isset(self::$conversion)) {
            self::$conversion                                               = self::base64Decode($value);
        }

        return self::$conversion;
    }

    /**
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    private static function checkBase64(string $value) : ?string
    {
        $res                                                                = self::convertBase64($value);
        if (!$res || !ctype_print($res)) {
            return self::getContextName() . self::ERROR_NO_BASE64;
        }

        return self::checkSpecialChars($res);
    }

    /**
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    private static function checkBase64Url(string $value) : ?string
    {
        $arrBase64                                                          = explode(";", $value, 3);
        if (count($arrBase64) !== 2) {
            return self::getContextName() . " invalid Format: [mimetype;base64content]";
        }
        $base64Content                                                      = explode(",", $arrBase64[1]);

        if ($base64Content[0] != "base64" || !ctype_print(end($base64Content))) {
            return self::getContextName() . self::ERROR_NO_BASE64;
        }

        self::$conversion                                                   = str_replace(['data://', 'data:'], [''], $arrBase64[0]) . ";" . str_replace(' ', '+', urldecode($arrBase64[1]));

        return null;
    }


    private static function checkJsonBase64(string $value) : ?string
    {
        //todo da fare
    }

    /**
     * @param string $value
     * @param string|null $mimetype_allowed
     * @return string|null
     */
    private static function checkBase64File(string $value, string $mimetype_allowed = null) : ?string
    {
        $arrBase64                                                          = explode(";", $value, 3);
        if (count($arrBase64) !== 3) {
            return self::getContextName() . " invalid Format: [mimetype;filename;base64content]";
        }

        $tmpfname                                                           = tempnam(sys_get_temp_dir(), "php");
        $handle                                                             = fopen($tmpfname, "w");
        stream_filter_append($handle, 'convert.base64-decode', STREAM_FILTER_WRITE);

        fwrite($handle, str_replace(' ', '+', urldecode($arrBase64[2])));
        fclose($handle);

        self::$conversion                                                   = $arrBase64[1];

        $_FILES[self::REQUEST_UPLOAD_PARAM_NAME]                            = [
                                                                                'name'          => $arrBase64[1],
                                                                                'type'          => $arrBase64[0],
                                                                                'tmp_name'      => $tmpfname,
                                                                                'error'         => 0,
                                                                                'size'          => filesize($tmpfname)
                                                                            ];

        return ValidatorFile::check(self::REQUEST_UPLOAD_PARAM_NAME, $mimetype_allowed);
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isEmail(string $value) : bool
    {
        $regex                                                              = (
            Kernel::$Environment::DEBUG
                                                                                ? '/^([\.0-9a-z_\-\+]+)@(([0-9a-z\-]+\.)+[0-9a-z\-]{2,12})$/i'
                                                                                : '/^([\.0-9a-z_\-]+)@(([0-9a-z\-]+\.)+[0-9a-z\-]{2,12})$/i'
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
        return !((bool) self::checkPassword($value, $rule));
    }

    /**
     * @param string $uuid
     * @return bool
     */
    public static function isUUID(string $uuid) : bool
    {
        return $uuid === "" || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) === 1);
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
     * @param string $url
     * @return bool
     */
    public static function isUrl(string $url) : bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * @param string|null $data
     * @return string
     */
    public static function csrf(string $data = null) : string
    {
        return hash_hmac("sha256", Discover::visitor() . $data, Kernel::$Environment::APPID);
    }

    /**
     * @param string $value
     * @return bool
     */
    private static function checkUsername(string $value) : bool
    {
        $dataError = self::is($value, "username");

        return $dataError->isError();
    }

    private static function checkPasswordAlphaNum(string $value, array &$error) : void
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
    private static function checkPassword(string $value, string $rule = null) : ?string
    {
        $error                                                              = array();
        if ($value) {
            switch ($rule) {
                case "kerberos":
                    self::checkPasswordAlphaNum($value, $error);

                    if (!preg_match("#[^a-zA-Z0-9]+#", $value)) {
                        $error[] = "Password must include at least one Special Character!";
                    }

                    /*$pspell_link                                            = pspell_new(vgCommon::LANG_CODE_TINY); //todo: non funziona il controllo
                    $word                                                   = preg_replace("#[^a-zA-Z]+#", "", $value);

                    if (!pspell_check($pspell_link, $word))                 $error[] = "Password must be impersonal!";
                    */
                    break;
                case "pin":
                    if (strlen($value) < 5) {
                        $error[] = "Password too short!";
                    }
                    if (!preg_match("#[0-9]+#", $value)) {
                        $error[] = "Password must include at least one number!";
                    }
                    break;
                default:
                    self::checkPasswordAlphaNum($value, $error);
            }
        }

        return (!empty($error)
            ? implode(", ", $error)
            : null
        );
    }


    /**
     * @param array $value
     * @param int $length
     * @param string $error_message
     * @return DataError
     */
    private static function isAllowed(array $value, int $length, string $error_message) : DataError
    {
        $size                                               = 0;
        $dataError                                          = new DataError();
        if ($length > 0) {
            foreach ($value as $item) {
                if ((is_array($item) && ($size += strlen(serialize($item))) > $length)
                    || (is_string($item) && ($size += strlen($item)) > $length)
                ) {
                    $dataError->error(413, $error_message);
                    break;
                }
            }

            $dataError->set("size", $size);
        }

        return $dataError;
    }

    /**
     * @param stdClass $rule
     * @param string|null $range
     */
    private static function setRuleOptions(stdClass &$rule, string $range = null) : void
    {
        if ($range) {
            if ($rule->filter == FILTER_VALIDATE_INT || $rule->filter == FILTER_VALIDATE_FLOAT) {
                if (strpos($range, ":") !== false) {
                    $arrOpt                                 = explode(":", $range);
                    if (is_numeric($arrOpt[0])) {
                        $rule->options["min_range"]         = $arrOpt[0];
                    }
                    if (is_numeric($arrOpt[1])) {
                        $rule->options["max_range"]         = $arrOpt[1];
                    }
                } elseif (is_numeric($range)) {
                    $rule->options["decimal"]               = $range;
                }
            } else {
                self::setRuleOptionsString($rule, $range);
            }
        }
    }

    /**
     * @param stdClass $rule
     * @param string $option
     */
    private static function setRuleOptionsString(stdClass &$rule, string $option) : void
    {
        if (strpos($option, ":") !== false) {
            $arrOpt                                 = explode(":", $option);
            if (is_numeric($arrOpt[1])) {
                $rule->length                       = $arrOpt[1];
            }
        } elseif (is_numeric($option)) {
            $rule->length                           = $option;
        } else {
            $rule->limit                            = $option;
        }
    }

    /**
     * @param string $name
     */
    private static function setContextName(string $name) : void
    {
        self::$contextName = $name;
    }

    /**
     * @return string
     */
    private static function getContextName() : string
    {
        return self::$contextName;
    }

    /**
     * @param string $size
     * @return int
     */
    private static function size2Bytes(string $size) : int
    {
        $ums = [
            "K" => 1,
            "M" => 2,
            "G" => 3,
            "T" => 4,
        ];

        $um = strtoupper(substr($size, -1));

        return (isset($ums[$um])
            ? substr($size, 0, -1) * (pow(1024, $ums[$um]))
            : $size
        );
    }
}
