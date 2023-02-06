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
namespace ff\libs;

use ff\libs\cache\Buffer;
use ff\libs\delivery\Notice;
use ff\libs\gui\Resource;
use ff\libs\storage\FilemanagerFs;
use ff\libs\util\ServerManager;
use ReflectionClass;

/**
 * Class Debug
 * @package ff\libs
 */
class Debug
{
    use ServerManager;

    const NAME_SPACE                        = __NAMESPACE__ . '\\';
    const ERROR_BUCKET                      = "exception";

    private const MAX_PAD                   = 40;
    private const RUNNER_EXCLUDE            = array(
                                                "index"     => true,
                                                "Kernel"    => true,
                                                "Debug"     => true,
                                                "Error"     => true,
                                                "Log"       => true
                                            );

    private static $disabled                = true;
    private static $app_start               = 0;
    private static $startWatch              = [];

    private static $exTime                  = [];
    private static $exTimeCache             = 0;
    private static $exTimeCacheIndex        = 0;
    private static $exTimeCacheResource     = 0;
    private static $exTimeCacheDelivery     = 0;

    private static $debug                   = [];
    private static $backtrace               = [];

    /**
     * Debug constructor.
     */
    public function __construct()
    {
        self::$disabled                     = false;
        self::$app_start                    = microtime(true);
        if (isset($_SERVER["REQUEST_TIME"])) {
            self::$exTime["autoload"]       = self::$app_start - $_SERVER["REQUEST_TIME"];
        }
        error_reporting(E_ALL);
        ini_set('display_errors', "On");

        $_SERVER["HTTPS"]                   = "on";

        /**
         * Performance Profiling
         */
        if (Kernel::$Environment::PROFILING) {
            self::benchmark();
        }
    }

    /**
     * @param $data
     * @param string $bucket
     */
    public static function set($data, string $bucket) : void
    {
        static $count                       = null;

        if (!empty($data)) {
            if ($bucket) {
                if (isset(self::$debug[$bucket])) {
                    $count[$bucket]         = ($count[$bucket] ?? 1) + 1;

                    $bucket                 .= "#" . $count[$bucket];
                }
                self::$debug[$bucket]       = $data;
            } elseif (is_array($data)) {
                self::$debug                = array_replace(self::$debug, $data);
            } else {
                array_push(self::$debug, $data);
            }
        }
    }

    public static function setBackTrace(array $backtrace) : void
    {
        if (Kernel::$Environment::PROFILING && empty(self::$backtrace)) {
            self::$backtrace = $backtrace;
        }
    }

