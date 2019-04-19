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
    const NAME_SPACE_BASIC                      = "widgets\\";
    const FRAMEWORK_CSS                         = "bootstrap4";
    const FONT_ICON                             = "fontawesome";
    const DIR                                   = null;

    private static $singleton                   = null;
    private static $grid_system                 = null;
    private static $tpl                         = null;

    protected $config                           = array();
    protected $js                               = null;
    protected $css                              = null;
    protected $html                             = null;

    protected abstract function getConfigDefault();

    /**
     * @param string $filename
     * @param null|array $config
     * @return ffTemplate
     */
    protected abstract function processTemplate($filename, $config = null);

    private function getSnippet() {



    }
    private function getPage() {
        if(is_array($this->js) && count($this->js)) {
            $scripts = '<script defer async src="' . implode('"></script><script defer async src="', $this->js) . '"></script>';
        }
        if(is_array($this->css) && count($this->css)) {
            $links = '<link rel="stylesheet" type="text/css" href="' . implode('" /><link rel="stylesheet" type="text/css" href="', $this->css) . '" />';
        }


        return '<!DOCTYPE html>
                <html>
                <head>
                    ' . $scripts . '
                    ' . $links . '
                </head>
                <body>
                ' . $this->html . '
                </body>
                </html>';


    }
    /**
     * @param string $name
     * @param null|string $bucket
     * @return Widget
     */
    public static function getInstance($name, $bucket = null) {

        $class_name                             = $bucket . self::NAME_SPACE_BASIC . ucfirst($name);
        if(!isset(self::$singleton[$class_name])) {
            self::$singleton[$class_name]       = new $class_name();
        }

        return self::$singleton[$class_name];
    }

    public function process($return = null) {
        $widget_name                            = basename(static::DIR);
        $config                                 = $this->getConfig();
        $path                                   = $this::getDiskPath("tpl") . ($config["tpl_path"]
                                                    ? $config["tpl_path"]
                                                    : "/" . $widget_name
                                                );
        $html_name                              = "/index.html";
        $css_name                               = "/style.css";
        $script_name                            = "/script.js";

        $filename                               = (is_file($path . $html_name)
                                                    ? $path . $html_name
                                                    : static::DIR . $html_name
                                                );

       /* $framework = $this->gridSystem()->getFramework();
        $fonticon = $this->gridSystem()->getFontIcon();
        print_r($framework);
        print_r($fonticon);
        die();*/

        $tpl                                    = $this->processTemplate($filename, $config);
        $this->html                             = $tpl->rpparse("main", false);
        $this->addCss($widget_name              , (is_file($path . $css_name)
                                                    ? $path . $css_name
                                                    : static::DIR . $css_name
                                                ));
        $this->addJs($widget_name               , (is_file($path . $script_name)
                                                    ? $path . $script_name
                                                    : static::DIR . $script_name
                                                ));

        switch ($return) {
            case "snippet":
                $output                         = array("html" => $this->getSnippet());
                break;
            case "page":
                $output                         = array("html" => $this->getPage());
                break;
            default:
                $output                         = array(
                                                    "html"  => $tpl->rpparse("main", false)
                                                    , "css" => $this->css
                                                    , "js"  => $this->js
                                                );
        }

        return $output;
    }

    protected function addJs($key, $url) {
        $this->js[$key] = str_replace($this::$disk_path, $this::SITE_PATH, $url);
    }
    protected function addCss($key, $url) {
        $this->css[$key] = str_replace($this::$disk_path, $this::SITE_PATH, $url);

    }

    /**
     * @return null|Gridsystem
     */
    protected function gridSystem() {
        if(!self::$grid_system)         { self::$grid_system = Gridsystem::factory(static::FRAMEWORK_CSS, static::FONT_ICON); }

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
        $this->config = array_replace_recursive($this->getConfigDefault(), (array) $config) ;

        return $this;
    }
    public function getConfig() {
        return $this->config;
    }
}
