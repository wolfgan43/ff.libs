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

use Exception;
use phpformsframework\libs\cache\Mem;

class Router implements Configurable
{
    const ERROR_BUCKET                                      = "routing";

    const PRIORITY_TOP 			                            = 0;
    const PRIORITY_VERY_HIGH	                            = 1;
    const PRIORITY_HIGH			                            = 2;
    const PRIORITY_NORMAL 		                            = 3;
    const PRIORITY_LOW			                            = 4;
    const PRIORITY_VERY_LOW		                            = 5;
    const PRIORITY_BOTTOM 		                            = 6;
    const PRIORITY_DEFAULT 		                            = Router::PRIORITY_NORMAL;

    private static $cache                                   = array();

    private $alias                                          = null;
    private $rules                                          = null;

    private $sorted                                         = false;

    public static function regexp($rule)
    {
        return "#" . (
            strpos($rule, "[") === false && strpos($rule, "^") === false && strpos($rule, "$") === false && strpos($rule, "(") === false
                ? str_replace("*", "(.*)", $rule)
                : $rule
            ) . "#i";
    }
    public static function loadSchema()
    {
        $config                                             = Config::rawData(Config::SCHEMA_ROUTER, true, "rule");
        if (is_array($config) && count($config)) {
            $schema                                         = array();
            foreach ($config as $rule) {
                $attr                                       = Dir::getXmlAttr($rule);
                $key                                        = (
                    $attr["path"]
                                                                ? $attr["path"]
                                                                : $attr["source"]
                                                            );
                $schema[$key]                               = $attr;
            }
            Config::setSchema($schema, Config::SCHEMA_ROUTER);
        }
    }

    public function __construct()
    {
        $cache = Mem::getInstance("router");
        $res = $cache->get("rawdata");

        if (!$res) {
            $this->addRules(Config::getSchema(Config::SCHEMA_ROUTER));

            $cache->set("rawdata", array(
                "rules" => $this->rules,
                "alias" => $this->alias
            ));
        } else {
            $this->rules = $res["rules"];
            $this->alias = $res["alias"];
        }
    }

    public function check($path, $source = null)
    {
        Debug::stopWatch("router/check");

        if (!isset(self::$cache[$path . ":" . $source])) {
            self::$cache[$path . ":" . $source] = (
                $source
                ? preg_match($this->regexp($source), $path)
                : $this->find($path)
            );
        }

        Debug::stopWatch("router/check");

        return self::$cache[$path . ":" . $source];
    }
    public function run($path = null)
    {
        $rule                                               = $this->check($path);

        if (is_array($rule)) {
            $destination                                    = $rule["destination"];
            if ($destination) {
                if (is_array($destination)) {
                    Response::send(self::caller($destination["obj"], $destination["method"], $this->replaceMatches($rule["matches"], $destination["params"])));
                } elseif ($rule["redirect"]) {
                    Response::redirect($this->replaceMatches($rule["matches"], $destination), $rule["redirect"]);
                } elseif (is_numeric($destination) || ctype_digit($destination)) {
                    Error::send($destination);
                } else {
                    $this->execute($destination . $path);
                }
            }
        } elseif ($rule) {
            $this->execute(Constant::DISK_PATH . $rule . $path);
        } else {
            $this->runWebRoot($path);
        }

        if (Constant::DEBUG) {
            Response::code(404);
            Debug::dump("Page Not Found!");
        }

        Error::send(404);
    }

    private function runWebRoot($path)
    {
        $webroot = Config::webRoot();
        if ($webroot) {
            $file = null;
            $arrPath = pathinfo($path);
            if (!isset($arrPath["extension"])) {
                if ($path == DIRECTORY_SEPARATOR && is_file($webroot . DIRECTORY_SEPARATOR . "index." . Constant::PHP_EXT)) {
                    $file = DIRECTORY_SEPARATOR . "index." . Constant::PHP_EXT;
                } elseif (is_file($webroot . $path . DIRECTORY_SEPARATOR . "index." . Constant::PHP_EXT)) {
                    $file = $path . DIRECTORY_SEPARATOR . "index." . Constant::PHP_EXT;
                } elseif (is_file($webroot . $path . "." . Constant::PHP_EXT)) {
                    $file = $path . "." . Constant::PHP_EXT;
                }
            }

            if ($file) {
                $this->execute($webroot . $file);
            }
        }
    }
    public function addRules($rules)
    {
        if (is_array($rules) && count($rules)) {
            foreach ($rules as $path => $params) {
                $this->addRule($path, $params);
            }
        }
    }

