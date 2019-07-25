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

use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\storage\Filemanager;

class Config implements Dumpable
{
    const ERROR_BUCKET                                              = "config";
    const APP_BASE_NAME                                             = "app";
    const LIBS_BASE_NAME                                            = "libs-base";
    const LIBS_NAME                                                 = "libs";
    const CONFIG_PATH                                               = array();

    const SCHEMA_ENV                                                = "env";
    const SCHEMA_LOCALE                                             = "locale";
    const SCHEMA_ROUTER                                             = "router";
    const SCHEMA_REQUEST                                            = "request";
    const SCHEMA_REQUEST_ACCESSCONTROL                              = "request/accesscontrol";
    const SCHEMA_REQUEST_PATTERNS                                   = "request/patterns";
    const SCHEMA_PAGES                                              = "pages";
    const SCHEMA_ENGINE                                             = "engine";
    const SCHEMA_SNIPPET                                            = "snippet";
    const SCHEMA_ALIAS                                              = "alias";
    const SCHEMA_MIRROR                                             = "mirror";
    const SCHEMA_CACHE                                              = "cache";
    const SCHEMA_MODELS                                             = "models";
    const SCHEMA_MODELS_ORM                                         = "models/orm";
    const SCHEMA_MODELSVIEW                                         = "modelsview";
    const SCHEMA_MODELSVIEW_ORM                                     = "modelsview/orm";

    private static $config                                          = null;
    private static $maps                                            = null;
    private static $schema                                          = null;
    private static $engine                                          = null;
    private static $rules                                           = null;
    private static $autoloads                                       = array();
    private static $webroot                                         = null;
    private static $dirstruct                                       = null;
    private static $file_config                                     = null;
    private static $file_maps                                       = null;
    private static $file_scans                                      = null;

    public static function dump()
    {
        return array(
            "config"        => self::$config,
            "schema"        => self::$schema,
            "engine"        => self::$engine,
            "rules"         => self::$rules,
            "maps"          => self::$maps,
            "dirstruct"     => self::$dirstruct,
            "autoloads"     => self::$autoloads,
            "file_config"   => self::$file_config,
            "file_maps"     => self::$file_maps,
            "file_scans"    => self::$file_scans
        );
    }

    public static function loadDirStruct()
    {
        Debug::stopWatch("config/loadDirStruct");

        $config                                                     = self::rawData("dirs", true);
        $scans                                                      = [self::LIBS_BASE_NAME => [], self::LIBS_NAME => [], self::APP_BASE_NAME => []];

        if (is_array($config["dir"]) && count($config["dir"])) {
            foreach ($config["dir"] as $dir) {
                $dir_attr                                           = Dir::getXmlAttr($dir);
                $dir_key                                            = (
                    isset($dir_attr["type"]) && self::APP_BASE_NAME != $dir_attr["type"]
                                                                        ? $dir_attr["type"] . "/"
                                                                        : ""
                                                                    ) . basename($dir_attr["path"]);

                if (isset($dir_attr["scan"])) {
                    $scan_type                                      = self::APP_BASE_NAME;
                    $scan_path                                      = str_replace("[LIBS_PATH]", Constant::LIBS_PATH, $dir_attr["path"]);
                    if ($scan_path != $dir_attr["path"]) {
                        $scan_type                                  = (
                            strpos($scan_path, Constant::LIBS_FF_PATH) === false
                                                                        ? self::LIBS_NAME
                                                                        : self::LIBS_BASE_NAME
                                                                    );
                    }

                    $scans[$scan_type][$scan_path]                  = $dir_attr["scan"];

                    continue;
                }

                if (isset($dir_attr["webroot"])) {
                    self::$webroot                                  = Constant::DISK_PATH . $dir_attr["path"];

                    continue;
                }

                self::$dirstruct[$dir_key]                          = $dir_attr;
                if (isset(self::$dirstruct[$dir_key]["autoload"])) {
                    $autoload_path = realpath(Constant::DISK_PATH . $dir_key);
                    if ($autoload_path) {
                        self::$autoloads[] = $autoload_path . "/autoload." . Constant::PHP_EXT;
                    }
                }
            }

            self::$file_scans = $scans[self::LIBS_BASE_NAME] + $scans[self::LIBS_NAME] + $scans[self::APP_BASE_NAME];
        }

        Debug::stopWatch("config/loadDirStruct");
    }

