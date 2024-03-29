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

use ff\libs\cache\Buffer;
use ff\libs\dto\ConfigRules;
use ff\libs\international\Locale;
use ff\libs\microservice\Gateway;
use ff\libs\security\Buckler;
use ff\libs\storage\FilemanagerFs;
use ff\libs\storage\FilemanagerScan;
use ff\libs\storage\Media;
use ff\libs\storage\Model;

/**
 * Class Config
 * @package ff\libs
 */
class Config implements Dumpable
{
    public const ERROR_BUCKET                                               = "config";

    private const APP_BASE_NAME                                             = Constant::RESOURCE_APP;
    private const LIBS_NAME                                                 = "libs";

    private const SCHEMA_CONF                                               = "config";
    private const SCHEMA_DIRSTRUCT                                          = "dirs";
    private const SCHEMA_PAGES                                              = "pages";
    private const SCHEMA_ENGINE                                             = "engine";

    private const SCHEMA_ROUTER                                             = "router";
    private const SCHEMA_REQUEST                                            = "request";

    private const SCHEMA_ENV                                                = "env";
    private const SCHEMA_HOOK                                               = "hook";
    private const SCHEMA_LOCALE                                             = "locale";
    private const SCHEMA_MODEL                                              = "model";
    private const SCHEMA_BUCKLER                                            = "buckler";
    private const SCHEMA_MEDIA                                              = "media";
    private const SCHEMA_GATEWAY                                            = "gateway";


    private const RAWDATA_XML_REPLACE                                       = Configurable::METHOD_REPLACE;
    private const RAWDATA_XML_APPEND                                        = Configurable::METHOD_APPEND;
    private const RAWDATA_XML_MERGE                                         = Configurable::METHOD_MERGE;

    /**
     * @var null
     */
    private static $exTime                                                  = 0;
    /**
     * @var null
     */
    private static $config_dirs                                             = [];
    /**
     * @var null
     */
    private static $config_files                                            = [];
    /**
     * @var null
     */
    private static $config_data                                             = [];
    /**
     * @var null
     */
    private static $config_unknown                                          = [];
    /**
     * @var null
     */
    private static $engine                                                  = [];

    /**
     * @var array
     */
    private static $autoloads                                               = [];
    /**
     * @var null
     */
    private static $webroot                                                 = null;
    /**
     * @var null
     */
    private static $dirstruct                                               = [];
    /**
     * @var null
     */
    private static $mapping_files                                           = [];
    /**
     * @var null
     */
    private static $mapping_data                                            = [];
    /**
     * @var null
     */
    private static $dirstruct_scan                                          = [];

    /**
     * @var array
     */
    private static $class_configurable                                      = array(
                                                                                self::SCHEMA_ROUTER     => Router::class,
                                                                                self::SCHEMA_REQUEST    => Request::class,
                                                                                self::SCHEMA_ENV        => Env::class,
                                                                                self::SCHEMA_HOOK       => Hook::class,
                                                                                self::SCHEMA_LOCALE     => Locale::class,
                                                                                self::SCHEMA_MODEL      => Model::class,
                                                                                self::SCHEMA_BUCKLER    => Buckler::class,
                                                                                self::SCHEMA_MEDIA      => Media::class,
                                                                                self::SCHEMA_GATEWAY    => Gateway::class
                                                                            );
    //@todo: da sistemare togliendo il configurable e il dumpable e farlo fisso con 1 unica variabile
    /**
     * @var array
     */
    private static $config_rules                                            = array(
        self::SCHEMA_DIRSTRUCT      => ["method" => self::RAWDATA_XML_MERGE,   "context"   => self::SCHEMA_DIRSTRUCT],
        self::SCHEMA_PAGES          => ["method" => self::RAWDATA_XML_MERGE,   "context"   => self::SCHEMA_PAGES],
        self::SCHEMA_ENGINE         => ["method" => self::RAWDATA_XML_REPLACE, "context"   => self::SCHEMA_ENGINE],
    );

