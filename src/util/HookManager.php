<?php
namespace phpformsframework\libs\util;

use phpformsframework\libs\Hook;

/**
 * Class HookManager
 * @package phpformsframework\libs\util
 */
trait HookManager
{
    /**
     * @param string $name
     * @param callable $func
     * @param int $priority
     */
    private static function on(string $name, callable $func, int $priority = Hook::HOOK_PRIORITY_NORMAL): void
    {
        Hook::register($name, $func, $priority);
    }

    /**
     * @param string $name
     * @param null|mixed $ref
     * @param null|mixed $params
     * @return array|null
     * @todo da tipizzare
     */
    private static function hook(string $name, &$ref = null, $params = null)
    {
        return Hook::handle($name, $ref, $params);
    }
}