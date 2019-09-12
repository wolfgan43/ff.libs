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
use phpformsframework\libs\Dir;
use phpformsframework\libs\storage\Filemanager;

class MemFs extends MemAdapter
{
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
            $this->getKey("set", $bucket, $name);

            $res = Filemanager::getInstance("php")->write(
                $value,
                self::getCacheDiskPath()
                . DIRECTORY_SEPARATOR . $bucket
                . DIRECTORY_SEPARATOR . $name
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
        if ($this->is_readable) {
            $this->getKey("get", $bucket, $name);
            $res = Filemanager::getInstance("php")
                ->read(
                    "/cache/data"
                    . "/" . $bucket
                    . "/" . $name
                );
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
        Filemanager::xpurge_dir(
            self::getCacheDiskPath()
            . "/" . $bucket
            . "/" . $name
        );
        return null;
    }


    public function clear($bucket = null)
    {
        $this->getKey("clear", $bucket);
        Filemanager::xpurge_dir(
            self::getCacheDiskPath()
            . "/" . $bucket
        );
    }


    private function getCacheDiskPath()
    {
        return Constant::DISK_PATH . Dir::getDiskPath("cache/data", true);
    }
}