    public function addRule($path, $params, $priority = null, $redirect = false)
    {
        $rule                           = false;
        $source                         = null;
        $destination                    = null;
        if (is_array($params)) {
            $source                     = (
                isset($params["source"])
                                            ? $params["source"]
                                            : $path
                                        );
            $destination                = (
                isset($params["destination"])
                                            ? $params["destination"]
                                            : null
                                        );

            if (!$priority && isset($params["priority"])) {
                $priority               = $params["priority"];
            }
            if (!$redirect && isset($params["redirect"])) {
                $redirect               = $params["redirect"];
            }
        }

        if ($path) {
            if ($destination || $redirect) {
                $rule                   = array(
                                            "source"        => $source
                                            , "destination" => $destination
                                            , "redirect"    => $redirect //null or redirect code
                                        );
            }

            if (!$this->setAlias($path, $rule) && $rule) {
                $this->sorted           = false;
                $key                    = $this->getPriority($priority) . "-" . (9 - substr_count($path, DIRECTORY_SEPARATOR)) . "-" . $source;
                $this->rules[$key]      = $rule;
            }
        }
    }

    private function execute($script)
    {
        /*
         * Anti injection, prevent error fs
         */
        if (!Dir::autoload($script)) {
            Error::run(Request::pathinfo());
        }
        exit;
    }

    private function setAlias($source, $rule)
    {
        $key = rtrim(rtrim(rtrim(ltrim($source, "^"), "$"), "*"), DIRECTORY_SEPARATOR);
        if (strpos($key, "*") === false && strpos($key, "+") === false && strpos($key, "(") === false && strpos($key, "[") === false) {
            $this->alias[$key] = $rule;
            return true;
        }

        return null;
    }

    private function getPriority($priority = null)
    {
        if ($priority === null) {
            $priority = Router::PRIORITY_DEFAULT;
        }

        return (is_numeric($priority)
            ? $priority
            : constant("Router::PRIORITY_" . strtoupper($priority))
        );
    }
    private function replaceMatches($matches, $in)
    {
        if (is_array($matches)) {
            foreach ($matches as $key => $match) {
                if (is_array($in)) {
                    foreach ($in as $i => $value) {
                        $in[$i]         = str_replace('$' . $key, $match, $value);
                    }
                } else {
                    $in                 = str_replace('$' . $key, $match, $in);
                }
            }
        }

        return $in;
    }

    private function sort()
    {
        if (!$this->sorted) {
            ksort($this->rules);
            $this->sorted = true;
        }
    }
    private function find($path)
    {
        Debug::stopWatch("router/find");

        $res                                            = null;
        $matches                                        = array();
        $match_path                                     = null;
        $tmp_path                                       = rtrim($path, DIRECTORY_SEPARATOR);
        if ($tmp_path) {
            do {
                if (isset($this->alias[$tmp_path])) {
                    if (!$match_path) {
                        $match_path                     = $tmp_path;
                    }
                    if ($this->alias[$tmp_path]) {
                        $res                            = $this->alias[$tmp_path];
                        break;
                    }
                }
                $tmp_path                               = dirname($tmp_path);
            } while ($tmp_path != DIRECTORY_SEPARATOR);
        }

        if ($res) {
            $res["path"]                                = $match_path;
            if (isset($res["source"]) && preg_match($this->regexp($res["source"]), $path, $matches)) {
                $res["matches"]                         = $matches;
            }
        } elseif (is_array($this->rules)) {
            $this->sort();
            foreach ($this->rules as $rule) {
                if (preg_match($this->regexp($rule["source"]), $path, $matches)) {
                    $res                                = $rule;
                    $res["path"]                        = $rule["source"];
                    $res["matches"]                     = $matches;
                    break;
                }
            }
        }

        Debug::stopWatch("router/find");
        return $res;
    }



    public static function caller($class_name, $method, $params)
    {
        $output                                                     = null;
        if ($class_name) {
            try {
                App::setRunner($class_name);

                if ($method && !is_array($method)) {
                    $obj                                            = new $class_name();
                    $output                                         = call_user_func_array(array(new $obj, $method), $params);
                }
            } catch (Exception $exception) {
                Error::register($exception->getMessage(), static::ERROR_BUCKET);
            }
        } elseif (is_callable($method)) {
            $output                                                 = call_user_func_array($method, $params);
        }
        return $output;
    }

}
