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

use phpformsframework\libs\App;
use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\EndUserManager;
use stdClass;
use Exception;

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

    protected $script_path                      = null;
    protected $path_info                        = null;
    protected $is_ajax                          = null;
    protected $request                          = null;
    protected $method                           = null;

    protected $skin                             = null;
    protected $requiredJs                       = array();
    protected $requiredCss                      = array();


    private $name                               = null;
    private $config                             = array();

    /**
     * @param stdClass $request
     * @return array
     */
    abstract protected function getConfigDefault(stdClass $request) : array;

    /**
     * @param array $config
     * @param stdClass $request
     * @param bool $isAjax
     * @return mixed
     */
    abstract protected function controller(array &$config, stdClass $request, bool $isAjax) : void;

    /**
     * @param array $config
     * @param stdClass $request
     * @return DataResponse|null
     */
    abstract protected function callToAction(array &$config, stdClass $request);

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
        $configuration                          = App::configuration();
        $request                                = App::request();

        $this->script_path                      = $configuration->page->script_path;
        $this->path_info                        = $configuration->page->path_info;
        $this->is_ajax                          = $request->isAjax();
        $this->request                          = $request->rawdata();
        $this->method                           = $request->methodValid($request->method(), ["GET"]);

        $this->config                           = array_replace_recursive($this->getConfigDefault($this->request), (array) $config);
        $this->name                             = $name;
    }

    /**
     * @return DataHtml
     * @throws Exception
     */
    private function getPage() : DataHtml
    {
        return Page::getInstance("html")
            ->injectAssets($this)
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
     * @return DataHtml
     */
    protected function getResources() : DataHtml
    {
        return Resource::widget($this->getSkin());
    }

    /**
     * @param string|DataHtml $name
     * @param array $data
     */
    protected function view($name, array $data = array()) : void
    {
        if (is_object($name)) {
            $this->injectAssets($name);
        } else {
            $widget_name                            = $this->getSkin();
            $view                                   = new View();
            $this->parseRequiredAssets();
            $resources                              = $this->getResources();
            $this->addJs($widget_name, $resources->getJs($name));
            $this->addCss($widget_name, $resources->getCss($name));

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
        if ($this->method) {
            $response = $this->callToAction($this->getConfig(), $this->request);
            if ($response) {
                $this->response()->send($response);
            }
        }

        $this->controller($this->getConfig(), $this->request, $this->is_ajax);

        if (!$this->html) {
            $this->view("index", $this->getConfig());
        }

        self::stopwatch("widget/" . $this->name);

        switch ($return) {
            case "snippet":
                $output                             = null; //$this->getSnippet();
                break;
            case "page":
                $output                             = $this->getPage();
                break;
            default:
                $output                             = $this->toDataHtml();
        }

        return $output;
    }

    /**
     * @param string $relative_path
     * @return string
     */
    protected function getUrl(string $relative_path) : string
    {
        return App::configuration()::SITE_PATH . $relative_path;
    }

    /**
     * @param string $url
     */
    protected function redirect(string $url) : void
    {
        $this->response()->redirect($url);
    }

    /**
     *
     */
    private function parseRequiredAssets()
    {
        foreach ($this->requiredJs as $js) {
            $this->addJs($js);
        }
        foreach ($this->requiredCss as $css) {
            $this->addCss($css);
        }
    }

    /**
     * @return DataHtml
     */
    private function toDataHtml() : DataHtml
    {
        return new DataHtml([
            "js"        => $this->js,
            "css"       => $this->css,
            "fonts"     => $this->fonts,
            "images"    => $this->images,
            "html"      => $this->html
        ]);
    }
    /**
     * @return FrameworkCss
     */
    protected function gridSystem() : FrameworkCss
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
