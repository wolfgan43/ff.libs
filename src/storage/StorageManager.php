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
namespace phpformsframework\libs\storage;

use phpformsframework\libs\Exception;

/**
 * Trait DataAccess
 * @package phpformsframework\libs\storage
 */
trait StorageManager
{
    /**
    * @param string $filepath
    * @return bool
    */
    private static function fileExists(string $filepath): bool
    {
        return file_exists($filepath);
    }

    /**
     * @return FilemanagerFs
     */
    private static function fileManager() : FilemanagerFs
    {
        return new FilemanagerFs();
    }

    /**
     * @param string $file_path
     * @return string
     * @throws Exception
     */
    private static function fileGetContents(string $file_path) : string
    {
        return FilemanagerFs::fileGetContents($file_path);
    }

    /**
     * @param string $file_path
     * @param string $data
     * @return bool
     */
    private static function filePutContents(string $file_path, string $data) : bool
    {
        return FilemanagerFs::makeDir(dirname($file_path)) && FilemanagerFs::filePutContents($file_path, $data);
    }

    /**
     * @param string $path
     * @param int $flag
     * @param array|null $extension
     * @return array
     */
    private static function fileScan(string $path, int $flag, array $extension = null) : array
    {
        return FilemanagerScan::scan([$path => ["flag" => $flag, "extension" => $extension]]);
    }

    /**
     * @param string $filename
     * @return string
     */
    private static function getMimeByFilename(string $filename) : string
    {
        return Media::getMimeByFilename($filename);
    }

    public static function getExtensionByFile(string $file) : string
    {
        return Media::getExtensionByFile($file);
    }
    /**
     * @param string $ext
     * @return string
     */
    private static function getMimeByExtension(string $ext) : string
    {
        return Media::getMimeByExtension($ext);
    }

    /**
     * @param string $url
     * @param array|null $params
     * @return array
     */
    private static function getQueryByUrl(string &$url, array $params = null) : array
    {
        return FilemanagerWeb::getQueryByUrl($url, $params);
    }
}