    /**
     * @return array
     */
    public static function dump() : array
    {
        return array(
            "config_rules"                                                  => self::$config_rules,
            "config_dirs"                                                   => self::$config_dirs,
            "config_files"                                                  => self::$config_files,
            "config_data"                                                   => self::$config_data,
            "config_unknown"                                                => self::$config_unknown,
            self::SCHEMA_ENGINE                                             => self::$engine,
            "autoloads"                                                     => self::$autoloads,
            "webroot"                                                       => self::$webroot,
            "dirstruct"                                                     => self::$dirstruct,
            "dirstruct_scan"                                                => self::$dirstruct_scan,
            "mapping_files"                                                 => self::$mapping_files,
            "mapping_data"                                                  => self::$mapping_data,
            "class_configurable"                                            => self::$class_configurable
        );
    }

    /**
     * @return float
     */
    public static function exTime() : float
    {
        return self::$exTime;
    }

    /**
     * LoadSchema: Directory Structure
     */
    public static function loadDirStruct()
    {
        Debug::stopWatch(self::SCHEMA_CONF . "/" . self::SCHEMA_DIRSTRUCT);

        $config                                                             = self::rawData(self::SCHEMA_DIRSTRUCT, true);
        $scans                                                              = [self::LIBS_NAME => [], self::APP_BASE_NAME => []];
        $dirs                                                               =& $config["dir"];
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                $dir_attr                                                   = Dir::getXmlAttr($dir);
                if (!isset($dir_attr["path"])) {
                    continue;
                }

                $dir_attr["path"]                                           = str_replace(
                    [
                        "[PROJECT_DOCUMENT_ROOT]",
                        "[THEME_PATH]"
                    ],
                    [
                        Kernel::$Environment::PROJECT_DOCUMENT_ROOT,
                        Kernel::$Environment::getThemePath()
                    ],
                    $dir_attr["path"]
                );

                $dir_key                                                    = $dir_attr["type"];
                if (isset($dir_attr["scan"])) {
                    $scan_path                                              = str_replace("[LIBS_PATH]", Constant::LIBS_PATH, $dir_attr["path"]);
                    $scan_type = (
                        $scan_path == $dir_attr["path"]
                        ? self::APP_BASE_NAME
                        : self::LIBS_NAME
                    );

                    $scans[$scan_type][$scan_path]                          = $dir_attr["scan"];
                    continue;
                }

                if (isset($dir_attr["webroot"])) {
                    self::$webroot                                          = Constant::DISK_PATH . $dir_attr["path"];
                }
                if (isset($dir_attr["name"])) {
                    $dir_name                                               = $dir_attr["name"];
                    unset($dir_attr["name"], $dir_attr["type"]);
                    self::$dirstruct[$dir_key][$dir_name]                   = $dir_attr;
                    if (isset(self::$dirstruct[$dir_key][$dir_name]["autoload"])) {
                        self::$autoloads[]                                  = self::$dirstruct[$dir_key][$dir_name]["path"];
                    }
                }
            }

