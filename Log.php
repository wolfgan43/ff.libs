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

use Thread;

if(!defined("LOG_SERVICES"))                                define("LOG_SERVICE", "fs");

class Log extends DirStruct {
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


    const LOG_DIR                                               = "/logs/HTTP_HOST";
    const REQUEST                                               = array(
                                                                    "message"           => "message"
                                                                    , "routine"         => "routine"
                                                                    , "action"          => "action"
                                                                    , "status"          => "status"
                                                                    , "response"        => "response"
                                                                );
    const TYPE_DEFAULT                                          = self::TYPE_INFO;
    private static $user                                        = null;
    private static $formats                                     = array(
                                                                    self::FORMAT_CLF                => array( //common log format
                                                                        "rule"                      => array(
                                                                            "remote_addr"           => "REMOTE_ADDR"
                                                                            , "identd"              => "IDENTD:ROUTINE:ACTION"
                                                                            , "auth_user"           => "AUTH"
                                                                            , "datetime"            => 'DATE:TIME.MICRO ZONE'
                                                                            , "from"                => "REQUEST_METHOD REQUEST_URI SERVER_PROTOCOL"
                                                                            , "status"              => "STATUS_CODE"
                                                                            , "size"                => "RESPONSE"
                                                                        )
                                                                        , "message"                 => ' "-" "MESSAGE"'
                                                                        , "encode"                  => "json"
                                                                        , "separator"               => " - "
                                                                        , "quote_prefix"            => ""
                                                                        , "quote_suffix"            => ""
                                                                    )
                                                                    , self::FORMAT_CLE                => array( //common log extend
                                                                        "rule"                      => array(
                                                                            "remote_addr"           => "REMOTE_ADDR"
                                                                            , "identd"              => "IDENTD:ROUTINE:ACTION"
                                                                            , "auth_user"           => "AUTH"
                                                                            , "datetime"            => 'DATE:TIME.MICRO ZONE'
                                                                            , "from"                => "REQUEST_METHOD REQUEST_URI SERVER_PROTOCOL"
                                                                            , "url_detail"          => "RESPONSE"
                                                                        )
                                                                        , "message"                 => ' "-" "MESSAGE"'
                                                                        , "encode"                  => null
                                                                        , "separator"               => " - "
                                                                        , "quote_prefix"            => ""
                                                                        , "quote_suffix"            => ""
                                                                    )
                                                                    , self::FORMAT_LSS              => array( //log server side
                                                                        "rule"                      => array(
                                                                            "datetime"              => 'DAYN MONTHN DAY TIME.MICRO YEAR'
                                                                            , "identd"              => "ROUTINE:ACTION"

                                                                            , "process"             => "pid PID:tid TID"
                                                                            , "remote_addr"         => "client REMOTE_ADDR:REMOTE_PORT"
                                                                        )
                                                                        , "message"                 => ' MESSAGE'
                                                                        , "encode"                  => "json"
                                                                        , "separator"               => " "
                                                                        , "quote_prefix"            => "["
                                                                        , "quote_suffix"            => "]"
                                                                        , "strip"                   => array(
                                                                                                        "[:]"
                                                                                                        , "[]"
                                                                                                    )
                                                                    )

                                                                );
    private static $rules                                       = array(
                                                                    self::TYPE_EMERGENCY             => array(
                                                                        "service"                   => "fs"
                                                                        , "unalterable"             => false
                                                                        , "notify"                  => true
                                                                        , "bucket"                  => "emergency"
                                                                        , "write_if"                => true
                                                                        , "override"                => false
                                                                        , "format"                  => self::FORMAT_LSS
                                                                    )
                                                                    , self::TYPE_ALERT             => array(
                                                                        "service"                   => "fs"
                                                                        , "unalterable"             => false
                                                                        , "notify"                  => true
                                                                        , "bucket"                  => "critical"
                                                                        , "write_if"                => true
                                                                        , "override"                => false
                                                                        , "format"                  => self::FORMAT_CLF
                                                                    )
                                                                    , self::TYPE_CRITICAL           => array(
                                                                        "service"                   => "fs"
                                                                        , "unalterable"             => false
                                                                        , "notify"                  => true
                                                                        , "bucket"                  => "alert"
                                                                        , "write_if"                => true
                                                                        , "override"                => false
                                                                        , "format"                  => self::FORMAT_LSS
                                                                    )
                                                                    , self::TYPE_ERROR              => array(
                                                                        "service"                   => "fs"
                                                                        , "unalterable"             => false
                                                                        , "notify"                  => false
                                                                        , "bucket"                  => "error"
                                                                        , "write_if"                => true
                                                                        , "override"                => false
                                                                        , "format"                  => self::FORMAT_CLF
                                                                    )
                                                                    , self::TYPE_WARNING            => array(
                                                                        "service"                   => "fs"
                                                                        , "unalterable"             => false
                                                                        , "notify"                  => false
                                                                        , "bucket"                  => "warning"
                                                                        , "write_if"                => true
                                                                        , "override"                => false
                                                                        , "format"                  => self::FORMAT_LSS
                                                                    )
                                                                    , self::TYPE_NOTICE             => array(
                                                                        "service"                   => "fs"
                                                                        , "unalterable"             => false
                                                                        , "notify"                  => false
                                                                        , "bucket"                  => "notice"
                                                                        , "write_if"                => true
                                                                        , "override"                => false
                                                                        , "format"                  => self::FORMAT_CLF
                                                                    )
                                                                    , self::TYPE_INFO               => array(
                                                                        "service"                   => "fs"
                                                                        , "unalterable"             => false
                                                                        , "notify"                  => false
                                                                        , "bucket"                  => "info"
                                                                        , "write_if"                => true
                                                                        , "override"                => false
                                                                        , "format"                  => self::FORMAT_CLF
                                                                    )
                                                                    , self::TYPE_DEBUG              => array(
                                                                        "service"                   => "fs"
                                                                        , "unalterable"             => false
                                                                        , "notify"                  => false
                                                                        , "bucket"                  => "debug"
                                                                        , "write_if"                => Debug::ACTIVE
                                                                        , "override"                => false
                                                                        , "format"                  => self::FORMAT_CLF
                                                                    )
                                                                );
    private static $routine                                     = array(
                                                                    "redirect"                    => array(
                                                                        "service"                   => "fs"
                                                                        , "unalterable"             => false
                                                                        , "notify"                  => false
                                                                        , "bucket"                  => "info_redirect"
                                                                        , "write_if"                => Debug::ACTIVE
                                                                        , "override"                => false
                                                                        , "format"                  => self::FORMAT_CLF
                                                                    )
                                                                );
    private static $procedure                                   = null;

