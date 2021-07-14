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

use phpformsframework\libs\storage\Filemanager;

/**
 * Class Log
 * @package phpformsframework\libs
 */
class Log
{
    public const CLASS_SEP                                      = "->";

    private const ENCODE_JSON                                   = "json";
    private const ENCODE_SERIALIZE                              = "serialize";
    private const PROTECTED_MESSAGE                             = "*protected*";
    private const PROTECTED_WORDS                               = array(
                                                                    "password"  => self::PROTECTED_MESSAGE,
                                                                    "secret"    => self::PROTECTED_MESSAGE,
                                                                    "hash"      => self::PROTECTED_MESSAGE,
                                                                    "SID"       => self::PROTECTED_MESSAGE
                                                                );
    /**
     * rfc5424 compliant
     */
    const TYPE_EMERGENCY                                        =   1;
    const TYPE_CRITICAL                                         =   2;
    const TYPE_ALERT                                            =   4;
    const TYPE_ERROR                                            =   8;
    const TYPE_WARNING                                          =   16;
    const TYPE_NOTICE                                           =   32;
    const TYPE_INFO                                             =   64;
    const TYPE_DEBUG                                            =   128;

    const TYPE_SYSTEM                                           = self::TYPE_EMERGENCY + self::TYPE_ALERT + self::TYPE_WARNING;
    const TYPE_REQUEST                                          = self::TYPE_CRITICAL + self::TYPE_ERROR + self::TYPE_NOTICE;

    const FORMAT_CLF                                            = "clf";
    const FORMAT_CLE                                            = "cle";
    const FORMAT_LSS                                            = "lss";

    const REQUEST                                               = array(
                                                                    "message"           => "message",
                                                                    "routine"           => "routine",
                                                                    "action"            => "action",
                                                                    "status"            => "status",
                                                                    "response"          => "response"
                                                                );
    const TYPE_DEFAULT                                          = self::TYPE_NOTICE;

