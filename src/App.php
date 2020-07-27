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
use phpformsframework\libs\gui\Widget;
use stdClass;
use Exception;

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
     * @var stdClass
     */
    protected static $user                                          = null;
    /**
     * @var stdClass
     */
    protected static $user_acl                                      = null;

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
        self::$configuration->page                                  =& Request::pageConfiguration();

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
     * @param string $collection_or_model|null
     * @return Model
     */
    public static function db(string $collection_or_model = null) : Model
    {
        return new Model($collection_or_model);
    }

    /**
     * @param string $name
     * @param array|null $config
     * @param string|null $return
     * @return dto\DataHtml
     */
    public static function widget(string $name, array $config = null, string $return = null) : dto\DataHtml
    {
        $class_name                                                 = get_called_class();

        Log::registerProcedure(str_replace(static::NAME_SPACE, "", $class_name), "widget" . Log::CLASS_SEP . $name);

        return Widget::getInstance($name, $config, $class_name::NAME_SPACE)
            ->render($return);
    }

    /**
     * @param string $name
     * @param array|null $config
     * @return dto\DataHtml
     */
    public static function page(string $name, array $config = null) : dto\DataHtml
    {
        return self::widget($name, $config, "page");
    }

    /**
     * @return stdClass|null
     */
    public static function getCurrentUser() : ?stdClass
    {
        return self::$user;
    }
}
