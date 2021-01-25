<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\App;
use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\dto\DataAdapter;
use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\Env;
use phpformsframework\libs\international\InternationalManager;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Response;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\Media;
use phpformsframework\libs\util\AdapterManager;
use Exception;

/**
 * Class Controller
 * @package phpformsframework\libs\gui
 * @property ControllerAdapter $adapter
 */
abstract class Controller
{
    use AdapterManager;
    use InternationalManager;
    use ClassDetector;

    protected const ERROR_BUCKET                = "controller";

    protected const TPL_ENGINE_DEFAULT          = "Html";
    protected const TPL_ENGINE_SMARTY           = "Smarty";
    protected const TPL_ENGINE_BLADE            = "Blade";

    protected const TPL_VAR_DEFAULT             = "content";

    private const TPL_VAR_PREFIX                = '$';

    private const CONTROLLER_ENGINE_DEFAULT     = "html";
    private const TEMPLATE_DEFAULT              = "responsive";
    private const THEME_DEFAULT                 = "default";
    private const METHOD_DEFAULT                = "get";
    private const LAYOUT_DEFAULT                = '<main>{' . self::TPL_VAR_PREFIX . 'content}</main>';

    /**
     * @var ControllerAdapter
     */
    private static $controllers                 = null;


    protected $method                           = null;
    protected $headers                          = null;
    protected $authorization                    = null;
    protected $request                          = null;
    protected $script_path                      = null;
    protected $path_info                        = null;
    protected $isXhr                            = null;

    protected $controllerEngine                 = null;
    protected $templateEngine                   = null;
    protected $templateType                     = self::TEMPLATE_DEFAULT;
    protected $theme                            = self::THEME_DEFAULT;
    protected $layout                           = self::LAYOUT_DEFAULT;

    protected $http_status_code                 = 200;
    protected $error                            = null;

    protected $class_name                       = null;

    protected $requiredJs                       = [];
    protected $requiredCss                      = [];
    protected $assigns                          = [];

    private $config                             = null;
    private $cache_time                         = null;

    /**
     * @var View
     * @todo da trovare un modo per renderla private
     */
    protected $view                             = null;

    abstract protected function get()           : void;
    abstract protected function post()          : void;
    abstract protected function put()           : void;
    abstract protected function delete()        : void;
    abstract protected function patch()         : void;

    /**
     * @param array|null $config
     * @return array
     * @throws Exception
     */
    public static function resources(array $config = null) : array
    {
        return (new static($config))->toArray();
    }

    /**
     * @param array|null $config
     * @return string|null
     * @throws Exception
     */
    public static function html(array $config = null) : ?string
    {
        return (new static($config))->renderDefault();
    }

    /**
     * Controller constructor.
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        $page                                   = App::configuration()->page;

        $this->class_name                       = str_replace(self::ERROR_BUCKET, "", strtolower($this->getClassName()));
        $this->method                           = $page->method;
        $this->request                          = (object) $page->getRequest();
        $this->headers                          = (object) $page->getHeaders();
        $this->authorization                    = $page->getAuthorization();
        $this->script_path                      = $page->script_path;
        $this->path_info                        = $page->path_info;
        $this->isXhr                            = $page->isAjax;

        $this->config                           = $config;
        $this->cache_time                       = Kernel::useCache() ? null : "?" . time();

        $adapter                                = $this->controllerEngine ?? Kernel::$Environment::CONTROLLER_ADAPTER ?? self::CONTROLLER_ENGINE_DEFAULT;

        if (!isset(self::$controllers[$adapter])) {
            self::$controllers[$adapter]        = $this->setAdapter($adapter, [$this->path_info, $this->http_status_code, $this->templateType]);
        }

        $this->adapter                          =& self::$controllers[$adapter];
    }

    /**
     * Utility Builder
     * ------------------------------------------------------------------------
     */

