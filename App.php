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

use phpformsframework\libs\tpl\Widget;

if(!defined("DOCUMENT_ROOT"))                               { define("DOCUMENT_ROOT", $_SERVER["DOCUMENT_ROOT"]); }
if(!defined("SITE_PATH"))                                   { define("SITE_PATH", str_replace(array(DOCUMENT_ROOT, "/vendor/phpformsframework/libs"), "", __DIR__)); }
if(!defined("CONF_PATH"))                                   { define("CONF_PATH", "/conf"); }
if(!defined("LIBS_PATH"))                                   { define("LIBS_PATH", "/vendor"); }

abstract class App extends DirStruct {
    const DEBUG                                                     = Debug::ACTIVE;

    protected static $script_engine                                 = null;

    public static function env($name = null, $value = null) {
        return Env::get($name, $value);
    }

    public static function isXHR() {
        return Request::isAjax();
    }

    protected static function hook($name, $func, $priority = null) {
        Hook::register($name, $func, $priority);
    }
    protected static function doHook($name, &$ref = null, $params = null) {
        return Hook::handle($name, $ref, $params);
    }

    public static function caller($obj, $method, $params) {
        $output = null;
        if($obj) {
            try {
                self::setRunner($obj);
                $output                                             = call_user_func_array(array(new $obj, $method), $params);
                /*if(!$output) { // todo: da finire
                    $page = Cms::getInstance("page");
                    $page->addContent($output);
                    $page->run();
                    exit;
                }*/
            } catch (\Exception $exception) {
                Error::send(503);
            }
        } else if(is_callable($method)) {
            $output                                                 = call_user_func_array($method, $params);
            /*if(!$output) {
                exit;
            }*/
        }/* elseif(class_exists($method)) { //todo:: da finire
            try {
                $class                                              = new \ReflectionClass($method);
                $instance = $class->newInstanceArgs($params);
            } catch (\exception $exception) {

            }
        }*/

        return $output;
    }
    public static function setRunner($what) {
        self::$script_engine                                        = ucfirst($what);

        return null;
    }
    public static function isRunnedAs($what) {
        if(self::$script_engine) {
            $res                                                    = self::$script_engine == ucfirst($what);
        } else {
            $path                                                   = self::getDiskPath($what, true);
            $res                                                    = self::getPathInfo($path);
        }
        return $res;
    }
    protected static function getClassPath($class_name = null) {
        $res = null;
        try {
            $reflector = new \ReflectionClass(($class_name ? $class_name : get_called_class()));
            $res = dirname($reflector->getFileName());
        } catch (\Exception $exception) {

        }
        return $res;
    }

    protected static function getSchema($bucket) {
        return Config::getSchema($bucket);
    }

    public static function widget($name, $config = null, $user_path = null) {
        $schema                         = self::getSchema("widgets");
        $class_name                     = get_called_class();

        if(!$user_path)                 { $user_path = self::getPathInfo(); }

        if(is_array($schema[$user_path])) {
            $config                     = array_replace_recursive($config, $schema[$user_path]);
        } elseif(is_array($schema[$name])) {
            $config                     = array_replace_recursive($config, $schema[$name]);
        }

        Log::registerProcedure($class_name, "widget:" . $name);

        return Widget::getInstance($name, $class_name)
            ->setConfig($config)->process();
    }
/*
    public static function getSchema($key = null) {
        return ($key
            ? (is_callable($key)
                ? $key(Kernel::config())
                : self::$schema[$key]
            )
            : self::$schema
        );
    }*/
}



class Env extends DirStruct {
    private static $env                                             = array();
    private static $packages                                        = null;

    public static function get($key = null, $value = null) {
        if($key) {
            $ref                                                    = &self::$env[$key];
        } else {
            $ref                                                    = &self::$env;
        }
        if($value !== null) {
            $ref                                                    = $value;
        }

        return $ref;
    }

    public static function getPackage($key = null, $path = null) {
        if(!$path)                                                  { $path = self::getDiskPath("packages", true); }
        if(!self::$packages && $key === null) {
            $fs                                                     = Filemanager::getInstance("xml");
            $packages                                               = new \DirectoryIterator(self::$disk_path . $path);

            foreach ($packages as $package) {
                if ($package->isDot())                              { continue; }

                $name                                               = $package->getBasename(".xml");
                $xml                                                = $fs->read($package->getPathname());
                self::loadSchema($name, $xml);
            }
        } elseif($key && self::$packages[$key] === null) {
            self::$packages[$key]                                   = false;
            if(is_file(self::$disk_path . $path . "/" . $key . ".xml")) {
                $xml                                                = Filemanager::getInstance("xml")->read(self::$disk_path . $path . "/" . $key . ".xml");
                self::loadSchema($key, $xml);
            }
        }

        return ($key
            ? self::$env["packages"][$key]
            : self::$packages
        );
    }

