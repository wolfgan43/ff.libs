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

/**
 * @ignore
 * @package FormsFramework
 */

/**
 * @ignore
 * @package FormsFramework
 */
define("FF_DISABLE_CACHE", defined("DEBUG_MODE") && isset($_REQUEST["__nocache__"]));


abstract class ffCacheAdapter
{
    const DISABLE_CACHE                                             = FF_DISABLE_CACHE;

    private $ttl            = ffCache::TTL;

	public abstract function set($name, $value = null, $bucket = ffCache::APPID);
	public abstract function get($name, $bucket = ffCache::APPID);
    public abstract function del($name, $bucket = ffCache::APPID);
	public abstract function clear($bucket = ffCache::APPID);


	protected function setTTL($val) {
	    $this->ttl = $val;

    }
    protected function getTTL() {
        return $this->ttl;
    }

    protected function getBucket($name = null) {
	    return ($name
            ? (substr($name, 0, 1) == "/"
                ? ffCache::APPID
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
            switch (ffCache::SERIALIZER) {
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
        switch (ffCache::SERIALIZER) {
            case "PHP":
                $data = unserialize($value);
                break;
            case "JSON":
                $data = json_decode($value);
                break;
            case "IGBINARY":
                break;
            default:
        }
        return ($data === false
            ? $value
            : $data
        );
    }

}
