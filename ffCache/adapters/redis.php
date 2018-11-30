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

if (!defined("FF_CACHE_REDIS_SERVER")) define("FF_CACHE_REDIS_SERVER", "127.0.0.1");
if (!defined("FF_CACHE_REDIS_PORT")) define("FF_CACHE_REDIS_PORT", 6379);


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
class ffCache_redis extends ffCacheAdapter
{
    const SERVER            = FF_CACHE_REDIS_SERVER;
    const PORT              = FF_CACHE_REDIS_PORT;

	/**
	 * @var Memcached
	 */
	private $conn	= null;

	function __construct($auth = null)
	{
        $this->conn = new Redis();
        $this->conn->pconnect(ffCache_redis::SERVER, ffCache_redis::PORT, $this->getTTL(), ffCache::APPID); // x is sent as persistent_id and would be another connection than the three before.
        if($auth)
            $this->conn->auth($auth);

        switch (ffCache::SERIALIZER) {
            case "PHP":
                $this->conn->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);	// use built-in serialize/unserialize
                break;
            case "IGBINARY":
                $this->conn->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);	// use igBinary serialize/unserialize
                break;
            default:
                $this->conn->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);	// don't serialize data

        }


        //$this->conn->setOption(Redis::OPT_PREFIX, ffCache::APPID . ':');	// use custom prefix on all keys


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
        $name = ltrim($name, "/");
        return ($value === null
            ? $this->del($name, $bucket)
            : ($bucket
                ? $this->conn->hSet($this->getBucket($bucket), $name, $value)
                : $this->conn->set($name, $value)
            )
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

        $name = ltrim($name, "/");
        return ($bucket
            ? ($name
                ? $this->conn->hGet($this->getBucket($bucket), $name)
                : $this->conn->hGetAll($this->getBucket($bucket))
            )
            : $this->conn->get($name)
        );
    }

    /**
     * Cancella una variabile
     * @param String $name il nome dell'elemento
     * @param String $bucket il name space
     * @return bool
     */
    function del($name, $bucket = ffCache::APPID)
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
     */
    function clear($bucket = ffCache::APPID)
    {
        // global reset
        if($bucket)
            $this->conn->delete($this->getBucket($bucket));
        else
            $this->conn->flushDb();

    }
}
