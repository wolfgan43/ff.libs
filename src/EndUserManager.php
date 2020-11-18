<?php
namespace phpformsframework\libs;

/**
 * Trait EndUserManager
 * @package phpformsframework\libs
 */
trait EndUserManager
{
    /**
     * @return Request
     */
    public static function request() : Request
    {
        static $request     = null;

        if (!$request) {
            $request        = new Request();
        }

        return $request;
    }

    /**
     * @return Response
     */
    public static function response() : Response
    {
        static $response    = null;

        if (!$response) {
            $response       = new Response();
        }

        return $response;
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
}
