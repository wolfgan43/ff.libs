<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\Env;

/**
 * Class ControllerUtil
 * @package phpformsframework\libs\gui
 */
trait ControllerUtil
{
    /**
     * @param string $url
     * @return string
     */
    private function mask(string $url) : string
    {
        $env                                    = Env::getAll();
        $env["{"]                               = "";
        $env["}"]                               = "";

        return str_ireplace(array_keys($env), array_values($env), $url);
    }
}