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

/**
 * Interface ViewAdapter
 * @package ff\libs\gui
 */
interface ViewAdapter
{
    const ERROR_BUCKET = "template";

    public function __construct(string $widget = null);

    /**
     * @param string $template_disk_path
     * @return $this
     */
    public function fetch(string $template_disk_path) : ViewAdapter;

    /**
     * @param string $content
     * @return $this
     */
    public function fetchContent(string $content) : ViewAdapter;

    /**
     * @param array|string $tpl_var
     * @param mixed|null $value
     * @return $this
     */
    public function assign($tpl_var, $value = null) : ViewAdapter;

    /**
     * @return string
     */
    public function display() : string;

    /**
     * @param string $sectionName
     * @param bool $repeat
     * @param bool $appendBefore
     * @return bool
     */
    public function parse(string $sectionName, bool $repeat = false, bool $appendBefore = false) : bool;

    /**
     * @param string $name
     * @return bool
     */
    public function isset(string $name) : bool;

    public function setLang(string $lang_code = null) : ViewAdapter;
}
