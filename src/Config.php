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
use phpformsframework\libs\dto\ConfigRules;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\security\Buckler;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\storage\Media;

/**
 * Class Config
 * @package phpformsframework\libs
 */
class Config implements Dumpable
{
    /**
     *
     */
    const ERROR_BUCKET                                                      = "config";
    /**
     *
     */
    const APP_BASE_NAME                                                     = "app";
    /**
     *
     */
    const LIBS_BASE_NAME                                                    = "libs-base";
    /**
     *
     */
    const LIBS_NAME                                                         = "libs";

    /**
     *
     */
    const SCHEMA_CONF                                                       = "config";
    /**
     *
     */
    const SCHEMA_DIRSTRUCT                                                  = "dirs";
    /**
     *
     */
    const SCHEMA_PAGES                                                      = "pages";
    /**
     *
     */
    const SCHEMA_ENGINE                                                     = "engine";

    /**
     *
     */
    const RAWDATA_XML_REPLACE                                               = 1;
    /**
     *
     */
    const RAWDATA_XML_MERGE                                                 = 2;
    /**
     *
     */
    const RAWDATA_XML_MERGE_RECOURSIVE                                      = 3;

    /**
     * @var null
     */
    private static $config_files                                            = null;
    /**
     * @var null
     */
    private static $config_data                                             = null;
    /**
     * @var null
     */
    private static $config_unknown                                          = null;
    /**
     * @var null
     */
    private static $engine                                                  = null;

    /**
     * @var array
     */
    private static $autoloads                                               = array();
    /**
     * @var null
     */
    private static $webroot                                                 = null;
    /**
     * @var null
     */
    private static $dirstruct                                               = null;
    /**
     * @var null
     */
    private static $mapping_files                                           = null;
    /**
     * @var null
     */
    private static $mapping_data                                            = null;
    /**
     * @var null
     */
    private static $dirstruct_scan                                          = null;

    //@todo: da popolare con l'installer
    /**
     * @var array
     */
    private static $class_configurable                                      = array(
                                                                                "router"        => Router::class,
                                                                                "request"       => Request::class,
                                                                                "env"           => Env::class,
                                                                                "hook"          => Hook::class,
                                                                                "locale"        => Locale::class,
                                                                                "model"         => Model::class,
                                                                                "buckler"       => Buckler::class,
                                                                                "media"         => Media::class
                                                                            );
    //@todo: da sistemare togliendo il configurable e il dumpable e farlo fisso con 1 unica variabile
    /**
     * @var array
     */
    private static $config_rules                                    = array(
        Config::SCHEMA_DIRSTRUCT    => ["method" => Config::RAWDATA_XML_MERGE_RECOURSIVE,   "context" => Config::SCHEMA_DIRSTRUCT],
        Config::SCHEMA_PAGES        => ["method" => Config::RAWDATA_XML_MERGE_RECOURSIVE,   "context" => Config::SCHEMA_PAGES],
        Config::SCHEMA_ENGINE       => ["method" => Config::RAWDATA_XML_REPLACE,            "context" => Config::SCHEMA_ENGINE],
    );

    /**
     * @return array
     */
    public static function dump() : array
    {
        return array(
            "config_rules"                                                  => self::$config_rules,
            "config_files"                                                  => self::$config_files,
            "config_data"                                                   => self::$config_data,
            "config_unknown"                                                => self::$config_unknown,
            "engine"                                                        => self::$engine,
            "autoloads"                                                     => self::$autoloads,
            "webroot"                                                       => self::$webroot,
            "dirstruct"                                                     => self::$dirstruct,
            "dirstruct_scan"                                                => self::$dirstruct_scan,
            "mapping_files"                                                 => self::$mapping_files,
            "mapping_data"                                                  => self::$mapping_data,
            "class_configurable"                                            => self::$class_configurable,
        );
    }