    private static $encoding                                    = self::ENCODE_JSON;
    private static $count                                       = array(
                                                                    self::TYPE_EMERGENCY    => 0,
                                                                    self::TYPE_CRITICAL     => 0,
                                                                    self::TYPE_ALERT        => 0,
                                                                    self::TYPE_ERROR        => 0,
                                                                    self::TYPE_WARNING      => 0,
                                                                    self::TYPE_NOTICE       => 0,
                                                                    self::TYPE_INFO         => 0,
                                                                    self::TYPE_DEBUG        => 0,
                                                                    null                    => 0
                                                                );
    private static $current_count                               = null;
    private static $current_routine                             = null;
    private static $current_size                                = null;
    private static $current_tags                                = array();
    private static $formats                                     = array(
                                                                    self::FORMAT_CLF                => array( //common log format
                                                                        "rule"                      => array(
                                                                            "remote_addr"           => "REMOTE_ADDR",
                                                                            "identd"                => "IDENTD:ROUTINE" . self::CLASS_SEP . "ACTION [pid:PID-COUNT TAGS]",
                                                                            "auth_user"             => "AUTH",
                                                                            "datetime"              => 'DATE:TIME.MICRO ZONE (EXTIMEs)',
                                                                            "from"                  => "REQUEST_METHOD PATHINFO SERVER_PROTOCOL AJAX",
                                                                            "status"                => "STATUS_CODE referer: REFERER",
                                                                            "size"                  => "SIZE"
                                                                        ),
                                                                        "message"                   => ' "-" "MESSAGE"',
                                                                        "separator"                 => " - ",
                                                                        "quote_prefix"              => "",
                                                                        "quote_suffix"              => ""
                                                                    ),
                                                                    self::FORMAT_CLE                => array( //common log extend
                                                                        "rule"                      => array(
                                                                            "remote_addr"           => "REMOTE_ADDR",
                                                                            "identd"                => "IDENTD:ROUTINE" . self::CLASS_SEP . "ACTION [pid:PID-COUNT]",
                                                                            "auth_user"             => "AUTH",
                                                                            "datetime"              => 'DATE:TIME.MICRO ZONE',
                                                                            "from"                  => "REQUEST_METHOD PATHINFO SERVER_PROTOCOL AJAX",
                                                                            "status"                => "STATUS_CODE",
                                                                            "size"                  => "SIZE"
                                                                        ),
                                                                        "message"                   => ' "-" "MESSAGE"',
                                                                        "separator"                 => " - ",
                                                                        "quote_prefix"              => "",
                                                                        "quote_suffix"              => ""
                                                                    ),
                                                                    self::FORMAT_LSS                => array( //log server side
                                                                        "rule"                      => array(
                                                                            "datetime"              => 'EXTIMEs DAYN MONTHN DAY TIME.MICRO YEAR',
                                                                            "identd"                => "ROUTINE" . self::CLASS_SEP . "ACTION",
                                                                            "process"               => "pid PID-COUNT:tid TID:resource TAGS",
                                                                            "remote_addr"           => "client REMOTE_ADDR:REMOTE_PORT REFERER"
                                                                        ),
                                                                        "message"                   => ' MESSAGE',
                                                                        "separator"                 => " ",
                                                                        "quote_prefix"              => "[",
                                                                        "quote_suffix"              => "]",
                                                                        "strip"                     => array(
                                                                                                        "[:]",
                                                                                                        "[]"
                                                                                                    )
                                                                    )
                                                                );
    private static $rules                                       = array(
                                                                    self::TYPE_EMERGENCY            => array(
                                                                        "service"                   => "fs",
                                                                        "unalterable"               => false,
                                                                        "notify"                    => true,
                                                                        "bucket"                    => "emergency",
                                                                        "write_if"                  => null,
                                                                        "override"                  => false,
                                                                        "format"                    => self::FORMAT_LSS
                                                                    ),
                                                                    self::TYPE_ALERT                => array(
                                                                        "service"                   => "fs",
                                                                        "unalterable"               => false,
                                                                        "notify"                    => true,
                                                                        "bucket"                    => "critical",
                                                                        "write_if"                  => null,
                                                                        "override"                  => false,
                                                                        "format"                    => self::FORMAT_CLF
                                                                    ),
                                                                    self::TYPE_CRITICAL             => array(
                                                                        "service"                   => "fs",
                                                                        "unalterable"               => false,
                                                                        "notify"                    => true,
                                                                        "bucket"                    => "alert",
                                                                        "write_if"                  => null,
                                                                        "override"                  => false,
                                                                        "format"                    => self::FORMAT_LSS
                                                                    ),
                                                                    self::TYPE_ERROR                => array(
                                                                        "service"                   => "fs",
                                                                        "unalterable"               => false,
                                                                        "notify"                    => false,
                                                                        "bucket"                    => "error",
                                                                        "write_if"                  => null,
                                                                        "override"                  => false,
                                                                        "format"                    => self::FORMAT_CLF
                                                                    ),
                                                                    self::TYPE_WARNING              => array(
                                                                        "service"                   => "fs",
                                                                        "unalterable"               => false,
                                                                        "notify"                    => false,
                                                                        "bucket"                    => "warning",
                                                                        "write_if"                  => null,
                                                                        "override"                  => false,
                                                                        "format"                    => self::FORMAT_LSS
                                                                    ),
                                                                    self::TYPE_NOTICE               => array(
                                                                        "service"                   => "fs",
                                                                        "unalterable"               => false,
                                                                        "notify"                    => false,
                                                                        "bucket"                    => "notice",
                                                                        "write_if"                  => null,
                                                                        "override"                  => false,
                                                                        "format"                    => self::FORMAT_CLF
                                                                    ),
                                                                    self::TYPE_INFO                 => array(
                                                                        "service"                   => "fs",
                                                                        "unalterable"               => false,
                                                                        "notify"                    => false,
                                                                        "bucket"                    => "info",
                                                                        "write_if"                  => null,
                                                                        "override"                  => false,
                                                                        "format"                    => self::FORMAT_LSS
                                                                    ),
                                                                    self::TYPE_DEBUG                => array(
                                                                        "service"                   => "fs",
                                                                        "unalterable"               => false,
                                                                        "notify"                    => false,
                                                                        "bucket"                    => "debug",
                                                                        "write_if"                  => __NAMESPACE__ . "\\App::debugEnabled",
                                                                        "override"                  => false,
                                                                        "format"                    => self::FORMAT_CLE
                                                                    )
                                                                );
    private static $routine                                     = array(
                                                                    "redirect"                      => array(
                                                                        "service"                   => "fs",
                                                                        "unalterable"               => false,
                                                                        "notify"                    => false,
                                                                        "bucket"                    => "info_redirect",
                                                                        "write_if"                  => null,
                                                                        "override"                  => false,
                                                                        "format"                    => self::FORMAT_CLF
                                                                    )
                                                                );
    private static $procedure                                   = null;

