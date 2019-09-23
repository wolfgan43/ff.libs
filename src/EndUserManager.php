<?php
namespace phpformsframework\libs;

use phpformsframework\libs\international\Translator;
use phpformsframework\libs\storage\Orm;

/**
 * Trait EndUserManager
 * @todo da portare dentro request response orm translator
 *
 * VERIFICARE TUTTI GLI OGGETTI HCORE
 * @package phpformsframework\libs
 */
trait EndUserManager
{
    use EndUserFunc;

    /**
     * @return Kernel
     */
    protected static function &Server()
    {
        return Kernel::$singleton;
    }

    protected static function &request()
    {
        return Kernel::$singleton->Request;
    }
    protected static function &response()
    {
        return Kernel::$singleton->Response;
    }
}