    public static function loadSchema($name = null, $data = null) {
        $config                                                     = ($data
                                                                        ? $data
                                                                        : Config::rawData("env", true)
                                                                    );
        if(is_array($config) && count($config)) {
            foreach ($config as $key => $value) {
                self::$packages[$name][$key]                        = self::getXmlAttr($value);

                self::$env[$key]                                    = self::$packages[$name][$key]["value"];
            }
        }
    }
}

abstract class DirStruct {
    const PHP_EXT                                                   = "php";
    const SITE_PATH                                                 = SITE_PATH;

    public static $disk_path                                        = DOCUMENT_ROOT;
    protected static $dirstruct                                     = array(
                                                                        "libs"              => array(
                                                                            "path"          => LIBS_PATH
                                                                            , "permission"  => ""
                                                                        )
                                                                        , "conf"            => array(
                                                                            "path"          => CONF_PATH
                                                                            , "permission"  => ""
                                                                        )
                                                                    );

    public static function getDiskPath($what = null, $relative = false) {
        $path                                                       = (isset(self::$dirstruct[$what])
                                                                        ? self::$dirstruct[$what]["path"]
                                                                        : $what
                                                                    );
		return ($relative
            ? $path
            : realpath(self::documentRoot() . $path)
        );
	}
	public static function checkDiskPath($abs_path) {
        return strpos(realpath($abs_path), self::$disk_path) === 0;
    }
    public static function getPathInfo($user_path = null) {
        $path_info                                                  = $_SERVER["PATH_INFO"];
        return ($user_path
            ? (strpos($path_info, $user_path) === 0
                ? substr($path_info, strlen($user_path))
                : false
            )
            : $path_info
        );
    }

    //todo: da verificare
    protected function loadDirStruct($autoload = false) {
        $config                                                     = Config::rawData("dirstruct", true);
        if(is_array($config) && count($config)) {
            foreach ($config AS $dir_key => $dir) {
                $dir_attr                                           = self::getXmlAttr($dir);
                self::$dirstruct[$dir_key]                          = $dir_attr;
                if($autoload && self::$dirstruct[$dir_key]["autoload"]) {
                    self::autoload(self::getDiskPath($dir_key) . "/autoload." . self::PHP_EXT, true);
                }
            }
        }
    }

    /**
     * @param $path
     * @param bool $once
     * @return bool|mixed
     */
    protected static function autoload($path, $once = false) {
        $rc                                                         = false;
        if(self::checkDiskPath($path)) {
            $rc                                                     = ($once
                                                                        ? require_once ($path)
                                                                        : include($path)
                                                                    );
        }

        return $rc;
    }
    protected static function documentRoot() {
        return self::$disk_path;
    }

    public static function getXmlAttr($item, $key = null) {
        $res = ($item["@attributes"]
            ? $item["@attributes"]
            : $item
        );

        return ($key
            ? $res[$key]
            : $res
        );
    }
}

class Hook {
    const EVENT_PRIORITY_HIGH                                       = 1000;
    const EVENT_PRIORITY_NORMAL                                     = 100;
    const EVENT_PRIORITY_LOW                                        = 10;

    private static $events                                          = null;

    /**
     * AddEvent
     * @param $name
     * @param $func
     * @param int $priority
     */
    public static function register($name, $func, $priority = self::EVENT_PRIORITY_NORMAL) {
        if(is_callable($func)) {
            Debug::dumpCaller("addEvent::" . $name);
            self::$events[$name][$priority + count((array)self::$events[$name])]    = $func;
        }
    }

    /**
     * DoEvent
     * @param $name
     * @param null|$ref
     * @param null $params
     * @return array|null
     */
    public static function handle($name, &$ref = null, $params = null) {
        $res                                                        = null;
        if(is_array(self::$events[$name])) {
            krsort(self::$events[$name], SORT_NUMERIC);
            foreach(self::$events[$name] AS $func) {
                $res[]                                              = $func($ref, $params);
            }
        }

        return $res;
    }
}