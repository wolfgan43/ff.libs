<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs\cache\adapters;

use ff\libs\Debug;
use ff\libs\Dumpable;
use ff\libs\Kernel;

/**
 * Class BufferAdapter
 * @package ff\libs\cache\adapters
 */
abstract class BufferAdapter implements Dumpable
{
    private static $indexes         = null;

    protected const ACTION_GET      = "get";
    protected const ACTION_SET      = "set";
    protected const ACTION_DEL      = "del";
    protected const ACTION_CLEAR    = "clear";

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
     * @param string $name
     * @param string|null $bucket
     * @return mixed
     */
    abstract protected function load(string $name, string $bucket = null);

    /**
     * @param string $name
     * @param mixed $data
     * @param string|null $bucket
     * @return bool
     */
    abstract protected function write(string $name, $data, string $bucket = null) : bool;


    /**
     * @param string $name
     * @return string|null
     */
    abstract public function getInfo(string $name) : ?string;

    /**
     * BufferAdapter constructor.
     * @param string $bucket
     * @param bool $readable
     * @param bool $writeable
     */
    public function __construct(string $bucket, bool $readable = true, bool $writeable = true)
    {
        $this->appid        = Kernel::$Environment::APPID;
        $this->bucket       = $bucket;
        $this->is_readable  = $readable;
        $this->is_writeable = $writeable;
    }

    /**
     * @param String $name
     * @param Mixed|null $value
     * @param array|null $files_expire
     * @return bool
     * @todo da tipizzare
     */
    public function set(string $name, $value = null, array $files_expire = null) : bool
    {
        Debug::stopWatch("CacheSet" . DIRECTORY_SEPARATOR . $this->bucket . DIRECTORY_SEPARATOR . $name);

        $res = false;
        if ($value === null) {
            $res = $this->del($name);
        } elseif ($this->is_writeable) {
            $this->cache($this->bucket, self::ACTION_SET, $name);

            $res = $this->write($name, $value, $this->getBucket());

            $this->setIndex($name, $files_expire);
        } else {
            $this->clear();
        }

        Debug::stopWatch("CacheSet" . DIRECTORY_SEPARATOR . $this->bucket . DIRECTORY_SEPARATOR . $name);

        return $res;
    }

    /**
     * @param String $name il nome dell'elemento
     * @return Mixed l'elemento
     * @todo da tipizzare
     */
    public function get(string $name)
    {
        Debug::stopWatch("Cache" . DIRECTORY_SEPARATOR . $this->bucket . DIRECTORY_SEPARATOR . $name);

        $res = null;
        if ($this->is_readable || $this->checkIndex($name)) {
            $this->cache($this->bucket, self::ACTION_GET, $name);

            $res = $this->load($name, $this->getBucket());

            Debug::stopWatch("Cache" . DIRECTORY_SEPARATOR . $this->bucket . DIRECTORY_SEPARATOR . $name);
        }

        return (empty($res)
            ? null
            : $res
        );
    }

    /**
     * @param string $name
     * @return bool
     */
    public function del(string $name) : bool
    {
        $this->cache($this->bucket, self::ACTION_DEL, $name);

        return true;
    }

    /**
     *
     */
    public function clear() : void
    {
        $this->cache($this->bucket, self::ACTION_CLEAR);
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
     * @return string
     */
    protected function getBucket() : string
    {
        return $this->bucket;
    }

    /**
     * @param string $name
     * @return string
     */
    private function key(string $name) : string
    {
        return $this->bucket . DIRECTORY_SEPARATOR . $name;
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

    /**
     * @param string $name
     * @param array|null $indexes
     */
    protected function setIndex(string $name, array $indexes = null) : void
    {
        $key = $this->key($name);

        if ($indexes && $this->verifyIndexes($key, $indexes)) {
            self::$indexes[$key] = $indexes;

            $this->write("index", self::$indexes);
        }
    }

    /**
     * @param string $key
     * @param array $indexes
     * @return bool
     */
    private function verifyIndexes(string $key, array $indexes) : bool
    {
        return !isset(self::$indexes[$key]) || !empty(array_diff(self::$indexes[$key], $indexes));
    }

    /**
     * @param string $name
     * @return array
     */
    private function &getIndex(string $name) : ?array
    {
        if (!self::$indexes) {
            self::$indexes          = $this->load("index");
        }

        $key                        = $this->key($name);

        return self::$indexes[$key];
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function checkIndex(string $name) : bool
    {
        Debug::stopWatch("CacheIndex" . DIRECTORY_SEPARATOR . $this->bucket . DIRECTORY_SEPARATOR . $name);

        $cached                     = true;
        $indexes                    =& $this->getIndex($name);
        if (!empty($indexes)) {
            foreach ($indexes as $file_index => &$last_update) {
                if (!file_exists($file_index)) {
                    $cached         = false;
                    continue;
                }
                $file_mtime         = @filemtime($file_index);
                if ($file_mtime > $last_update) {
                    $last_update    = $file_mtime;
                    $cached         = false;
                }
            }

            if (!$cached) {
                $this->write("index", self::$indexes);
            }
        }
        Debug::stopWatch("CacheIndex" . DIRECTORY_SEPARATOR . $this->bucket . DIRECTORY_SEPARATOR . $name);

        return $cached;
    }
}
