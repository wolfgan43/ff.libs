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
     * @param $res
     * @param $cnf
     * @return bool
     * @todo da tipizzare
     */
    private function cacheRequest(string $action, array $params = null, &$res = null, &$cnf = null) : bool
    {
        return Buffer::request(static::ERROR_BUCKET, $action, $params, $res, $cnf);
    }

    /**
     * @param string $value
     */
    private function cacheSetProcess(string $value) : void
    {
        Buffer::set($value);
    }

    /**
     * @param $response
     * @param $config
     */
    private function cacheStore($response, $config = null)
    {
        Buffer::store($response, $config);
    }


    private function cacheUpdate() : void
    {
        Buffer::update();
    }

    private function stopWatch(string $bucket) : void
    {
        static $exTime = null;

        if (!$exTime) {
            $exTime = microtime(true);
        } else {
            Buffer::setExTime(microtime(true) - $exTime, $bucket);
            $exTime = null;
        }


    }
}
