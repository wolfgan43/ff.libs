<?php
/**
 * shared memory cache
 * 
 * @package FormsFramework
 * @subpackage base
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright (c) 2004-2017, Samuele Diella
 * @license https://opensource.org/licenses/LGPL-3.0
 * @link http://www.formsphpframework.com
 */

/**
 * shared memory cache
 *
 * @package FormsFramework
 * @subpackage base
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright (c) 2004-2017, Samuele Diella
 * @license https://opensource.org/licenses/LGPL-3.0
 * @link http://www.formsphpframework.com
 */
class ffCache_apc extends ffCacheAdapterAdapter
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
	function set($name, $value = null, $bucket = ffCache::APPID)
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
	function get($name, $bucket = ffCache::APPID)
	{
        if($this::DISABLE_CACHE)
            return;

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
    function del($name, $bucket = ffCache::APPID)
    {
        return @apc_delete($this->getKey($name, $bucket));
    }
	/**
	 * Pulisce la cache
	 * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
	 * Se non si specificano le relazioni, verrà cancellata l'intera cache
	 */
	function clear($bucket = ffCache::APPID)
	{
        // global reset
        apc_clear_cache("user");
	}
}
