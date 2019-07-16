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

namespace phpformsframework\libs\cache;

class Globals
{
    private static $instances =  array();

    public function __call($method, $args)
    {
        if (isset($this->$method)) {
            $func = $this->$method;
            return call_user_func_array($func, $args);
        }

        return null;
    }

    /**
     * Questa funzione restituisce un "finto" namespace sotto forma di oggetto attraverso il quale è possibile definire
     * variabili ed oggetti in modo implicito (magic).
     *
     * @param string $bucket il nome del namespace desiderato.
     * @return Globals
     */
    public static function getInstance($bucket = null)
    {
        if (!isset(Globals::$instances[$bucket])) {
            Globals::$instances[$bucket] = new Globals();
        }
        return Globals::$instances[$bucket];
    }

    public static function set($name, $value = null, $bucket = null)
    {
        if (!isset(Globals::$instances[$bucket])) {
            Globals::$instances[$bucket] = new Globals();
        }
        self::$instances[$bucket]->$name = $value;

        return true;
    }

    public static function get($name, $bucket = null)
    {
        if (!isset(Globals::$instances[$bucket])) {
            Globals::$instances[$bucket] = new Globals();
        }

        return (isset(self::$instances[$bucket]->$name)
            ? self::$instances[$bucket]->$name
            : null
        );
    }

    public static function del($name, $bucket = null)
    {
        if (isset(self::$instances[$bucket]->$name)) {
            unset(self::$instances[$bucket]->$name);
        }
        return true;
    }
    public static function clear($bucket = null)
    {
        if (self::$instances[$bucket]) {
            unset(self::$instances[$bucket]);
        }
        return true;
    }
}
