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
namespace phpformsframework\libs\storage\adapters;

use phpformsframework\libs\Error;
use phpformsframework\libs\storage\drivers\Array2XML;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\storage\FilemanagerAdapter;
use Exception;

/**
 * Class FilemanagerXml
 * @package phpformsframework\libs\storage\adapters
 */
class FilemanagerXml extends FilemanagerAdapter
{
    const EXT                                                   = "xml";

    /**
     * @param string $file_path
     * @param string|null $var
     * @return array|null
     */
    protected function loadFile(string $file_path, string $var = null) : ?array
    {
        return Array2XML::XML_TO_ARR(Filemanager::fileGetContent($file_path));
    }

    /**
     * @param array $data
     * @param string $var
     * @return string
     */
    protected function output(array $data, string $var) : string
    {
        $xml                                                    = null;
        $root_node                                              = (
            $var
            ? $var
            : "root"
        );

        try {
            $xml                                                = Array2XML::createXML($root_node, $data);
        } catch (Exception $e) {
            Error::register($e->getMessage(), static::ERROR_BUCKET);
        }

        return $xml->saveXML();
    }
}
