<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Debug;
use phpformsframework\libs\dto\DataAdapter;
use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\Response;
use phpformsframework\libs\storage\Filemanager;
use Exception;

/**
 * Class Widget
 * @package phpformsframework\libs\gui
 */
abstract class Widget extends Controller
{
    use AssetsManager;

    private const VIEW_DEFAULT                      = "index";

    protected const ERROR_BUCKET                    = "widget";

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
     * @var View
     */
    private $view                                   = null;
    private $template                               = null;

    /**
     * @param array|null $config
     * @return array
     * @throws Exception
     */
    public static function toJson(array $config = null) : array
    {
        $controller = new static($config);
        $controller->render("get");

        return $controller->toArray();
    }

    /**
     * @param array|null $config
     * @return DataHtml
     * @throws Exception
     */
    public static function toHtml(array $config = null) : DataHtml
    {
        $controller = new static($config);
        $controller->render("get");

        return $controller->toDataHtml();
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
    private function render(string $method = null): void
    {
        $this->{$method ?? $this->method}();
        $this->parseAssets();

        $this->html                                 = $this->getView()->display();
    }

    public function display(): void
    {
        $this->render();

        $this->layout()
            ->injectAssets($this)
            ->addContent($this->html)
            ->display();
    }

    /**
     * @param array|null $config
     * @return DataHtml
     * @throws Exception
     */
    public function snippet(array $config = null) : DataHtml
    {
        $this->config                               = $config ?? [];
        $this->render();

        return $this->toDataHtml();
    }

    /**
     * @param string $widgetName
     * @param array|null $config
     * @throws Exception
     */
    protected function load(string $widgetName, array $config = null) : void
    {
        /**
         * @var Widget $controller
         */
        $controller = new $widgetName($config);
        $controller->render($this->method);

        $this->injectAssets($controller);
        $this->view = $controller->view;
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
            throw new Exception("View missing for " . $this->name, 403);
        }

        return $this->view;
    }

    /**
     * @param string|null $template_name
     * @param string|null $theme_name
     * @return View
     * @throws Exception
     * @todo da gestire il tema
     */
    protected function view(string $template_name = null, string $theme_name = null) : View
    {
        $this->template                             = $template_name ?? self::VIEW_DEFAULT;
        $resources                                  = $this->getResources();
        if (empty($resources->html[$this->template])) {
            throw new Exception("Template not found for Widget " . $this->name, 404);
        }

        if (!isset($this->views[$this->template])) {
            $this->views[$this->template]           = (new View($this->config($resources->cfg[$this->template] ?? null)))->fetch($resources->html[$this->template]);
        }

        return $this->view                          =& $this->views[$this->template];
    }

    /**
     * @param string|null $template_name
     * @return object
     */
    protected function getConfig(string $template_name = null) : object
    {
        return json_decode(json_encode($this->config($this->getResources()->cfg[$template_name ?? self::VIEW_DEFAULT] ?? null)));
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
        $template                               = $this->template;
        $this->parseRequiredAssets();

        if (!empty($resources->js[$template])) {
            $this->addJs($widget_name, $resources->js[$template]);
        }
        if (!empty($resources->css[$template])) {
            $this->addCss($widget_name, $resources->css[$template]);
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