    /**
     * @param int|null $type
     * @param string|null $bucket
     * @param string $format
     * @param bool|null $unalterable
     * @param callable|null $write_if
     * @param bool|null $override
     * @return array
     */
    public static function extend(int $type = null, string $bucket = null, string $format = null, bool $unalterable = null, callable $write_if = null, bool $override = null): array
    {
        $rule = (
            isset(self::$rules[$type])
            ? self::$rules[$type]
            : self::$rules[self::TYPE_DEFAULT]
        );
        $name = $rule["bucket"];

        if ($bucket) {
            $rule["bucket"] .= "_" . $bucket;
            $name .= "." . $bucket;
        }
        if ($write_if !== null) {
            $rule["write_if"] = $write_if;
        }
        if ($unalterable !== null) {
            $rule["unalterable"] = $unalterable;
        }
        if ($format !== null) {
            $rule["format"] = $format;
        }
        if ($override !== null) {
            $rule["override"] = $override;
        }

        self::$routine[$name] = $rule;

        return $rule;
    }

    /**
     * @param string|null $name
     */
    public static function setRoutine(string $name = null): void
    {
        $arrRoutine = explode(".", $name, 2);
        $bucket = (
            isset($arrRoutine[1])
            ? $arrRoutine[1]
            : null
        );
        $const = "TYPE_" . strtoupper($arrRoutine[0]);
        $type = (
            defined("self::" . $const)
            ? constant("self::" . $const)
            : self::TYPE_DEFAULT
        );

        self::extend($type, $bucket);

        self::$current_routine = $name;
    }

    /**
     * @param int $size
     */
    public static function setSize(int $size): void
    {
        self::$current_size = $size;
    }

    /**
     * @param string $tag
     */
    public static function addTag(string $tag): void
    {
        self::$current_tags[$tag] = $tag;
    }

    /**
     * @return string
     */
    protected static function getUser(): string
    {
        $res = App::getCurrentUser()->uuid ?? null;
        if (!$res) {
            $res = (
                isset($_COOKIE[session_name()])
                ? "user"
                : "guest"
            );
        }

        return $res;
    }

    /**
     * @param string $key
     * @param array $format
     */
    public static function addFormat(string $key, array $format): void
    {
        self::$formats[$key] = $format;
    }

    /**
     * @param string $routine
     * @param string $action
     */
    public static function registerProcedure(string $routine, string $action): void
    {
        self::$procedure = array(
            "routine" => $routine,
            "action" => $action
        );
    }

    /** system is unusable [SYSTEM]
     * @param $message
     * @param string|null $bucket
     * @param string $routine
     * @param string $action
     */
    public static function emergency($message, string $bucket = null, string $routine = null, string $action = null)
    {
        self::run($message, self::TYPE_EMERGENCY, $bucket, $routine, $action);
    }

    /** critical conditions [REQUEST]
     * @param $message
     * @param string|null $bucket
     * @param int $status
     */
    public static function alert($message, string $bucket = null, int $status = null)
    {
        self::run($message, self::TYPE_ALERT, $bucket, null, null, $status);
    }

    /** action must be taken immediately [SYSTEM]
     * @param $message
     * @param string|null $bucket
     * @param string $routine
     * @param string $action
     */
    public static function critical($message, string $bucket = null, string $routine = null, string $action = null)
    {
        self::run($message, self::TYPE_CRITICAL, $bucket, $routine, $action);
    }

    /** error conditions  [REQUEST]
     * @param $message
     * @param string|null $bucket
     * @param int $status
     */
    public static function error($message, string $bucket = null, int $status = null)
    {
        self::run($message, self::TYPE_ERROR, $bucket, null, null, $status);
    }

    /** warning conditions [SYSTEM]
     * @param $message
     * @param string|null $bucket
     * @param string $routine
     * @param string $action
     */
    public static function warning($message, string $bucket = null, string $routine = null, string $action = null)
    {
        self::run($message, self::TYPE_WARNING, $bucket, $routine, $action);
    }

    /** normal but significant condition [REQUEST]
     * @param $message
     * @param string|null $bucket
     * @param int $status
     */
    public static function notice($message, string $bucket = null, int $status = null)
    {
        self::run($message, self::TYPE_NOTICE, $bucket, null, null, $status);
    }

