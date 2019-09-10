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
use ReflectionClass;
use Exception;

class Debug
{
    const ERROR_BUCKET                      = "exception";

    private static $app_start               = null;

    private static $startWatch              = array();
    private static $exTime                  = array();

    private static $debug                   = array();

    public function __construct()
    {
        self::$app_start                   = microtime(true);

        Log::extend("profiling", Log::TYPE_DEBUG, array(
            "bucket"        => "profiling",
            "write_if"    => Kernel::$Environment::PROFILING,
            "override"    => true,
            "format"      => Log::FORMAT_CLE,
        ));

        if (self::isEnabled()) {
            error_reporting(E_ALL);
            ini_set('display_errors', "On");

            $_SERVER["HTTPS"]               = "on";


            register_shutdown_function(function () {
                $time                       = self::exTimeApp();
                if ($time > 10000) {
                    Log::error("Timeout: " . $time);
                }
            });

            /**
             * Performance Profiling
             */
            if (Kernel::$Environment::PROFILING) {
                self::benchmark();
            }
        }
    }

    public static function isEnabled()
    {
        return Kernel::$Environment::DEBUG;
    }
    /**
     * @param $bucket
     * @return float|null
     */
    public static function stopWatch($bucket)
    {
        if (isset(self::$exTime[$bucket])) {
            $bucket                         = $bucket . "-" . (count(self::$exTime) + 1);
        }

        if (isset(self::$startWatch[$bucket])) {
            self::$exTime[$bucket]          = number_format(microtime(true) - self::$startWatch[$bucket], 4, '.', '');

            return (float) self::$exTime[$bucket];
        } else {
            self::$startWatch[$bucket]      = microtime(true);
            return null;
        }
    }

    public static function exTime($bucket)
    {
        return (isset(self::$startWatch[$bucket])
            ? number_format(self::$startWatch[$bucket], 3, '.', '')
            : null
        );
    }

    public static function exTimeApp()
    {
        $duration                           = microtime(true) - self::$app_start;
        return number_format($duration, 3, '.', '');
    }

