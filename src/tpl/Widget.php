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

use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\EndUserManager;

abstract class Widget
{
    use AssetsManager;
    use EndUserManager;

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
     * @param array $config
     * @param string $callToAction
     * @return void|DataHtml
     */
    abstract protected function controller(&$config, $callToAction);

    /**
     * @param View $view
     * @param array $config
     */
    abstract protected function renderTemplate(&$view, $config);

    public function __construct($name, $config)
    {
        $this->config                           = array_replace_recursive($this->getConfigDefault(), (array) $config);
        $this->name                             = $name;

    }

    //todo: da fare
    private function getSnippet()
    {
        return null;
    }

    /**
     * @return DataHtml
     */
    private function getPage()
    {
        return Page::getInstance("html")
            ->addAssets($this->js, $this->css)
            //->setLayout("empty")
            ->addContent($this->html)
            ->render();
    }
    /**
     * @param string $name
     * @param null|array $config
     * @param null|string $bucket
     * @return Widget
     */
    public static function getInstance($name, $config = null, $bucket = null)
    {
        Debug::stopWatch("widget/" . $name);

        $class_name                             = $bucket . self::NAME_SPACE_BASIC . ucfirst($name);
        if (!isset(self::$singleton[$class_name])) {
            self::$singleton[$class_name]       = new $class_name($name, $config);
        }

        return self::$singleton[$class_name];
    }

    private function inject($widget_data)
    {
        $this->js                               = $widget_data->js;
        $this->css                              = $widget_data->css;
        $this->html                             = $widget_data->html;
    }

    private function getSkin()
    {
        return $this->name . (
            $this->skin
            ? "-" . $this->skin
            : ""
        );
    }

    protected function getResources()
    {
        return Resource::widget($this->getSkin());
    }

    /**
     * @param string|object $name
     * @param array $data
     */
    protected function view($name = "index", $data = array())
    {
        if (is_object($name)) {
            $this->inject($name);
        } else {
            $widget_name                            = $this->getSkin();
            $view                                   = new View();

            $resources                              = $this->getResources();

            $this->addJs($widget_name, $resources->js[$name]);
            $this->addCss($widget_name, $resources->css[$name]);

            $this->html                             = $view
                                                        ->fetch($resources->html[$name])
                                                        ->assign(function (&$view) use ($data) {
                                                            $this->renderTemplate($view, $data);
                                                        })
                                                        ->display();
        }
    }


    /**
     * @param null|string $return
     * @return DataHtml
     */
    public function render($return = null)
    {
        $this->controller($this->getConfig(), $this->request()->method());

        self::stopwatch("widget/" . $this->name);

        $output                                     = new DataHtml();
        switch ($return) {
            case "snippet":
                $output->html($this->getSnippet());
                break;
            case "page":
                $output                             = $this->getPage();
                break;
            default:
                $output
                    ->html($this->html)
                    ->css($this->css)
                    ->js($this->js);
        }

        return $output;
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

    private function &getConfig()
    {
        return $this->config;
    }
}
