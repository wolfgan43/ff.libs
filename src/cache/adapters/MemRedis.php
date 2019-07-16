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

use phpformsframework\libs\cache\MemAdapter;
use phpformsframework\libs\Constant;
use Redis as MC;

class MemRedis extends MemAdapter
{
    public static $server       = "127.0.0.1";
    public static $port         = 6379;
    public static $auth         = null;

    private $conn	= null;

    public function __construct($bucket = null)
    {
        parent::__construct($bucket);

        $this->conn = new MC();
        $this->conn->pconnect(static::$server, static::$port, $this->getTTL(), Constant::APPID); // x is sent as persistent_id and would be another connection than the three before.
        if (static::$auth) {
            $this->conn->auth(static::$auth);
        }
        switch (static::$serializer) {
            case "PHP":
                $this->conn->setOption(MC::OPT_SERIALIZER, MC::SERIALIZER_PHP);	// use built-in serialize/unserialize
                break;
            case "IGBINARY":
                $this->conn->setOption(MC::OPT_SERIALIZER, MC::SERIALIZER_IGBINARY);	// use igBinary serialize/unserialize
                break;
            default:
                $this->conn->setOption(MC::OPT_SERIALIZER, MC::SERIALIZER_NONE);	// don't serialize data

        }


        //$this->conn->setOption(MC::OPT_PREFIX, self::APPID . ':');	// use custom prefix on all keys
    }

    /**
     * Inserisce un elemento nella cache
     * Oltre ai parametri indicati, accetta un numero indefinito di chiavi per relazione i valori memorizzati
     * @param String $name il nome dell'elemento
     * @param Mixed $value l'elemento
     * @param String $bucket il name space
     * @return bool if storing both value and rel table will success
     */
    public function set($name, $value = null, $bucket = null)
    {
        if ($value === null) {
            $res = $this->del($name, $bucket);
        } else {
            $this->getKey("set", $bucket, $name);

            $res = (
                $bucket
                ? $this->conn->hSet($bucket, $name, $value)
                : $this->conn->set($name, $value)
            );
        }

        return $res;
    }

    /**
     * Recupera un elemento dalla cache
     * @param String $name il nome dell'elemento
     * @param String $bucket il name space
     * @return Mixed l'elemento
     */
    public function get($name, $bucket = null)
    {
        $res = false;
        if (!Constant::$disable_cache) {
            $this->getKey("get", $bucket, $name);

            if ($bucket) {
                $res = (
                    $name
                    ? $this->conn->hGet($bucket, $name)
                    : $this->conn->hGetAll($bucket)
                );
            } else {
                $res = $this->conn->get($name);
            }
        }

        return $res;
    }

    /**
     * Cancella una variabile
     * @param String $name il nome dell'elemento
     * @param String $bucket il name space
     * @return bool
     */
    public function del($name, $bucket = null)
    {
        $this->getKey("del", $bucket, $name);

        return ($bucket
            ? $this->conn->hDel($bucket, $name)
            : $this->conn->delete($name)
        );
    }
    /**
     * Pulisce la cache
     * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
     * Se non si specificano le relazioni, verrà cancellata l'intera cache
     * @param string $bucket
     */
    public function clear($bucket = null)
    {
        $this->getKey("del", $bucket);

        // global reset
        if ($bucket) {
            $this->conn->delete($bucket);
        } else {
            $this->conn->flushDb();
        }
    }
}
