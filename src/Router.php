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

use ff\libs\util\ServerManager;
use ff\libs\util\TypesConverter;

/**
 * Class Router
 * @package ff\libs
 */
class Router implements Configurable, Dumpable
{
    use TypesConverter;
    use ServerManager;

    const PRIORITY_TOP 			                            = 0;
    const PRIORITY_VERY_HIGH	                            = 1;
    const PRIORITY_HIGH			                            = 2;
    const PRIORITY_NORMAL 		                            = 3;
    const PRIORITY_LOW			                            = 4;
    const PRIORITY_VERY_LOW		                            = 5;
    const PRIORITY_BOTTOM 		                            = 6;
    const PRIORITY_DEFAULT 		                            = Router::PRIORITY_NORMAL;

    public const CALLER_CLASS                               = 'ff\\libs\\gui\\Controller';
    public const CALLER_METHOD                              = "display";

    private const ERROR_RESPONSE_NO_INSTANCEOF              = "Response must be an instance of dto\DataAdapter";
    private const ERROR_RESPONSE_IS_NULL                    = "Controller not Implemented";

    private const HOOK_ON_AFTER_RUN                         = "App::afterRun";

    private static $cache                                   = array();

    private static $routes                                  = null;
    private static $rules                                   = null;

    private static $sorted                                  = false;
    private static $target                                  = null;

    private static $script_engine                           = null;

    /**
     * @access private
     * @param dto\ConfigRules $configRules
     * @return dto\ConfigRules
     */
    public static function loadConfigRules(dto\ConfigRules $configRules) : dto\ConfigRules
    {
        return $configRules
            ->add("router");
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig(array $config)
    {
        self::$routes                                       = $config["routes"];
        self::$rules                                        = $config["rules"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema(array $rawdata) : array
    {
        if (!empty($rawdata)) {
            $schema                                         = [];
            if (isset($rawdata["rule"])) {
                $rules                                      = $rawdata["rule"];
                foreach ($rules as $rule) {
                    $attr                                   = Dir::getXmlAttr($rule);
                    $key                                    = $attr["path"] ?: $attr["source"];
                    $schema[$key]                           = $attr;
                }
            }

            if (isset($rawdata["pages"])) {
                $schema                                     = array_replace($rawdata["pages"], $schema);
            }

            self::addRules($schema);
        }

        return array(
            "routes"    => self::$routes,
            "rules"     => self::$rules
        );
    }

    /**
     * @return array
     */
    public static function dump() : array
    {
        return array(
            "routes"    => self::$routes,
            "rules"     => self::$rules
        );
    }

    /**
     * @return string
     */
    public static function getRunner() : ?string
    {
        return self::$script_engine;
    }

    /**
     * @param string $what
     */
    public static function setRunner(string $what) : void
    {
        self::$script_engine                                        = ucfirst(basename(str_replace('\\', '/', $what)));
    }

    /**
     * @param string $path
     * @return array|null
     */
    public static function find(string $path) : ?array
    {
        $target                                             = $path;
        if (!isset(self::$cache[$target])) {
            self::$target                                   = $target;
            self::$cache[$target]                           = self::process($path);
        }

        return self::$cache[$target];
    }

    /**
     * @param string|null $path
     */
    public static function run(string $path = null) : void
    {
        if ($rule = self::find($path)) {
            $destination                                    = $rule["destination"];

            if (is_array($destination)) {
                Response::send(self::caller($destination["obj"], $destination["method"], self::replaceMatches($rule["matches"], $destination["params"] ?? [])));
            } elseif ($rule["redirect"]) {
                Response::redirect(self::replaceMatches($rule["matches"], $destination), $rule["redirect"]);
            } elseif (is_numeric($destination) || ctype_digit($destination)) {
                Response::sendError($destination);
            }
        }

        Hook::handle(self::HOOK_ON_AFTER_RUN);

        self::runWebRoot($destination ?? self::pathinfo());

        Response::sendError();
    }

    /**
     * @param string|null $path
     */
    private static function runWebRoot(string $path = null) : void
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
                Response::sendHeaders();
                self::execute($webroot . $file);
            }
        }
    }

    /**
     * Anti injection, prevent error fs
     * @param string $script
     */
    private static function execute(string $script) : void
    {
        try {
            ob_start();
            if (!Autoloader::loadScript($script) && empty(ob_get_length())) {
                Response::sendError("404");
            }
        } catch (\Exception $e) {
            Debug::setBackTrace($e->getTrace());
            Response::sendError($e->getCode(), $e->getMessage());
        }
        exit;
    }

    /**
     * @param string $path
     * @param string $destination
     */
    public static function addRoute(string $path, string $destination) : void
    {
        self::addRule($path, array("route" => $destination));
    }

    /**
     * @param array $rules
     */
    private static function addRules(array $rules) : void
    {
        foreach ($rules as $path => $params) {
            self::addRule($path, $params);
        }
    }

