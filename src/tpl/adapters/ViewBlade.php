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
namespace phpformsframework\libs\tpl\adapters;

use Exception;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Error;
use phpformsframework\libs\tpl\ViewAdapter;
use eftec\bladeone\BladeOne;

class ViewBlade extends BladeOne implements ViewAdapter
{
    const PATH                          = DIRECTORY_SEPARATOR . "blade";


    private $tpl_file                   = null;
    private $data                       = array();

    public function __construct()
    {
        parent::__construct(Dir::getDiskPath("views"), Constant::CACHE_DISK_PATH . $this::PATH);
    }
    public function isset($name)
    {
        // TODO: Implement isset() method.
    }
    public function assign($data, $value = null)
    {
        if ($value) {
            $this->data[$data]          = $value;
        } else {
            $this->data                 = array_replace($this->data, $data);
        }

        return $this;
    }
    public function fetch($file_disk_path)
    {
        $this->tpl_file                 = $file_disk_path;

        return $this;
    }
    public function display()
    {
        $tpl                            = null;
        try {
            $filename                   = pathinfo($this->tpl_file, PATHINFO_FILENAME);
            $tpl                        = $this->run($filename, $this->data);
        } catch (Exception $e) {
            Error::register("Blade: " . $e->getMessage(), static::ERROR_BUCKET);
        }

        return $tpl;
    }
    public function parse($sectionName, $repeat = false)
    {
        // TODO: Implement parse() method.
    }

    public function getTemplateFile($templateName = '')
    {
        return $this->tpl_file;
    }
}
