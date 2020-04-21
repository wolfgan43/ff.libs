<?php
namespace phpformsframework\libs\cache;

/**
 * Class Buffer
 * @package phpformsframework\libs\cache
 */
trait Cashable
{
    /**
     * @param string $action
     * @param array|null $params
     * @param null $res
     * @return bool
     * @todo da tipizzare
     */
    public static function cacheRequest(string $action, array $params = null, &$res = null) : bool
    {
        return Buffer::request(static::ERROR_BUCKET, $action, $params, $res);
    }

    /**
     * @param string $value
     */
    public static function cacheSetProcess(string $value) : void
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


    public static function cacheUpdate() : void
    {
        Buffer::update();
    }
}