    public static function webRoot()
    {
        return self::$webroot;
    }
    public static function getDir($name = null)
    {
        return (isset(self::$dirstruct[$name])
            ? self::$dirstruct[$name]["path"]
            : self::$dirstruct
        );
    }
    public static function getScans($rules)
    {
        if (is_array($rules) && count($rules)) {
            $pattens                                                = null;
            foreach (self::$file_scans as $path => $key) {
                if (isset($rules[$key])) {
                    $pattens[$path] = $rules[$key];
                }
            }

            return $pattens;
        }

        return null;
    }
    public static function getAutoloads()
    {
        return self::$autoloads;
    }

    public static function loadMap($bucket, $name = null)
    {
        if (!$name) {
            $name = "default";
        }
        $map_name                                                   = $bucket . "_" . $name;

        if (!self::$file_maps) {
            self::load();
        }

        Debug::stopWatch("config/loadMap/" . $map_name);
        $cache                                                      = Mem::getInstance("maps");
        self::$maps[$bucket][$name]                                 = $cache->get($map_name);
        if (!self::$maps[$bucket][$name]) {
            self::$maps[$bucket][$name]                             = array();

            if (isset(self::$file_maps[$map_name])) {
                self::$maps[$bucket][$name]                         = Filemanager::getInstance("json")->read(self::$file_maps[$map_name]);
            }

            $cache->set($map_name, self::$maps[$bucket][$name]);
        }
        Debug::stopWatch("config/loadMap/". $map_name);
    }

    public static function mapping($bucket, $name = null)
    {
        if (!isset(self::$maps[$bucket][$name])) {
            self::loadMap($bucket, $name);
        }

        $extension                                                  = self::$maps[$bucket];
        if ($name && !isset($extension[$name])) {
            $extension[$name]                                       = null;
        }

        return ($name
            ? $extension[$name]
            : $extension
        );
    }
    public static function addEngine($bucket, $callback)
    {
        self::$engine[$bucket]                                      = $callback;
    }
    public static function addRule($key, $method, $callback = null)
    {
        self::$rules[$key]                                          = array(
                                                                        "method"        => $method
                                                                        , "callback"    => $callback
                                                                    );
    }

    public static function rawData($key = null, $remove = false, $sub_key = null)
    {
        if ($key && !isset(self::$config[$key])) {
            self::$config[$key] = null;
        }

        $res                                                        = (
            $key
                                                                        ? self::$config[$key]
                                                                        : self::$config
                                                                    );
        if ($remove && $key) {
            unset(self::$config[$key]);
        }

        return ($sub_key
            ? $res[$sub_key]
            : $res
        );
    }

    private static function loadRules()
    {
        self::addRule("env", "replace");
        self::addRule("locale", "replace");
        self::addRule("dirs", "mergesub");

        self::addRule("alias", "mergesub", "phpformsframework\libs\Config::loadAlias");
        self::addRule("engine", "replace", "phpformsframework\libs\Config::loadEngine");
        self::addRule("router", "mergesub", "phpformsframework\libs\Config::loadSchema");
        self::addRule("pages", "mergesub", "phpformsframework\libs\Config::loadSchema");
        self::addRule("snippet", "replace", "phpformsframework\libs\Config::loadSnippet");
        self::addRule("mirror", "mergesub", "phpformsframework\libs\Config::loadMirror");
        self::addRule("models", "replace", "phpformsframework\libs\Config::loadModels");
        self::addRule("modelsview", "mergesub", "phpformsframework\libs\Config::loadModelsView");

        self::addRule("request", "mergesub", "phpformsframework\libs\Request::loadSchema");
        self::addRule("patterns", "mergesub", "phpformsframework\libs\Request::loadSchema");

        self::addRule("badpath", "mergesub", "phpformsframework\libs\security\Buckler::loadSchema");
        self::addRule("media", "mergesub", "phpformsframework\libs\storage\Media::loadSchema");
    }