    /**
     * @param string $path
     * @param array|null $params
     * @param int|null $priority
     * @param bool $redirect
     */
    private static function addRule(string $path, array $params = null, int $priority = null, bool $redirect = false) : void
    {
        $rule                           = null;
        $source                         = null;
        $destination                    = null;
        if (is_array($params)) {
            $source                     = $params["source"]         ?? $path;
            $destination                = $params["destination"]    ?? null;

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
                                            "source"        => $source,
                                            "destination"   => $destination,
                                            "redirect"      => $redirect //null or redirect code
                                        );
            }
            if (!self::setRoutes($path, $rule)) {
                $key                    = self::getPriority($priority) . "-" . (9 - substr_count($path, DIRECTORY_SEPARATOR)) . "-" . $path;
                self::$rules[$key]      = $rule;
            }
        }
    }

    /**
     * @param string $source
     * @param array|null $rule
     * @return bool
     */
    private static function setRoutes(string $source, array $rule = null) : bool
    {
        $key = rtrim(rtrim(rtrim(ltrim($source, "^"), "$"), "*"), DIRECTORY_SEPARATOR);
        if (strpos($key, "*") === false && strpos($key, "+") === false && strpos($key, "(") === false && strpos($key, "[") === false) {
            self::$routes[$key ?: DIRECTORY_SEPARATOR] = $rule;
            return true;
        }

        return false;
    }

    /**
     * @param int|null $priority
     * @return int
     */
    private static function getPriority(int $priority = null) : int
    {
        if ($priority === null) {
            $priority = Router::PRIORITY_DEFAULT;
        }

        return (is_numeric($priority)
            ? $priority
            : constant("Router::PRIORITY_" . strtoupper($priority))
        );
    }

    /**
     * @todo da tipizzare
     * @param array $matches
     * @param $in
     * @return array|mixed
     */
    private static function replaceMatches(array $matches, $in)
    {
        foreach ($matches as $key => $match) {
            if (is_array($in)) {
                foreach ($in as $i => $value) {
                    $in[$i]             = str_replace('$' . $key, $match, $value);
                }
            } else {
                $in                     = str_replace('$' . $key, $match, $in);
            }
        }

        return $in;
    }

    /**
     *
     */
    private static function sort()
    {
        if (!self::$sorted) {
            ksort(self::$rules);
            self::$sorted = true;
        }
    }

    /**
     * @param string $path
     * @return array|null
     */
    private static function process(string $path) : ?array
    {
        Debug::stopWatch("router/process");

        $res                                            = null;
        $matches                                        = [];
        $match_path                                     = null;
        $tmp_path                                       = rtrim($path, DIRECTORY_SEPARATOR) ?: DIRECTORY_SEPARATOR;
        do {
            if (isset(self::$routes[$tmp_path])) {
                if (!$match_path) {
                    $match_path                         = $tmp_path;
                }
                if (self::$routes[$tmp_path]) {
                    $res                                = self::$routes[$tmp_path];
                    break;
                }
            }
        } while ($tmp_path != DIRECTORY_SEPARATOR && $tmp_path = dirname($tmp_path));

        if ($res) {
            $res["path"]                                = $match_path;
            $res["matches"]                             = (
            isset($res["source"]) && preg_match(self::regexp($res["source"]), $path, $matches)
                ? $matches
                : []
            );
        } elseif (is_array(self::$rules)) {
            self::sort();
            foreach (self::$rules as $rule) {
                if (preg_match(self::regexp($rule["source"]), $path, $matches)) {
                    $res                                = $rule;
                    $res["path"]                        = $rule["source"];
                    $res["matches"]                     = $matches;
                    break;
                }
            }
        }

        Debug::stopWatch("router/process");

        return $res;
    }

    /**
     * @param string $class_name
     * @param string $method
     * @param array $params
     * @return dto\DataAdapter
     */
    private static function caller(string $class_name, string $method, array $params) : dto\DataAdapter
    {
        $output                                                     = null;
        try {
            if (is_subclass_of($class_name, self::CALLER_CLASS)) {
                if (!empty($method)) {
                    $params[]                                   = $method;
                }
                $method                                         = self::CALLER_METHOD;
            } elseif (empty($params)) {
                $params[]                                   = Kernel::$Page->mapRequest();
            }

            self::setRunner($class_name);
            if ($method && !is_array($method)) {
                $output                                         = (new $class_name())->$method(...$params);
            }
        } catch (\Exception $e) {
            Debug::setBackTrace($e->getTrace());
            Response::sendError($e->getCode(), $e->getMessage());
        }

        if (!$output) {
            Response::sendError(501, self::ERROR_RESPONSE_IS_NULL);
        } elseif (!($output instanceof dto\DataAdapter)) {
            Response::sendError(500, self::ERROR_RESPONSE_NO_INSTANCEOF);
        }
        return $output;
    }
}