    public static function extend($routine, $type, Array $params = null) {
        $rule                                                   = self::getRule($type);
        self::$routine[$routine]                                = $rule;
        if ($params) {
            self::$routine[$routine]                            = array_replace(self::$routine[$routine], $params);
            if($rule["bucket"] && $params["bucket"]) {
                self::$routine[$routine]["bucket"]              = $rule["bucket"] . "_" . $params["bucket"];
            }
        }
    }
    public static function setUser($name) {
        self::$user                                             = $name;
    }
    protected static function getUser() {
        return (self::$user
            ? self::$user
            : (isset($_COOKIE[session_name()])
                ? "user"
                : "guest"
            )
        );
    }
    public static function addFormat($key, $format) {
        self::$formats[$key]                                    = $format;
    }
    public static function registerProcedure($routine, $action, $bucket = null) {
        self::$procedure                                        = array(
                                                                    "routine"   => $routine
                                                                    , "action"  => $action
                                                                    , "bucket"  => $bucket
                                                                );
    }

    /** system is unusable [SYSTEM]
     * @param $message
     * @param null $routine
     * @param null $action
     */
    public static function emergency($message, $routine = null, $action = null) {
        self::run($message, self::TYPE_EMERGENCY, $routine, $action);
    }

    /** critical conditions [REQUEST]
     * @param $message
     * @param null $status
     * @param null $response
     */
    public static function alert($message, $status = null, $response = null) {
        self::run($message, self::TYPE_ALERT, null, null, $status, $response);
    }

    /** action must be taken immediately [SYSTEM]
     * @param $message
     * @param null $routine
     * @param null $action
     */
    public static function critical($message, $routine = null, $action = null) {
        self::run($message, self::TYPE_CRITICAL, $routine, $action);
    }

