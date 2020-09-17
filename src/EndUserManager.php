<?php
namespace phpformsframework\libs;

use Exception;

/**
 * Trait EndUserManager
 * @package phpformsframework\libs
 */
trait EndUserManager
{
    /**
     * @todo da tipizzare
     * @return Request
     */
    public static function &request()
    {
        return Kernel::load()->Request;
    }

    /**
     * @todo da tipizzare
     * @return Response
     */
    public static function &response()
    {
        return Kernel::load()->Response;
    }
    /**
     * @todo da tipizzare
     * @param string $name
     * @param mixed|null $value
     * @param bool $permanent
     * @return mixed|null
     */
    public static function env(string $name, $value = null, bool $permanent = false)
    {
        return ($value === null
            ? Env::get($name)
            : Env::set($name, $value, $permanent)
        );
    }

    /**
     * @param string $name
     * @param callable $func
     * @param int $priority
     */
    public static function on(string $name, callable $func, int $priority = Hook::HOOK_PRIORITY_NORMAL) : void
    {
        Hook::register($name, $func, $priority);
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @param null|mixed $ref
     * @param null|mixed $params
     * @return array|null
     */
    public static function hook(string $name, &$ref = null, $params = null)
    {
        return Hook::handle($name, $ref, $params);
    }

    /**
     * @param int $status
     * @param string $msg
     * @throws Exception
     */
    public static function throwError(int $status, string $msg) : void
    {
        throw new Exception($msg, $status);
    }

    /**
     * @param $data
     * @param string|null $bucket
     */
    public static function debug($data, string $bucket = null) : void
    {
        Debug::set($data, $bucket ?? static::ERROR_BUCKET);
    }

    /**
     * @return bool
     */
    public static function debugEnabled() : bool
    {
        return Kernel::$Environment::DEBUG;
    }

    /**
     * @param string $bucket
     * @return float|null
     */
    public static function stopWatch(string $bucket) : ?float
    {
        return Debug::stopWatch($bucket);
    }

    /**
     * @param string|null $collection
     * @param string|null $mainTable
     * @return storage\Orm
     */
    public static function orm(string $collection = null, string $mainTable = null) : storage\Orm
    {
        return Model::orm($collection, $mainTable);
    }
}
