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

use phpformsframework\libs\cache\adapters\MemAdapter;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\AdapterManager;

/**
 * Class Mem
 * @package phpformsframework\libs\cache
 */
class Mem // apc | memcached | redis | globals
{
    use AdapterManager;

    private static $singletons                          = null;

    /**
     * @param string|null $bucket
     * @param bool $force
     * @param string|null $memAdapter
     * @return MemAdapter
     */
    public static function getInstance(string $bucket = null, bool $force = false, string $memAdapter = null) : MemAdapter
    {
        if (!$memAdapter) {
            $memAdapter                                 = Kernel::$Environment::CACHE_MEM_ADAPTER;
        }
        if (!$force) {
            $force                                      = Kernel::useCache();
        }

        if (!isset(self::$singletons[$memAdapter][$bucket])) {
            self::$singletons[$memAdapter][$bucket]     = self::loadAdapter($memAdapter, [$bucket, $force, $force]);
        }

        return self::$singletons[$memAdapter][$bucket];
    }
}
