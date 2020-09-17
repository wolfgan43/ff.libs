<?php
/**
 * VGallery: CMS based on FormsFramework
 * Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage core
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/gpl-3.0.html
 *  @link https://github.com/wolfgan43/vgallery
 */
namespace phpformsframework\libs;

use phpformsframework\libs\dto\RequestPage;

use Exception;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\security\Discover;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\util\Normalize;

/**
 * Class App
 * @package phpformsframework\libs
 */
abstract class App implements Dumpable
{
    use EndUserManager;

    const NAME_SPACE                                                = __NAMESPACE__ . '\\';
    const ERROR_BUCKET                                              = 'app';

    private static $script_engine                                   = null;

    /**
     * @var Constant
     */
    private static $configuration                                   = null;

    /**
     * @param string $environment
     * @return RequestPage
     * @throws Exception
     */
    public static function &construct(string $environment) : RequestPage
    {
        self::$configuration                                        = new $environment();
        Request::pageConfiguration(self::$configuration->page);

        return self::$configuration->page;
    }


    /**
     * @return Constant
     */
    public static function &configuration()
    {
        return self::$configuration;
    }

    /**
     * @param array|null $userVars
     * @return array
     */
    public static function dump(array $userVars = null) : array
    {
        return array(
            "isRunnedAs"    => self::$script_engine,
            "Configuration" => self::$configuration,
            "Vars"          => Env::get(),
            "userVars"      => $userVars,
            "Environment"   => '*protected*'
        );
    }

    /**
     * @return string
     */
    public static function getRunner() : string
    {
        return self::$script_engine;
    }

    /**
     * @param string $what
     */
    public static function setRunner(string $what) : void
    {
        self::$script_engine                                        = ucfirst(basename(str_replace('\\', '/', $what)));
    }

    /**
     * @param string $what
     * @return bool
     */
    public static function isRunnedAs(string $what) : bool
    {
        if (self::$script_engine) {
            $res                                                    = self::$script_engine == ucfirst($what);
        } else {
            $path                                                   = Dir::findAppPath($what, true);
            $res                                                    = $path && strpos(Request::pathinfo(), $path) === 0;
        }
        return $res;
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
     * @return Filemanager
     */
    public static function filemanager() : storage\Filemanager
    {
        static $filemanager     = null;
        if (!$filemanager) {
            $filemanager        = new Filemanager();
        }

        return $filemanager;
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
