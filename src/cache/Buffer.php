<?php
namespace phpformsframework\libs\cache;

use phpformsframework\libs\Debug;
use phpformsframework\libs\Dumpable;

/**
 * Class Buffer
 * @package phpformsframework\libs\cache
 */
class Buffer implements Dumpable
{
    private static $cache                           = [];
    private static $exTime                          = [];
    private static $process                         = [];

    private static $bucket                          = null;
    private static $pid                             = null;

    public static function exTime()
    {
        return self::$exTime;
    }

    /**
     * @param string $bucket
     * @param array|null $params
     */
    public static function request(string $bucket, array $params = null) : void
    {
        self::setPid($bucket);
        Debug::stopWatch(self::$pid);

        self::$cache[self::$pid]["request"]         = $params;
    }

    /**
     * @param $value
     */
    public static function set(string $value)
    {
        $label                                      = (count(self::$process) + 1) . ". " . self::$pid;
        self::$process[$label] = $value;
        self::$cache[self::$pid]["process"][]       =& self::$process[$label];
    }

    /**
     * @param $response
     */
    public static function store($response)
    {
        self::$cache[self::$pid]["response"]        = $response;
        self::$cache[self::$pid]["exTime"]          = Debug::stopWatch(self::$pid);

        self::$exTime["exTime - " . self::$bucket]  = self::$cache[self::$pid]["exTime"] + (self::$exTime["exTime - " . self::$bucket] ?? 0);

/*
        print_r(self::exTime());
        print_r(self::$cache);
        print_r(self::$process);
        print_r(self::$exTime);*/
    }

    public static function update(string $bucket)
    {
    }

    /**
     * @return int
     */
    private static function setPid($bucket) : void
    {
        self::$bucket                               = $bucket;

        self::$pid                                  = str_pad((count(self::$cache) + 1), 3, "0", STR_PAD_LEFT) . "|" . $bucket;
    }

    /**
     * @return array
     */
    public static function dump(): array
    {
        return self::$cache;
    }
}
