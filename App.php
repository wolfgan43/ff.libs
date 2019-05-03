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

abstract class App extends DirStruct {
    const NAME_SPACE                                                = 'phpformsframework\\libs\\';
    const DEBUG                                                     = true; //Debug::ACTIVE;

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

    public static function caller($class_name, $method, $params) {
        $output = null;
        if($class_name) {
            try {
                self::setRunner($class_name);

                //$obj = new $class_name();
                //$output = $obj->$method($params[0]);
                if(is_callable($class_name . "::" . $method)) {
                    $output                                         = call_user_func_array($class_name . "::" . $method, $params);
                } else {
                    $obj                                            = new $class_name();
                    $output                                         = call_user_func_array(array(new $obj, $method), $params);
                }
                //todo: da verificare benchmark

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
        self::$script_engine                                        = self::getClassName($what);
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
    protected static function getClassName($class_name = null) {
        $res = null;
        try {
            $reflector                                              = new \ReflectionClass($class_name);
            $res                                                    = $reflector->getShortName();
        } catch (\Exception $exception) {

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

    /**
     * @return App
     */
    private static function getCalledClass() {
        return get_called_class();
    }
    public static function widget($name, $config = null, $user_path = null) {
        $schema                         = self::getSchema("widgets");
        $class_name                     = self::getCalledClass();
        if(!$user_path)                 { $user_path = self::getPathInfo(); }

        if(isset($schema[$user_path]) && is_array($schema[$user_path])) {
            $config                     = array_replace_recursive($config, $schema[$user_path]);
        } elseif(isset($schema[$name]) && is_array($schema[$name])) {
            $config                     = array_replace_recursive($config, $schema[$name]);
        }

        Log::registerProcedure($class_name, "widget:" . $name);

        return Widget::getInstance($name, $class_name::NAME_SPACE)
            ->setConfig($config)
            ->process("page");
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





