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
namespace ff\libs\gui;

use ff\libs\Autoloader;
use ff\libs\cache\Buffer;
use ff\libs\Config;
use ff\libs\Constant;
use ff\libs\Debug;
use ff\libs\Dumpable;
use ff\libs\Mappable;
use ff\libs\storage\FilemanagerScan;
use ff\libs\util\ServerManager;
use ff\libs\Exception;

/**
 * Class Resource
 * @package ff\libs\gui
 */
class Resource extends Mappable implements Dumpable
{
    use ServerManager;

    const ERROR_BUCKET                          = "resource";

    public const TYPE_ASSET_CSS                 = Constant::RESOURCE_ASSET_CSS;
    public const TYPE_ASSET_JS                  = Constant::RESOURCE_ASSET_JS;
    public const TYPE_ASSET_FONTS               = Constant::RESOURCE_ASSET_FONTS;
    public const TYPE_ASSET_IMAGES              = Constant::RESOURCE_ASSET_IMAGES;

    public const TYPE_NOTICE                    = Constant::RESOURCE_NOTICE;
    public const TYPE_LAYOUTS                   = Constant::RESOURCE_LAYOUTS;
    public const TYPE_VIEWS                     = Constant::RESOURCE_VIEWS;
    public const TYPE_CONTROLLERS               = Constant::RESOURCE_CONTROLLERS;
    public const TYPE_WIDGETS                   = Constant::RESOURCE_WIDGETS;
    public const TYPE_COMPONENTS                = "components";

    /**
     * @var Resource
     */
    private static $singleton                   = null;
    private static $exTime                      = 0;

    protected $rules                            = null;
    private $resources                          = [];

    /**
     * @return array
     */
    public static function dump() : array
    {
        if (!self::$singleton) {
            self::$singleton = new Resource();
        }

        return self::$singleton->resources;
    }

    /**
     * @return float
     */
    public static function exTime() : float
    {
        return self::$exTime;
    }

    /**
     * @param string|null $widget
     * @return array
     */
    public static function &views(string $widget = null) : array
    {
        if (!self::$singleton) {
            self::$singleton = new Resource();
        }

        if ($widget) {
            return self::$singleton->resources[self::TYPE_WIDGETS][$widget]["tpl"];
        } else {
            return self::$singleton->resources[self::TYPE_VIEWS];
        }
    }

    /**
     * @return Controller[]
     */
    public static function &components() : array
    {
        if (!self::$singleton) {
            self::$singleton = new Resource();
        }

        return self::$singleton->resources[self::TYPE_COMPONENTS];
    }

    /**
     * @param string $type
     * @return array
     */
    public static function type(string $type) : array
    {
        if (!self::$singleton) {
            self::$singleton = new Resource();
        }

        return self::$singleton->resources[$type] ?? [];
    }

    /**
     * @param string $name
     * @param string $type
     * @return string|array|null
     * @todo da tipizzare
     */
    public static function cascading(string $name, string $type)
    {
        if (!self::$singleton) {
            self::$singleton                    = new Resource();
        }

        $file                                   = null;
        $pathinfo                               = self::pathinfo();
        while ($pathinfo != DIRECTORY_SEPARATOR) {
            if (isset(self::$singleton->resources[$type][$name . str_replace(DIRECTORY_SEPARATOR, "_", $pathinfo)])) {
                $file                           = self::$singleton->resources[$type][$name . str_replace(DIRECTORY_SEPARATOR, "_", $pathinfo)];
                break;
            }

            $pathinfo                           = dirname($pathinfo);
        }

        if (!$file && isset(self::$singleton->resources[$type][$name])) {
            $file                               = self::$singleton->resources[$type][$name];
        }

        return $file;
    }

    /**
     * @param string $name
     * @param string $type
     * @return string|null
     */
    public static function get(string $name, string $type) : ?string
    {
        if (!self::$singleton) {
            self::$singleton = new Resource();
        }

        return self::$singleton->resources[$type][$name] ?? null;
    }

    public static function image(string $name) : ?string
    {
        if (!self::$singleton) {
            self::$singleton = new Resource();
        }

        return self::$singleton->resources[self::TYPE_ASSET_IMAGES][$name] ?? null;
    }

    /**
     * @param string $name
     * @return array
     */
    public static function widget(string $name) : array
    {
        if (!self::$singleton) {
            self::$singleton = new Resource();
        }
        return self::$singleton->resources[self::TYPE_WIDGETS][$name] ?? [];
    }

    /**
     * Resource constructor.
     * @param string $map_name
     * @throws Exception
     */
    public function __construct(string $map_name = "default")
    {
        Debug::stopWatch("resource/loadResources");

        $cache                                  = Buffer::cache("resource");
        $this->resources                        = $cache->get("rawdata");
        if (!$this->resources) {
            parent::__construct($map_name);

            $patterns                           = Config::getScans($this->rules);

            $this->resources                    = array_replace([
                                                    self::TYPE_ASSET_CSS        => [],
                                                    self::TYPE_ASSET_JS         => [],
                                                    self::TYPE_ASSET_FONTS      => [],
                                                    self::TYPE_ASSET_IMAGES     => [],
                                                    self::TYPE_NOTICE           => [],
                                                    self::TYPE_LAYOUTS          => [],
                                                    self::TYPE_VIEWS            => [],
                                                    self::TYPE_CONTROLLERS      => [],
                                                    self::TYPE_WIDGETS          => [],
                                                    self::TYPE_COMPONENTS       => []
                                                ], FilemanagerScan::scan($patterns));

            $map_file                           = Config::getFileMap(self::ERROR_BUCKET, $map_name);
            $config_dirs                        = [
                $map_file => filemtime($map_file)
            ];

            $dirs                               = [];
            $last_path                          = null;
            $paths                              = array_keys($patterns);
            sort($paths);

            foreach ($paths as $path) {
                if (!$last_path || strpos($path, $last_path) === false) {
                    $path                       = rtrim($path, DIRECTORY_SEPARATOR . "*") . "*";
                    $dirs[$path]                = ["flag" => FilemanagerScan::SCAN_DIR_RECURSIVE];
                    $last_path                  = $path;

                    if (is_dir(Constant::DISK_PATH . $path)) {
                        $config_dirs[Constant::DISK_PATH . $path] = filemtime(Constant::DISK_PATH . $path);
                    }
                }
            }

            FilemanagerScan::scan($dirs, function ($path) use (&$config_dirs) {
                $config_dirs[$path]             = filemtime($path);
            });

            if (!empty($this->resources[self::TYPE_CONTROLLERS])) {
                $this->resources[self::TYPE_COMPONENTS] = Autoloader::includes2Classes($this->resources[self::TYPE_CONTROLLERS]);
            }

            $cache->set("rawdata", $this->resources, $config_dirs);
        }

        self::$exTime = Debug::stopWatch("resource/loadResources");
    }
}
