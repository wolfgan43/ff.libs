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

namespace phpformsframework\libs\tpl;

use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Config;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Debug;
use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Error;
use phpformsframework\libs\Mappable;
use phpformsframework\libs\Request;
use phpformsframework\libs\storage\Filemanager;
use Exception;

/**
 * Class Resource
 * @package phpformsframework\libs\tpl
 */
class Resource extends Mappable implements Dumpable
{
    const ERROR_BUCKET                          = "resource";
    /**
     * @var Resource
     */
    private static $singleton                   = null;

    protected $rules                            = null;
    private $resources                          = null;

    /**
     * Resource constructor.
     * @param string $map_name
     */
    public function __construct(string $map_name = "default")
    {
        parent::__construct($map_name);

        $this->loadResources();
    }

    /**
     * @return array
     */
    public static function dump() : array
    {
        if (!self::$singleton) {
            self::$singleton = new Resource();
        }

        return (array) self::$singleton->resources;
    }

    /**
     * @param array|null $excludeDirname
     */
    private function loadResources(array $excludeDirname = null) : void
    {
        Debug::stopWatch("resource/loadResources");

        $cache                                  = Buffer::cache("resource");
        $this->resources                        = $cache->get("rawdata");
        if (!$this->resources) {
            $patterns                           = Config::getScans($this->rules);
            Filemanager::scanExclude($excludeDirname);
            $this->resources                    = Filemanager::scan($patterns);

            $config_dirs                        = [];
            $dirs                               = [];
            $last_path                          = null;
            $paths                              = array_keys($patterns);
            sort($paths);

            foreach ($paths as $path) {
                if (strpos($path, $last_path) === false) {
                    $path                       = dirname(rtrim($path, "\\*"));
                    $dirs[$path]                = ["flag" => Filemanager::SCAN_DIR_RECURSIVE];
                    $last_path                  = $path;

                    if (is_dir(Constant::DISK_PATH . $path)) {
                        $config_dirs[Constant::DISK_PATH . $path] = filemtime(Constant::DISK_PATH . $path);
                    }
                }
            }

            Filemanager::scan($dirs, function ($path) use (&$config_dirs) {
                $config_dirs[$path]             = filemtime($path);
            });

            $cache->set("rawdata", $this->resources, $config_dirs);
        }

        Debug::stopWatch("resource/loadResources");
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
        //  echo "type " . $type . "<br>\n";

        return (isset(self::$singleton->resources[$type])
            ? self::$singleton->resources[$type]
            : array()
        );
    }

    /**
     * @param string $name
     * @param string $type
     * @return string|null
     */
    public static function get(string $name, string $type) : ?string
    {
        if (!self::$singleton) {
            self::$singleton                    = new Resource();
        }
        //echo "get " . $name . "type " . $type . "<br>\n";

        $file                                   = null;
        $pathinfo                               = Request::pathinfo();
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
     * @return DataHtml
     */
    public static function widget(string $name) : DataHtml
    {
        if (!self::$singleton) {
            self::$singleton = new Resource();
        }
        //echo "widget " . $name . "<br>\n";
        return new DataHtml(self::$singleton->resources["widget"][$name]);
    }

    /**
     * @param string $name
     * @param string $type
     * @return string|null
     * @throws Exception
     */
    public static function load(string $name, string $type) : ?string
    {
        $path                                   = self::get($name, $type);
        if ($path) {
            return Filemanager::fileGetContent($path);
        } else {
            Error::register("Layout not Found: " . $name, static::ERROR_BUCKET);
        }

        return null;
    }
}
