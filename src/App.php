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
namespace ff\libs;

use ff\libs\dto\RequestPage;
use ff\libs\international\Locale;
use ff\libs\security\Discover;
use ff\libs\security\Validator;
use ff\libs\util\Normalize;

/**
 * Class App
 * @package ff\libs
 */
abstract class App implements Dumpable
{
    const NAME_SPACE                                                = __NAMESPACE__ . '\\';
    const ERROR_BUCKET                                              = 'app';

    /**
     * @return string
     */
    public static function &configuration() : string
    {
        return Kernel::$Environment;
    }

    /**
     * @param string|null $path_info
     * @param array|null $request
     * @param array|null $headers
     * @return RequestPage
     */
    public static function &page(string $path_info = null, array $request = null, array $headers = null) : RequestPage
    {
        if ($path_info) {
            return Request::load($path_info, $request, $headers);
        } else {
            return Kernel::$Page;
        }
    }


    /**
     * @param array|null $userVars
     * @return array
     */
    public static function dump(array $userVars = null) : array
    {
        return array(
            "isRunnedAs"    => Router::getRunner(),
            "Config"        => self::configuration(),
            "Env"           => Env::getAll(),
            "userVars"      => $userVars,
            "Constant"      => '*protected*'
        );
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
     * @return Normalize
     */
    public static function normalize() : util\Normalize
    {
        static $normalize       = null;

        if (!$normalize) {
            $normalize          = new Normalize();
        }

        return $normalize;
    }

    /**
     * @return Validator
     */
    public static function validator() : security\Validator
    {
        static $validator       = null;
        if (!$validator) {
            $validator          = new Validator();
        }

        return $validator;
    }

    /**
     * @return Discover
     */
    public static function discover() : security\Discover
    {
        static $discover        = null;
        if (!$discover) {
            $discover           = new Discover();
        }

        return $discover;
    }
    /**
     * @return Locale
     */
    public static function locale() : international\Locale
    {
        static $locale          = null;
        if (!$locale) {
            $locale             = new Locale();
        }

        return $locale;
    }
}
