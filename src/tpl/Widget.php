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
use phpformsframework\optimizer\Resources;

/**
 * Class Widget
 * @package phpformsframework\libs\tpl
 * @todo private function getSnippet()
 */
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

    protected $skin                             = null;

    private $config                             = array();

    /**
     * @return array
     */
    abstract protected function getConfigDefault() : array;

    /**
     * @param array $config
     * @param string $callToAction
     */
    abstract protected function controller(array &$config, string $callToAction);

    /**
     * @param View $view
     * @param array $config
     */
    abstract protected function renderTemplate(View &$view, array $config);

    /**
     * Widget constructor.
     * @param string $name
     * @param array|null $config
     */
    public function __construct(string $name, array $config = null)
    {
        $this->config                           = array_replace_recursive($this->getConfigDefault(), (array) $config);
        $this->name                             = $name;
    }

    /**
     * @return DataHtml
     */
    private function getPage() : DataHtml
    {
        return Page::getInstance("html")
            ->addAssets($this->js, $this->css)
            ->addContent($this->html)
            ->render();
    }

    /**
     * @param string $name
     * @param array|null $config
     * @param string|null $bucket
     * @return Widget
     */
    public static function getInstance(string $name, array $config = null, string $bucket = null) : Widget
    {
        self::stopwatch("widget/" . $name);

        $class_name                             = $bucket . self::NAME_SPACE_BASIC . ucfirst($name);
        if (!isset(self::$singleton[$class_name])) {
            self::$singleton[$class_name]       = new $class_name($name, $config);
        }

        return self::$singleton[$class_name];
    }

    /**
     * @param DataHtml $widget
     */
    private function inject(DataHtml $widget) : void
    {
        $this->js                               = $widget->js;
        $this->css                              = $widget->css;
        $this->html                             = $widget->html;
    }

    /**
     * @return string
     */
    private function getSkin() : string
    {
        return $this->name . (
            $this->skin
            ? "-" . $this->skin
            : ""
        );
    }

    /**
     * @return object|null
     */
    protected function getResources() : ?object
    {
        return Resource::widget($this->getSkin());
    }

    /**
     * @param string|DataHtml $name
     * @param array $data
     */
    protected function view($name = "index", array $data = array()) : void
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
    public function render(string $return = null) : DataHtml
    {
        $this->controller($this->getConfig(), $this->request()->method());

        self::stopwatch("widget/" . $this->name);

        $output                                     = new DataHtml();
        switch ($return) {
            case "snippet":
                $output                             = $this->getSnippet();
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
     * @return FrameworkCss|null
     */
    protected function gridSystem() : ?FrameworkCss
    {
        if (!self::$grid_system) {
            self::$grid_system = Gridsystem::getInstance();
        }

        return self::$grid_system;
    }

    /**
     * @return array
     */
    private function &getConfig()
    {
        return $this->config;
    }
}
