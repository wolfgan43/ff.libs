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

if (!defined("FF_CACHE_MEMCACHED_SERVER")) define("FF_CACHE_MEMCACHED_SERVER", "localhost");
if (!defined("FF_CACHE_MEMCACHED_PORT")) define("FF_CACHE_MEMCACHED_PORT", 11211);

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
class ffCache_memcached extends ffCacheAdapter
{
    const SERVER            = FF_CACHE_MEMCACHED_SERVER;
    const PORT              = FF_CACHE_MEMCACHED_PORT;

	/**
	 * @var Memcached
	 */
	private $conn	= null;

	function __construct($auth = null)
	{
		$this->conn = new Memcached(ffCache::APPID);

		if($auth) {
            $this->conn->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $this->conn->addServer(ffCache_memcached::SERVER, ffCache_memcached::PORT);
            $this->conn->setSaslAuthData(ffCache::APPID, $auth);
        } else {
            $this->conn->addServer(ffCache_memcached::SERVER, ffCache_memcached::PORT);
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
    function set($name, $value = null, $bucket = ffCache::APPID)
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
	function get($name, $bucket = ffCache::APPID)
	{
        if($this::DISABLE_CACHE)
            return;

        if($name) {
            $res = $this->conn->get($this->getKey($name, $bucket));
            $res = ($this->conn->getResultCode() === Memcached::RES_SUCCESS
                ? $this->getValue($res)
                : false
            );
        } else {
	        $prefix = $this->getBucket($bucket);
	        if($prefix) {
                $keys = $this->getAllKeys();
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
    function del($name, $bucket = ffCache::APPID)
    {
        return $this->conn->delete($this->getKey($name, $bucket));
    }
	/**
	 * Pulisce la cache
	 * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
	 * Se non si specificano le relazioni, verrà cancellata l'intera cache
	 */
	function clear($bucket = ffCache::APPID)
	{
		// global reset
        $this->conn->flush();
	}
}