    /** informational messages [REQUEST]
     * @param $message
     * @param string|null $bucket
     * @param int $status
     */
    public static function info($message, string $bucket = null, int $status = null)
    {
        self::run($message, self::TYPE_INFO, $bucket, null, null, $status);
    }

    /** debug-level messages
     * @param $message
     * @param string|null $bucket
     * @param string $routine
     * @param string $action
     * @param int $status
     */
    public static function debugging($message, string $bucket = null, string $routine = null, string $action = null, int $status = null)
    {
        if (App::debugEnabled()) {
            if ($routine) {
                self::addTag($routine);
            }

            self::run($message, self::TYPE_DEBUG, $bucket, $routine, $action, $status);
        }
    }

    /**
     * @param $message
     */
    public static function write($message)
    {
        self::run($message);
    }

    /**
     * @return string|null
     */
    public static function getLogDir(): ?string
    {
        return Kernel::$Environment::LOG_DISK_PATH;
    }

    /**
     * @param $message
     * @param int|null $type
     * @param string|null $bucket
     * @param string|null $routine
     * @param string|null $action
     * @param int|null $status
     * @todo da tipizzare
     */
    private static function run($message, int $type = null, string $bucket = null, string $routine = null, string $action = null, int $status = null): void
    {
        if ($message) {
            $rule = self::getRoutine($type, $bucket);
            if ($rule && self::writable($rule)) {
                self::$current_count = self::$count[$type];

                $procedure = self::findProcedure($routine, $action);
                $content = self::fetchByFormat(
                    $rule["format"],
                    self::encodeMessage($message),
                    $procedure["routine"],
                    $procedure["action"],
                    $status
                );
                /**
                 * @todo: da finire
                 *
                 * if ($rule["unalterable"]) {
                 *    self::hashing($message, $bucket);
                 * }
                 *
                 * if ($rule["notify"]) {
                 *    self::notify($message, $bucket);
                 * }
                 */

                self::set($content, $rule["bucket"], $rule["override"]);
                self::$count[$type]++;
            }
        }
    }

    /**
     * @param $data
     * @param string|null $filename
     * @param bool $override
     * @todo da tipizzare
     */
    protected static function set($data, string $filename = null, bool $override = false): void
    {
        $log_path = self::getLogDir();
        if ($log_path) {
            $file = $log_path . '/' . Kernel::$Environment::APPNAME . "_" . date("Y-m-d") . "_" . $filename . '.txt';

            if ($override) {
                Filemanager::fsave($data, $file);
            } else {
                Filemanager::fappend($data, $file);
            }
        }
    }

    /**
     * @param array $rule
     * @return bool|null
     */
    private static function writable(array $rule): ?bool
    {
        return (is_callable($rule["write_if"])
            ? $rule["write_if"]()
            : true
        );
    }

    /**
     * @param string|null $routine
     * @param string|null $action
     * @return array
     */
    private static function findProcedure(string $routine = null, string $action = null): array
    {
        if (self::$procedure) {
            $res = self::$procedure;
        } else {
            $runners = Debug::getRunners();
            $res = array(
                "routine" => implode(self::CLASS_SEP, array_keys($runners)),
                "action" => end($runners)
            );
        }

        if ($routine) {
            $res["routine"] = $routine;
        }
        if ($action) {
            $res["action"] = $action;
        }

        return $res;
    }

    /**
     * @param int|null $type
     * @param string|null $bucket
     * @return array|null
     */
    private static function getRoutine(int $type = null, string $bucket = null): ?array
    {
        if (!$type) {
            $type = self::$current_routine;
        }
        $res = (
            isset(self::$routine[$type])
            ? self::$routine[$type]
            : self::extend($type)
        );

        if ($bucket) {
            $res["bucket"] .= "_" . $bucket;
        }

        return $res;
    }

    /**
     * @param string $name
     * @return array
     */
    private static function getFormat(string $name): array
    {
        return self::$formats[$name];
    }

    /**
     * @param string $format_name
     * @param string $message
     * @param string|null $routine
     * @param string|null $action
     * @param int|null $status
     * @return string
     */
    private static function fetchByFormat(string $format_name, string $message, string $routine = null, string $action = null, int $status = null): string
    {
        $format = self::getFormat($format_name);
        $content = self::fetch(
            $format["quote_prefix"]
            . implode(
                $format["quote_suffix"]
                . $format["separator"]
                . $format["quote_prefix"],
                $format["rule"]
            ) . $format["quote_suffix"],
            $routine,
            $action,
            $status
        );
        if (isset($format["strip"]) && $format["strip"]) {
            $content = str_replace($format["strip"], "", $content);
        }

        return $content . self::fetchMessage($message, $format["message"]);
    }

