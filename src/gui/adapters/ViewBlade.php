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
namespace ff\libs\gui\adapters;

if (!class_exists("BladeOne")) {
    /**
     * Class ViewBlade
     * @package ff\libs\gui\adapters
     */
    class ViewBlade
    {
    }
    return null;
}

use ff\libs\Constant;
use ff\libs\Dir;
use ff\libs\Exception;
use eftec\bladeone\BladeOne;

/**
 * Class ViewBlade
 * @package ff\libs\gui\adapters
 */
class ViewBlade extends BladeOne implements ViewAdapter
{
    const VIEW_PATH                     = DIRECTORY_SEPARATOR . "blade";

    private $tpl_file                   = null;
    private $widget                     = null;

    private $data                       = array();

    public function __construct(string $widget = null)
    {
        parent::__construct(Dir::findViewPath(), Constant::CACHE_DISK_PATH . $this::VIEW_PATH);

        $this->widget                   = $widget;
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
     * @param array|string $tpl_var
     * @param mixed|null $value
     * @return ViewAdapter
     */
    public function assign($tpl_var, $value = null) : ViewAdapter
    {
        if ($value) {
            $this->data[$tpl_var]       = $value;
        } else {
            $this->data                 = array_replace($this->data, $tpl_var);
        }

        return $this;
    }

    /**
     * @param string $template_disk_path
     * @return ViewAdapter
     */
    public function fetch(string $template_disk_path) : ViewAdapter
    {
        $this->tpl_file                 = $template_disk_path;

        return $this;
    }

    /**
     * @param string $content
     * @return ViewAdapter
     */
    public function fetchContent(string $content): ViewAdapter
    {
        // TODO: Implement fetchContent() method.

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function display() : string
    {
        $filename                   = pathinfo($this->tpl_file, PATHINFO_FILENAME);
        return $this->run($filename, $this->data);
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

    /**
     * @param string|null $lang_code
     * @return $this
     */
    public function setLang(string $lang_code = null) : ViewAdapter
    {
        return $this;
    }
}
