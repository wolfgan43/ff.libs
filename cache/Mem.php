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

if (!defined("FF_PHP_EXT"))                         define("FF_PHP_EXT", "php");
if (!defined("FF_CACHE_ADAPTER"))                   define("FF_CACHE_ADAPTER", false);
if (!defined("FF_CACHE_SERIALIZER"))                define("FF_CACHE_SERIALIZER", "PHP");
if (!defined("APPID"))                              define("APPID", $_SERVER["HTTP_HOST"]);

abstract class memAdapter
{
    const SERIALIZER                = FF_CACHE_SERIALIZER;
    const TTL                       = 0;
    const APPID                     = APPID;

    private $ttl                    = self::TTL;

	public abstract function set($name, $value = null, $bucket = self::APPID);
	public abstract function get($name, $bucket = self::APPID);
    public abstract function del($name, $bucket = self::APPID);
	public abstract function clear($bucket = self::APPID);


	protected function setTTL($val) {
	    $this->ttl = $val;

    }
    protected function getTTL() {
        return $this->ttl;
    }

    protected function getBucket($name = null) {
	    return ($name
            ? (substr($name, 0, 1) == "/"
                ? self::APPID
                : ""
            ) . $name
            : ""
        );
    }
    protected function getKey($name, $bucket = null) {
        return ($bucket
            ? $this->getBucket($bucket) . "/" . ltrim($name, "/")
            : $name
        );
    }

    protected function setValue($value) {
        if(is_array($value)) {
            switch (self::SERIALIZER) {
                case "PHP":
                    $value = serialize($value);
                    break;
                case "JSON":
                    $value = json_encode($value);
                    break;
                case "IGBINARY":
                    break;
                default:
            }
        }
	    return $value;
    }
    protected function getValue($value) {
        switch (self::SERIALIZER) {
            case "PHP":
                $data = unserialize($value);
                break;
            case "JSON":
                $data = json_decode($value);
                break;
            case "IGBINARY":
                $data = null;
                break;
            default:
                $data = null;
        }
        return ($data === false
            ? $value
            : $data
        );
    }

}

class Mem // apc | memcached | redis | globals
{
    const TYPE                      = 'phpformsframework\\libs\\cache\\mem';
    const ADAPTER                   = FF_CACHE_ADAPTER;

    private static $singletons = null;


    /**
     * @param bool|string $memAdapter
     * @param null $auth
     * @return memAdapter
     */
    public static function getInstance($memAdapter = self::ADAPTER, $auth = null)
    {
        if($memAdapter) {
            if (!isset(self::$singletons[$memAdapter])) {
                $class_name = self::TYPE . ucfirst($memAdapter);
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