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
namespace phpformsframework\libs\cache;

use phpformsframework\libs\cache\adapters\BufferAdapter;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Log;
use phpformsframework\libs\Router;
use phpformsframework\libs\util\AdapterManager;
use phpformsframework\libs\util\TypesConverter;
use stdClass;

/**
 * Class Buffer
 * @package phpformsframework\libs\cache
 */
class Buffer implements Dumpable
{
    use AdapterManager;
    use TypesConverter;

    private const SEP                                   = "/";
    private const SYMBOL                                = " (from cache)";

    private static $cache                               = [];

    private static $exTime                              = [];
    private static $process                             = [];

    private static $pid                                 = null;
    /**
     * @var stdClass
     */
    private static $tid                                 = null;


    public static function clear(string $bufferAdapter = null) : void
    {
        self::loadAdapter($bufferAdapter ?? Kernel::$Environment::CACHE_BUFFER_ADAPTER, ["", Kernel::useCache()])->clear();
    }

    /**
     * @param string $bucket
     * @param bool|null $force
     * @param string|null $bufferAdapter
     * @return BufferAdapter
     */
    public static function cache(string $bucket, bool $force = null, string $bufferAdapter = null) : BufferAdapter
    {
        return self::loadAdapter($bufferAdapter ?? Kernel::$Environment::CACHE_BUFFER_ADAPTER, [$bucket, $force ?? Kernel::useCache()]);
    }

    /**
     * @param string $source_file
     * @param string|null $cache_file
     * @return bool
     */
    public static function cacheIsValid(string $source_file, string $cache_file = null) : bool
    {
        return $cache_file
            && (Kernel::useCache() || filemtime($cache_file) >= filemtime($source_file));
    }

    /**
     * @param string $bucket
     * @return float
     */
    public static function exTime(string $bucket) : float
    {
        return self::$exTime[$bucket] ?? 0;
    }

    /**
     * @param string $bucket
     * @param string $action
     * @param array|null $params
     * @param $res
     * @param $cnf
     * @return bool
     * @todo da tipizzare
     */
    public static function request(string $bucket, string $action, array $params = null, &$res = null, &$cnf = null) : bool
    {
        self::watchStart($bucket, $action);

        $pid                                                = self::hash($bucket, $action, $params);
        if (!self::cacheEnabled()) {
            return self::init($pid, $bucket, $action, $params);
        }

        return ($res && self::isCached($pid)
            ? self::initRef($pid, $res, $cnf)
            : self::init($pid, $bucket, $action, $params)
        );
    }

    /**
     * @param $response
     * @param $config
     */
    public static function store($response, $config = null) : void
    {
        $exTime                                             = self::watchStop();
        if (self::cacheEnabled()) {
            $cache                                          =& self::$cache[self::$pid];

            $cache->response                                = $response;
            $cache->config                                  = $config;
            $cache->exTime                                  = $exTime;

            self::reset();

            Log::debugging($cache->request, $cache->bucket, $cache->bucket, $cache->action);
        }
    }

    /**
     */
    public static function update() : void
    {
        $exTime                                             = self::watchStop();
        if (self::cacheEnabled()) {
            $cache                                          =& self::$cache[self::$pid];

            $cache->exTime                                  = $exTime;

            self::reset();

            Log::debugging($cache->request, $cache->bucket, $cache->bucket, $cache->action);
        }
    }

    /**
     * @param string $bucket
     * @param string $action
     */
    private static function watchStart(string $bucket, string $action) : void
    {
        self::$tid                                          = new stdClass();
        self::$tid->bucket                                  = $bucket;
        self::$tid->pkey                                    = Router::getRunner() . self::SEP . $bucket . self::SEP . $action;

        Debug::stopWatch(self::$tid->pkey);
    }

    /**
     * @return float
     */
    private static function watchStop() : float
    {
        $exTime                                             = Debug::stopWatch(self::$tid->pkey);

        self::setExTime($exTime, self::$tid->bucket);

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
        return $bucket . self::SEP . $action . "-" . self::checkSumArray($params);
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
     * @param $res
     * @param $cnf
     * @return bool
     */
    private static function initRef(string $pid, &$res = null, &$cnf = null) : bool
    {
        self::setPid($pid);

        $res                                                = self::$cache[self::$pid]->response;
        $cnf                                                = self::$cache[self::$pid]->config;
        foreach (self::$cache[self::$pid]->process as $process) {
            self::setProcess($process, true);
        }

        self::$cache[self::$pid]->exTime                    = self::watchStop();

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
        self::$cache[self::$pid]->process                   = [];
        self::$cache[self::$pid]->request                   = $params;
        self::$cache[self::$pid]->bucket                    = $bucket;
        self::$cache[self::$pid]->action                    = $action;
        self::$cache[self::$pid]->pkey                      = Router::getRunner() . self::SEP . self::$cache[self::$pid]->bucket . self::SEP . self::$cache[self::$pid]->action;

        return true;
    }

    /**
     * @param string $value
     */
    public static function set(string $value) : void
    {
        if (isset(self::$cache[self::$pid])) {
            self::$cache[self::$pid]->process[]             =& self::setProcess($value);
        }
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

        self::$process[$label]                              = preg_replace('/\s+/', ' ', $value);

        return self::$process[$label];
    }

    private static function reset() : void
    {
        self::$pid                                          = null;
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

    /**
     * @param float $time
     * @param string $bucket
     */
    public static function setExTime(float $time, string $bucket) : void
    {
        self::$exTime[$bucket] = $time + (self::$exTime[$bucket] ?? 0);
    }
}
