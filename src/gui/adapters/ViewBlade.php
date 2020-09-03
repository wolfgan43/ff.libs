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
namespace phpformsframework\libs\gui\adapters;

if (!class_exists("BladeOne")) {
    class ViewBlade
    {
    }
    return null;
}

use Exception;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Error;
use phpformsframework\libs\gui\ViewAdapter;
use eftec\bladeone\BladeOne;

/**
 * Class ViewBlade
 * @package phpformsframework\libs\gui\adapters
 */
class ViewBlade extends BladeOne implements ViewAdapter
{
    const VIEW_PATH                     = DIRECTORY_SEPARATOR . "blade";


    private $tpl_file                   = null;
    private $data                       = array();

    public function __construct()
    {
        parent::__construct(Dir::findAppPath("views"), Constant::CACHE_DISK_PATH . $this::VIEW_PATH);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isset(string $name) : bool
    {
        // TODO: Implement isset() method.
        return false;
    }

    /**
     * @param array|callable|string $data
     * @param mixed|null $value
     * @return ViewAdapter
     */
    public function assign($data, $value = null) : ViewAdapter
    {
        if ($value) {
            $this->data[$data]          = $value;
        } else {
            $this->data                 = array_replace($this->data, $data);
        }

        return $this;
    }

    /**
     * @param string $file_disk_path
     * @return ViewAdapter
     */
    public function fetch(string $file_disk_path) : ViewAdapter
    {
        $this->tpl_file                 = $file_disk_path;

        return $this;
    }

    /**
     * @return string
     */
    public function display() : string
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

    /**
     * @param string $sectionName
     * @param bool $repeat
     * @param bool $appendBefore
     * @return bool
     */
    public function parse(string $sectionName, $repeat = false, bool $appendBefore = false) : bool
    {
        // TODO: Implement parse() method.
        return false;
    }
}
