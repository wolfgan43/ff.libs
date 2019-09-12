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

namespace phpformsframework\libs\cache;

use phpformsframework\libs\Error;
use phpformsframework\libs\Kernel;

class Mem // apc | memcached | redis | globals
{
    const NAME_SPACE                                    = __NAMESPACE__ . '\\adapters\\';

    private static $singletons                          = null;

    /**
     * @param bool|string $memAdapter
     * @param bool $force
     * @param null|string $bucket
     * @return MemAdapter
     */
    public static function getInstance($bucket = null, $force = false, $memAdapter = null)
    {
        if (!$memAdapter) {
            $memAdapter                                 = Kernel::$Environment::CACHE_MEM_ADAPTER;
        }
        if (!$force) {
            $force                                      = Kernel::useCache();
        }

        if (!isset(self::$singletons[$memAdapter][$bucket])) {
            $class_name                                 = static::NAME_SPACE . "Mem" . ucfirst($memAdapter);
            if (class_exists($class_name)) {
                self::$singletons[$memAdapter][$bucket] = new $class_name($bucket, $force, $force);
            } else {
                Error::register("Cache Mem Adapter not supported: " . $memAdapter);
            }
        }

        return self::$singletons[$memAdapter][$bucket];
    }
}