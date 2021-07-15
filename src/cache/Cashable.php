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
