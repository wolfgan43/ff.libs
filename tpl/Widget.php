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
namespace phpformsframework\libs\tpl;

use phpformsframework\libs\DirStruct;
use phpformsframework\libs\Request;
use phpformsframework\libs\Response;

abstract class Widget extends DirStruct {
    const TYPE                                  = "Widget";
    const FRAMEWORK_CSS                         = "bootstrap4";
    const FONT_ICON                             = "fontawesome";
    private static $singleton                   = null;
    private static $grid_system                 = null;
    private static $tpl                         = null;

    protected $config                           = array();
    protected $output                           = null;

    protected abstract function getConfigDefault();
    public abstract function process();

    public function getJson() {
        $this->process();
        return $this->output;
    }
    public function getSnippet() {


        $output = $this->process();

    }
    /**
     * @param string $name
     * @param null|string $bucket
     * @return Widget
     */
    public static function getInstance($name, $bucket = null) {

        $class_name                             = ucfirst($bucket . self::TYPE) . ucfirst($name);
        if(!isset(self::$singleton[$class_name])) {
            self::$singleton[$class_name]       = new $class_name();
        }

        return self::$singleton[$class_name];
    }

    /**
     * @return null|Gridsystem
     */
    protected function gridSystem() {
        if(!self::$grid_system)         { self::$grid_system = Gridsystem::factory("bootstrap4", "fontawesome"); }

        return self::$grid_system;
    }

    /**
     * @return null|ffTemplate
     */
    protected function tpl() {
        if(!self::$tpl)                 { self::$tpl = new ffTemplate(); }

        return self::$tpl;
    }

    public function setConfig($config) {
        $this->config = (array) $config;

        return $this;
    }
    public function getConfig() {
        return array_replace_recursive($this->getConfigDefault(), $this->config);
    }
}