            self::$dirstruct_scan                                           = $scans[self::LIBS_NAME] + $scans[self::APP_BASE_NAME];
        }

        Debug::stopWatch(self::SCHEMA_CONF . "/" . self::SCHEMA_DIRSTRUCT);
    }

    /**
     * @return string|null
     */
    public static function webRoot(): ?string
    {
        return self::$webroot;
    }

    /**
     * @param string $bucket
     * @param string $name
     * @return string
     * @throws Exception
     */
    public static function getFileMap(string $bucket, string $name) : string
    {
        if (!isset(self::$mapping_files[$bucket][$name])) {
            throw new Exception("Mapping file: " . $name . " in bucket: " . $bucket . " not found", 500);
        }

        return self::$mapping_files[$bucket][$name];
    }

    /**
     * @param string|null $name
     * @param string $bucket
     * @return string|null
     */
    public static function getDir(string $name = null, string $bucket = self::APP_BASE_NAME) : ?string
    {
        return (isset(self::$dirstruct[$bucket][$name])
            ? self::$dirstruct[$bucket][$name]["path"]
            : null
        );
    }

    /**
     * @param string|null $bucket
     * @return array
     */
    public static function getDirBucket(string $bucket = null) : array
    {
        if (!$bucket) {
            return self::$dirstruct;
        }

        return self::$dirstruct[$bucket] ?? [];
    }

    /**
     * @param array $rules
     * @return array|null
     */
    public static function getScans(array $rules) : ?array
    {
        if (!empty($rules)) {
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
     * @param string $name
     * @return array|null
     */
    public static function getSchema(string $name) : ?array
    {
        return self::$config_unknown[$name] ?? null;
    }

    /**
     * @param array $simpleXml
     * @return object
     */
    public static function getXmlAttr(array $simpleXml) : object
    {
        return (object) ($simpleXml["@attributes"] ?? []);
    }

    /**
     * @param string $namespace
     * @throws Exception
     */
    public static function autoloadRegister(string $namespace) : void
    {
        if (!empty(self::$autoloads)) {
            /**
             * @var Autoloader $class_name
             */
            $class_name = $namespace . "Autoloader";
            $class_name::register(self::$autoloads);
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
        Debug::stopWatch(self::SCHEMA_CONF . "/map/" . $map_name);

        $cache                                                              = Buffer::cache("maps");
        self::$mapping_data[$bucket][$name]                                 = $cache->get($map_name);
        if (!self::$mapping_data[$bucket][$name]) {
            self::$mapping_data[$bucket][$name]                             = array();

            if (isset(self::$mapping_files[$bucket][$name])) {
                self::$mapping_data[$bucket][$name]                         = FilemanagerFs::loadFile("json")->read(self::$mapping_files[$bucket][$name]);

                $cache->set($map_name, self::$mapping_data[$bucket][$name], [self::$mapping_files[$bucket][$name] => filemtime(self::$mapping_files[$bucket][$name])]);
            }
        }

        Debug::stopWatch(self::SCHEMA_CONF . "/map/". $map_name);
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

        return self::$mapping_data[$bucket][$name] ?? null;
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
    private static function rawData(string $key, bool $remove = false) : ?array
    {
        $res                                                                = null;
        if (isset(self::$config_data[$key])) {
            $res                                                            = self::$config_data[$key];
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
        Debug::stopWatch(self::SCHEMA_CONF . "/load");

        $cache                                                              = Buffer::cache(static::ERROR_BUCKET);
        $rawdata                                                            = $cache->get("rawdata");
        if (!$rawdata) {
            $rawdata                                                        = self::loadFile($paths);
            $cache->set("rawdata", self::dump() + $rawdata, self::$config_dirs + self::$config_files);
        } else {
            self::loadConfig($rawdata);

            if (!empty($rawdata["class_configurable"])) {
                foreach ($rawdata["class_configurable"] as $class_basename => $class_name) {
                    if (!empty($rawdata[$class_basename])) {
                        /**
                         * @var Configurable $class_name
                         */
                        $class_name::loadConfig($rawdata[$class_basename]);
                    }
                }
            }
        }

        self::$exTime = Debug::stopWatch(self::SCHEMA_CONF . "/load");
    }

    /**
     * @param array $paths
     * @return array
     */
    private static function loadFile(array $paths) : array
    {
        clearstatcache();

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
            Constant::CONFIG_PATHS,
            $paths,
            Constant::CONFIG_APP_PATHS
        );

        FilemanagerScan::scan($paths, function ($file) {
            $pathinfo                                                   = pathinfo($file);
            $dir                                                        = $pathinfo["dirname"];

            if (!isset(self::$config_dirs[$dir])) {
                self::$config_dirs[$dir]                                = filemtime($dir);
            }

            switch ($pathinfo["extension"]) {
                case "xml":
                    self::$config_files[$file]                          = filemtime($file);

                    self::loadFileXml($file);
                    break;
                case "map":
                    $arrFN                                              = explode("_", $pathinfo["filename"], 2);
                    self::$mapping_files[$arrFN[0]][$arrFN[1] ?? "unknown"]     = $file;
                    break;
                default:
                    Exception::warning("Config file Extension not supported", static::ERROR_BUCKET);
            }
        });

        /**
         * Load Kernel Config by Xml
         */
        static::loadDirStruct();
        static::loadEngine();
        static::loadPages();

        /**
         * @var Configurable $class_name
         */
        foreach (self::$class_configurable as $class_basename => $class_name) {
            Debug::stopWatch(self::SCHEMA_CONF . "/" . $class_basename);
            if (isset(self::$config_data[$class_basename])) {
                $rawdata[$class_basename]                               = $class_name::loadSchema(self::$config_data[$class_basename]);
                unset(self::$config_data[$class_basename]);
            } else {
                Exception::warning("no configuration for: " . $class_basename, static::ERROR_BUCKET);
            }
            Debug::stopWatch(self::SCHEMA_CONF . "/" . $class_basename);
        }

        return $rawdata;
    }


    /**
     * @param string $file
     * @throws Exception
     */
    private static function loadFileXml(string $file) : void
    {
        $configs                                                            = FilemanagerFs::loadFile("xml")->read($file);
        if (is_array($configs)) {
            foreach ($configs as $key => $config) {
                if (isset(self::$config_rules[$key])) {
                    $context                                                = self::$config_rules[$key]["context"];

                    if (!isset(self::$config_data[$context])) {
                        self::$config_data[$context]                        = array();
                    }

                    switch (self::$config_rules[$key]["method"]) {
                        case self::RAWDATA_XML_REPLACE:
                            self::loadFileXmlReplace($context, $config);
                            break;
                        case self::RAWDATA_XML_APPEND:
                            self::loadFileXmlMerge($context, $config);
                            break;
                        case self::RAWDATA_XML_MERGE:
                        default:
                            self::loadFileXmlMergeSub($context, $config);
                    }
                } else {
                    self::$config_unknown[$key]                           = array_replace(self::$config_unknown[$key] ?? [], $config);
                }
            }
        } elseif ($configs === false) {
            throw new Exception("Syntax Error in Config.xml: " . $file, 500);
        }
    }


    /**
     * @param string $key
     * @param array $config
     */
    private static function loadFileXmlReplace(string $key, array $config) : void
    {
        self::$config_data[$key]                                            = array_replace(self::$config_data[$key], (array)$config);
    }

    /**
     * @param string $key
     * @param array $config
     */
    private static function loadFileXmlMerge(string $key, array $config) : void
    {
        if (!empty($config)) {
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
    private static function loadFileXmlMergeSub(string $key, array $config) : void
    {
        if (!empty($config)) {
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
        self::$config_dirs                                                  = $config["config_dirs"];
        self::$config_files                                                 = $config["config_files"];
        self::$config_data                                                  = $config["config_data"];
        self::$config_unknown                                               = $config["config_unknown"];
        self::$engine                                                       = $config[self::SCHEMA_ENGINE];
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
        Debug::stopWatch(self::SCHEMA_CONF . "/" . self::SCHEMA_PAGES);

        $config                                                                         = static::rawData(self::SCHEMA_PAGES, true);
        $router                                                                         = array();
        $request                                                                        = array();
        $path2params                                                                    = array();

        if (!empty($config["page"])) {
            foreach ($config["page"] as $page) {
                $attr                                                                   = Dir::getXmlAttr($page);
                $key                                                                    = $attr["path"] ?? $attr["source"];

                if (!$key) {
                    continue;
                }

                $source                                                                 = null;
                $params                                                                 = [];

                /**
                 * accept_path_info
                 */
                if (substr($key, -1) === "*") {
                    $attr["accept_path_info"]                                           = true;
                    $key                                                                = substr($key, 0, -1);
                }

                /**
                 * path2params anonymous
                 * @todo da finire la cattura delle * all'interno del path della rotta
                 */
                if (($star_count = substr_count($key, "*")) > 0) {
                    //$source                                                         = str_replace("*", "(.*)", $key);
                    $key                                                                = explode("*", $key, 2)[0];
                    for ($i = 1; $i <= $star_count; $i++) {
                    //    $params[] = '$' . $i;
                    }
                }

                $key                                                                    = rtrim($key, DIRECTORY_SEPARATOR) ?: DIRECTORY_SEPARATOR;

                /**
                 * path2params
                 */
                if ($key != DIRECTORY_SEPARATOR && preg_match_all('#/{([^/]*)}#i', $key, $vars)) {
                    $regexp                                                             = '#^' . str_replace($vars[0], "(?:/([^/]+))?", $key);
                    $regexp                                                             .= (
                        str_ends_with($regexp, '?')
                        ? '#i'
                        : '$#i'
                    );
                    $key                                                                = str_replace($vars[0], "", $key) ?: DIRECTORY_SEPARATOR;
                    $path2params[$key]                                                  = [
                                                                                            "matches"   => $vars[1],
                                                                                            "regexp"    => $regexp
                                                                                        ];
                }

                unset($page["@attributes"]);
                unset($attr["source"]);
                unset($attr["path"]);

                /**
                 * Controller
                 */
                if (isset($attr["controller"])) {
                    if (isset($source)) {
                        $router[$key]["source"]                                         = $source;
                    }

                    $controller                                                         = explode("::", $attr["controller"], 2);
                    $router[$key]["destination"]                                        = [
                                                                                            "obj"       => $controller[0],
                                                                                            "method"    => $controller[1] ?? "",
                                                                                            "params"    => $params
                                                                                        ];
                } elseif (isset($attr[self::SCHEMA_ENGINE]) && isset(self::$engine[$attr[self::SCHEMA_ENGINE]])) {
                    $router[$key]                                                       = self::$engine[$attr[self::SCHEMA_ENGINE]][self::SCHEMA_ROUTER];
                } elseif (!isset($router[$key])) {
                    $router[$key]                                                       = null;
                }

                if (!isset($attr["controller"])) {
                    $attr["controller"]                                                 = false;
                }

                if (isset($attr["priority"])) {
                    $router[$key]["priority"]                                           = $attr["priority"];
                    unset($attr["priority"]);
                }

                if (isset($attr[self::SCHEMA_ENGINE])
                    && isset(self::$engine[$attr[self::SCHEMA_ENGINE]])
                    && self::$engine[$attr[self::SCHEMA_ENGINE]]["properties"]
                ) {
                    $attr                                                               = array_replace(self::$engine[$attr[self::SCHEMA_ENGINE]]["properties"], $attr);
                }

                $request[$key]                                                          = $page;
                $request[$key][self::SCHEMA_CONF]                                       = $attr;
            }

            krsort($path2params);

            self::$config_data[self::SCHEMA_ROUTER][self::SCHEMA_PAGES]                 = $router;
            self::$config_data[self::SCHEMA_REQUEST]["path2params"]                     = $path2params;
            self::$config_data[self::SCHEMA_REQUEST][self::SCHEMA_PAGES]                = $request;
        }

        Debug::stopWatch(self::SCHEMA_CONF . "/" . self::SCHEMA_PAGES);
    }

    /**
     *
     */
    public static function loadEngine()
    {
        Debug::stopWatch(self::SCHEMA_CONF . "/" . self::SCHEMA_ENGINE);

        $schema                                                                         = array();
        $config                                                                         = static::rawData(self::SCHEMA_ENGINE, true);
        if (!empty($config)) {
            foreach ($config as $key => $engine) {
                $attr                                                                   = Dir::getXmlAttr($engine);
                if (isset($attr["source"])) {
                    $schema[$key][self::SCHEMA_ROUTER]["source"]                        = $attr["source"];
                }

                if (isset($attr["redirect"])) {
                    $schema[$key][self::SCHEMA_ROUTER]["destination"]                   = $attr["redirect"];
                    $schema[$key][self::SCHEMA_ROUTER]["redirect"]                      = 301;
                } elseif (isset($attr["path"])) {
                    $schema[$key][self::SCHEMA_ROUTER]["destination"]                   = $attr["path"];
                } else {
                    if (isset($attr["obj"])) {
                        $schema[$key][self::SCHEMA_ROUTER]["destination"]["obj"]        = $attr["obj"];
                    }
                    if (isset($attr["method"])) {
                        $schema[$key][self::SCHEMA_ROUTER]["destination"]["method"]     = $attr["method"];
                    }
                    if (isset($attr["params"])) {
                        $schema[$key][self::SCHEMA_ROUTER]["destination"]["params"]     = explode(",", $attr["params"]);
                    }
                }

                unset($attr["source"]);
                unset($attr["redirect"]);
                unset($attr["path"]);
                unset($attr["obj"]);
                unset($attr["method"]);
                unset($attr["params"]);

                if (isset($attr["priority"])) {
                    $schema[$key][self::SCHEMA_ROUTER]["priority"]                      = $attr["priority"];
                    unset($attr["priority"]);
                }

                $schema[$key]["properties"]                                             = $attr;
                $schema[$key]["properties"]["accept_path_info"]                         = true;
            }

            self::$engine                                                               = $schema;
        }

        Debug::stopWatch(self::SCHEMA_CONF . "/" . self::SCHEMA_ENGINE);
    }
}
