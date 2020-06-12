<?php
namespace phpformsframework\libs\cache;

use phpformsframework\libs\App;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Log;
use stdClass;

/**
 * Class Buffer
 * @package phpformsframework\libs\cache
 */
class Buffer implements Dumpable
{
    private const SEP                                   = "/";
    private const SEP_EXTIME                            = "exTime - ";
    private const SYMBOL                                = " (from cache)";

    private static $cache                               = [];
    /**
     * @var stdClass
     */
    private static $stats                               = null;
    private static $process                             = [];

    private static $pid                                 = null;
    /**
     * @var stdClass
     */
    private static $tid                                 = null;

    /**
     * @return array|null
     */
    public static function exTime() : array
    {
        return self::$stats->extime ?? [];
    }
    /**
     * @return array|null
     */
    public static function stats() : ?stdClass
    {
        return self::$stats;
    }
    /**
     * @param string $bucket
     * @param string $action
     * @param array|null $params
     * @param null $ref
     * @return bool
     * @todo da tipizzare
     */
    public static function request(string $bucket, string $action, array $params = null, &$ref = null) : bool
    {
        self::startWatch($bucket, $action);

        if (!self::cacheEnabled()) {
            return true;
        }
        $pid                                            = self::hash($bucket, $action, $params);

        return ($ref && self::isCached($pid)
            ? self::initRef($pid, $ref)
            : self::init($pid, $bucket, $action, $params)
        );
    }

    /**
     * @param $response
     */
    public static function store($response) : void
    {
        $exTime                                             = self::stopWatch();
        if (self::cacheEnabled()) {
            $cache                                          =& self::$cache[self::$pid];

            $cache->response                                = $response;
            $cache->exTime                                  = $exTime;

            self::clear();

            Log::debugging($cache->request, $cache->bucket, $cache->bucket, $cache->action);
        }

        /*  print_r(self::exTime());
          print_r(self::$cache);
          print_r(self::$process);
          print_r(self::$exTime);*/
    }

    /**
     */
    public static function update() : void
    {
        $exTime                                             = self::stopWatch();
        if (self::cacheEnabled()) {
            $cache                                          =& self::$cache[self::$pid];

            $cache->exTime                                  = $exTime;

            self::clear();

            Log::debugging($cache->request, $cache->bucket, $cache->bucket, $cache->action);
        }
    }

    /**
     * @param string $bucket
     * @param string $action
     */
    private static function startWatch(string $bucket, string $action) : void
    {
        self::$tid                                          = new stdClass();
        self::$tid->bucket                                  = $bucket;
        self::$tid->pkey                                    = App::getRunner() . self::SEP . $bucket . self::SEP . $action;

        App::stopWatch(self::$tid->pkey);
    }

    /**
     * @return float
     */
    private static function stopWatch() : float
    {
        $exTime                                             = App::stopWatch(self::$tid->pkey);

        self::setStats($exTime, self::$tid->bucket, self::$tid->pkey);

        self::$tid                                          = null;

        return $exTime;
    }

    /**
     * @param string $bucket
     * @param string $action
     * @param array|null $params
     * @return string
     */
    private static function hash(string $bucket, string $action, array $params = null) : string
    {
        return $bucket . self::SEP . $action. "-" . crc32(json_encode($params));
    }

    /**
     * @param string $key
     * @return bool
     */
    private static function isCached(string $key) : bool
    {
        return !empty(self::$cache[$key]->response);
    }

    /**
     * @param string $pid
     * @param null $ref
     * @return bool
     */
    private static function initRef(string $pid, &$ref = null) : bool
    {
        self::setPid($pid);

        $ref                                                = self::$cache[self::$pid]->response;
        foreach (self::$cache[self::$pid]->process as $process) {
            self::setProcess($process, true);
        }

        self::$cache[self::$pid]->exTime                    = self::stopWatch();

        return false;
    }

    /**
     * @param string $pid
     * @param string $bucket
     * @param string $action
     * @param array|null $params
     * @return bool
     */
    private static function init(string $pid, string $bucket, string $action, array $params = null) : bool
    {
        self::setPid($pid);

        self::$cache[self::$pid]                            = new stdClass();
        self::$cache[self::$pid]->request                   = $params;
        self::$cache[self::$pid]->bucket                    = $bucket;
        self::$cache[self::$pid]->action                    = $action;
        self::$cache[self::$pid]->pkey                      = App::getRunner() . self::SEP . self::$cache[self::$pid]->bucket . self::SEP . self::$cache[self::$pid]->action;

        return true;
    }
    /**
     * @param $value
     */
    public static function set(string $value) : void
    {
        self::$cache[self::$pid]->process[]                 =& self::setProcess($value);
    }


    /**
     * @param string $value
     * @param bool $from_cache
     * @return string
     */
    private static function &setProcess(string $value, bool $from_cache = false) : string
    {
        $label                                              =   (count(self::$process) + 1) . ". " .
                                                                (self::$cache[self::$pid]->pkey ?? null) .
                                                                ($from_cache ? self::SYMBOL : "");

        self::$process[$label]                              = $value;

        return self::$process[$label];
    }

    private static function clear() : void
    {
        self::$pid                                          = null;
    }

    /**
     * @param float $time
     * @param string $bucket
     * @param string $pkey
     */
    private static function setStats(float $time, string $bucket, string $pkey) : void
    {
        self::$stats->extime[self::SEP_EXTIME . $bucket]    = $time + (self::$stats->extime[self::SEP_EXTIME . $bucket] ?? 0);
        self::$stats->extime_action[$pkey]                  = $time + (self::$stats->extime_action[$pkey] ?? 0);
        self::$stats->count[$pkey]                          = 1 + (self::$stats->count[$pkey] ?? 0);
    }

    /**
     * @param string $pid
     * @return void
     */
    private static function setPid(string $pid) : void
    {
        self::$pid                                          = $pid;
    }

    /**
     * @return bool
     */
    private static function cacheEnabled() : bool
    {
        return Kernel::$Environment::CACHE_BUFFER;
    }

    /**
     * @return array
     */
    public static function dump(): array
    {
        return [
            "cache"     => self::$cache,
            "process"   => self::$process
        ];
    }
}