    public static function load($paths = array())
    {
        Debug::stopWatch("config/load");
        $cache                                                      = Mem::getInstance("config");
        $res                                                        = $cache->get("rawdata");
        if (!$res) {
            $paths = array_replace(
                array(
                Constant::CONFIG_FF_DISK_PATH => array("filter" => array("xml", "json"))),
                static::CONFIG_PATH,
                $paths
            );
            self::loadRules();

            Filemanager::scan($paths, function ($file) {
                $pathinfo = pathinfo($file);
                switch ($pathinfo["extension"]) {
                    case "xml":
                        self::$file_config[$file]                   = filemtime($file);

                        self::loadXml($file);
                        break;
                    case "json":
                        self::$file_maps[$pathinfo["filename"]]     = $file;
                        break;
                    default:
                        Error::registerWarning("Config file Extension not supported", static::ERROR_BUCKET);
                }
            });

            self::loadSchema();

            $cache->set("rawdata", array(
                "rules"         => self::$rules,
                "config"        => self::$config,
                "schema"        => self::$schema,
                "engine"        => self::$engine,
                "autoloads"     => self::$autoloads,
                "webroot"       => self::$webroot,
                "dirstruct"     => self::$dirstruct,
                "file_config"   => self::$file_config,
                "file_maps"     => self::$file_maps,
                "file_scans"    => self::$file_scans
            ));
        } else {
            self::$rules                                            = $res["rules"];
            self::$config                                           = $res["config"];
            self::$schema                                           = $res["schema"];
            self::$engine                                           = $res["engine"];
            self::$autoloads                                        = $res["autoloads"];
            self::$webroot                                          = $res["webroot"];
            self::$dirstruct                                        = $res["dirstruct"];
            self::$file_config                                      = $res["file_config"];
            self::$file_maps                                        = $res["file_maps"];
            self::$file_scans                                       = $res["file_scans"];
        }

        /**
         * Load Env by Xml.
         */
        Env::loadSchema();

        /**
         * Load Locale and Lang by Xml.
         */
        Locale::loadSchema();



        Debug::stopWatch("config/load");
    }

    private static function loadXml($file)
    {
        Debug::stopWatch("config/loadXml");

        self::$file_config[$file]                               = filemtime($file);
        $configs                                                = Filemanager::getInstance("xml")->read($file);
        foreach ($configs as $key => $config) {
            if (!isset(self::$config[$key])) {
                self::$config[$key] = array();
            }
            $method                                             = (
                isset(self::$rules[$key]["method"])
                                                                    ? self::$rules[$key]["method"]
                                                                    : null
                                                                );

            switch ($method) {
                case "replace":
                    self::loadXmlReplace($key, $config);
                    break;
                case "merge":
                    self::loadXmlMerge($key, $config);
                    break;
                case "mergesub":
                    self::loadXmlMergeSub($key, $config);
                    break;
                default:
                    self::$config[$key]                         = $config;
            }
        }

        Debug::stopWatch("config/loadXml");
    }
    private static function loadXmlReplace($key, $config)
    {
        self::$config[$key]                                         = array_replace(self::$config[$key], (array)$config);
    }
    private static function loadXmlMerge($key, $config)
    {
        if (is_array($config) && count($config)) {
            if (!isset($config[0])) {
                $config = array($config);
            }
            self::$config[$key]                                     = array_merge(self::$config[$key], $config);
        }
    }
    private static function loadXmlMergeSub($key, $config)
    {
        if (is_array($config) && count($config)) {
            foreach ($config as $sub_key => $sub_config) {
                if (!isset($sub_config[0])) {
                    $sub_config = array($sub_config);
                }
                if (isset(self::$config[$key][$sub_key])) {
                    self::$config[$key][$sub_key]                   = array_merge(self::$config[$key][$sub_key], $sub_config);
                } else {
                    self::$config[$key][$sub_key]                   = $sub_config;
                }
            }
        }
    }

