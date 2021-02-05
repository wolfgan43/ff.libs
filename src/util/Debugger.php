<?php
namespace phpformsframework\libs\util;

use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Request;
use phpformsframework\libs\Router;

/**
 * Class Debugger
 * @package phpformsframework\libs
 */
trait Debugger
{
    /**
     * @return string
     */
    private static function getRunner() : ?string
    {
        return Router::getRunner();
    }

    /**
     * @param string $what
     */
    private static function setRunner(string $what) : void
    {
        Router::setRunner($what);
    }

    /**
     * @param string $what
     * @return bool
     */
    private static function isRunnedAs(string $what) : bool
    {
        $script                                                     = Router::getRunner();
        if ($script) {
            $res                                                    = $script == ucfirst($what);
        } else {
            $path                                                   = Dir::findAppPath($what, true);
            $res                                                    = $path && strpos(Request::pathinfo(), $path) === 0;
        }
        return $res;
    }

    /**
     * @param string $bucket
     * @return float|null
     */
    private static function stopWatch(string $bucket) : ?float
    {
        return Debug::stopWatch($bucket);
    }

    /**
     * @param array $backtrace
     * @return void
     */
    private static function setBackTrace(array $backtrace) : void
    {
        Debug::setBackTrace($backtrace);
    }
}
