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

use Composer\Autoload\ClassLoader;
use Composer\Composer;
use Exception;
use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\dto\ConfigRules;
use phpformsframework\libs\storage\Filemanager;
use ReflectionClass;

class Config implements Dumpable
{
    const ERROR_BUCKET                                              = "config";
    const APP_BASE_NAME                                             = "app";
    const LIBS_BASE_NAME                                            = "libs-base";
    const LIBS_NAME                                                 = "libs";
    const CONFIG_PATH                                               = array();

    const SCHEMA_CONF                                               = "config";
    const SCHEMA_DIRSTRUCT                                          = "dirs";
    const SCHEMA_PAGES                                              = "pages";
    const SCHEMA_ENGINE                                             = "engine";

    const RAWDATA_XML_REPLACE                                        = 1;
    const RAWDATA_XML_MERGE                                          = 2;
    const RAWDATA_XML_MERGE_RECOURSIVE                               = 3;

    private static $config                                          = null;
    private static $config_unknown                                  = null;
    private static $maps                                            = null;
    private static $engine                                          = null;

    private static $config_rules                                    = array(
        Config::SCHEMA_DIRSTRUCT    => ["method" => Config::RAWDATA_XML_MERGE_RECOURSIVE,   "context" => Config::SCHEMA_DIRSTRUCT],
        Config::SCHEMA_PAGES        => ["method" => Config::RAWDATA_XML_MERGE_RECOURSIVE,   "context" => Config::SCHEMA_PAGES],
        Config::SCHEMA_ENGINE       => ["method" => Config::RAWDATA_XML_REPLACE,            "context" => Config::SCHEMA_ENGINE],
    );
    private static $autoloads                                       = array();
    private static $webroot                                         = null;
    private static $dirstruct                                       = null;
    private static $file_config                                     = null;
    private static $file_maps                                       = null;
    private static $file_scans                                      = null;
    private static $class_configurable                              = null;
    private static $class_dumpable                                  = null;

    public static function dump()
    {
        return array(
            "config_rules"          => self::$config_rules,
            "config"                => self::$config,
            "config_unknown"        => self::$config_unknown,
            "engine"                => self::$engine,
            "maps"                  => self::$maps,
            "autoloads"             => self::$autoloads,
            "webroot"               => self::$webroot,
            "dirstruct"             => self::$dirstruct,
            "file_config"           => self::$file_config,
            "file_maps"             => self::$file_maps,
            "file_scans"            => self::$file_scans,
            "class_configurable"    => self::$class_configurable,
            "class_dumpable"        => self::$class_dumpable
        );
    }

    private static function loadRawData($rawdata)
    {
        self::$config               = $rawdata["config"];
        self::$config_unknown       = $rawdata["config_unknown"];
        self::$engine               = $rawdata["engine"];
        self::$config_rules         = $rawdata["config_rules"];
        self::$maps                 = $rawdata["maps"];
        self::$autoloads            = $rawdata["autoloads"];
        self::$webroot              = $rawdata["webroot"];
        self::$dirstruct            = $rawdata["dirstruct"];
        self::$file_config          = $rawdata["file_config"];
        self::$file_maps            = $rawdata["file_maps"];
        self::$file_scans           = $rawdata["file_scans"];
        self::$class_configurable   = $rawdata["class_configurable"];
        self::$class_dumpable       = $rawdata["class_dumpable"];
    }


    public static function loadDirStruct()
    {
        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_DIRSTRUCT);

