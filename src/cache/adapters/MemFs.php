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

use phpformsframework\libs\Constant;
use phpformsframework\libs\storage\Filemanager;

/**
 * Class MemFs
 * @package phpformsframework\libs\cache\adapters
 */
class MemFs extends MemAdapter
{
    private const CACHE_DISK_PATH = Constant::CACHE_DISK_PATH . DIRECTORY_SEPARATOR . "data";

    /**
     * Inserisce un elemento nella cache
     * Oltre ai parametri indicati, accetta un numero indefinito di chiavi per relazione i valori memorizzati
     * @param String $name il nome dell'elemento
     * @param Mixed|null $value l'elemento
     * @param String|null $bucket il name space
     * @return bool if storing both value and rel table will success
     */
    public function set(string $name, $value = null, string $bucket = null) : bool
    {
        $res = false;
        if ($value === null) {
            $res = $this->del($name, $bucket);
        } elseif ($this->is_writeable) {
            $this->getKey("set", $bucket, $name);

            $res = Filemanager::getInstance("php")->write(
                $value,
                self::getCacheDiskPath(DIRECTORY_SEPARATOR . $bucket . DIRECTORY_SEPARATOR . $name)
            );
        }
        return $res;
    }

    /**
     * @param String $name il nome dell'elemento
     * @param String|null $bucket il name space
     * @return Mixed l'elemento
     */
    public function get(string $name, string $bucket = null)
    {
        $res = null;
        if ($this->is_readable) {
            $this->getKey("get", $bucket, $name);

            $res = Filemanager::loadScript(DIRECTORY_SEPARATOR . $bucket . DIRECTORY_SEPARATOR . $name, self::CACHE_DISK_PATH);

        }

        return $res;
    }

    /**
     * @param String $name il nome dell'elemento
     * @param String|null $bucket il name space
     * @return bool
     */
    public function del(string $name, string $bucket = null) : bool
    {
        $this->getKey("del", $bucket, $name);
        return Filemanager::xPurgeDir(
            self::getCacheDiskPath(DIRECTORY_SEPARATOR . $bucket . DIRECTORY_SEPARATOR . $name)
        );
    }

    /**
     * @param string|null $bucket
     */
    public function clear(string $bucket = null) : void
    {
        $this->getKey("clear", $bucket);
        Filemanager::xPurgeDir(
            self::getCacheDiskPath(DIRECTORY_SEPARATOR . $bucket)
        );
    }

    /**
     * @param string $path
     * @return string
     */
    private function getCacheDiskPath(string $path) : string
    {
        return self::CACHE_DISK_PATH . $path;
    }
}