    /**
     * @return string
     */
    private static function getIdentity() : string
    {
        static $identity = null;

        if (!$identity) {
            $identity = (
                function_exists("posix_getpwuid")
                ? posix_getpwuid(posix_geteuid())['name']
                : "NULL"
            );
        }
        return $identity;
    }

    /**
     * @return string
     */
    private static function getThread() : string
    {
        static $thread = null;

        if (!$thread) {
            $thread = (
                class_exists("Thread")
                ? \Thread::getCurrentThreadId()
                : "NULL"
            );
        }
        return $thread;
    }

    /**
     * @return string
     */
    private static function getPid() : string
    {
        static $pid = null;

        if (!$pid) {
            $pid = (
                function_exists("getmypid")
                ? getmypid()
                : "NULL"
            );
        }
        return $pid;
    }


    /**
     * @param string $content
     * @param string|null $routine
     * @param string|null $action
     * @param int|null $status
     * @return string
     */
    private static function fetch(string $content, string $routine = null, string $action = null, int $status = null) : string
    {
        $server = null;
        /*foreach ($_SERVER as $key => $value) {
            if (!is_array($value)) {
                $server[$key] = $value;
            }
        }*/

        if (is_array($server)) {
            $content = str_replace(
                array_keys($server),
                array_values($server),
                $content
            );
        }

        $microtime  = explode(".", microtime(true));
        $micro      = (
            isset($microtime[1])
                        ? $microtime[1]
                        : 0
                    );
        return str_replace(
            [
                "ROUTINE",
                "ACTION",
                "IDENTD",
                "AUTH",
                "EXTIME",
                "DATE",
                "TIME",
                "ZONE",
                "DAYN",
                "MONTHN",
                "DAY",
                "MICRO",
                "YEAR",
                "PID",
                "COUNT",
                "TID",
                "STATUS_CODE",
                "SIZE",
                "TAGS",
                "REMOTE_ADDR",
                "REMOTE_PORT",
                "REFERER",
                "AJAX",
                "PATHINFO",
                "REQUEST_METHOD",
                "SERVER_PROTOCOL"
            ],
            [
                $routine,
                $action,
                self::getIdentity(),
                self::getUser(),
                Debug::exTimeApp(),
                strftime('%d/%b/%Y'),
                strftime('%H:%M:%S'),
                strftime('%z'),
                strftime('%a'),
                strftime('%b'),
                strftime('%d'),
                $micro,
                strftime('%Y'),
                self::getPid(),
                self::$current_count,
                self::getThread(),
                Response::httpCode($status),
                self::$current_size,
                implode(",", self::$current_tags),
                Request::remoteAddr(),
                Request::remotePort(),
                Request::referer(),
                (Request::isAjax() ? "ajax" : ""),
                Request::pathinfo(),
                Request::method(),
                Request::serverProtocol()
            ],
            $content
        );
    }

    /**
     * @param array $message
     * @return array
     */
    private static function hideSensitiveData(array $message) : array
    {
        return array_replace($message, array_intersect_key(self::PROTECTED_WORDS, $message));
    }

    /**
     * @todo da tipizzare
     * @param $message
     * @return string
     */
    private static function encodeMessage($message) : string
    {
        if (is_array($message)) {
            $message = self::hideSensitiveData($message);
            switch (self::$encoding) {
                case self::ENCODE_JSON:
                    $message = json_encode($message);
                    break;
                case self::ENCODE_SERIALIZE:
                    $message = serialize($message);
                    break;
                default:
                    $message = print_r($message, true);
            }
        }

        return $message;
    }

    /**
     * @param string $encode
     */
    public static function setEncoding(string $encode) : void
    {
        self::$encoding = $encode;
    }
    /**
     * @param string $message
     * @param string $format
     * @return string
     */
    private static function fetchMessage(string $message, string $format) : string
    {
        return str_replace(
            "MESSAGE",
            $message,
            $format
        );
    }

    /**
     * @todo da finire
     *
     * protected static function get($bucket, $tail = null)
     * {
     * }
     * private static function hashing($string, $bucket)
     * {
     * }
     * private static function notify($message, $object)
     * {
     * }
     */
}
