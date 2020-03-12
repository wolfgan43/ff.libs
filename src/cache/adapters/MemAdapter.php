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
namespace phpformsframework\libs\cache\adapters;

use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Kernel;

/**
 * Class MemAdapter
 * @package phpformsframework\libs\cache\adapters
 */
abstract class MemAdapter implements Dumpable
{
    protected $appid                = null;

    public static $serializer       = "PHP";
    public static $dump             = array();

    private $ttl                    = 0;
    private $bucket                 = null;
    protected $is_readable          = true;
    protected $is_writeable         = true;

    /**
     * @return array
     */
    public static function dump() : array
    {
        return self::$dump;
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @param null $value
     * @param string|null $bucket
     * @return bool
     */
    abstract public function set(string $name, $value = null, string $bucket = null) : bool;

    /**
     * @todo da tipizzare
     * @param string $name
     * @param string|null $bucket
     * @return mixed
     */
    abstract public function get(string $name, string $bucket = null);

    /**
     * @param string $name
     * @param string|null $bucket
     * @return bool
     */
    abstract public function del(string $name, string $bucket = null) : bool;

    /**
     * @param string|null $bucket
     */
    abstract public function clear(string $bucket = null) : void;

    /**
     * MemAdapter constructor.
     * @param string|null $bucket
     * @param bool $readable
     * @param bool $writeable
     */
    public function __construct(string $bucket = null, bool $readable = true, bool $writeable = true)
    {
        $this->appid        = Kernel::$Environment::APPID;
        $this->bucket       = $bucket;
        $this->is_readable  = $readable;
        $this->is_writeable = $writeable;
    }

    /**
     * @param string $bucket
     * @param string $action
     * @param string $name
     */
    private function cache(string $bucket, string $action, string $name = "*") : void
    {
        self::$dump[$action . " => " . $bucket . " => " . $name] = (
            isset(self::$dump[$action . " => " . $bucket . " => " . $name])
            ? self::$dump[$action . " => " . $bucket . " => " . $name] + 1
            : 1
        );
    }

    /**
     * @param int $val
     */
    protected function setTTL(int $val) : void
    {
        $this->ttl = $val;
    }

    /**
     * @return int
     */
    protected function getTTL() : int
    {
        return $this->ttl;
    }

    /**
     * @param string|null $name
     * @return string
     */
    protected function getBucket(string $name = null) : string
    {
        return ($name
            ? $name
            : $this->bucket
        );
    }

    /**
     * @param string $action
     * @param string|null $bucket
     * @param string|null $name
     * @return string
     */
    protected function getKey(string $action, string &$bucket = null, string &$name = null) : string
    {
        $bucket = $this->getBucket($bucket);
        $name = ltrim($name, "/");

        $this->cache($bucket, $action, $name);

        return ($bucket
            ? $bucket . "/" . $name
            : $name
        );
    }

    /**
     * @todo da tipizzare
     * @param $value
     * @return string
     */
    protected function setValue($value) : string
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
        return (string) $value;
    }

    /**
     * @todo da tipizzare
     * @param string $value
     * @return mixed|null
     */
    protected function getValue(string $value)
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
        return $data;
    }
}