    /** error conditions  [REQUEST]
     * @param $message
     * @param null $status
     * @param null $response
     */
    public static function error($message, $status = null, $response = null) {
        self::run($message, self::TYPE_ERROR, null, null, $status, $response);
    }

    /** warning conditions [SYSTEM]
     * @param $message
     * @param null $routine
     * @param null $action
     */
    public static function warning($message, $routine = null, $action = null) {
        self::run($message, self::TYPE_WARNING, $routine, $action);
    }

    /** normal but significant condition [REQUEST]
     * @param $message
     * @param null $status
     * @param null $response
     */
    public static function notice($message, $status = null, $response = null) {
        self::run($message, self::TYPE_NOTICE, null, null, $status, $response);
    }

    /** informational messages [REQUEST]
     * @param $message
     * @param null $status
     * @param null $response
     */
    public static function info($message, $status = null, $response = null) {
        self::run($message, self::TYPE_INFO, null, null, $status, $response);
    }

    /** debug-level messages
     * @param $message
     * @param null $routine
     * @param null $action
     * @param null $status
     */
    public static function debugging($message, $routine = null, $action = null, $status = null) {
        self::run($message, self::TYPE_DEBUG, $routine, $action, $status);
    }

    /** Request: informational messages
     * @param $message
     * @param null $status
     * @param null $size
     */
    public static function request($message, $status = null, $size = null) {
        self::run($message, null, null, null, $status, $size);
    }


    /**
     * @param $message
     * @param null $type
     * @param null $status
     * @param null $response
     */
    public static function write($message, $type = null, $status = null, $response = null) {
        self::run($message, $type, null, null, $status, $response);
    }

    public static function getLogDir() {
        return self::getDiskPath("cache") . str_replace("HTTP_HOST", $_SERVER["HTTP_HOST"], self::LOG_DIR);
    }

    private static function run($message, $type = null, $routine = null, $action = null, $status = null, $response = null) {
        if(self::writable($type)) {
            $procedure                                              = self::findProcedure($routine, $action);


            $rule                                                   = self::getRoutine($type
                ? $type
                : $procedure["routine"]
            );



            if($rule["write_if"]) {
                $bucket                                             = ($rule["bucket"]
                    ? $rule["bucket"]
                    : $procedure["bucket"]
                );

                $content                                            = self::fetchByFormat(
                    $rule["format"]
                    , $message
                    , ($procedure["caller"] && $bucket != $procedure["caller"]
                        ? $procedure["caller"] . ":"
                        : ""
                    ) . $procedure["routine"]
                    , $procedure["action"]
                    , $status
                    , $response
                );

                if($rule["unalterable"])                            { self::hashing($message, $bucket); }

                if($rule["notify"])                                 { self::notify($message, $bucket); }

                self::set($content, $bucket, $rule["override"]);
            }
        }
    }

    protected static function set($data, $filename = null, $override = false) {
        $log_path                                                   = self::getLogDir();
        if(!is_dir($log_path))                                      { mkdir($log_path, 0777, true); }

        $file                                                       = $log_path . '/' . date("Y-m-d") . "_" . $filename . '.txt';

        if($override) {
            Filemanager::fsave($data, $file);
        } else {
            Filemanager::fappend($data, $file);
        }
    }

    //todo: da finire
    protected static function get($bucket, $tail = null) {

    }


