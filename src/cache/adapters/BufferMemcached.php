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
namespace phpformsframework\libs\cache\adapters;

use Memcached as MC;
use phpformsframework\libs\Kernel;

/**
 * Class MemMemcached
 * @package phpformsframework\libs\cache\adapters
 */
class BufferMemcached extends BufferAdapter
{
    public static $server   = "127.0.0.1";
    public static $port     = 11211;
    public static $auth     = null;

    private $conn	= null;

    /**
     * MemMemcached constructor.
     * @param string|null $bucket
     * @param bool $readable
     * @param bool $writeable
     */
    public function __construct(string $bucket, bool $readable = true, bool $writeable = true)
    {
        parent::__construct(Kernel::$Environment::APPNAME . "/" . $bucket, $readable, $writeable);

        $this->conn = new MC($this->appid);

        if (static::$auth) {
            $this->conn->setOption(MC::OPT_BINARY_PROTOCOL, true);
            $this->conn->addServer(static::$server, static::$port);
            $this->conn->setSaslAuthData($this->appid, static::$auth);
        } else {
            $this->conn->addServer(static::$server, static::$port);
        }
    }

    /**
     * @param string $name
     * @param string|null $bucket
     * @return mixed
     */
    protected function load(string $name, string $bucket = null)
    {
        $res = $this->conn->get($bucket . DIRECTORY_SEPARATOR . $name);
        return ($this->conn->getResultCode() === MC::RES_SUCCESS
            ? $this->getValue($res)
            : null
        );
    }

    /**
     * @param string $name
     * @param mixed $data
     * @param string|null $bucket
     * @return bool
     */
    protected function write(string $name, $data, string $bucket = null): bool
    {
        return $this->conn->set($bucket . DIRECTORY_SEPARATOR . $name, $this->setValue($data), $this->getTTL());
    }

    /**
     * Cancella una variabile
     * @param String $name il nome dell'elemento
     * @return bool
     */
    public function del(string $name) : bool
    {
        parent::del($name);

        return $this->conn->delete($this->getBucket() . DIRECTORY_SEPARATOR . $name);
    }

    /**
     * Pulisce la cache
     * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
     * Se non si specificano le relazioni, verrà cancellata l'intera cache
     */
    public function clear() : void
    {
        parent::clear();

        $this->conn->flush();
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getInfo(string $name): ?string
    {
        return null;
    }
}
