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
     * @param null $cnf
     * @return bool
     * @todo da tipizzare
     */
    public static function cacheRequest(string $action, array $params = null, &$res = null, &$cnf = null) : bool
    {
        return Buffer::request(static::ERROR_BUCKET, $action, $params, $res, $cnf);
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
     * @param $config
     */
    public static function cacheStore($response, $config = null)
    {
        Buffer::store($response, $config);
    }


    public static function cacheUpdate() : void
    {
        Buffer::update();
    }
}
