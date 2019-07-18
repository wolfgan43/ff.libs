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

use phpformsframework\libs\cache\adapters\MemGlobal;
use phpformsframework\libs\Constant;

class Mem // apc | memcached | redis | globals
{
    const NAME_SPACE                      = __NAMESPACE__ . '\\adapters\\';

    private static $singletons = null;

    /**
     * @param bool|string $memAdapter
     * @param null|string $bucket
     * @return MemAdapter
     */
    public static function getInstance($bucket = null, $memAdapter = Constant::CACHE_MEM)
    {
        if (!isset(self::$singletons[$memAdapter][$bucket])) {
            if(!$memAdapter) {
                $memAdapter = "Global";
            }
            $class_name                 = static::NAME_SPACE . "Mem" . ucfirst($memAdapter);
            self::$singletons[$memAdapter][$bucket] = new $class_name($bucket);
        }

        return self::$singletons[$memAdapter][$bucket];
    }
}