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

use phpformsframework\libs\Autoloader;
use phpformsframework\libs\Constant;
use phpformsframework\libs\storage\Filemanager;

/**
 * Class MemFs
 * @package phpformsframework\libs\cache\adapters
 */
class MemFs extends MemAdapter
{
    private const CACHE_PATH        = Constant::CACHE_PATH . DIRECTORY_SEPARATOR . "data";
    private const CACHE_DISK_PATH   = Constant::DISK_PATH . self::CACHE_PATH;
    private const FILE_TYPE         = Constant::PHP_EXT;



    /**
     * @param string $name
     * @param string|null $bucket
     * @return mixed
     */
    protected function load(string $name, string $bucket = null)
    {
        return Autoloader::loadScript($this->getCacheDiskPath($bucket . DIRECTORY_SEPARATOR . $name));
    }

    /**
     * @param string $name
     * @param mixed $data
     * @param string|null $bucket
     * @return bool
     */
    protected function write(string $name, $data, string $bucket = null) : bool
    {
        return Filemanager::getInstance(self::FILE_TYPE)->write(
            $data,
            self::getCacheDiskPath($bucket . DIRECTORY_SEPARATOR . $name)
        );
    }

    /**
     * @param String $name il nome dell'elemento
     * @return bool
     */
    public function del(string $name) : bool
    {
        parent::del($name);

        return Filemanager::xPurgeDir(
            self::getCacheDiskPath($this->getBucket() . DIRECTORY_SEPARATOR . $name)
        );
    }

    /**
     *
     */
    public function clear() : void
    {
        parent::clear();

        Filemanager::xPurgeDir(self::CACHE_PATH . $this->getBucket());
    }

    /**
     * @param string $path
     * @return string
     */
    private function getCacheDiskPath(string $path) : string
    {
        return self::CACHE_DISK_PATH . $path . "." . Constant::PHP_EXT;
    }

    /**
     * @return string
     */
    protected function getBucket(): string
    {
        return DIRECTORY_SEPARATOR . parent::getBucket();
    }
}