    private static function writable($type) {
        $routine                                                    = self::getRoutine($type);
        return $routine["write_if"];
    }
    private static function findProcedure($routine = null, $action = null) {
        $routine_caller                                             = null;
        if(self::$procedure) {
            $routine                                                = self::$procedure["routine"];
            $action                                                 = self::$procedure["action"];
            $bucket                                                 = (self::$procedure["bucket"]
                ? self::$procedure["bucket"]
                : $routine
            );
        } else {
            $caller_parent                                          = array();
            $caller_true                                            = array();
            $bucket                                                 = $routine;
            if(!$routine || !$action) {
                $trace                                              = debug_backtrace();
                if(is_array($trace) && count($trace)) {
                    $caller                                         = $trace[3];

                    for($i = 4; $i < count($trace); $i++) {
                        if($trace[$i]["function"] != "require"
                            && $trace[$i]["function"] != "require_once"
                            && $trace[$i]["function"] != "include"
                            && $trace[$i]["function"] != "incluce_once"
                        ) {
                            $caller_parent                          = $trace[$i];
                            break;
                        }
                    }
                    for($n = ++$i; $n < count($trace); $n++) {
                        if($trace[$n]["function"] != "require"
                            && $trace[$n]["function"] != "require_once"
                            && $trace[$n]["function"] != "include"
                            && $trace[$n]["function"] != "incluce_once"
                        ) {
                            $caller_true                            = $trace[$n];
                            break;
                        }
                    }


                    if(!$routine)                                   { $routine = $caller_parent['class']; }
                    if($caller['class'])                            { $bucket = $routine_caller = $caller['class']; }

                    $action                                         = ($action
                        ? $action
                        : ($caller_parent['class'] == $caller_true['class']
                            ? $caller_true['function']
                            : $caller_parent['function']
                        )
                    );
                }

            }
        }
        return array(
            "bucket"                                            => $bucket
            , "routine"                                         => $routine
            , "caller"                                          => $routine_caller
            , "action"                                          => $action
        );
    }

    private static function getRoutine($name = null) {
        return (self::$routine[$name]
            ? self::$routine[$name]
            : self::getRule($name)
        );
    }
    private static function getRule($name = null) {
        if(self::$rules[$name]) {
            $rule                                               = self::$rules[$name];
        } else {
            $rule                                               = self::$rules[self::TYPE_DEFAULT];
            //$rule["bucket"]                                     = $name;
        }
        return $rule;
    }
    private static function getFormat($name) {
        return self::$formats[$name];
    }

    private static function fetchByFormat($format_name, $message = null, $routine = null, $action = null, $status = null, $response = null) {
        $format                                                 = self::getFormat($format_name);
        $content                                                = self::fetch(
            $format["quote_prefix"]
            . implode($format["quote_suffix"]
                . $format["separator"]
                . $format["quote_prefix"]
                , $format["rule"]
            ) . $format["quote_suffix"]
            , $routine
            , $action
            , $status
            , $response
        );
        if($format["strip"])                                    { $content = str_replace($format["strip"], "", $content); }

        return $content . self::fetchMessage($message, $format["message"], $format["encode"]);
    }
    private static function fetch($content, $routine = null, $action = null, $status = null, $response = null) {
        $content = str_replace(
            array_keys($_SERVER)
            , array_values($_SERVER)
            , $content
        );

        $content = str_replace(
            array(
                "ROUTINE"
            , "ACTION"
            , "IDENTD"
            , "AUTH"
            , "DATE"
            , "TIME"
            , "ZONE"
            , "DAYN"
            , "MONTHN"
            , "DAY"
            , "MICRO"
            , "YEAR"
            , "PID"
            , "TID"
            , "EXTIME"
            , "STATUS_CODE"
            , "RESPONSE"
            )
            , [
                $routine
            , $action
            , posix_getpwuid(posix_geteuid())['name']
            , self::getUser()
            , strftime('%d/%b/%Y')
            , strftime('%H:%M:%S')
            , strftime('%z')
            , strftime('%a')
            , strftime('%b')
            , strftime('%d')
            , explode(".", microtime(true))[1]
            , strftime('%Y')
            , getmypid()
            , (class_exists("Thread") ? Thread::getCurrentThreadId() : null)
            , Debug::stopWatch()
            , Response::code($status)
            , $response
            ]
            , $content
        );

        return $content;
    }
    private static function encodeMessage($message, $encode = null) {
        if(is_array($message)) {
            switch ($encode) {
                case "json":
                    $message = json_encode($message);
                    break;
                case "serialize":
                    $message = serialize($message);
                    break;
                default:
                    $message = print_r($message, true);
            }
        }

        return $message;
    }
    private static function fetchMessage($message, $format, $encode = null) {
        $content = null;
        if($message) {
            $content = str_replace(
                "MESSAGE"
                , self::encodeMessage($message, $encode)
                , $format
            );
        }
        return $content;
    }


    private static function hashing($string, $bucket) {

    }

    private static function notify($message, $object) {

    }
}



