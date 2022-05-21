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
namespace phpformsframework\libs\storage\adapters;

use phpformsframework\libs\storage\drivers\Array2XML;
use phpformsframework\libs\storage\FilemanagerFs;
use phpformsframework\libs\storage\FilemanagerAdapter;
use phpformsframework\libs\Exception;

/**
 * Class FilemanagerFsXml
 * @package phpformsframework\libs\storage\adapters
 */
class FilemanagerFsXml extends FilemanagerAdapter
{
    const EXT                                                   = "xml";

    /**
     * @param string $file_path
     * @param string|null $var
     * @return array|null
     * @throws Exception
     */
    protected function loadFile(string $file_path, string $var = null) : ?array
    {
        return Array2XML::xml2Array(FilemanagerFs::fileGetContents($file_path));
    }

    /**
     * @param array $data
     * @param string|null $var
     * @return string
     * @throws Exception
     */
    protected function output(array $data, string $var = null) : string
    {
        $root_node                                              = $var ?: "root";
        $xml                                                    = Array2XML::createXML($root_node, $data);

        return $xml->saveXML();
    }
}