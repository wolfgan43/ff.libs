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
namespace phpformsframework\libs\cache\mem;

use phpformsframework\libs\cache\MemAdapter;
use Memcached AS MC;

if (!defined("FF_CACHE_MEMCACHED_SERVER"))  { define("FF_CACHE_MEMCACHED_SERVER", "localhost"); }
if (!defined("FF_CACHE_MEMCACHED_PORT"))    { define("FF_CACHE_MEMCACHED_PORT", 11211); }

class Memcached extends MemAdapter {
    const SERVER            = FF_CACHE_MEMCACHED_SERVER;
    const PORT              = FF_CACHE_MEMCACHED_PORT;

	private $conn	= null;

	function __construct($auth = null)
	{
		$this->conn = new MC(self::APPID);

		if($auth) {
            $this->conn->setOption(MC::OPT_BINARY_PROTOCOL, true);
            $this->conn->addServer($this::SERVER, $this::PORT);
            $this->conn->setSaslAuthData(self::APPID, $auth);
        } else {
            $this->conn->addServer($this::SERVER, $this::PORT);
        }
	}

	/**
	 * Inserisce un elemento nella cache
	 * Oltre ai parametri indicati, accetta un numero indefinito di chiavi per relazione i valori memorizzati
	 * @param String $name il nome dell'elemento
	 * @param Mixed $value l'elemento
     * @param String $bucket il name space
     * @return bool if storing both value and rel table will success
	 */
    function set($name, $value = null, $bucket = self::APPID)
    {
        return ($value === null
            ? $this->del($name, $bucket)
            : $this->conn->set($this->getKey($name, $bucket), $this->setValue($value), $this->getTTL())
        );
    }

	/**
	 * Recupera un elemento dalla cache
	 * @param String $name il nome dell'elemento
     * @param String $bucket il name space
	 * @return Mixed l'elemento 
	 */
	function get($name, $bucket = self::APPID)
	{
        $res = null;
        if($name) {
            $res = $this->conn->get($this->getKey($name, $bucket));
            $res = ($this->conn->getResultCode() === MC::RES_SUCCESS
                ? $this->getValue($res)
                : false
            );
        } else {
	        $prefix = $this->getBucket($bucket);
	        if($prefix) {
                $keys = $this->conn->getAllKeys();
                if (is_array($keys) && count($keys)) {
                    foreach ($keys AS $key) {
                        if (strpos($key, $prefix) === 0) {
                            $real_key = substr($key, strlen($prefix));
                            $res[$real_key] = $this->get($real_key, $bucket);
                        }
                    }
                }
            }
        }

        return $res;
	}

    /**
     * Cancella una variabile
     * @param String $name il nome dell'elemento
     * @param String $bucket il name space
     * @return bool
     */
    function del($name, $bucket = self::APPID)
    {
        return $this->conn->delete($this->getKey($name, $bucket));
    }
	/**
	 * Pulisce la cache
	 * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
	 * Se non si specificano le relazioni, verrà cancellata l'intera cache
     * @param string $bucket
	 */
	function clear($bucket = self::APPID)
	{
		// global reset
        $this->conn->flush();
	}
}
