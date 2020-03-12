<?php
namespace phpformsframework\libs\cache;

use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\Debug;

/**
 * Class Buffer
 * @package phpformsframework\libs\cache
 */
trait Cashable
{
    use ClassDetector;

    public static function exTime()
    {
        return self::$exTime2;
    }

    /**
     * @param string $action
     * @param array|null $params
     */
    public static function cacheRequest(string $action, array $params = null) : void
    {
        Buffer::request(self::getClassName() . "/" . $action, $params);
    }

    /**
     * @param string $value
     */
    public static function cacheSet(string $value)
    {
        Buffer::set($value);
    }

    /**
     * @param $response
     */
    public static function cacheStore($response)
    {
        Buffer::store($response);
    }

    public static function cacheUpdate(string $bucket)
    {
        Buffer::update($bucket);
    }
}
