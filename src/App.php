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

use phpformsframework\libs\tpl\Widget;

abstract class App implements Dumpable
{
    const NAME_SPACE                                                = __NAMESPACE__ . '\\';
    const ERROR_BUCKET                                              = 'app';

    protected static $script_engine                                 = null;
    protected static $page                                          = null;

    public static function getPage($key = null)
    {
        if ($key && !isset(self::$page[$key])) {
            self::$page[$key]                                       = null;
        }

        return ($key
            ? self::$page[$key]
            : self::$page
        );
    }


    public static function dump()
    {
        return array(
            "isRunnedAs"    => self::$script_engine,
            "page"          => self::$page
        );
    }

    /**
     * @param array|object $page
     */
    public static function setPage($page)
    {
        self::$page = (array) $page;
    }

    protected static function hook($name, $func, $priority = null)
    {
        Hook::register($name, $func, $priority);
    }
    protected static function doHook($name, &$ref = null, $params = null)
    {
        return Hook::handle($name, $ref, $params);
    }

    public static function setRunner($what)
    {
        self::$script_engine                                        = basename(str_replace('\\', '/', $what));
    }
    public static function isRunnedAs($what)
    {
        if (self::$script_engine) {
            $res                                                    = self::$script_engine == ucfirst($what);
        } else {
            $path                                                   = Dir::getDiskPath($what, true);
            $res                                                    = $path && strpos(Request::pathinfo(), $path) === 0;
        }
        return $res;
    }

    /**
     * @param string $name
     * @param null|array $config
     * @param null|string $return
     * @return dto\DataHtml
     */
    public static function widget($name, $config = null, $return = null)
    {
        $class_name                     = get_called_class();

        Log::registerProcedure($class_name, "widget:" . $name);

        return Widget::getInstance($name, $class_name::NAME_SPACE)
            ->setConfig($config)
            ->render($return);
    }
    public static function page($name, $config = null)
    {
        return self::widget($name, $config, "page");
    }
}
