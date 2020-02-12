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
use Memcached as MC;
use phpformsframework\libs\Kernel;

class MemMemcached extends MemAdapter
{
    public static $server   = "127.0.0.1";
    public static $port     = 11211;
    public static $auth     = null;

    private $conn	= null;

    public function __construct($bucket = null)
    {
        parent::__construct($bucket);

        $this->conn = new MC(Kernel::$Environment::APPID);

        if (static::$auth) {
            $this->conn->setOption(MC::OPT_BINARY_PROTOCOL, true);
            $this->conn->addServer(static::$server, static::$port);
            $this->conn->setSaslAuthData(Kernel::$Environment::APPID, static::$auth);
        } else {
            $this->conn->addServer(static::$server, static::$port);
        }
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
        $res = false;
        if ($value === null) {
            $res = $this->del($name, $bucket);
        } elseif ($this->is_writeable) {
            $key = $this->getKey("set", $bucket, $name);
            $res = $this->conn->set($key, $this->setValue($value), $this->getTTL());
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
        if ($this->is_readable) {
            $key = $this->getKey("get", $bucket, $name);
            if ($name) {
                $this->conn->get($key);
                if ($this->conn->getResultCode() === MC::RES_SUCCESS) {
                    $res = $this->getValue($res);
                }
            } else {
                $keys = $this->conn->getAllKeys();
                if (!empty($keys)) {
                    foreach ($keys as $value) {
                        if (strpos($value, $bucket) === 0) {
                            $real_key = substr($value, strlen($bucket));
                            $res[$real_key] = $this->get($real_key, $bucket);
                        }
                    }
                }
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
        $key = $this->getKey("del", $bucket, $name);

        return $this->conn->delete($key);
    }
    /**
     * Pulisce la cache
     * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
     * Se non si specificano le relazioni, verrà cancellata l'intera cache
     * @param string $bucket
     */
    public function clear($bucket = null)
    {
        $this->getKey("clear", $bucket);

        // global reset
        $this->conn->flush();
    }
}