    /**
     * @param array $rawdata
     */
    private static function loadRawData(array $rawdata) : void
    {
        self::$config_rules                                                 = $rawdata["config_rules"];
        self::$config_files                                                 = $rawdata["config_files"];
        self::$config_data                                                  = $rawdata["config_data"];
        self::$config_unknown                                               = $rawdata["config_unknown"];
        self::$engine                                                       = $rawdata["engine"];
        self::$autoloads                                                    = $rawdata["autoloads"];
        self::$webroot                                                      = $rawdata["webroot"];
        self::$dirstruct                                                    = $rawdata["dirstruct"];
        self::$mapping_files                                                = $rawdata["mapping_files"];
        self::$mapping_data                                                 = $rawdata["mapping_data"];
        self::$dirstruct_scan                                               = $rawdata["dirstruct_scan"];
        self::$class_configurable                                           = $rawdata["class_configurable"];
    }

    /**
     * LoadSchema: Directory Structure
     */
    public static function loadDirStruct()
    {
        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_DIRSTRUCT);

        $config                                                             = self::rawData(static::SCHEMA_DIRSTRUCT, true);
        $scans                                                              = [self::LIBS_BASE_NAME => [], self::LIBS_NAME => [], self::APP_BASE_NAME => []];

        if (is_array($config["dir"]) && count($config["dir"])) {
            foreach ($config["dir"] as $dir) {
                $dir_attr                                                   = Dir::getXmlAttr($dir);
                $dir_attr["path"]                                           = str_replace("[PROJECT_DOCUMENT_ROOT]", Kernel::$Environment::PROJECT_DOCUMENT_ROOT, $dir_attr["path"]);

                $dir_key                                                    = $dir_attr["type"];
                $dir_name                                                   = ltrim(basename($dir_attr["path"]), ".");
                if (isset($dir_attr["scan"])) {
                    $scan_type                                              = self::APP_BASE_NAME;
                    $scan_path                                              = str_replace("[LIBS_PATH]", Constant::LIBS_PATH, $dir_attr["path"]);

                    if ($scan_path != $dir_attr["path"]) {
                        $scan_type                                          = (
                            strpos($scan_path, Constant::LIBS_FF_PATH) === false
                            ? self::LIBS_NAME
                            : self::LIBS_BASE_NAME
                        );
                    }

                    $scans[$scan_type][$scan_path]                          = $dir_attr["scan"];
                    continue;
                }

                if (isset($dir_attr["webroot"])) {
                    self::$webroot                                          = Constant::DISK_PATH . $dir_attr["path"];
                    continue;
                }

                self::$dirstruct[$dir_key][$dir_name]                       = $dir_attr;
                if (isset(self::$dirstruct[$dir_key]["autoload"])) {
                    self::$autoloads[]                                      = self::$dirstruct[$dir_key]["path"];
                }
            }

            self::$dirstruct_scan                                           = $scans[self::LIBS_BASE_NAME] + $scans[self::LIBS_NAME] + $scans[self::APP_BASE_NAME];
        }

        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_DIRSTRUCT);
    }

    /**
     * @return string|null
     */
    public static function webRoot()
    {
        return self::$webroot;
    }

    /**
     * @param string $bucket
     * @return array|null
     */
    public static function getFilesMap(string $bucket) : ?array
    {
        return (isset(self::$mapping_files[$bucket])
            ? self::$mapping_files[$bucket]
            : null
        );
    }

    /**
     * @param string|null $name
     * @param string $bucket
     * @return string|null
     */
    public static function getDir(string $name = null, string $bucket = "app") : ?string
    {
        return (isset(self::$dirstruct[$bucket][$name])
            ? self::$dirstruct[$bucket][$name]["path"]
            : null
        );
    }

    /**
     * @param string|false $bucket
     * @return array|null
     */
    public static function getDirBucket(string $bucket = "app") : ?array
    {
        if (!$bucket) {
            return self::$dirstruct;
        }
        return (isset(self::$dirstruct[$bucket])
            ? self::$dirstruct[$bucket]
            : null
        );
    }

    /**
     * @param array $rules
     * @return array|null
     */
    public static function getScans(array $rules) : ?array
    {
        if (is_array($rules) && count($rules)) {
            $pattens                                                        = null;
            foreach (self::$dirstruct_scan as $path => $key) {
                if (isset($rules[$key])) {
                    $pattens[$path]                                         = $rules[$key];
                }
            }

            return $pattens;
        }

        return null;
    }

    /**
     *
     */
    public static function autoloadRegister() : void
    {
        if (is_array(self::$autoloads) && count(self::$autoloads)) {
            spl_autoload_register(function ($class_name) {
                foreach (self::$autoloads as $autoload) {
                    Dir::autoload(Constant::DISK_PATH . $autoload . DIRECTORY_SEPARATOR . str_replace('\\', '/', $class_name) . "." . Constant::PHP_EXT);
                }
            });
        }
    }

    /**
     * @param string $bucket
     * @param string|null $name
     */
    private static function loadMap(string $bucket, string $name = null) : void
    {
        if (!$name) {
            $name                                                           = "default";
        }
        $map_name                                                           = $bucket . "_" . $name;
        Debug::stopWatch(static::SCHEMA_CONF . "/map/" . $map_name);

        $cache                                                              = Mem::getInstance("maps");
        self::$mapping_data[$bucket][$name]                                 = $cache->get($map_name);
        if (!self::$mapping_data[$bucket][$name]) {
            self::$mapping_data[$bucket][$name]                             = array();

            if (isset(self::$mapping_files[$bucket][$name])) {
                self::$mapping_data[$bucket][$name]                         = Filemanager::getInstance("json")->read(self::$mapping_files[$bucket][$name]);
            }

            $cache->set($map_name, self::$mapping_data[$bucket][$name]);
        }
        Debug::stopWatch(static::SCHEMA_CONF . "/map/". $map_name);
    }

    /**
     * @param string $bucket
     * @param string $name
     * @return array|null
     */
    public static function mapping(string $bucket, string $name) : ?array
    {
        if (!isset(self::$mapping_data[$bucket][$name])) {
            self::loadMap($bucket, $name);
        }

        return (isset(self::$mapping_data[$bucket][$name])
            ? self::$mapping_data[$bucket][$name]
            : null
        );
    }

    /**
     * @param ConfigRules $rules
     */
    public static function addRules(ConfigRules $rules) : void
    {
        self::$config_rules                                                 = self::$config_rules + $rules->toArray();
    }

    /**
     * @param string $key
     * @param bool $remove
     * @return array|null
     */
    private static function rawData(string $key, $remove = false) : ?array
    {
        $res = null;
        if (isset(self::$config_data[$key])) {
            $res = self::$config_data[$key];
            if ($remove) {
                unset(self::$config_data[$key]);
            }
        }
        return $res;
    }

    /**
     * @param array $paths
     */
    public static function load(array $paths = array()) : void
    {
        Debug::stopWatch(static::SCHEMA_CONF . "/load");

        $cache                                                              = Mem::getInstance("config");
        $rawdata                                                            = $cache->get("rawdata");
        if (!$rawdata) {
            $rawdata                                                        = array();


            foreach (self::$class_configurable as $class_basename => $class_name) {
                /**
                 * @var Configurable $class_name
                 */
                $configRules                                                = new ConfigRules($class_basename);
                self::addRules($class_name::loadConfigRules($configRules));
            }


            /**
             * Find Config.xml
             */
            $paths = array_replace(
                Constant::CONFIG_DISK_PATHS,
                $paths
            );

            Filemanager::scan($paths, function ($file) {
                $pathinfo                                                   = pathinfo($file);
                switch ($pathinfo["extension"]) {
                    case "xml":
                        self::$config_files[$file]                          = filemtime($file);

                        self::loadXml($file);
                        break;
                    case "map":
                        $arrFN                                              = explode("_", $pathinfo["filename"], 2);
                        self::$mapping_files[$arrFN[0]][$arrFN[1]]          = $file;
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
                if (isset(self::$config_data[$class_basename])) {
                    $rawdata[$class_basename]                               = $class_name::loadSchema(self::$config_data[$class_basename]);
                    unset(self::$config_data[$class_basename]);
                } else {
                    Error::registerWarning("no configuration for: " . $class_basename, static::ERROR_BUCKET);
                }
                Debug::stopWatch(static::SCHEMA_CONF . "/" . $class_basename);
            }

            $cache->set("rawdata", self::dump() + $rawdata);
        } else {
            self::loadRawData($rawdata);

            if (is_array($rawdata["class_configurable"]) && count($rawdata["class_configurable"])) {
                foreach ($rawdata["class_configurable"] as $class_basename => $class_name) {
                    if (!empty($rawdata[$class_basename])) {
                        $class_name::loadConfig($rawdata[$class_basename]);
                    }
                }
            }
        }

        Debug::stopWatch(static::SCHEMA_CONF . "/load");
    }

    /**
     * @param string $file
     */
    private static function loadXml(string $file) : void
    {
        Debug::stopWatch(static::SCHEMA_CONF . "/loadXml");

        self::$config_files[$file]                                          = filemtime($file);
        $configs                                                            = Filemanager::getInstance("xml")->read($file);

        foreach ($configs as $key => $config) {
            if (isset(self::$config_rules[$key])) {
                $context                                                    = self::$config_rules[$key]["context"];

                if (!isset(self::$config_data[$context])) {
                    self::$config_data[$context]                            = array();
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
                self::$config_unknown[$key]                                 = $config;
            }
        }

        Debug::stopWatch(static::SCHEMA_CONF . "/loadXml");
    }


    /**
     * @param string $key
     * @param array $config
     */
    private static function loadXmlReplace(string $key, array $config) : void
    {
        self::$config_data[$key]                                            = array_replace(self::$config_data[$key], (array)$config);
    }

    /**
     * @param string $key
     * @param array $config
     */
    private static function loadXmlMerge(string $key, array $config) : void
    {
        if (is_array($config) && count($config)) {
            if (!isset($config[0])) {
                $config                                                     = array($config);
            }
            self::$config_data[$key]                                        = array_merge(self::$config_data[$key], $config);
        }
    }

    /**
     * @param string $key
     * @param array $config
     */
    private static function loadXmlMergeSub(string $key, array $config) : void
    {
        if (is_array($config) && count($config)) {
            foreach ($config as $sub_key => $sub_config) {
                if (!isset($sub_config[0])) {
                    $sub_config                                             = array($sub_config);
                }
                if (isset(self::$config_data[$key][$sub_key])) {
                    self::$config_data[$key][$sub_key]                      = array_merge(self::$config_data[$key][$sub_key], $sub_config);
                } else {
                    self::$config_data[$key][$sub_key]                      = $sub_config;
                }
            }
        }
    }

    /**
     * @param array $config
     */
    public static function loadConfig(array $config) : void
    {
        self::$config_rules                                                 = $config["config_rules"];
        self::$config_files                                                 = $config["config_files"];
        self::$config_data                                                  = $config["config"];
        self::$config_unknown                                               = $config["config_unknown"];
        self::$engine                                                       = $config["engine"];
        self::$autoloads                                                    = $config["autoloads"];
        self::$webroot                                                      = $config["webroot"];
        self::$dirstruct                                                    = $config["dirstruct"];
        self::$dirstruct_scan                                               = $config["dirstruct_scan"];
        self::$mapping_files                                                = $config["mapping_files"];
        self::$mapping_data                                                 = $config["mapping_data"];
    }

    /**
     *
     */
    public static function loadPages()
    {
        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_PAGES);

        $config                                                             = Config::rawData(static::SCHEMA_PAGES, true);
        $router                                                             = array();
        $request                                                            = array();

        if (isset($config["page"]) && is_array($config["page"]) && count($config["page"])) {
            foreach ($config["page"] as $page) {
                $attr                                                       = Dir::getXmlAttr($page);
                $key                                                        = (
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
                    $router[$key]                                           = $attr;
                    unset($attr["destination"]);
                } elseif (isset($attr["engine"]) && isset(self::$engine[$attr["engine"]])) {
                    $router[$key]                                           = self::$engine[$attr["engine"]]["router"];
                } elseif (!isset($router[$key])) {
                    $router[$key]                                           = null;
                }


                if (isset($attr["priority"])) {
                    $router[$key]["priority"]                               = $attr["priority"];
                    unset($attr["priority"]);
                }

                if (isset($attr["engine"]) && isset(self::$engine[$attr["engine"]]) && self::$engine[$attr["engine"]]["properties"]) {
                    $attr = array_replace(self::$engine[$attr["engine"]]["properties"], $attr);
                }

                $request[$key]                                              = $page;
                $request[$key]["config"]                                    = $attr;
            }

            self::$config_data["router"]["pages"]                           = $router;
            self::$config_data["request"]["pages"]                          = $request;
        }

        Debug::stopWatch(static::SCHEMA_CONF . "/" . static::SCHEMA_PAGES);
    }

    /**
     *
     */
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