    private static function numberFormat(float $exTime) : string
    {
        return number_format($exTime, 4, '.', '');
    }
    /**
     * @return array|null
     */
    public static function get() : ?array
    {
        $res                                = null;
        if (Kernel::$Environment::DEBUG) {
            $res                            = self::$debug;
            $res["exTime - Autoload"]       = self::numberFormat(self::exTime("autoload"));
            $res["exTime - App"]            = self::numberFormat(self::exTime("autoload") + self::exTimeApp());
            $res["exTime - UserCode"]       = self::numberFormat(self::exTimeApp() - Config::exTime() - Resource::exTime() - Notice::exTime() - self::$exTimeCache - Buffer::exTime("orm") + self::$exTimeCacheResource + self::$exTimeCacheDelivery);
            $res["exTime - Cache"]          = self::numberFormat(self::$exTimeCache) . " (" . self::numberFormat(self::$exTimeCacheIndex) . " indexing)";
            $res["exTime - Conf"]           = self::numberFormat(Config::exTime());
            $res["exTime - Orm"]            = self::numberFormat(Buffer::exTime("orm")) . (Buffer::exTime("orm") ? " (" . self::numberFormat(Buffer::exTime("database")) . " db)" : null);
            $res["exTime - Resource"]       = self::numberFormat(Resource::exTime() - self::$exTimeCacheResource);
            $res["exTime - Delivery"]       = self::numberFormat(Notice::exTime() - self::$exTimeCacheDelivery);
            $res["App - Cache"]             = (!Kernel::useCache() ? "off" : "on (" . Kernel::$Environment::CACHE_BUFFER_ADAPTER . ", " . Kernel::$Environment::CACHE_DATABASE_ADAPTER . ", " . Kernel::$Environment::CACHE_MEDIA_ADAPTER . ")");
            $res["query"]                   = Buffer::dump()["process"];
            $res["backtrace"]               = self::dumpBackTrace();
        }

        return $res;
    }
    /**
     * @param string $bucket
     * @return float|null
     */
    public static function stopWatch(string $bucket) : ?float
    {
        static $count                       = null;

        if (!Kernel::$Environment::DEBUG) {
            return 0;
        }

        if (isset(self::$startWatch[$bucket])) {
            $key                            = $bucket;
            if (isset(self::$exTime[$bucket])) {
                $count[$bucket]             = ($count[$bucket] ?? 1) + 1;

                $key                        .= "#" . $count[$bucket];
            }
            self::$exTime[$key]             = number_format(microtime(true) - self::$startWatch[$bucket], 4, '.', '');

            if (strpos($bucket, "Cache") === 0) {
                if (strpos($bucket, "Cache/resource") === 0) {
                    self::$exTimeCacheResource  += (float) self::$exTime[$key];
                }
                if (strpos($bucket, "Cache/delivery") === 0) {
                    self::$exTimeCacheDelivery  += (float) self::$exTime[$key];
                }
                self::$exTimeCache              += (float) self::$exTime[$key];
            }
            if (strpos($bucket, "CacheIndex") === 0) {
                self::$exTimeCacheIndex         += (float) self::$exTime[$key];
            }
            unset(self::$startWatch[$bucket]);
            return (float) self::$exTime[$key];
        } else {
            self::$startWatch[$bucket]      = microtime(true);
            return null;
        }
    }

    /**
     * @param $bucket
     * @return float|null
     */
    public static function exTime(string $bucket) : ?float
    {
        return (isset(self::$exTime[$bucket])
            ? number_format(self::$exTime[$bucket], 3, '.', '')
            : null
        );
    }

    /**
     * @return float
     */
    public static function exTimeApp() : float
    {
        $duration                           = microtime(true) - self::$app_start;
        return number_format($duration, 3, '.', '');
    }