    /**
     * @param string $doc_type
     * @return $this
     */
    protected function setDocType(string $doc_type) : self
    {
        $this->adapter->doc_type                = $doc_type;

        return $this;
    }
    /**
     * @param string $body_class
     * @return $this
     */
    protected function setBodyClass(string $body_class) : self
    {
        $this->adapter->body_class              = $body_class;

        return $this;
    }

    /**
     * @param string $url
     * @return string
     */
    private function maskEnv(string $url) : string
    {
        $env                                    = Env::getAll();
        $env["{"]                               = "";
        $env["}"]                               = "";

        return str_ireplace(array_keys($env), array_values($env), $url);
    }

    /**
     * @param string $relative_path
     * @return string
     */
    public function getWebUrl(string $relative_path) : string
    {
        return Kernel::$Environment::SITE_PATH . $this->maskEnv($relative_path);
    }

    /**
     * @param string $filename_or_url
     * @param string|null $mode
     * @return string
     * @throws Exception
     */
    public function getImageUrl(string $filename_or_url, string $mode = null) : string
    {
        return Media::getUrl(Resource::image($filename_or_url) ?? $this->maskEnv($filename_or_url), $mode);
    }

    /**
     * @param string $filename_or_url
     * @param string|null $mode
     * @param string|null $alt
     * @return string
     * @throws Exception
     */
    public function getImageTag(string $filename_or_url, string $mode = null, string $alt = null) : string
    {
        $altTag = (
            $alt
            ? ' alt="' . $alt . '"'
            : null
        );

        return '<img src="' . ($this->getImageUrl($filename_or_url, $mode) ?? Media::getIcon("spacer", $mode)) . '"' . $altTag . ' />';
    }


    /**
     * Assets Method
     * ------------------------------------------------------------------------
     */
    protected const ASSET_LOCATION_ASYNC        = ControllerAdapter::ASSET_LOCATION_ASYNC;
    protected const ASSET_LOCATION_HEAD         = ControllerAdapter::ASSET_LOCATION_HEAD;
    protected const ASSET_LOCATION_BODY_TOP     = ControllerAdapter::ASSET_LOCATION_BODY_TOP;
    protected const ASSET_LOCATION_BODY_BOTTOM  = ControllerAdapter::ASSET_LOCATION_BODY_BOTTOM;
    protected const ASSET_LOCATION_DEFAULT      = self::ASSET_LOCATION_HEAD;

    /**
     * @param array $ref
     * @param string $type
     * @param string $media
     * @param string $key
     * @throws Exception
     */
    private function addAssetDeps(array &$ref, string $type, string $media, string $key) : void
    {
        $asset_name                                                             = "";
        $assets                                                                 = explode(".", str_replace(".min", "", $key));
        foreach ($assets as $asset) {
            $asset_name                                                         .= $asset;
            if (($asset_url = Resource::get($asset_name, $type)) && filesize($asset_url)) {
                $ref[Media::getUrlRelative($asset_url) . $this->cache_time]     = $media;
            }
            $asset_name                                                         .= ".";
        }
    }

