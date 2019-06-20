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
use ReflectionClass;
use Exception;

abstract class App extends DirStruct  {
    const NAME_SPACE                                                = 'phpformsframework\\libs\\';
    const ERROR_BUCKET                                              = 'exception';
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

    public static function caller($class_name, $method, $params) {
        $output                                                     = null;
        if($class_name) {
            try {
                self::setRunner($class_name);

                if($method && !is_array($method)) {
                    $obj                                            = new $class_name();
                    $output                                         = call_user_func_array(array(new $obj, $method), $params);
                }
            } catch (Exception $exception) {
                Error::register($exception->getMessage(), self::ERROR_BUCKET);
            }
        } else if(is_callable($method)) {
            $output                                                 = call_user_func_array($method, $params);
        }
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
            $reflector                                              = new ReflectionClass($class_name);
            $res                                                    = $reflector->getShortName();
        } catch (Exception $exception) {
            Error::register($exception->getMessage(), self::ERROR_BUCKET);
        }
        return $res;
    }
    protected static function getClassPath($class_name = null) {
        $res = null;
        try {
            $reflector = new ReflectionClass(($class_name ? $class_name : get_called_class()));
            $res = dirname($reflector->getFileName());
        } catch (Exception $exception) {
            Error::register($exception->getMessage(), self::ERROR_BUCKET);
        }
        return $res;
    }

    /**
     * @deprecated
     * @param $class_name
     * @param $method_name
     * @return bool|null
     */
    protected static function isStatic($class_name, $method_name) {
        $res = null;
        try {
            $reflector = new ReflectionClass($class_name);
            $method = $reflector->getMethod($method_name);
            $res = $method->isStatic();
        } catch (Exception $exception) {
            Error::register($exception->getMessage(), self::ERROR_BUCKET);
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
    public static function widget($name, $config = null, $return = null) {
        $schema                         = self::getSchema("widgets");
        $class_name                     = self::getCalledClass();
        $user_path                      = self::getPathInfo();

        if(isset($schema[$user_path]) && is_array($schema[$user_path])) {
            $config                     = array_replace_recursive($config, $schema[$user_path]);
        } elseif(isset($schema[$name]) && is_array($schema[$name])) {
            $config                     = array_replace_recursive($config, $schema[$name]);
        }

        Log::registerProcedure($class_name, "widget:" . $name);

        return Widget::getInstance($name, $class_name::NAME_SPACE)
            ->setConfig($config)
            ->process($return);
    }
    public static function page($name, $config = null) {
        return self::widget($name, $config, "page");
    }
}