    public static function setSchema($data, $bucket = null)
    {
        if (is_array($data)) {
            if ($bucket) {
                if (isset(self::$schema[$bucket])) {
                    self::$schema[$bucket]                          = array_replace(self::$schema[$bucket], $data);
                } else {
                    self::$schema[$bucket]                          = $data;
                }
            } else {
                self::$schema                                       = $data;
            }
        }
    }
    public static function getSchema($bucket = null)
    {
        if ($bucket && !isset(self::$schema[$bucket])) {
            self::$schema[$bucket]                                  = array();
            if (isset(self::$config[$bucket])) {
                $callback                                           = (
                    isset(self::$rules[$bucket]["callback"])
                                                                        ? self::$rules[$bucket]["callback"]
                                                                        : null
                                                                    );

                if (is_callable($callback)) {
                    $callback();
                }
            }
        }

        return (array) (
            $bucket
            ? self::$schema[$bucket]
            : self::$schema
        );
    }


    private static function loadSchema()
    {
        Debug::stopWatch("config/loadSchema");

        /**
         * Load Env and DirStruct by Xml
         */
        Config::loadDirStruct();

        $config                                                 = Config::rawData(static::SCHEMA_PAGES, true, "page");
        $router                                                 = Config::getSchema(static::SCHEMA_ROUTER);
        $request                                                = Config::getSchema(static::SCHEMA_REQUEST);

        if (is_array($config) && count($config)) {
            $schema                                             = array();
            $engine                                             = Config::getSchema(static::SCHEMA_ENGINE);
            foreach ($config as $page) {
                $attr                                           = Dir::getXmlAttr($page);
                $key                                            = (
                    isset($attr["path"])
                    ? $attr["path"]
                    : $attr["source"]
                );
                if (!$key) {
                    continue;
                }
                unset($attr["source"]);
                unset($attr["path"]);

                if ($key == "/") {
                    $key = "*";
                }

                if (isset($attr["source"]) && isset($attr["destination"])) {
                    $router[$key]                               = $attr;
                    unset($attr["destination"]);
                } elseif (isset($attr["engine"]) && isset($engine[$attr["engine"]])) {
                    $router[$key]                               = $engine[$attr["engine"]]["router"];
                } elseif (!isset($router[$key])) {
                    $router[$key]                               = null;
                }


                if (isset($attr["priority"])) {
                    $router[$key]["priority"]                   = $attr["priority"];
                    unset($attr["priority"]);
                }

                if (isset($attr["engine"]) && isset($engine[$attr["engine"]]) && $engine[$attr["engine"]]["properties"]) {
                    $attr = array_replace($engine[$attr["engine"]]["properties"], $attr);
                }
                if (!isset($schema[$key])) {
                    $schema[$key] = array();
                }
                $schema[$key]                                   = array_replace($schema[$key], $attr);

                $request                                        = Request::setSchema($page, $key, $request);
            }

            Config::setSchema($router, static::SCHEMA_ROUTER);
            Config::setSchema($request, static::SCHEMA_REQUEST);
            Config::setSchema($schema, static::SCHEMA_PAGES);
        }

        Debug::stopWatch("config/loadSchema");
    }

    public static function loadEngine()
    {
        $schema                                                             = array();
        $config                                                             = Config::rawData(static::SCHEMA_ENGINE, true);
        if (is_array($config) && count($config)) {
            foreach ($config as $key => $engine) {
                $attr                                                       = Dir::getXmlAttr($engine);
                if (isset($attr["source"])) {
                    $schema[$key]["router"]["source"]                       = $attr["source"];
                    unset($attr["source"]);
                }
                if (isset($attr["path"])) {
                    $schema[$key]["router"]["destination"]                  = $attr["path"];
                    unset($attr["path"]);
                    unset($attr["obj"]);
                    unset($attr["instance"]);
                    unset($attr["method"]);
                    unset($attr["params"]);
                } else {
                    if (isset($attr["obj"])) {
                        $schema[$key]["router"]["destination"]["obj"]       = $attr["obj"];
                        unset($attr["obj"]);
                    }
                    if (isset($attr["instance"])) {
                        $schema[$key]["router"]["destination"]["instance"]  = $attr["instance"];
                        unset($attr["instance"]);
                    }
                    if (isset($attr["method"])) {
                        $schema[$key]["router"]["destination"]["method"]    = $attr["method"];
                        unset($attr["method"]);
                    }
                    if (isset($attr["params"])) {
                        $schema[$key]["router"]["destination"]["params"]    = explode(",", $attr["params"]);
                        unset($attr["params"]);
                    }
                }
                if (isset($attr["priority"])) {
                    $schema[$key]["router"]["priority"]                     = $attr["priority"];
                    unset($attr["priority"]);
                }

                $schema[$key]["properties"]                                 = $attr;
            }

            Config::setSchema($schema, static::SCHEMA_ENGINE);
        }
    }



