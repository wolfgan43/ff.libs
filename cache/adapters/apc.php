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
use phpformsframework\libs\Debug;
use APCIterator;

class memApc extends memAdapter
{
	function __construct($auth = null)
	{
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
            : @apc_store($this->getKey($name, $bucket), $this->setValue($value), $this->getTTL())
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
        if(Debug::ACTIVE) {
            return null;
        }
        $res = null;
        if($name) {
            $success = null;
            $res = apc_fetch($this->getKey($name, $bucket), $success);
            $res = ($success
                ? $this->getValue($res)
                : false
            );
        } else {
	        $prefix = $this->getBucket($bucket);
            foreach (new APCIterator('user', '#^' . preg_quote($prefix) . '\.#') as $item) {
                $res[$item["key"]] = $item["value"];
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
        return @apc_delete($this->getKey($name, $bucket));
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
        apc_clear_cache("user");
	}
}
