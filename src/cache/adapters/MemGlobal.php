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

namespace phpformsframework\libs\cache\adapters;

use stdClass;

/**
 * Class MemGlobal
 * @package phpformsframework\libs\cache\adapters
 */
class MemGlobal extends MemAdapter
{
    private static $instances = [];
    /**
     * @param string $method
     * @param array|null $args
     * @return mixed|null
     */
    public function __call(string $method, array $args = null)
    {
        if (isset($this->$method)) {
            return $this->$method(...$args);
        }

        return null;
    }
    /**
     * @todo da tipizzare
     * Inserisce un elemento nella cache
     * Oltre ai parametri indicati, accetta un numero indefinito di chiavi per relazione i valori memorizzati
     * @param String $name il nome dell'elemento
     * @param Mixed|null $value l'elemento
     * @param String|null $bucket il name space
     * @return bool if storing both value and rel table will success
     */
    public function set(string $name, $value = null, string $bucket = null) : bool
    {
        if ($value === null) {
            return $this->del($name, $bucket);
        }

        $this->getKey("set", $bucket, $name);

        if (!isset(self::$instances[$bucket])) {
            self::$instances[$bucket] = new stdClass();
        }

        self::$instances[$bucket]->$name = $value;

        return true;
    }

    /**
     * @todo da tipizzare
     * Recupera un elemento dalla cache
     * @param String $name il nome dell'elemento
     * @param String|null $bucket il name space
     * @return Mixed l'elemento
     */
    public function get(string $name, string $bucket = null)
    {
        $this->getKey("get", $bucket, $name);
        $res = null;
        if ($name) {
            $res = self::$instances[$bucket]->$name ?? null;
        } else {
            $keys = self::$instances[$bucket];
            if (!empty($keys)) {
                foreach ($keys as $key => $value) {
                    if (strpos($key, $bucket) === 0) {
                        $real_key = substr($key, strlen($bucket));
                        $res[$real_key] = $value;
                    }
                }
            }
        }

        return $res;
    }

    /**
     * Cancella una variabile
     * @param String $name il nome dell'elemento
     * @param String|null $bucket il name space
     * @return bool
     */
    public function del(string $name, string $bucket = null) : bool
    {
        $this->getKey("del", $bucket, $name);

        if (isset(self::$instances[$bucket]->$name)) {
            unset(self::$instances[$bucket]->$name);
        }
        return true;
    }

    /**
     * Pulisce la cache
     * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
     * Se non si specificano le relazioni, verrà cancellata l'intera cache
     * @param string|null $bucket
     */
    public function clear(string $bucket = null) : void
    {
        $this->getKey("clear", $bucket);

        if (self::$instances[$bucket]) {
            unset(self::$instances[$bucket]);
        }
    }
}
