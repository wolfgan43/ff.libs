<?php

if(!defined("CMS_START"))               define("CMS_START", 0);

class Debug extends vgCommon
{
    const STOPWATCH                         = CMS_START;

    private static $microtime               = STOPWATCH;

    private static $debug                      = array();

    /**
     * @param null $start
     * @return mixed|string
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

        self::$microtime                = STOPWATCH;
        return number_format($duration, 2, '.', '');
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
                    Logs::write($error, "notice");
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

                    if(function_exists("cache_sem_remove"))
                        cache_sem_remove($_SERVER["PATH_INFO"]);

                    break;
                default:
            }
        });



        //exit;
    }
    public static function dumpLog($filename, $data = null) {
        $debug_backtrace = debug_backtrace();

        foreach($debug_backtrace AS $i => $value) {
            if ($i) {
                if (basename($value["file"]) == "vgCommon.php") {
                    continue;
                }
                if (basename($value["file"]) == "cm.php") {
                    break;
                }

                $trace = $value;
            }
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

        $data["source"] = $trace;
        Logs::write($data, $filename);
    }

    public static function dumpCaller($note = null, $backtrace = null) {
        $disk_path                          = (defined("FF_DISK_PATH")
                                                ? FF_DISK_PATH
                                                : str_replace(vgCommon::BASE_PATH, "", __DIR__)
                                            );
        $debug_backtrace                    = (is_array($backtrace)
                                                ? $backtrace
                                                : debug_backtrace()
                                            );
        foreach($debug_backtrace AS $i => $trace) {
            if($i) {
                if (basename($trace["file"]) == "vgCommon.php") {
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

    public static function dump($backtrace = null) {
        $html                               = "";
        $disk_path                          = (defined("FF_DISK_PATH")
            ? FF_DISK_PATH
            : str_replace(vgCommon::BASE_PATH, "", __DIR__)
        );

        $debug_backtrace                    = (is_array($backtrace)
                                                ? $backtrace
                                                : debug_backtrace()
                                            );

        foreach($debug_backtrace AS $i => $trace) {
            if($i) {
                if(basename($trace["file"]) == "vgCommon.php") {
                    continue;
                }
                if(basename($trace["file"]) == "cm.php") {
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
                    if($trace["file"]) {
                        $label = 'Line in: ' . '<b>' . str_replace($disk_path, "", $trace["file"])  . '</b>';
                        $list_start = '<ol start="' . $trace["line"] . '">';
                        $list_end = '</ol>';
                    } else {
                        $label = 'Func: ' . '<b>' .  $trace["function"] . '</b>';
                        $list_start = '<ul>';
                        $list_end = '</ul>';

                    }

                    $html .=  $list_start . '<li><a style="text-decoration: none;" href="javascript:void(0);" onclick="this.nextSibling.style = \'display:none\';">' . $label . '</a><pre>' . print_r($trace, true). '</pre></li>' . $list_end;
                }
                $res[] = $trace;
            }
        }

        if(is_string($backtrace) && $backtrace) echo "<b>" . $backtrace . "</b>";
        echo "<hr />";
        echo "<center>BACKTRACE</center>";
        echo "<hr />";
        echo $html;
    }

    /**
     * @param bool $end
     * @param bool $isXHR
     * @return mixed
     */
    public static function benchmark($end = false) {
        static $res;

        if(function_exists("getrusage"))
        {
            $ru = getrusage();
            $isXHR = ($_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest"
                ? true
                : false
            );
            if ($end) {
                $res["mem"] 			= number_format(memory_get_usage(true) - $res["mem"], 0, ',', '.');
                $res["mem_peak"] 		= number_format(memory_get_peak_usage(true) - $res["mem_peak"], 0, ',', '.');
                $res["cpu"] 			= number_format(abs(($ru['ru_utime.tv_usec'] + $ru['ru_stime.tv_usec']) - $res["cpu"]), 0, ',', '.');
                $res["includes"] 		= get_included_files();
                $res["classes"] 		= get_declared_classes();
                $res["db"] 				= Storage::$cache;
                $res["exTime"] 			= microtime(true) - $res["exTime"];

                if (extension_loaded('xhprof') && is_dir(FF_DISK_PATH . "/xhprof_lib") && class_exists("XHProfRuns_Default")) {
                    $path_info = ($_SERVER["PATH_INFO"] == "/"
                        ? "Home"
                        : $_SERVER["PATH_INFO"]
                    );

                    $xhr_path_info = ($_SERVER["XHR_PATH_INFO"] == "/"
                        ? "Home"
                        : $_SERVER["XHR_PATH_INFO"]
                    );
                    $profiler_namespace = str_replace(array(".", "&", "?", "__nocache__"), array(",", "", "", ""), "[" . round($res["exTime"], 2) . "s] "
                        . str_replace("/", "_", trim($path_info, "/"))
                        . ($isXHR
                            ? ($xhr_path_info != $path_info && $xhr_path_info
                                ? " (" . str_replace("/", "_", trim($xhr_path_info, "/")) . ")"
                                : ""
                            ) . " - Request"
                            : ""
                        ))
                        . ($end !== true
                            ? " - " . $end
                            : ""
                        );

                    $xhprof_data = xhprof_disable();
                    $xhprof_runs = new XHProfRuns_Default();
                    $run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
                    $profiler_url = sprintf('https://www.paginemediche.info/xhprof_html/index.php?run=%s&source=%s', $run_id, $profiler_namespace);

                    //  printf('nbsp;<a href="%s" target="_blank">Profiler output</a><br>', $profiler_url);
                }

                Logs::write("URL: " . $_SERVER["REQUEST_URI"] . " (" . $end . ") Benchmark: " . print_r($res, true) . "Profiler: " . $profiler_url, "benchmark" .  ($isXHR ? "_xhr" : ""));
                return $res;
            } else {
                $res["mem"]             = memory_get_usage(true);
                $res["mem_peak"]        = memory_get_peak_usage(true);
                $res["cpu"]             = $ru['ru_utime.tv_usec'] + $ru['ru_stime.tv_usec'];
                $res["exTime"] 			= microtime(true);

                if (extension_loaded('xhprof') && is_dir(FF_DISK_PATH . "/xhprof_lib")) {
                    include_once FF_DISK_PATH . '/xhprof_lib/utils/xhprof_lib.php';
                    include_once FF_DISK_PATH . '/xhprof_lib/utils/xhprof_runs.php';

                    xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
                }
            }
        }

    }
    public static function page($page) {
        if(self::$debug) {
            Logs::write(array(
                  "\n*********************************************\n"
                . print_r(
                      (array) $page
                    + array("isXHR" => Cms::isXHR())
                    , true)
                . "*********************************************"
                )
                + self::$debug
            , "benchmark_page"
            , true);
        }

    }
}