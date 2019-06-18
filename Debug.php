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

use phpformsframework\libs\storage\Database;
use ReflectionClass;
use Exception;

if(!defined("APP_START"))               { define("APP_START", microtime(true)); }
if(!defined("DEBUG_PROFILING"))         { define("DEBUG_PROFILING", false); }
if(!defined("DEBUG_MODE"))              { define("DEBUG_MODE", false); }

Log::extend("profiling", Log::TYPE_DEBUG, array(
    "bucket"        => "profiling"
    , "write_if"    => DEBUG_PROFILING
    , "override"    => true
    , "format"      => Log::FORMAT_CLE
));

if(DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

class Debug extends DirStruct
{
    const STOPWATCH                         = APP_START;
    const PROFILING                         = DEBUG_PROFILING;
    const ACTIVE                            = DEBUG_MODE;

    private static $microtime               = self::STOPWATCH;

    private static $debug                   = array();

    /**
     * @param null $microtime
     * @return string
     */
    public static function startWatch($microtime = null)
    {
        self::$microtime                = ($microtime
                                            ? $microtime
                                            : microtime(true)
                                        );
        return number_format(self::$microtime, 2, '.', '');
    }
    public static function stopWatch($microtime = null)
    {
        $duration                       = ($microtime
                                            ? $microtime
                                            : microtime(true)
                                        ) - self::$microtime;

        self::$microtime                = self::STOPWATCH;
        return number_format($duration, 2, '.', '');
    }

    private static function exTime() {
        $duration                       = microtime(true) - self::STOPWATCH;
        return number_format($duration, 3, '.', '');
    }

    public static function registerErrors() {
        declare(ticks=1);

        register_tick_function(function() {
            $GLOBALS["backtrace"]                           = debug_backtrace();
        });

        register_shutdown_function(function() {
            $error = error_get_last();

            switch ($error['type']) {
                case E_NOTICE:
                case E_USER_NOTICE:
                    Log::warning($error);
                    break;
                case E_WARNING:
                case E_DEPRECATED:
                    header("Content-Type: text/html");

                    echo "<br /><br /><b>Warning</b>: " . $error["message"] . " in <b>" . $error["file"] . "</b> on line <b>" . $error["line"] . "</b>";

                case E_ERROR:
                case E_RECOVERABLE_ERROR:

                case E_PARSE:
                case E_STRICT:


                case E_CORE_ERROR:
                case E_CORE_WARNING:
                case E_COMPILE_ERROR:

                case E_COMPILE_WARNING:
                case E_USER_ERROR:
                case E_USER_WARNING:

                case E_USER_DEPRECATED:
                    self::dump($GLOBALS["backtrace"]);
                    if(function_exists("cache_sem_remove")) {
                        cache_sem_remove($_SERVER["PATH_INFO"]);
                    }
                    break;
                default:
            }
        });
    }
    public static function dumpLog($filename, $data = null) {
        $trace                                  = self::get_backtrace();

        $data["source"]                         = $trace;
        Log::write($data, $filename);
    }

    public static function dumpCaller($note = null, $backtrace = null) {
        if(self::PROFILING) {
            $disk_path                          = self::$disk_path;
            $debug_backtrace                    = (is_array($backtrace)
                                                    ? $backtrace
                                                    : debug_backtrace()
                                                );
            foreach($debug_backtrace AS $i => $trace) {
                if($i) {
                    if (basename($trace["file"]) == "Debug.php") {
                        continue;
                    }
                    if (basename($trace["file"]) == "cm.php") {
                        break;
                    }

                    if($trace["file"]) {
                        $res = $trace["line"] . ' Line in: ' . str_replace($disk_path, "", $trace["file"]);
                    } else {
                        $res = 'Func: ' . $trace["function"];
                    }
                    if($res) {
                        self::$debug[] = $res . "\n" . str_repeat(" ", 8) . (is_array($note) ? print_r($note, true) : $note);
                        break;
                    }
                }
            }
        }
    }

    private static function get_backtrace($backtrace = null) {
        $res                                = null;
        $debug_backtrace                    = (is_array($backtrace)
                                                ? $backtrace
                                                : debug_backtrace()
                                            );

        foreach($debug_backtrace AS $i => $trace) {
            if($i) {
                if(isset($trace["file"]) && basename($trace["file"]) == "vgCommon.php") {
                    continue;
                }
                if(isset($trace["file"]) && basename($trace["file"]) == "cm.php") {
                    break;
                }

                unset($trace["object"]);
                if (is_array($trace["args"]) && count($trace["args"])) {
                    foreach ($trace["args"] AS $key => $value) {
                        if (is_object($value)) {
                            $trace["args"][$key] = "Object: " . get_class($value);
                        } elseif(is_array($value)) {
                            foreach($value AS $subkey => $subvalue) {
                                if(is_object($subvalue)) {
                                    $trace["args"][$key][$subkey] = "Object: " . get_class($subvalue);
                                } elseif(is_array($subvalue)) {
                                    $trace["args"][$key][$subkey] = $subvalue;
                                } else {
                                    $trace["args"][$key][$subkey] = $subvalue;
                                }
                            }

                        }
                    }
                }
                $res[] = $trace;
            }
        }

        return $res;
    }

    private static function dumpInterface() {
        $classes                                = get_declared_classes();
        $implements                             = array();
        /**
         * @var $classDumpable Dumpable
         */
        foreach($classes as $class_name) {
            try {
                $reflect                        = new ReflectionClass($class_name);
                if($reflect->implementsInterface(__NAMESPACE__ . '\\Dumpable')) {
                    $classDumpable              = $class_name;
                    $parent                     = $reflect->getParentClass();

                    if(!$parent || !isset($implements[$parent->getName()])) {
                        $implements[basename(str_replace('\\', '/', $class_name))]    = (array) $classDumpable::dump();
                    }
                }
            } catch (Exception $exception) {
                Error::register($exception->getMessage(), "exception");
            }
        }
        return $implements;
    }
    public static function dump($error_message = null, $return = false) {
        $html_backtrace                     = "";
        $html_dumpable                      = "";
        $disk_path                          = self::$disk_path;
        $debug_backtrace                    = self::get_backtrace();
        $collapse = (Request::isAjax() && Request::method() != "GET"
            ? ''
            : 'display:none;'
        );
        foreach($debug_backtrace AS $i => $trace) {
            if(isset($trace["file"])) {
                $label = 'Line in: ' . '<b>' . str_replace($disk_path, "", $trace["file"])  . '</b>';
                $list_start = '<ol start="' . $trace["line"] . '">';
                $list_end = '</ol>';
            } else {
                $label = 'Func: ' . '<b>' .  $trace["function"] . '</b>';
                $list_start = '<ul>';
                $list_end = '</ul>';

            }
            $html_backtrace .=  $list_start . '<li><a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } ">' . $label . '</a><code style="' . $collapse . '"><pre>' . print_r($trace, true). '</pre></code></li>' . $list_end;
        }

        $dumpable = self::dumpInterface();
        $files_count = 0;
        $db_query_count = 0;
        $db_query_cache_count = 0;
        if(is_array($dumpable) && count($dumpable)) {
            foreach ($dumpable as $interface => $dump) {
                $dump = array_filter($dump);
                if(is_array($dump) && count($dump)) {
                    $html_dumpable .= '<hr />' . '<h5>&nbsp;' . $interface . '</h5>';
                    $html_dumpable .= '<ul>';
                    foreach ($dump as $key => $value) {
                        $arrKey = explode(":", $key);
                        if($value === true) {
                            $html_dumpable .= '<li>' . $arrKey[0] . '</li>';
                        } else {
                            $html_dumpable .= '<li><a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } ">' . $arrKey[0] . '</a><code style="' . $collapse . '"><pre>' . print_r($value, true) . '</pre></code></li>';
                        }
                        if(strtolower($interface) == "filemanager" && $key == "storage") {
                            $files_count++;
                        }
                        if(strtolower($interface) == "database") {
                            $db_query_count++;
                            if($value === true) {
                                $db_query_cache_count++;
                            }
                        }
                    }
                    $html_dumpable .= '</ul>';
                }
            }
        }

        $errors = array_filter((array) Error::raise());
        $errors_count = 0;
        if(is_array($errors) && count($errors)) {
            $html_dumpable .= '<hr />' . '<h5>&nbsp;' . "Errors" . '</h5>';
            $html_dumpable .= '<code><ul>';
            foreach ($errors as $bucket => $error) {
                if(is_array($error) && count($error)) {
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
        if(is_array($included_files) && count($included_files)) {
            $html_dumpable .= '<hr />' . '<a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } "><h5>' . "Includes" . " (" . count($included_files) . ")" . '</h5></a>';
            $html_dumpable .= '<pre style="' . $collapse . '"><ul>';
            foreach ($included_files as $included_file) {
                $html_dumpable .= '<li>' . str_replace(self::$disk_path, "", $included_file) . '</li>';
                $included_files_count++;
                if(strtolower(pathinfo($included_file, PATHINFO_FILENAME)) == "autoload") {
                    $included_files_autoload_count++;
                }
            }
            $html_dumpable .= '</ul></pre>';
        }

        $constants = get_defined_constants(true);

        $constants_user = $constants["user"];
        if(is_array($constants_user) && count($constants_user)) {
            $html_dumpable .= '<hr />' . '<a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } "><h5>' . "Constants" . " (" . count($constants_user) . ")" . '</h5></a>';
            $html_dumpable .= '<pre style="' . $collapse . '"><ul>';
            foreach ($constants_user as $name => $value) {
                $html_dumpable .= '<li>' . $name . '</li>';
            }

            $html_dumpable .= '</ul></pre>';
        }

        $html   = ($error_message
                    ? "<hr /><b>" . $error_message . "</b>"
                    : ""
                );

        $html .= '<hr />' . '<center>'
            . '<span style="padding:15px;">BackTrace: ' . count($debug_backtrace) . '</span>'
            . '<span style="padding:15px;">Errors: ' . $errors_count . '</span>'
            . '<span style="padding:15px;">Includes: ' . $included_files_count . ' (' . $included_files_autoload_count . ' autoloads)' . '</span>'
            . '<span style="padding:15px;">Constants: ' . count($constants_user) . '</span>'
            . '<span style="padding:15px;">Files: ' . $files_count . '</span>'
            . '<span style="padding:15px;">DB Query: ' . $db_query_count . ' (' . $db_query_cache_count . ' cached)'. '</span>'
            . '<span style="padding:15px;">ExTime: ' . self::exTime() . '</span>'
            . '</center>';

        $html   .= '<table>';
        $html   .= '<thead>';
        $html   .= '<tr>'         . '<th>BACKTRACE</th>'      . '<th>VARIABLES</th>'           . '</tr>';
        $html   .= '</thead>';
        $html   .= '<tbody>';
        $html   .= '<tr>'         . '<td valign="top">' . $html_backtrace . '</td>'  . '<td valign="top">' . $html_dumpable . '</td>'  . '</tr>';
        $html   .= '</tr>';
        $html   .= '</tbody>';
        $html   .= '</table>';

        if($return) {
            return $html;
        } else {
            echo $html;
            return null;
        }
    }

    /**
     * @param bool $end
     * @return mixed
     */
    public static function benchmark($end = false) {
        static $res;

        if(function_exists("getrusage"))
        {
            $ru = getrusage();
            if ($end) {
                $res["mem"] 			= number_format(memory_get_usage(true) - $res["mem"], 0, ',', '.');
                $res["mem_peak"] 		= number_format(memory_get_peak_usage(true) - $res["mem_peak"], 0, ',', '.');
                $res["cpu"] 			= number_format(abs(($ru['ru_utime.tv_usec'] + $ru['ru_stime.tv_usec']) - $res["cpu"]), 0, ',', '.');
                $res["includes"] 		= get_included_files();
                $res["classes"] 		= get_declared_classes();
                $res["db"] 				= Database::dump();
                $res["exTime"] 			= microtime(true) - $res["exTime"];

                if (extension_loaded('xhprof') && is_dir(FF_DISK_PATH . "/xhprof_lib") && class_exists("XHProfRuns_Default")) {
                    $path_info          = ($_SERVER["PATH_INFO"] == DIRECTORY_SEPARATOR
                                            ? "Home"
                                            : $_SERVER["PATH_INFO"]
                                        );

                    $xhr_path_info      = ($_SERVER["XHR_PATH_INFO"] == DIRECTORY_SEPARATOR
                                            ? "Home"
                                            : $_SERVER["XHR_PATH_INFO"]
                                        );
                    $profiler_namespace = str_replace(array(".", "&", "?", "__nocache__"), array(",", "", "", ""), "[" . round($res["exTime"], 2) . "s] "
                        . str_replace(DIRECTORY_SEPARATOR, "_", trim($path_info, DIRECTORY_SEPARATOR))
                        . ($xhr_path_info != $path_info && $xhr_path_info
                            ? " (" . str_replace(DIRECTORY_SEPARATOR, "_", trim($xhr_path_info, DIRECTORY_SEPARATOR)) . ")"
                            : ""
                        )
                        . (Request::isAjax()
                            ? " - Request"
                            : ""
                        ))
                        . ($end !== true
                            ? " - " . $end
                            : ""
                        );

                    $xhprof_data = xhprof_disable();
                    $xhprof_runs = new \XHProfRuns_Default();
                    $run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
                    $res["url"] = sprintf("http" . ($_SERVER["HTTPS"] ? "s" : "") . "://" . $_SERVER["HTTP_HOST"] . '/xhprof_html/index.php?run=%s&source=%s', $run_id, $profiler_namespace);
                }

                Log::write($res, "profiling", null, (Request::isAjax() ? "xhr" : "page"));

                return $res;
            } else {
                $res["mem"]             = memory_get_usage(true);
                $res["mem_peak"]        = memory_get_peak_usage(true);
                $res["cpu"]             = $ru['ru_utime.tv_usec'] + $ru['ru_stime.tv_usec'];
                $res["exTime"] 			= microtime(true);

                if (extension_loaded('xhprof') && is_dir(FF_DISK_PATH . "/xhprof_lib")) {
                    self::autoload(self::$disk_path . '/xhprof_lib/utils/xhprof_lib.php', true);
                    self::autoload(self::$disk_path . '/xhprof_lib/utils/xhprof_runs.php', true);

                    xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
                }
            }
        }
        return null;
    }

    public static function stackTraceOnce() {
        $debug_backtrace                    = debug_backtrace();
        $trace                              = $debug_backtrace[2];

        if(isset($trace["file"])) {
            $res                            = str_replace(self::$disk_path, "", $trace["file"]);
        } else {
            $res                            = $trace["function"];
        }

        return $res;
    }

    public static function stackTrace($plainText = false) {
        $res                                = null;
        $debug_backtrace                    = debug_backtrace();
        unset($debug_backtrace[0]);

        foreach ($debug_backtrace AS $i => $trace) {
            if(isset($trace["file"])) {
                $res[]                      = $trace["file"] . " on line " . $trace["line"];
            } else {
                $res[]                      = "Func: " . $trace["function"];
            }
        }

        return ($plainText
            ? implode(", ", $res)
            : $res
        );
    }

    public static function page($page) {
        Log::debugging(array(
            "page"      => $page
            , "isXHR"   => Request::isAjax()
        ), "Dump", "page");
    }
}