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

use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\Config;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Mappable;
use phpformsframework\libs\Request;
use phpformsframework\libs\storage\Filemanager;

class Resource extends Mappable implements Dumpable
{
    /**
     * @var Resource
     */
    private static $singleton                   = null;

    protected $rules                            = null;
    private $resources                          = null;

    public function __construct($map_name)
    {
        parent::__construct($map_name);

        $this->loadResources();
    }

    public static function dump()
    {
        return self::$singleton->resources;
    }

    private function loadResources($excludeDirname = null)
    {
        Debug::stopWatch("resource/loadResources");

        $cache = Mem::getInstance("resource");
        $this->resources = $cache->get("rawdata");
        if (!$this->resources) {
            $patterns                           = Config::getScans($this->rules);
            Filemanager::scanExclude($excludeDirname);
            $this->resources                    = Filemanager::scan($patterns);

            $cache->set("rawdata", $this->resources);
        }

        Debug::stopWatch("resource/loadResources");
    }
    public static function type($type, $rule_name = "default")
    {
        if (!self::$singleton) {
            self::$singleton = new Resource($rule_name);
        }

        return (isset(self::$singleton->resources[$type])
            ? self::$singleton->resources[$type]
            : array()
        );
    }
    public static function get($name, $type, $rule_name = "default")
    {
        if (!self::$singleton) {
            self::$singleton = new Resource($rule_name);
        }


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
}
