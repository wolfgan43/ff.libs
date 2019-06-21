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

use phpformsframework\libs\cache\MemAdapter;
use phpformsframework\libs\Constant;
use Redis AS MC;

if (!defined("FF_CACHE_REDIS_SERVER"))  { define("FF_CACHE_REDIS_SERVER", "127.0.0.1"); }
if (!defined("FF_CACHE_REDIS_PORT"))    { define("FF_CACHE_REDIS_PORT", 6379); }

class MemRedis extends MemAdapter {
    const SERVER            = FF_CACHE_REDIS_SERVER;
    const PORT              = FF_CACHE_REDIS_PORT;

	private $conn	= null;

	function __construct($auth = null)
	{
        $this->conn = new MC();
        $this->conn->pconnect($this::SERVER, $this::PORT, $this->getTTL(), Constant::APPID); // x is sent as persistent_id and would be another connection than the three before.
        if($auth) {
            $this->conn->auth($auth);
        }
        switch (Constant::CACHE_SERIALIZER) {
            case "PHP":
                $this->conn->setOption(MC::OPT_SERIALIZER, MC::SERIALIZER_PHP);	// use built-in serialize/unserialize
                break;
            case "IGBINARY":
                $this->conn->setOption(MC::OPT_SERIALIZER, MC::SERIALIZER_IGBINARY);	// use igBinary serialize/unserialize
                break;
            default:
                $this->conn->setOption(MC::OPT_SERIALIZER, MC::SERIALIZER_NONE);	// don't serialize data

        }


        //$this->conn->setOption(MC::OPT_PREFIX, self::APPID . ':');	// use custom prefix on all keys


    }

    /**
     * Inserisce un elemento nella cache
     * Oltre ai parametri indicati, accetta un numero indefinito di chiavi per relazione i valori memorizzati
     * @param String $name il nome dell'elemento
     * @param Mixed $value l'elemento
     * @param String $bucket il name space
     * @return bool if storing both value and rel table will success
     */
    function set($name, $value = null, $bucket = Constant::APPID)
    {
        $name = ltrim($name, "/");
        if($value === null) {
            $res = $this->del($name, $bucket);
        } else {
            $res = ($bucket
                ? $this->conn->hSet($this->getBucket($bucket), $name, $value)
                : $this->conn->set($name, $value)
            );
        }

        return $res;
    }

    /**
     * Recupera un elemento dalla cache
     * @param String $name il nome dell'elemento
     * @param String $bucket il name space
     * @return Mixed l'elemento
     */
    function get($name, $bucket = Constant::APPID)
    {
        $name = ltrim($name, "/");
        if($bucket) {
            $res = ($name
                ? $this->conn->hGet($this->getBucket($bucket), $name)
                : $this->conn->hGetAll($this->getBucket($bucket))
            );
        } else {
            $res =  $this->conn->get($name);
        }

        return $res;
    }

    /**
     * Cancella una variabile
     * @param String $name il nome dell'elemento
     * @param String $bucket il name space
     * @return bool
     */
    function del($name, $bucket = Constant::APPID)
    {
        $name = ltrim($name, "/");
        return ($bucket
            ? $this->conn->hDel($this->getBucket($bucket), $name)
            : $this->conn->delete($name)
        );
    }
    /**
     * Pulisce la cache
     * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
     * Se non si specificano le relazioni, verrà cancellata l'intera cache
     * @param string $bucket
     */
    function clear($bucket = Constant::APPID)
    {
        // global reset
        if($bucket) {
            $this->conn->delete($this->getBucket($bucket));
        } else {
            $this->conn->flushDb();
        }
    }
}
