<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Debug;
use phpformsframework\libs\dto\DataAdapter;
use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\Response;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\util\ResourceConverter;
use Exception;

/**
 * Class Widget
 * @package phpformsframework\libs\gui
 */
abstract class Widget extends Controller
{
    use AssetsManager;
    use ResourceConverter;

    private const RENDER_SNIPPET                    = "snippet";
    private const RENDER_PAGE                       = "page";
    private const RENDER_JSON                       = "json";

    protected const ERROR_BUCKET                    = "widget";

    private static $render                          = null;

    private const NAME_SPACE_BASIC                  = "widgets\\";

    protected $requiredJs                           = [];
    protected $requiredCss                          = [];

    private $name                                   = null;
    private $config                                 = [];
    private $resources                              = null;
    private $skin                                   = null;

    /**
     * @var View[]
     */
    private $views                                  = [];
    /**
     * @var array
     */
    private $view                                   = "index";

    /**
     * @param array|null $config
     * @return DataHtml
     * @throws Exception
     */
    public static function snippet(array $config = null) : DataHtml
    {
        self::$render                               = self::RENDER_SNIPPET;
        return self::widget($config)->toDataHtml();
    }

    /**
     * @param array|null $config
     * @throws Exception
     */
    public static function page(array $config = null) : void
    {
        self::$render                               = self::RENDER_PAGE;

        $widget = self::widget($config);
        $widget->layout()
            ->injectAssets($widget)
            ->addContent($widget->html)
            ->display();
    }

    /**
     * @param array|null $config
     */
    protected static function displayHtml(array $config = null) : void
    {
        static::{self::$render}($config);
    }

    /**
     * @param array|null $config
     * @return array
     * @throws Exception
     */
    protected static function displayJson(array $config = null) : array
    {
        return self::widget($config, "get")->toArray();
    }

    /**
     * @param array|null $config
     * @param string|null $method
     * @return static
     * @throws Exception
     */
    private static function widget(array $config = null, string $method = null) : self
    {
        Debug::stopWatch(static::ERROR_BUCKET . "/" . self::getClassName());

        $widget = new static($config);

        $widget->display($method);

        Debug::stopWatch(static::ERROR_BUCKET . "/" . self::getClassName());

        return $widget;
    }

    /**
     * Widget constructor.
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        parent::__construct();

        $this->name                                 = strtolower($this->getClassName());
        $this->config                               = $config ?? [];
    }

    /**
     * @param string|null $method
     * @throws Exception
     */
    public function display(string $method = null): void
    {
        if ($method) {
            $this->$method();
        } else {
            parent::display();
        }

        $this->parseAssets();

        $this->html                                 = $this->getView()->display();
    }

    /**
     * @param DataAdapter $data
     * @param array $headers
     */
    protected function send(DataAdapter $data, array $headers = []) : void
    {
        Response::send($data, $headers);
    }

    /**
     * @param string|null $destination
     * @param int|null $http_response_code
     * @param array|null $headers
     */
    protected function redirect(string $destination = null, int $http_response_code = null, array $headers = null)
    {
        Response::redirect($destination, $http_response_code, $headers);
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
     * @return View
     * @throws Exception
     */
    private function getView() : View
    {
        if (!$this->view) {
            throw new Exception("View missing for " . $this->name, 400);
        }

        return $this->views[$this->view];
    }

    /**
     * @param string|null $template_name
     * @param string|null $theme_name
     * @return View
     * @throws Exception
     */
    protected function view(string $template_name = null, string $theme_name = null) : View
    {
        $this->view                                 = $template_name ?? $this->view;
        if (!isset($this->views[$this->view])) {
            $resources                              = $this->getResources();
            if (empty($resources->html[$this->view])) {
                throw new Exception("Template not found for Widget " . $this->name, 404);
            }

            $this->views[$this->view]               = (new View($this->config($resources->cfg[$this->view] ?? null)))
                                                        ->fetch($resources->html[$this->view]);
        }

        return $this->views[$this->view];
    }

    /**
     * @param string|null $template_name
     * @return object
     */
    protected function getConfig(string $template_name = null) : object
    {
        return json_decode(json_encode($this->config($this->getResources()->cfg[$template_name ?? $this->view] ?? null)));
    }

    /**
     * @param string|null $file_path
     * @return array|null
     */
    private function config(string $file_path = null) : ?array
    {
        static $configs = null;

        if ($file_path && !isset($configs[$file_path])) {
            $configs[$file_path] = array_replace_recursive($this->loadConfig($file_path), $this->config);
        }

        return $configs[$file_path] ?? null;
    }

    /**
     * @return DataHtml
     */
    private function getResources() : object
    {
        $skin                                       = $this->getSkin();
        if (!isset($this->resources[$skin])) {
            $this->resources[$skin]                 = Resource::widget($skin);
        }

        return (object) ($this->resources[$skin] ?? null);
    }

    /**
     * @throws Exception
     */
    private function parseAssets() : void
    {
        $widget_name                            = $this->getSkin();
        $resources                              = $this->getResources();

        $this->parseRequiredAssets();
        if (!empty($resources->js[$this->view])) {
            $this->addJs($widget_name, $resources->js[$this->view]);
        }
        if (!empty($resources->css[$this->view])) {
            $this->addCss($widget_name, $resources->css[$this->view]);
        }

    }

    /**
     * @throws Exception
     */
    private function parseRequiredAssets() : void
    {
        foreach ($this->requiredJs as $js) {
            $this->addJs($js);
        }
        foreach ($this->requiredCss as $css) {
            $this->addCss($css);
        }
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
     * @param string $file_path
     * @return array
     */
    private function loadConfig(string $file_path) : array
    {
        $widget_name                                                        = $this->getSkin();

        Debug::stopWatch(static::ERROR_BUCKET . "/map/" . $widget_name);

        $cache                                                              = Buffer::cache("widget");
        $config                                                             = $cache->get($widget_name);
        if (!$config) {
            $config                                                         = Filemanager::getInstance("json")->read($file_path);

            $cache->set($widget_name, $config, [$file_path => filemtime($file_path)]);
        }

        Debug::stopWatch(static::ERROR_BUCKET . "/map/". $widget_name);

        return (array) $config;
    }
}