    public static function registerErrors()
    {
        declare(ticks=1);

        register_tick_function(function () {
            $GLOBALS["backtrace"]           = debug_backtrace();
        });

        register_shutdown_function(function () {
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

                    // no break
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
                    if (function_exists("cache_sem_remove")) {
                        cache_sem_remove($_SERVER["PATH_INFO"]);
                    }
                    break;
                default:
            }
        });
    }
    public static function dumpLog($filename, $data = null)
    {
        $trace                                  = self::get_backtrace();

        $data["source"]                         = $trace;
        Log::write($data, $filename);
    }

    public static function dumpCaller($note = null, $backtrace = null)
    {
        if (Kernel::$Environment::PROFILING) {
            $debug_backtrace                    = (
                is_array($backtrace)
                                                    ? $backtrace
                                                    : debug_backtrace()
                                                );
            foreach ($debug_backtrace as $i => $trace) {
                if ($i) {
                    if (basename($trace["file"]) == "Debug.php") {
                        continue;
                    }
                    if (basename($trace["file"]) == "cm.php") {
                        break;
                    }

                    if ($trace["file"]) {
                        $res = $trace["line"] . ' Line in: ' . str_replace(Constant::DISK_PATH, "", $trace["file"]);
                    } else {
                        $res = 'Func: ' . $trace["function"];
                    }
                    if ($res) {
                        self::$debug[] = $res . "\n" . str_repeat(" ", 8) . (is_array($note) ? print_r($note, true) : $note);
                        break;
                    }
                }
            }
        }
    }

    private static function get_backtrace($backtrace = null)
    {
        $res                                = null;
        $debug_backtrace                    = (
            is_array($backtrace)
                                                ? $backtrace
                                                : debug_backtrace()
                                            );

        foreach ($debug_backtrace as $i => $trace) {
            if ($i) {
                if (isset($trace["file"]) && basename($trace["file"]) == "vgCommon.php") {
                    continue;
                }
                if (isset($trace["file"]) && basename($trace["file"]) == "cm.php") {
                    break;
                }

                unset($trace["object"]);
                if (is_array($trace["args"]) && count($trace["args"])) {
                    foreach ($trace["args"] as $key => $value) {
                        if (is_object($value)) {
                            $trace["args"][$key] = "Object: " . get_class($value);
                        } elseif (is_array($value)) {
                            foreach ($value as $subkey => $subvalue) {
                                if (is_object($subvalue)) {
                                    $trace["args"][$key][$subkey] = "Object: " . get_class($subvalue);
                                } elseif (is_array($subvalue)) {
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

    private static function dumpInterface()
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
            } catch (Exception $exception) {
                Error::register($exception->getMessage(), static::ERROR_BUCKET);
            }
        }
        return $implements;
    }

    private static function dumpCommandLine($error_message = null)
    {
        $debug_backtrace = array_reverse(self::get_backtrace());

        if (isset($debug_backtrace[0]["file"]) && basename($debug_backtrace[0]["file"]) == "Error.php") {
            unset($debug_backtrace[0]);
        }

        foreach ($debug_backtrace as $i => $trace) {
            if (isset($trace["file"])) {
                print $trace["file"] . ":" . $trace["line"] . "\n";
            } else {
                if (0 && isset($trace["class"]) && isset($debug_backtrace[$i + 1]["args"]) && isset($debug_backtrace[$i + 1]["args"][0])) {
                    $operation = str_replace(array("Object: ", "\\"), array("", "/"), $debug_backtrace[$i + 1]["args"][0]) . $trace["type"] . $trace["function"] . '(' . implode(", ", $trace["args"]) . ')';
                } else {
                    $operation = (
                        isset($trace["class"])
                        ?  basename(str_replace("\\", "/", $trace["class"])) . $trace["type"] . $trace["function"] . '(' . implode(", ", $trace["args"]) . ')'
                        : $trace["function"]
                    );
                }
                echo "Call " . $operation . "\n";
            }
        }

        echo "---------------------------------------------------------------------\n";
        echo $error_message . "\n";

        return null;
    }

    public static function dump($error_message = null, $return = false)
    {
        if (self::isCommandLineInterface() || Request::accept() != "text/html") {
            return self::dumpCommandLine($error_message);
        }

        $html_backtrace                     = "";
        $html_dumpable                      = "";
        $debug_backtrace                    = self::get_backtrace();
        $collapse = (
            Request::isAjax() && Request::method() != "GET"
            ? ''
            : 'display:none;'
        );

        if (isset($debug_backtrace[0]["file"]) && basename($debug_backtrace[0]["file"]) == "Error.php") {
            unset($debug_backtrace[0]);
        }

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
            $html_backtrace .=  $list_start . '<li><a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } ">' . $label . '</a><code style="' . $collapse . '"><pre>' . print_r($trace, true). '</pre></code></li>' . $list_end;
        }

        $dumpable = self::dumpInterface();
        $files_count = 0;
        $db_query_count = 0;
        $db_query_cache_count = 0;
        if (is_array($dumpable) && count($dumpable)) {
            foreach ($dumpable as $interface => $dump) {
                $dump = array_filter($dump);
                if (is_array($dump) && count($dump)) {
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
                        if (strtolower($interface) == "database") {
                            $db_query_count++;
                            if ($value === true) {
                                $db_query_cache_count++;
                            }
                        }
                    }
                    $html_dumpable .= '</ul>';
                }
            }
        }

        if (is_array(self::$exTime) && count(self::$exTime)) {
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

        $errors = array_filter((array) Error::raise());
        $errors_count = 0;
        $dirstruct = Config::getDir();
        if (is_array($dirstruct) && count($dirstruct)) {
            foreach ($dirstruct as $dir) {
                if (isset($dir["path"]) && !is_dir(Constant::DISK_PATH . $dir["path"]) && !Filemanager::makeDir($dir["path"])) {
                    $errors["dirstruct"][] = "Failed to Write " . $dir["path"] . " Check permission";
                } elseif (isset($dir["writable"]) && $dir["writable"] && !is_writable(Constant::DISK_PATH . $dir["path"])) {
                    $errors["dirstruct"][] = "Dir " . $dir["path"] . " is not Writable";
                } elseif (!is_readable(Constant::DISK_PATH . $dir["path"])) {
                    $errors["dirstruct"][] = "Dir " . $dir["path"] . " is not Readible";
                }
            }
        }

        if (is_array($errors) && count($errors)) {
            $html_dumpable .= '<hr />' . '<h5>&nbsp;' . "Errors" . '</h5>';
            $html_dumpable .= '<code><ul>';
            foreach ($errors as $bucket => $error) {
                if (is_array($error) && count($error)) {
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
        if (is_array($included_files) && count($included_files)) {
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
        if (is_array($constants_user) && count($constants_user)) {
            $html_dumpable .= '<hr />' . '<a style="text-decoration: none; white-space: nowrap;" href="javascript:void(0);" onclick=" if(this.nextSibling.style.display) { this.nextSibling.style.display = \'\'; } else { this.nextSibling.style.display = \'none\'; } "><h5>' . "Constants" . " (" . count($constants_user) . ")" . '</h5></a>';
            $html_dumpable .= '<pre style="' . $collapse . '"><ul>';
            foreach ($constants_user as $name => $value) {
                $html_dumpable .= '<li>' . $name . '</li>';
            }

            $html_dumpable .= '</ul></pre>';
        }

        $html   = (
            $error_message
                    ? "<hr /><b>" . $error_message . "</b>"
                    : ""
                );

        $html_benchmark = "";
        if (Kernel::$Environment::PROFILING) {
            $benchmark = self::benchmark(true);
            $html_benchmark = '<span style="padding:15px;">Mem: ' . $benchmark["mem"] . '</span>'
                . '<span style="padding:15px;">MemPeak: ' . $benchmark["mem_peak"] . '</span>'
                . '<span style="padding:15px;">CPU: ' . $benchmark["cpu"] . '</span>';
        }

        $html .= '<hr />' . '<center>'
            . '<span style="padding:15px;">BackTrace: ' . count($debug_backtrace) . '</span>'
            . '<span style="padding:15px;">Errors: ' . $errors_count . '</span>'
            . '<span style="padding:15px;">Includes: ' . $included_files_count . ' (' . $included_files_autoload_count . ' autoloads)' . '</span>'
            . '<span style="padding:15px;">Constants: ' . count($constants_user) . '</span>'
            . '<span style="padding:15px;">Files: ' . $files_count . '</span>'
            . '<span style="padding:15px;">DB Query: ' . $db_query_count . ' (' . $db_query_cache_count . ' cached)'. '</span>'
            . '<span style="padding:15px;">ExTime: ' . self::exTimeApp() . '</span>'
            . $html_benchmark
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

        if ($return) {
            return $html;
        } else {
            echo $html;
            return null;
        }
    }

    private static function convertMem($size)
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

        Debug::stopWatch("debug/benchmark");
        if (function_exists("getrusage")) {
            $ru = getrusage();
            if ($end) {
                $res["mem"] 			= self::convertMem(memory_get_usage());
                $res["mem_peak"] 		= self::convertMem(memory_get_peak_usage());
                $res["cpu"] 			= number_format(abs(($ru['ru_utime.tv_usec'] + $ru['ru_stime.tv_usec']) - $res["cpu"]), 0, ',', '.');

                if (extension_loaded('xhprof') && is_dir(Constant::DISK_PATH . "/xhprof_lib") && class_exists("XHProfRuns_Default")) {
                    $path_info          = (
                        $_SERVER["PATH_INFO"] == DIRECTORY_SEPARATOR
                                            ? "Home"
                                            : $_SERVER["PATH_INFO"]
                                        );

                    $xhr_path_info      = (
                        $_SERVER["XHR_PATH_INFO"] == DIRECTORY_SEPARATOR
                                            ? "Home"
                                            : $_SERVER["XHR_PATH_INFO"]
                                        );
                    $profiler_namespace = str_replace(array(".", "&", "?", "__nocache__"), array(",", "", "", ""), "[" . round($res["exTime"], 2) . "s] "
                        . str_replace(DIRECTORY_SEPARATOR, "_", trim($path_info, DIRECTORY_SEPARATOR))
                        . (
                            $xhr_path_info != $path_info && $xhr_path_info
                            ? " (" . str_replace(DIRECTORY_SEPARATOR, "_", trim($xhr_path_info, DIRECTORY_SEPARATOR)) . ")"
                            : ""
                        )
                        . (
                            Request::isAjax()
                            ? " - Request"
                            : ""
                        ))
                        . (
                            $end !== true
                            ? " - " . $end
                            : ""
                        );

                    $xhprof_data = xhprof_disable();
                    $xhprof_runs = new \XHProfRuns_Default();
                    $run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
                    $res["url"] = sprintf("http" . ($_SERVER["HTTPS"] ? "s" : "") . "://" . $_SERVER["HTTP_HOST"] . '/xhprof_html/index.php?run=%s&source=%s', $run_id, $profiler_namespace);
                }

                Log::write($res, "benchmark", null, (Request::isAjax() ? "xhr" : "page"));

                return $res;
            } else {
                $res["cpu"]             = $ru['ru_utime.tv_usec'] + $ru['ru_stime.tv_usec'];

                if (extension_loaded('xhprof') && is_dir(Constant::DISK_PATH . "/xhprof_lib")) {
                    Dir::autoload(Constant::DISK_PATH . '/xhprof_lib/utils/xhprof_lib.php', true);
                    Dir::autoload(Constant::DISK_PATH . '/xhprof_lib/utils/xhprof_runs.php', true);

                    xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
                }
            }
        }

        Debug::stopWatch("debug/benchmark");
        return null;
    }
    private static function isCommandLineInterface()
    {
        return (php_sapi_name() === 'cli');
    }

    public static function stackTraceOnce()
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

    public static function stackTrace($plainText = false)
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

        return ($plainText
            ? implode(", ", $res)
            : $res
        );
    }

    public static function page($page)
    {
        Log::debugging(array(
            "page"      => $page
            , "isXHR"   => Request::isAjax()
        ), "Dump", "page");
    }
}