    /**
     * @param array $ref
     * @param string $type
     * @param string $media
     * @param string $filename_or_url
     * @return self
     * @throws Exception
     */
    private function addAsset(array &$ref, string $type, string $media, string $filename_or_url) : self
    {
        if (Validator::isUrl($filename_or_url)) {
            $ref[$this->maskEnv($filename_or_url)]                                      = $media;
        } elseif (Validator::isFile($filename_or_url)) {
            if (Dir::checkDiskPath($filename_or_url)) {
                if (filesize($filename_or_url)) {
                    $ref[Media::getUrlRelative($filename_or_url) . $this->cache_time]   = $media;
                }
            } else {
                $this->addAssetDeps($ref, $type, $media, $filename_or_url);
            }
        } else {
            throw new Exception("Invalid Asset Path: " . $filename_or_url, 500);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $content
     * @param string $type
     * @param string $type_content
     * @return $this
     */
    public function addMeta(string $key, string $content, string $type = "name", $type_content = "content") : self
    {
        $this->adapter->meta[$key]              = array(
                                                    $type           => $key,
                                                    $type_content   => $content
                                                );

        return $this;
    }

    /**
     * @param string $filename_or_url
     * @param string|null $device
     * @param string|null $media_query
     * @return $this
     * @throws Exception
     */
    protected function addStylesheet(string $filename_or_url, string $device = null, string $media_query = null) : self
    {
        $this->addAsset($this->adapter->css, Resource::TYPE_ASSET_CSS, $this->attrMedia($device, $media_query), $filename_or_url);

        return $this;
    }

    /**
     * @param string $content
     * @param string|null $device
     * @param string|null $media_query
     * @return $this
     */
    protected function addStylesheetEmbed(string $content, string $device = null, string $media_query = null) : self
    {
        $this->adapter->style[$this->attrMedia($device, $media_query)][] = $content;

        return $this;
    }

    /**
     * @param string $filename_or_url
     * @param string|null $device
     * @param string|null $media_query
     * @return $this
     * @throws Exception
     */
    protected function addFont(string $filename_or_url, string $device = null, string $media_query = null) : self
    {
        $this->addAsset($this->adapter->fonts, Resource::TYPE_ASSET_FONTS, $this->attrMedia($device, $media_query), $filename_or_url);

        return $this;
    }

    /**
     * @param string $filename_or_url
     * @param string|null $location
     * @return $this
     * @throws Exception
     */
    protected function addJavascript(string $filename_or_url, string $location = null) : self
    {
        $this->addAsset($this->adapter->js, Resource::TYPE_ASSET_JS, $location ?? self::ASSET_LOCATION_DEFAULT, $filename_or_url);

        return $this;
    }

    /**
     * @param string $filename_or_url
     * @return $this
     * @throws Exception
     */
    protected function addJavascriptAsync(string $filename_or_url) : self
    {
        $this->addAsset($this->adapter->js, Resource::TYPE_ASSET_JS, self::ASSET_LOCATION_ASYNC, $filename_or_url);

        return $this;
    }

    /**
     * @param string $content
     * @param string|null $location
     * @return $this
     */
    protected function addJavascriptEmbed(string $content, string $location = null) : self
    {
        $this->adapter->js_embed[$content]      = $location ?? self::ASSET_LOCATION_DEFAULT;

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    protected function addStructuredData(array $data) : self
    {
        $this->adapter->structured_data         = array_replace($this->adapter->structured_data, $data);

        return $this;
    }

    /**
     * @param string $content
     * @param string|null $type
     * @return $this
     */
    protected function addJsTemplate(string $content, string $type = null) : self
    {
        $this->adapter->js_template[$content]   = $type;

        return $this;
    }

    /**
     * @param string|null $device
     * @param string|null $media_query
     * @return string
     */
    private function attrMedia(string $device = null, string $media_query = null) : string
    {
        return $device .
            (
                $device && $media_query
                ? " and "
                : null
            ) .
            (
                $media_query
                ? "(" . $media_query . ")"
                : null
            );
    }

    /**
     * Standard Method
     * ------------------------------------------------------------------------
     */

    /**
     * @param int $status
     * @param string|null $msg
     * @return $this
     */
    public function error(int $status, string $msg = null) : self
    {
        $this->http_status_code     = $status;
        $this->error                = $msg;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function display() : void
    {
        $this->render();

        $this->adapter
            ->assign(self::TPL_VAR_DEFAULT, $this->view)
            ->display();
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function renderDefault() : ?string
    {
        $this->render(self::METHOD_DEFAULT);
        return ($this->view
            ? $this->view->display()
            : null
        );
    }

    /**
     * @return array
     * @throws Exception
     */
    private function toArray() : array
    {
        return [
            "css"               => $this->adapter->css,
            "style"             => $this->adapter->style,
            "fonts"             => $this->adapter->fonts,
            "js"                => $this->adapter->js,
            "js_embed"          => $this->adapter->js_embed,
            "js_template"       => $this->adapter->js_template,
            "structured_data"   => $this->adapter->structured_data,
            "html"              => $this->renderDefault()
        ];
    }


    /**
     * @return DataHtml
     * @throws Exception
     */
    public function snippet() : DataHtml
    {
        return new DataHtml($this->toArray());
    }

    /**
     * @param string $widgetName
     * @param array|null $config
     * @throws Exception
     */
    protected function replaceWith(string $widgetName, array $config = null) : void
    {
        $this->requiredJs       = [];
        $this->requiredCss      = [];
        /**
         * @var Controller $controller
         */
        $controller             = new $widgetName($config);
        $controller->render($this->method);
        $this->view             = $controller->view;
    }


    /**
     * @param DataAdapter|array $data
     * @param array $headers
     * @throws Exception
     * @todo da tipizzare
     */
    protected function send($data, array $headers = []) : void
    {
        if (is_array($data)) {
            Response::sendJson($data, $headers);
        } else {
            Response::send($data, $headers);
        }
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
     * @param array|null $assign
     * @throws Exception
     */
    protected function default(array $assign = null) : void
    {
        $this
            ->layout($this->layout, true)
                ->assign(
                    self::TPL_VAR_DEFAULT,
                    $this->view()
                        ->assign($assign)
                );
    }

    /**
     * @param string|null $layout_name
     * @param bool $include_layout_assets
     * @param string|null $theme_name
     * @return ControllerAdapter
     * @throws Exception
     */
    protected function layout(string $layout_name = null, bool $include_layout_assets = false, string $theme_name = null) : ControllerAdapter
    {
        $this->adapter->setLayout($this->getLayout($layout_name), $this->getTheme($theme_name));
        if ($layout_name && $include_layout_assets) {
            $this->addStylesheet($layout_name);
            $this->addJavascriptAsync($layout_name);
        }

        return $this->adapter;
    }

    /**
     * @param string|null $template_name
     * @return string
     */
    private function getTemplate(string $template_name = null) : string
    {
        return $template_name ?? $this->class_name;
    }

    /**
     * @param string|null $template_name
     * @param bool $include_assets
     * @return View
     * @throws Exception
     * @todo da gestire il tema
     */
    private function loadView(string $template_name = null, bool $include_assets = true) : View
    {
        $template                       = $this->getTemplate($template_name);
        if (!($file_path = Resource::get(str_replace(['.tpl', '.html'], '', $template), Resource::TYPE_VIEWS))) {
            throw new Exception("View not Found: " . $template . " in " . static::class, 500);
        }

        if ($include_assets) {
            $this->addStylesheet($template);
            $this->addJavascriptAsync($template);
        }

        return $this->view              = (new View(null, $this->templateEngine))
                                            ->fetch($file_path)
                                            ->assign($this->assigns);
    }

    /**
     * @param string|null $template_name
     * @param bool $include_template_assets
     * @return View
     * @throws Exception
     */
    protected function view(string $template_name = null, bool $include_template_assets = true) : View
    {
        return $this->view ?? $this->loadView($template_name, $include_template_assets);
    }

    /**
     * Private Method
     * ------------------------------------------------------------------------
     */


    /**
     * @param string|null $layout_name
     * @return string|null
     */
    private function getLayout(string $layout_name = null) : ?string
    {
        return $layout_name ?? $this->layout;
    }

    /**
     * @param string|null $theme_name
     * @return string|null
     */
    private function getTheme(string $theme_name = null) : ?string
    {
        return $theme_name ?? $this->theme;
    }


    /**
     * @param string|null $method
     * @throws Exception
     */
    private function render(string $method = null) : void
    {
        $bucket = static::ERROR_BUCKET . "/" . $this->class_name;
        Debug::stopWatch($bucket);

        $this->{$method ?? $this->method}();

        $this->parseAssets();

        Debug::stopWatch($bucket);
    }

    /**
     * @throws Exception
     */
    private function parseAssets() : void
    {
        foreach ($this->requiredJs as $js) {
            $this->addJavascriptAsync($js);
        }
        foreach ($this->requiredCss as $css) {
            $this->addStylesheet($css);
        }
    }
}