    /**
     * @return array|null
     */
    private static function getBacktrace() : ?array
    {
        $res                                = [];
        $debug_backtrace                    = self::$backtrace;
        foreach ($debug_backtrace as $i => $trace) {
            if (isset($trace["file"])) {
                $res[$i]["file"] = $trace["file"];
            }
            if (isset($trace["line"])) {
                $res[$i]["line"] = $trace["line"];
            }
            if (isset($trace["type"])) {
                $res[$i]["type"] = $trace["type"];
            }
            if (isset($trace["class"])) {
                $res[$i]["class"] = $trace["class"];
            }
            if (isset($trace["function"])) {
                $res[$i]["function"] = $trace["function"];
            }

            if (!empty($trace["args"])) {
                foreach ($trace["args"] as $key => $value) {
                    if (is_object($value)) {
                        $res[$i]["args"][$key] = "Object: " . get_class($value);
                    } elseif (is_array($value)) {
                        foreach ($value as $subkey => $subvalue) {
                            if (is_object($subvalue)) {
                                $res[$i]["args"][$key][$subkey] = "Object: " . get_class($subvalue);
                            } elseif (is_array($subvalue)) {
                                $res[$i]["args"][$key][$subkey] = $subvalue;
                            } else {
                                $res[$i]["args"][$key][$subkey] = $subvalue;
                            }
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**
     * @return array
     */
    private static function dumpInterface() : array
    {
        $classes                                = get_declared_classes();
        $implements                             = array();
        /**
         * @var $classDumpable Dumpable
         */
        foreach ($classes as $class_name) {
            try {
                $reflect                        = new ReflectionClass($class_name);
                if ($reflect->implementsInterface(__NAMESPACE__ . '\\Dumpable')) {
                    $classDumpable              = $class_name;
                    $parent                     = $reflect->getParentClass();
                    if (!$parent || !isset($implements[basename(str_replace('\\', '/', $parent->getName()))])) {
                        $implements[basename(str_replace('\\', '/', $class_name))]    = (array) $classDumpable::dump();
                    }
                }
            } catch (\Exception $e) {
                Response::httpCode(500);
                die($e->getMessage());
            }
        }
        return $implements;
    }

    /**
     * @return array
     */
    public static function dumpBackTrace() : array
    {
        $res = array();
        $debug_backtrace = array_reverse(self::getBacktrace());
        foreach ($debug_backtrace as $trace) {
            if (isset($trace["file"]) && basename($trace["file"]) == "Error.php") {
                continue;
            }
            if (isset($trace["file"]) && basename($trace["file"]) == "Debug.php") {
                continue;
            }

            $class_name = basename(str_replace("\\", "/", $trace["class"] ?? ""));
            if (isset($trace["file"])) {
                $caller = $class_name . "::" . $trace["function"];
                $pad = self::MAX_PAD - strlen($caller);
                if ($pad < 0) {
                    $pad = 0;
                }
                $res[] =  $caller . str_repeat(" ", $pad) .  " ==> " . str_replace(Constant::DOCUMENT_ROOT . DIRECTORY_SEPARATOR, "", $trace["file"]) . ":" . $trace["line"];
            } else {
                $operation = (
                    isset($trace["class"])
                    ?  $class_name . $trace["type"] . $trace["function"] . '(' . json_encode($trace["args"]) . ')'
                    : $trace["function"]
                );

                $res[] = "Call " . $operation;
            }
        }

        return $res;
    }
    /**
     * @param string|null $error_message
     * @return string
     */
    private static function dumpCommandLine(string $error_message = null) : string
    {
        $cli = "\n----------------------------------------------------------------------------------------------------\n"
            . implode("\n", self::dumpBackTrace());
        return $cli . "\n----------------------------------------------------------------------------------------------------\n"
            . $error_message . "\n\n";
    }

    /**
     * @param array $debug_backtrace
     * @param string $collapse
     * @return string
     */
    private static function dumpBackTraceHtml(array $debug_backtrace, string $collapse) : string
    {
        $html                     = "";
        foreach ($debug_backtrace as $i => $trace) {
            $operation = '<mark>' . (
                isset($trace["class"])
                    ?  basename(str_replace("\\", "/", $trace["class"])) . $trace["type"] . $trace["function"]
                    : $trace["function"]
                ) . '</mark>';
            if (isset($trace["file"])) {
                $label = 'Line ' . $operation . ' in: ' . '<b>' . str_replace(Constant::DISK_PATH, "", $trace["file"])  . '</b>';
                $list_start = '<ol start="' . $trace["line"] . '">';
                $list_end = '</ol>';
            } else {
                if (isset($trace["class"]) && isset($debug_backtrace[$i + 1]["args"]) && isset($debug_backtrace[$i + 1]["args"][0][0])) {
                    $operation = '<mark>' . basename(str_replace(array("Object: ", "\\"), array("", "/"), $debug_backtrace[$i + 1]["args"][0][0])) . $trace["type"] . $trace["function"] . '(' . implode(", ", $trace["args"]) . ')</mark>';
                }

                $label = 'Call ' . $operation;
                $list_start = '<ul>';
                $list_end = '</ul>';
            }
            $html .=  $list_start . '<li><a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } ">' . $label . '</a><code style="' . $collapse . '"><pre>' . print_r($trace, true). '</pre></code></li>' . $list_end;
        }
        return $html;
    }

    /**
     * @param bool $backtrace
     * @return string
     */
    private static function dumpLogHtml(bool $backtrace = true) : string
    {
        $log = self::get();
        if (!$backtrace) {
            unset($log["backtrace"]);
        }

        return '<hr /><h5>rawLogs</h5>' . '<pre>' . print_r($log, true) . '</pre>';
    }

    /**
     * @param string|null $error_message
     * @param bool $return
     * @return string|null
     */
    public static function dump(string $error_message = null, bool $return = false) : ?string
    {
        self::stopWatch("debugger");

        if (self::$disabled) {
            return null;
        }

        if (self::isCli()) {
            echo self::dumpCommandLine($error_message);
            exit;
        }

        $html_dumpable                      = "";
        $debug_backtrace                    = self::getBacktrace();
        $collapse = (
            self::isXhr() && self::requestMethod() != Request::METHOD_GET
            ? ''
            : 'display:none;'
        );

        $dumpable = self::dumpInterface();
        $files_count = 0;
        $db_query_count = 0;
        $db_query_cache_count = 0;
        if (!empty($dumpable)) {
            foreach ($dumpable as $interface => $dump) {
                $dump = array_filter($dump);
                ksort($dump);
                if (!empty($dump)) {
                    $html_dumpable .= '<hr />' . '<h5>&nbsp;' . $interface . '</h5>';
                    $html_dumpable .= '<ul>';
                    foreach ($dump as $key => $value) {
                        $arrKey = explode(":", $key);
                        if ($value === true) {
                            $html_dumpable .= '<li>' . $arrKey[0] . '</li>';
                        } else {
                            $html_dumpable .= '<li><a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } ">' . $arrKey[0] . '</a><code style="' . $collapse . '"><pre>' . print_r($value, true) . '</pre></code></li>';
                        }
                        if (strtolower($interface) == "filemanager" && $key == "storage") {
                            foreach ($value as $file_index) {
                                $files_count = $files_count + count($file_index);
                            }
                        }
                    }
                    if (strtolower($interface) == "buffer" && !empty($dump["process"])) {
                        foreach ($dump["process"] as $key => $process) {
                            $db_query_count++;
                            if (strpos($key, "cache") !== false) {
                                $db_query_cache_count++;
                            }
                        }
                    }
                    $html_dumpable .= '</ul>';
                }
            }
        }

        if (!empty(self::$exTime)) {
            $html_dumpable .= '<hr />' . '<h5>&nbsp;' . "StopWatch" . '</h5>';
            $html_dumpable .= '<code><ul>';
            foreach (self::$exTime as $bucket => $exTime) {
                if ($exTime > 0.09) {
                    $fontsize = "large";
                } elseif ($exTime > 0.009) {
                    $fontsize = "normal";
                } else {
                    $fontsize = "x-small";
                }
                $html_dumpable .= '<li><span style="font-size: ' .  $fontsize. ';">' . $exTime . '</span> => <span>' . $bucket. '</span></li>';
            }
            $html_dumpable .= '</ul></code>';
        }

        $errors = array_filter(Exception::dump());
        $errors_count = 0;
        $dirstruct = Config::getDirBucket();
        if (!empty($dirstruct)) {
            foreach ($dirstruct as $dirBucket) {
                foreach ($dirBucket as $dir) {
                    if (!empty($dir["virtual"])) {
                        continue;
                    }

                    if (isset($dir["path"]) && !is_dir(Constant::DISK_PATH . $dir["path"]) && !FilemanagerFs::makeDir($dir["path"])) {
                        $errors["dirstruct"][] = "Failed to Write " . $dir["path"] . " Check permission";
                    } elseif (isset($dir["writable"]) && $dir["writable"] && !is_writable(Constant::DISK_PATH . $dir["path"])) {
                        $errors["dirstruct"][] = "Dir " . $dir["path"] . " is not Writable";
                    } elseif (!is_readable(Constant::DISK_PATH . $dir["path"])) {
                        $errors["dirstruct"][] = "Dir " . $dir["path"] . " is not Readible";
                    }
                }
            }
        }

        if (!empty($errors)) {
            $html_dumpable .= '<hr />' . '<h5>&nbsp;' . "Errors" . '</h5>';
            $html_dumpable .= '<code><ul>';
            foreach ($errors as $bucket => $error) {
                if (!empty($error)) {
                    foreach ($error as $msg) {
                        $html_dumpable .= '<li><b>' . $bucket. '</b> => ' . $msg . '</li>';
                        $errors_count++;
                    }
                }
            }
            $html_dumpable .= '</ul></code>';
        }

        $included_files = get_included_files();
        $included_files_count = 0;
        $included_files_autoload_count = 0;
        if (!empty($included_files)) {
            $html_dumpable .= '<hr />' . '<a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } "><h5>' . "Includes" . " (" . count($included_files) . ")" . '</h5></a>';
            $html_dumpable .= '<pre style="' . $collapse . '"><ul>';
            foreach ($included_files as $included_file) {
                $html_dumpable .= '<li>' . str_replace(Constant::DISK_PATH, "", $included_file) . '</li>';
                $included_files_count++;
                if (strtolower(pathinfo($included_file, PATHINFO_FILENAME)) == "autoload") {
                    $included_files_autoload_count++;
                }
            }
            $html_dumpable .= '</ul></pre>';
        }

        $constants = get_defined_constants(true);

        $constants_user = $constants["user"];
        if (!empty($constants_user)) {
            $html_dumpable .= '<hr />' . '<a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } "><h5>' . "Constants" . " (" . count($constants_user) . ")" . '</h5></a>';
            $html_dumpable .= '<pre style="' . $collapse . '"><ul>';
            foreach ($constants_user as $name => $value) {
                $html_dumpable .= '<li>' . $name . '</li>';
            }

            $html_dumpable .= '</ul></pre>';
        }



        $html = '<style type="text/css">
    BODY {
        padding-bottom: 30px;
    }
    .x-debugger {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: calc(100vh - 30px);
        background: rgba(255,255, 255, 0.94);
        -webkit-transform: translate3d(0, 100%, 0);
        z-index: 999999;
        will-change: transform;
        -webkit-transition: -webkit-transform .35s ease;
        transition: transform .35s ease, -webkit-transform .35s ease;
    }
    .x-debugger.active {
        -webkit-transform: translate3d(0, 0, 0);
        transform: translate3d(0, 0, 0);
    }
    .x-debugger .head {
        text-align: right;
        cursor: pointer;
        position: absolute;
        margin-top: -30px;
        background: #f3f3f3;
        border-top: 1px solid #cacaca;
        border-bottom: 1px solid #cacaca;
        width: 100%;
        font-size: 0.8rem;
        color: #5a5a5a;
        padding: 5px 10px;
    }
    .x-debugger .head > b {
        float: left;
    }
    .x-debugger .head > span {
        padding: 0 10px;
        white-space: nowrap;
    }
    .x-debugger .body {
        overflow-x: hidden;
        overflow-y: scroll;
        height: 100%;
        padding: 30px 10px;
    }
    .x-debugger .body table {
        width: 100%;
    }
    .x-debugger .body pre {
        white-space: pre-wrap;
    }   
    .x-debugger .body tbody td {
        vertical-align: text-top;
        max-width: 50vw;
    }
    .x-debugger a {
        color: #007bff;
    }
    .x-debugger h5 {
        color: #333;
    }
    </style>';

    $js = '<script type="text/javascript">' . "
    (function () {
        'use strict';
        
        const threshold = 160;
    
        const main = () => {
            const widthThreshold = window.outerWidth - window.innerWidth > threshold;
            const heightThreshold = window.outerHeight - window.innerHeight > threshold;
            const orientation = widthThreshold ? 'vertical' : 'horizontal';
    
            if (
                !(heightThreshold && widthThreshold) &&
                ((window.Firebug && window.Firebug.chrome && window.Firebug.chrome.isInitialized) || widthThreshold || heightThreshold)
            ) {
                if(document.getElementsByClassName('x-debugger')[0].style.display != 'block') {
                    document.getElementsByClassName('x-debugger')[0].style.display = 'block';
                }
            } else {
                if(document.getElementsByClassName('x-debugger')[0].style.display != 'none') {
                    document.getElementsByClassName('x-debugger')[0].style.display = 'none';
                }
            }
        };
    
        main();
        setInterval(main, 500);
    })();
</script>";

        $html .= '<div class="x-debugger">';
        $html .= '<div class="head" onclick=" if(document.getElementsByClassName(\'x-debugger\')[0].className.indexOf(\' active\') === -1) { document.getElementsByClassName(\'x-debugger\')[0].className = document.getElementsByClassName(\'x-debugger\')[0].className + \' active\'; } else { document.getElementsByClassName(\'x-debugger\')[0].className = document.getElementsByClassName(\'x-debugger\')[0].className.replace(\' active\', \'\'); } ">';
        $html .= (
            $error_message
            ? "<b>" . $error_message . "</b>"
            : ""
        );
        $html .= '<span>ExTime ' . self::numberFormat(self::exTime("autoload") + self::exTimeApp()) . '</span>'
        . '<span>BackTrace: ' . count($debug_backtrace) . '</span>'
        . '<span>Errors: ' . $errors_count . '</span>'
        . '<span>Includes: ' . $included_files_count . ' (' . $included_files_autoload_count . ' autoloads)' . '</span>'
        . '<span>Constants: ' . count($constants_user) . '</span>'
        . '<span>Files: ' . $files_count . '</span>'
        . '<span>DB Query: ' . $db_query_count . ' (' . $db_query_cache_count . ' cached)'. '</span>'
        . '<span>Adapters ('
        . 'Template: '  . '<em>' . Kernel::$Environment::TEMPLATE_ADAPTER   . '</em>, '
        . 'DB: '        . '<em>' . Kernel::$Environment::DATABASE_ADAPTER   . '</em>, '
        . 'Smtp: '      . '<em>' . Kernel::$Environment::NOTICE_SMTP_DRIVER  . '</em>, '
        . 'Sms: '       . '<em>' . Kernel::$Environment::NOTICE_SMS_DRIVER  . '</em>, '
        . 'Push: '      . '<em>' . Kernel::$Environment::NOTICE_PUSH_DRIVER  . '</em>, '
        . 'Translate: ' . '<em>' . Kernel::$Environment::TRANSLATOR_ADAPTER . '</em>'
        . ')</span>'
        . '<span>Cache ('
        . 'Mem: '       . (!Kernel::useCache() ? "<span style='color:red;'>" : "<span style='color:green;'>") . Kernel::$Environment::CACHE_BUFFER_ADAPTER      . '</span>, '
        . 'DB: '        . (!Kernel::useCache() ? "<span style='color:red;'>" : "<span style='color:green;'>") . Kernel::$Environment::CACHE_DATABASE_ADAPTER      . '</span>, '
        . 'Media: '     . (!Kernel::useCache() ? "<span style='color:red;'>" : "<span style='color:green;'>") . Kernel::$Environment::CACHE_MEDIA_ADAPTER     . '</span>'
        . ')</span>';

        $html .= '<br />'
            . '<span>Autoloader: ' . self::numberFormat(self::exTime("autoload")) . '</span>'
            . '<span>App: ' . self::numberFormat(self::exTimeApp()) . '</span>'
            . '<span>( UserCode: ' . self::numberFormat(self::exTimeApp() - Config::exTime() - Resource::exTime() - Notice::exTime() - self::$exTimeCache - Buffer::exTime("orm") + self::$exTimeCacheResource + self::$exTimeCacheDelivery) . '</span>'
            . '<span>Cache: ' . self::numberFormat(self::$exTimeCache) . " (" . self::numberFormat(self::$exTimeCacheIndex) . " indexing)" . ' </span>'
            . '<span>Conf: ' . self::numberFormat(Config::exTime()) . ' </span>'
            . '<span>Orm: ' . self::numberFormat(Buffer::exTime("orm")) . (Buffer::exTime("orm") ? " (" . self::numberFormat(Buffer::exTime("database"))  . " db)" : null) . ' </span>'
            . '<span>Resource: ' . self::numberFormat(Resource::exTime() - self::$exTimeCacheResource) . ' </span>'
            . '<span>Delivery: ' . self::numberFormat(Notice::exTime() - self::$exTimeCacheDelivery) . ' </span>'
            . '<span>Debugger: {debug_extime}) </span>';
        if (Kernel::$Environment::PROFILING) {
            $benchmark = self::benchmark(true);
            $html .= '<span>Mem: ' . $benchmark["mem"] . '</span>'
                . '<span>MemPeak: ' . $benchmark["mem_peak"] . '</span>'
                . '<span>CPU: ' . $benchmark["cpu"] . '</span>';
        }
        $html .= '</div>';

        $html .= '<div class="body">';
        $html   .= '<table>';
        $html   .= '<thead>';
        $html   .= '<tr>'         . '<th>BACKTRACE</th>'      . '<th>VARIABLES</th>'           . '</tr>';
        $html   .= '</thead>';
        $html   .= '<tbody>';
        $html   .= '<tr>'         . '<td>' . self::dumpBackTraceHtml($debug_backtrace, $collapse) . self::dumpLogHtml(false) . '</td>'  . '<td>' . $html_dumpable . '</td>'  . '</tr>';
        $html   .= '</tr>';
        $html   .= '</tbody>';
        $html   .= '</table>';
        $html   .= '</div></div>';

        $html = str_replace("{debug_extime}", self::numberFormat(self::stopWatch("debugger")), $html) . $js;

        if ($return) {
            return $html;
        } else {
            echo $html;
            return null;
        }
    }

    /**
     * @param int $size
     * @return string
     */
    private static function convertMem(int $size) : string
    {
        $unit=array('B','KB','MB','GB','TB','PB');
        return @round($size/pow(1024, ($i=(int) floor(log($size, 1024)))), 2).' '.$unit[$i];
    }


    /**
     * @param bool $end
     * @return mixed
     */
    public static function benchmark($end = false)
    {
        static $res;

        if (function_exists("getrusage")) {
            $ru = getrusage();
            if ($end) {
                $res["mem"] 			= self::convertMem(memory_get_usage());
                $res["mem_peak"] 		= self::convertMem(memory_get_peak_usage());
                $res["cpu"] 			= number_format(abs(($ru['ru_utime.tv_usec'] + $ru['ru_stime.tv_usec']) - $res["cpu"]), 0, ',', '.');

                return $res;
            } else {
                $res["cpu"]             = $ru['ru_utime.tv_usec'] + $ru['ru_stime.tv_usec'];
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public static function stackTraceOnce() : string
    {
        $debug_backtrace                    = debug_backtrace();
        $trace                              = $debug_backtrace[2];

        if (isset($trace["file"])) {
            $res                            = str_replace(Constant::DISK_PATH, "", $trace["file"]);
        } else {
            $res                            = $trace["function"];
        }

        return $res;
    }

    /**
     * @return array
     */
    public static function getRunners() : array
    {
        $res                                = array();
        $debug_backtrace                    = debug_backtrace();
        foreach (array_column($debug_backtrace, "class") as $i => $runner) {
            $res[basename(str_replace('\\', '/', $runner))] = $debug_backtrace[$i]["function"];
        }

        return array_diff_key(array_reverse($res, true), self::RUNNER_EXCLUDE);
    }
    /**
     * @return array|null
     */
    public static function stackTrace() : ?array
    {
        $res                                = null;
        $debug_backtrace                    = debug_backtrace();
        unset($debug_backtrace[0]);

        foreach ($debug_backtrace as $trace) {
            if (isset($trace["file"])) {
                $res[]                      = $trace["file"] . " on line " . $trace["line"];
            } else {
                $res[]                      = "Func: " . $trace["function"];
            }
        }

        return $res;
    }
}
