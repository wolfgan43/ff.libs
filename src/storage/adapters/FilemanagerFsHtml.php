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

use phpformsframework\libs\storage\FilemanagerAdapter;

/**
 * Class FilemanagerFsHtml
 * @package phpformsframework\libs\storage\adapters
 */
class FilemanagerFsHtml extends FilemanagerAdapter //todo: da finire
{
    const EXT                                                   = "html";

    /**
     * @param string $file_path
     * @param string|null $var
     * @return array|null
     */
    protected function loadFile(string $file_path, string $var = null) : ?array
    {
        // TODO: Implement load_file() method.
        return [];
    }

    /**
     * @param array $data
     * @param string|null $var
     * @return string
     */
    protected function output(array $data, string $var = null) : string
    {
        // TODO: Implement output() method.
        return "";
    }
}