        $config                                                     = self::rawData(static::SCHEMA_DIRSTRUCT, true);
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
                    self::$autoloads[]                              = Constant::DISK_PATH . $dir_attr["path"];
                }
            }

            self::$file_scans                                       = $scans[self::LIBS_BASE_NAME] + $scans[self::LIBS_NAME] + $scans[self::APP_BASE_NAME];
        }

        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_DIRSTRUCT);
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
    public static function autoloadRegister()
    {
        if (is_array(self::$autoloads) && count(self::$autoloads)) {
            spl_autoload_register(function ($class_name) {
                foreach (self::$autoloads as $autoload) {
                    Dir::autoload($autoload . DIRECTORY_SEPARATOR . $class_name . "." . Constant::PHP_EXT);
                }
            });
        }
    }

    public static function loadMap($bucket, $name = null)
    {
        if (!$name) {
            $name = "default";
        }
        $map_name                                                   = $bucket . "_" . $name;

        Debug::stopWatch(static::SCHEMA_CONF . "/map/" . $map_name);
        $cache                                                      = Mem::getInstance("maps");
        self::$maps[$bucket][$name]                                 = $cache->get($map_name);
        if (!self::$maps[$bucket][$name]) {
            self::$maps[$bucket][$name]                             = array();

            if (isset(self::$file_maps[$map_name])) {
                self::$maps[$bucket][$name]                         = Filemanager::getInstance("json")->read(self::$file_maps[$map_name]);
            }

            $cache->set($map_name, self::$maps[$bucket][$name]);
        }
        Debug::stopWatch(static::SCHEMA_CONF . "/map/". $map_name);
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

    /**
     * @param ConfigRules $rules
     */
    public static function addRules($rules)
    {
        self::$config_rules                                         = self::$config_rules + $rules->toArray();
    }
    private static function rawData($key, $remove = false)
    {
        $res = null;
        if (isset(self::$config[$key])) {
            $res = self::$config[$key];
            if ($remove) {
                unset(self::$config[$key]);
            }
        }
        return $res;
    }

    public static function load($paths = array())
    {
        Debug::stopWatch(static::SCHEMA_CONF . "/load");

        $cache                                                      = Mem::getInstance("config");
        $rawdata                                                    = $cache->get("rawdata");
        if (!$rawdata) {
            $rawdata                                                = array();

            $classes                                                = get_declared_classes();
            //@todo: da trovare un modo per importare anche queste
            $classes[] = 'phpformsframework\\libs\\Model';
            foreach ($classes as $class_name) {
                try {
                    $reflect                                        = new ReflectionClass($class_name);
                    if ($reflect->implementsInterface(__NAMESPACE__ . '\\Dumpable')) {
                        $parent                                     = $reflect->getParentClass();
                        if (!$parent || !isset(self::$class_dumpable[strtolower(basename(str_replace('\\', '/', $parent->getName())))])) {
                            self::$class_dumpable[strtolower(basename(str_replace('\\', '/', $class_name)))]  = $class_name;
                        }
                    }

                    if ($reflect->implementsInterface(__NAMESPACE__ . '\\Configurable')) {
                        $parent                                     = $reflect->getParentClass();

                        if (!$parent || !isset(self::$class_configurable[strtolower(basename(str_replace('\\', '/', $parent->getName())))])) {
                            $class_basename                         = strtolower(basename(str_replace('\\', '/', $class_name)));
                            self::$class_configurable[$class_basename] = $class_name;
                            /**
                             * @var Configurable $class_name
                             */
                            $configRules                            = new ConfigRules($class_basename);
                            self::addRules($class_name::loadConfigRules($configRules));
                        }
                    }
                } catch (Exception $exception) {
                    Error::register($exception->getMessage(), static::ERROR_BUCKET);
                }
            }

            /**
             * Find Config.xml
             */
            $paths = array_replace(
                array(
                    Constant::CONFIG_FF_DISK_PATH => array("filter" => array("xml", "map"))),
                static::CONFIG_PATH,
                $paths
            );

            Filemanager::scan($paths, function ($file) {
                $pathinfo                                           = pathinfo($file);
                switch ($pathinfo["extension"]) {
                    case "xml":
                        self::$file_config[$file]                   = filemtime($file);

                        self::loadXml($file);
                        break;
                    case "map":
                        self::$file_maps[$pathinfo["filename"]]     = $file;
                        break;
                    default:
                        Error::registerWarning("Config file Extension not supported", static::ERROR_BUCKET);
                }
            });

            /**
             * Load Kernel Config by Xml
             */
            Config::loadDirStruct();
            Config::loadEngine();
            Config::loadPages();

            /**
             * @var Configurable $class_name
             */

            foreach (self::$class_configurable as $class_basename => $class_name) {
                Debug::stopWatch(static::SCHEMA_CONF . "/" . $class_basename);

                $rawdata[$class_basename]                           = $class_name::loadSchema(self::$config[$class_basename]);
                unset(self::$config[$class_basename]);

                Debug::stopWatch(static::SCHEMA_CONF . "/" . $class_basename);
            }

            $cache->set("rawdata", self::dump() + $rawdata);
        } else {
            self::loadRawData($rawdata);

            if (is_array($rawdata["class_configurable"]) && count($rawdata["class_configurable"])) {
                foreach ($rawdata["class_configurable"] as $class_basename => $class_name) {
                    $class_name::loadConfig($rawdata[$class_basename]);
                }
            }
        }

        Debug::stopWatch(static::SCHEMA_CONF . "/load");
    }

    private static function loadXml($file)
    {
        Debug::stopWatch(static::SCHEMA_CONF . "/loadXml");

        self::$file_config[$file]                                   = filemtime($file);
        $configs                                                    = Filemanager::getInstance("xml")->read($file);

        foreach ($configs as $key => $config) {
            if (isset(self::$config_rules[$key])) {
                $context                                            = self::$config_rules[$key]["context"];

                if (!isset(self::$config[$context])) {
                    self::$config[$context]                         = array();
                }

                switch (self::$config_rules[$key]["method"]) {
                    case self::RAWDATA_XML_REPLACE:
                        self::loadXmlReplace($context, $config);
                        break;
                    case self::RAWDATA_XML_MERGE:
                        self::loadXmlMerge($context, $config);
                        break;
                    case self::RAWDATA_XML_MERGE_RECOURSIVE:
                    default:
                        self::loadXmlMergeSub($context, $config);
                }
            } else {
                self::$config_unknown[$key]                         = $config;
            }
        }

        Debug::stopWatch(static::SCHEMA_CONF . "/loadXml");
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

    public static function loadConfig($config)
    {
        self::$config_rules                                         = $config["config_rules"];
        self::$config                                               = $config["config"];
        self::$engine                                               = $config["engine"];
        self::$autoloads                                            = $config["autoloads"];
        self::$webroot                                              = $config["webroot"];
        self::$dirstruct                                            = $config["dirstruct"];
        self::$file_config                                          = $config["file_config"];
        self::$file_maps                                            = $config["file_maps"];
        self::$file_scans                                           = $config["file_scans"];
    }

    public static function loadPages()
    {
        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_PAGES);

        $config                                                     = Config::rawData(static::SCHEMA_PAGES, true);
        $router                                                     = array();
        $request                                                    = array();

        if (isset($config["page"]) && is_array($config["page"]) && count($config["page"])) {
            foreach ($config["page"] as $page) {
                $attr                                               = Dir::getXmlAttr($page);
                $key                                                = (
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
                    $router[$key]                                   = $attr;
                    unset($attr["destination"]);
                } elseif (isset($attr["engine"]) && isset(self::$engine[$attr["engine"]])) {
                    $router[$key]                                   = self::$engine[$attr["engine"]]["router"];
                } elseif (!isset($router[$key])) {
                    $router[$key]                                   = null;
                }


                if (isset($attr["priority"])) {
                    $router[$key]["priority"]                       = $attr["priority"];
                    unset($attr["priority"]);
                }

                if (isset($attr["engine"]) && isset(self::$engine[$attr["engine"]]) && self::$engine[$attr["engine"]]["properties"]) {
                    $attr = array_replace(self::$engine[$attr["engine"]]["properties"], $attr);
                }

                $request[$key]                                      = $page;
                $request[$key]["config"]                            = $attr;
            }

            self::$config["router"]["pages"]                        = $router;
            self::$config["request"]["pages"]                       = $request;
        }

        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_PAGES);
    }

    public static function loadEngine()
    {
        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_ENGINE);

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

            self::$engine                                                   = $schema;
        }

        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_ENGINE);
    }
}
