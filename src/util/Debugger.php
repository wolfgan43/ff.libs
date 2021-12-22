<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace phpformsframework\libs\util;

use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Router;

/**
 * Class Debugger
 * @package phpformsframework\libs
 */
trait Debugger
{
    use ServerManager;
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
            $res                                                    = $path && strpos(self::pathinfo(), $path) === 0;
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
