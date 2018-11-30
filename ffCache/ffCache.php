<?php
/**
 * @ignore
 * @package FormsFramework
 */

/**
 * @ignore
 * @package FormsFramework
 */
if (!defined("FF_PHP_EXT"))                         define("FF_PHP_EXT", "php");
if (!defined("FF_CACHE_ADAPTER"))                   define("FF_CACHE_ADAPTER", false);
if (!defined("FF_CACHE_SERIALIZER"))                define("FF_CACHE_SERIALIZER", "PHP");
if (!defined("APPID"))                              define("APPID", $_SERVER["HTTP_HOST"]);


class ffCache // apc | memcached | redis | globals
{
    const ADAPTER                   = FF_CACHE_ADAPTER;
    const SERIALIZER                = FF_CACHE_SERIALIZER;
    const TTL                       = 0;
    const APPID                     = APPID;


    private static $singletons = null;
	
	/**
	 *
	 * @param type $eType
	 * @return ffCacheAdapter
	 */
	static public function getInstance($eType = ffCache::ADAPTER, $auth = null)
    {
        if($eType) {
            if (!isset(self::$singletons[$eType])) {
                require_once("adapters/" . $eType . "." . FF_PHP_EXT);
                $classname = "ffCache_" . $eType;
                self::$singletons[$eType] = new $classname($auth);
            }
        } else {
            self::$singletons[$eType] = new ffCache();
        }

        return self::$singletons[$eType];
    }

	private function __construct()
	{
	}

    public function set($name, $value = null, $bucket = null) {
        $name = ltrim($name, "/");

        return ffGlobals::set($name, $value, $bucket);
    }
    public function get($name, $bucket = null) {
        $name = ltrim($name, "/");

        if($name) {
            $res = ffGlobals::get($name, $bucket);
        } else {
	        $prefix = $bucket;
	        $keys = ffGlobals::getInstance();
	        if(is_array($keys) && count($keys)) {
	            foreach($keys AS $key => $value) {
                    if (strpos($key, $prefix) === 0) {
                        $real_key = substr($key, strlen($prefix));
                        $res[$real_key] = $value;
                    }
                }
            }
        }

        return $res;
    }
    public function del($name, $bucket = null) {
        $name = ltrim($name, "/");

        return ffGlobals::del($name, $bucket);
    }
    public function clear($bucket = null) {
        return ffGlobals::clear($bucket);
    }

}
