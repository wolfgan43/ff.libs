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

use phpformsframework\libs\Kernel;
use Redis as MC;

/**
 * Class MemRedis
 * @package phpformsframework\libs\cache\adapters
 */
class BufferRedis extends BufferAdapter
{
    public static $server       = "127.0.0.1";
    public static $port         = 6379;
    public static $auth         = null;

    private $conn	= null;

    /**
     * MemRedis constructor.
     * @param string|null $bucket
     * @param bool $readable
     * @param bool $writeable
     */
    public function __construct(string $bucket, bool $readable = true, bool $writeable = true)
    {
        parent::__construct(Kernel::$Environment::APPNAME . "/" . $bucket, $readable, $writeable);

        $this->conn = new MC();
        $this->conn->pconnect(static::$server, static::$port, $this->getTTL(), $this->appid);
        if (static::$auth) {
            $this->conn->auth(static::$auth);
        }
        switch (static::$serializer) {
            case "PHP":
                $this->conn->setOption(MC::OPT_SERIALIZER, MC::SERIALIZER_PHP);
                break;
            case "IGBINARY":
                $this->conn->setOption(MC::OPT_SERIALIZER, MC::SERIALIZER_IGBINARY);
                break;
            default:
                $this->conn->setOption(MC::OPT_SERIALIZER, MC::SERIALIZER_NONE);

        }
    }

    /**
     * @param string $name
     * @param string|null $bucket
     * @return mixed
     */
    protected function load(string $name, string $bucket = null)
    {
        return ($bucket
            ? $this->conn->hGet($bucket, $name)
            : $this->conn->get($name)
        );
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param string|null $bucket
     * @return bool
     */
    protected function write(string $name, $value, string $bucket = null): bool
    {
        return ($bucket
            ? $this->conn->hSet($bucket, $name, $value)
            : $this->conn->set($name, $value)
        );
    }


    /**
     * Cancella una variabile
     * @param String $name il nome dell'elemento
     * @return bool
     */
    public function del(string $name) : bool
    {
        parent::del($name);

        $bucket = $this->getBucket();

        return ($bucket
            ? $this->conn->hDel($bucket, $name)
            : $this->conn->delete($name)
        );
    }

    /**
     * Pulisce la cache
     * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
     * Se non si specificano le relazioni, verrà cancellata l'intera cache
     */
    public function clear() : void
    {
        parent::clear();

        $bucket = $this->getBucket();

        if ($bucket) {
            $this->conn->delete($bucket);
        } else {
            $this->conn->flushDb();
        }
    }
}
