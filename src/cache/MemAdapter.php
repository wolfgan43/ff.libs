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

use phpformsframework\libs\Constant;

abstract class MemAdapter
{
    public static $serializer       = "PHP";
    public static $dump             = array();

    private $ttl                    = 0;
    private $bucket                 = null;

    public static function dump()
    {
        return self::$dump;
    }

    abstract public function set($name, $value = null, $bucket = null);
    abstract public function get($name, $bucket = null);
    abstract public function del($name, $bucket = null);
    abstract public function clear($bucket = null);

    public function __construct($bucket = null)
    {
        $this->bucket = Constant::APPID . "/" . $bucket;
    }

    private function cache($bucket, $action, $name = "*")
    {
        self::$dump[$action . " => " . $bucket . " => " . $name] = (
            isset(self::$dump[$action . " => " . $bucket . " => " . $name])
            ? self::$dump[$action . " => " . $bucket . " => " . $name] + 1
            : 1
        );
    }

    protected function setTTL($val)
    {
        $this->ttl = $val;
    }
    protected function getTTL()
    {
        return $this->ttl;
    }

    protected function getBucket($name = null)
    {
        return ($name
            ? $name
            : $this->bucket
        );
    }
    protected function getKey($action, &$bucket, &$name = null)
    {
        $bucket = $this->getBucket($bucket);
        $name = ltrim($name, "/");

        $this->cache($bucket, $action, $name);

        return ($bucket
            ? $bucket . "/" . $name
            : $name
        );
    }

    protected function setValue($value)
    {
        if (is_array($value)) {
            switch (static::$serializer) {
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
    protected function getValue($value)
    {
        switch (static::$serializer) {
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