    public static function loadSnippet()
    {
        $config                                                 = Config::rawData(static::SCHEMA_SNIPPET, true);
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            foreach ($config as $key => $snippet) {
                $attr                                           = Dir::getXmlAttr($snippet);

                if (isset($attr["params"])) {
                    $attr["params"] = explode(",", $attr["params"]);
                }

                $schema[$key]                                   = $attr;
            }

            Config::setSchema($schema, static::SCHEMA_SNIPPET);
        }
    }

    public static function loadAlias()
    {
        $config                                                 = Config::rawData(static::SCHEMA_ALIAS, true, "domain");
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            foreach ($config as $domain) {
                $attr                                           = Dir::getXmlAttr($domain);
                $schema[$attr["name"]]                          = $attr["path"];
            }

            Config::setSchema($schema, static::SCHEMA_ALIAS);
        }
    }
    public static function loadMirror()
    {
        $config                                                 = Config::rawData(static::SCHEMA_MIRROR, true, "domain");
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            foreach ($config as $domain) {
                $attr                                           = Dir::getXmlAttr($domain);
                $schema[$attr["name"]]                          = $attr["proxy"];
            }

            Config::setSchema($schema, static::SCHEMA_MIRROR);
        }
    }

    public static function loadCache()
    {
        $config                                                 = Config::rawData(static::SCHEMA_CACHE, true);
        $schema                                                 = array();
        if (is_array($config["rule"]) && count($config["rule"])) {
            foreach ($config["rule"] as $cache) {
                $attr                                           = Dir::getXmlAttr($cache);
                $key                                            = $attr["path"];
                unset($attr["path"]);
                $schema["rule"][$key]                           = $attr;
            }
        }
        if (is_array($config["priority"]) && count($config["priority"])) {
            foreach ($config["priority"] as $cache) {
                $attr                                           = Dir::getXmlAttr($cache);
                $schema["priority"][]                           = $attr["path"];
            }
        }

        Config::setSchema($schema, static::SCHEMA_CACHE);
    }

    public static function loadModels()
    {
        $config                                                 = Config::rawData(static::SCHEMA_MODELS, true);
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            $schema_orm                                         = array();
            foreach ($config as $key => $model) {
                foreach ($model["field"] as $field) {
                    $attr                                       = Dir::getXmlAttr($field);

                    if (isset($attr["db"])) {
                        $schema_orm[$key][$attr["db"]]          = $attr["source"];
                    } else {
                        $schema[$key]                           = $attr;
                    }
                }
            }

            Config::setSchema($schema, static::SCHEMA_MODELS);
            Config::setSchema($schema_orm, static::SCHEMA_MODELS_ORM);
        }
    }

    public static function loadModelsView()
    {
        $config                                                 = Config::rawData(static::SCHEMA_MODELSVIEW, true, "view");
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            $schema_orm                                         = array();
            foreach ($config as $view) {
                $view_attr                                      = Dir::getXmlAttr($view);
                $model_name                                     = $view_attr["model"];
                $view_name                                      = $view_attr["name"];
                foreach ($view["field"] as $field) {
                    $attr                                       = Dir::getXmlAttr($field);
                    if (isset($attr["db"])) {
                        $schema_orm[$model_name][$view_name][$attr["db"]]       = $attr["source"];
                    } else {
                        $schema[$model_name][$view_name]                        = $attr;
                    }
                }
            }

            Config::setSchema($schema, static::SCHEMA_MODELSVIEW);
            Config::setSchema($schema_orm, static::SCHEMA_MODELSVIEW_ORM);
        }
    }
}
