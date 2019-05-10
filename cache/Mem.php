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

use phpformsframework\libs\cache\mem\Adapter;

if (!defined("FF_CACHE_ADAPTER"))                   { define("FF_CACHE_ADAPTER", false); }

class Mem // apc | memcached | redis | globals
{
    const NAME_SPACE                      = 'phpformsframework\\libs\\cache\\mem\\';
    const ADAPTER                           = FF_CACHE_ADAPTER;

    private static $singletons = null;


    /**
     * @param bool|string $memAdapter
     * @param null $auth
     * @return Adapter
     */
    public static function getInstance($memAdapter = self::ADAPTER, $auth = null)
    {
        if($memAdapter) {
            if (!isset(self::$singletons[$memAdapter])) {
                $class_name = static::NAME_SPACE . ucfirst($memAdapter);
                self::$singletons[$memAdapter] = new $class_name($auth);
            }
        } else {
            self::$singletons[$memAdapter] = new Mem();
        }

        return self::$singletons[$memAdapter];
    }

    public function set($name, $value = null, $bucket = null) {
        $name = ltrim($name, "/");

        return Globals::set($name, $value, $bucket);
    }
    public function get($name, $bucket = null) {
        $name = ltrim($name, "/");
        $res = null;
        if($name) {
            $res = Globals::get($name, $bucket);
        } else {
            $prefix = $bucket;
            $keys = Globals::getInstance();
            if(is_array($keys) && count($keys)) {
                foreach($keys AS $key => $value) {
                    if (strpos($key, $prefix) === 0) {
                        $real_key = substr($key, strlen($prefix));
                        $res[$real_key] = $value;
                    }
                }
            }
        }

        return $res;
    }
    public function del($name, $bucket = null) {
        $name = ltrim($name, "/");

        return Globals::del($name, $bucket);
    }
    public function clear($bucket = null) {
        return Globals::clear($bucket);
    }

}