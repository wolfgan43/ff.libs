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

use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Error;

abstract class Widget
{
    const ERROR_BUCKET                          = "widget";
    const NAME_SPACE_BASIC                      = "widgets\\";

    private static $singleton                   = null;
    private static $grid_system                 = null;
    private static $tpl                         = null;

    private $name                               = null;
    protected $config                           = array();
    protected $js                               = null;
    protected $css                              = null;
    protected $html                             = null;
    protected $status                           = null;

    abstract protected function getConfigDefault();

    /**
     * @param string $filename
     * @param null|array $config
     * @return ffTemplate
     */
    abstract protected function processTemplate($filename, $config = null);

    //todo: da fare
    private function getSnippet()
    {
        return null;
    }
    private function getPage()
    {
        return Page::getInstance("html")
            ->setStatus($this->status)
            ->addAssets($this->js, $this->css)
            //->setLayout("empty")
            ->addContent($this->html)
            ->process();
    }
    /**
     * @param string $name
     * @param null|string $bucket
     * @return Widget
     */
    public static function getInstance($name, $bucket = null)
    {
        Debug::stopWatch("widget/" . $name);

        $class_name                             = $bucket . self::NAME_SPACE_BASIC . ucfirst($name);
        if (!isset(self::$singleton[$class_name])) {
            self::$singleton[$class_name]       = new $class_name();
            self::$singleton[$class_name]->name = $name;
        }

        return self::$singleton[$class_name];
    }
    protected function inject($widget_data)
    {
        $this->js                               = $widget_data["js"];
        $this->css                              = $widget_data["css"];
        $this->html                             = $widget_data["html"];
    }
    protected function loadTemplate()
    {
        $widget_name                            = $this->name;


        $config                                 = $this->getConfig();
        $html_name                              = Resource::get($widget_name . "/html", "widget");
        $css_name                               = Resource::get($widget_name . "/css", "widget");
        $script_name                            = Resource::get($widget_name . "/js", "widget");
        if ($html_name) {
            $tpl                                = $this->processTemplate($html_name, $config);
            $this->html                         = $tpl->rpparse("main", false);
            if ($css_name) {
                $this->addCss($widget_name, $css_name);
            }
            if ($script_name) {
                $this->addJs($widget_name, $script_name);
            }
        } else {
            Error::register("Widget Template not found: " . $widget_name, static::ERROR_BUCKET);
        }

        Debug::stopWatch("widget/" . $widget_name);
    }

    public function process($return = null)
    {
        $this->loadTemplate();

        switch ($return) {
            case "snippet":
                $output                         = array("html" => $this->getSnippet());
                break;
            case "page":
                $output                         = array("html" =>  $this->getPage());
                break;
            default:
                $output                         = array(
                                                    "html"  => $this->html
                                                    , "css" => $this->css
                                                    , "js"  => $this->js
                                                );
        }

        return $output;
    }

    protected function addJs($key, $url)
    {
        if (!Dir::checkDiskPath($url) || filesize($url)) {
            $this->js[$key] = $url;
        }
    }
    protected function addCss($key, $url)
    {
        if (!Dir::checkDiskPath($url) || filesize($url)) {
            $this->css[$key] = $url;
        }
    }

    /**
     * @return null|FrameworkCss
     */
    protected function gridSystem()
    {
        if (!self::$grid_system) {
            self::$grid_system = Gridsystem::getInstance();
        }

        return self::$grid_system;
    }

    /**
     * @return null|ffTemplate
     */
    protected function tpl()
    {
        if (!self::$tpl) {
            self::$tpl = new ffTemplate();
        }

        return self::$tpl;
    }

    public function setConfig($config)
    {
        $this->config = array_replace_recursive($this->getConfigDefault(), (array) $config) ;

        return $this;
    }
    public function getConfig()
    {
        return $this->config;
    }
}
