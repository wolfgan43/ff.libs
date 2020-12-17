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
 */
abstract class Controller
{
    //add content invertire valore / chiave trasformandolo in chiave valore

    //rimuovere addjs e addcss in favore di addStylesheet e addJavascript
    //in addjavascript e addStylesheet usare la chiave, se no verificare il path e scriverlo
    //in add content recuperare le view php o censirle in qualche modo
    //far caricare i controller automaticamente sia in layout che in view

    use AdapterManager;
    use InternationalManager;
    use ClassDetector;

    protected const ERROR_BUCKET                = "controller";

    private const CONTROLLER_TYPE_DEFAULT       = "html";
    private const TEMPLATE_DEFAULT              = "responsive";
    private const THEME_DEFAULT                 = "default";
    private const METHOD_DEFAULT                = "get";
    private const LAYOUT_DEFAULT                = "<main>{content}</main>";

    protected $method                           = null;
    protected $headers                          = null;
    protected $authorization                    = null;
    protected $request                          = null;
    protected $script_path                      = null;
    protected $path_info                        = null;
    protected $isXhr                            = null;

    protected $controllerType                   = null;
    protected $templateType                     = self::TEMPLATE_DEFAULT;
    protected $theme                            = self::THEME_DEFAULT;
    protected $layout                           = self::LAYOUT_DEFAULT;

    protected $http_status_code                 = 200;
    protected $error                            = null;

    protected $class_name                       = null;
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
    public static function toArray(array $config = null) : array
    {
        return (new static($config))->renderGet();
    }

    /**
     * Controller constructor.
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        $page                       = App::configuration()->page;

        $this->class_name           = str_replace(self::ERROR_BUCKET, "", strtolower($this->getClassName()));
        $this->method               = $page->method;
        $this->request              = (object) $page->getRequest();
        $this->headers              = (object) $page->getHeaders();
        $this->authorization        = $page->getAuthorization();
        $this->script_path          = $page->script_path;
        $this->path_info            = $page->path_info;
        $this->isXhr                = $page->isAjax;

        $this->config               = $config;
        $this->cache_time           = Kernel::useCache() ? null : "?" . time();


        $this->setAdapter($this->controllerType ?? Kernel::$Environment::CONTROLLER_ADAPTER ?? self::CONTROLLER_TYPE_DEFAULT, [$this->path_info, $this->http_status_code, $this->templateType]);
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
        $this->doc_type                         = $doc_type;

        return $this;
    }
    /**
     * @param string $body_class
     * @return $this
     */
    protected function setBodyClass(string $body_class) : self
    {
        $this->body_class                       = $body_class;

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
        return Media::getUrl(Resource::get($filename_or_url, Resource::TYPE_ASSET_IMAGES) ?? $this->maskEnv($filename_or_url), $mode);
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

    private $title              = null; //da gestire
    private $description        = null; //da gestire
    private $hreflang           = null; //da gestire
    private $canonical          = null; //da gestire
    private $next               = null; //da gestire
    private $prev               = null; //da gestire
    private $author             = null; //da gestire
    private $manifest           = null; //da gestire
    private $amp                = null; //da gestire
    private $rss                = null; //da gestire

    private $css                = [];
    private $style              = [];
    private $fonts              = [];
    private $js                 = [];
    private $js_embed           = [];
    private $js_template        = [];
    private $structured_data    = [];

    private $meta               = [];

    private $doc_type                            = null;
    private $body_class                          = null;

    /**
     * @param Controller $controller
     * @return self
     */
    private function replaceAssetsWith(Controller $controller) : self
    {
        $this->css              = $controller->css;
        $this->style            = $controller->style;
        $this->fonts            = $controller->fonts;
        $this->js               = $controller->js;
        $this->js_embed         = $controller->js_embed;
        $this->js_template      = $controller->js_template;
        $this->structured_data  = $controller->structured_data;

        return $this;
    }
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
        $this->meta[$key]                       = array(
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
        $this->addAsset($this->{Resource::TYPE_ASSET_CSS}, Resource::TYPE_ASSET_CSS, $this->attrMedia($device, $media_query), $filename_or_url);

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
        $this->style[$this->attrMedia($device, $media_query)][] = $content;

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
        $this->addAsset($this->{Resource::TYPE_ASSET_FONTS}, Resource::TYPE_ASSET_FONTS, $this->attrMedia($device, $media_query), $filename_or_url);

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
        $this->addAsset($this->{Resource::TYPE_ASSET_JS}, Resource::TYPE_ASSET_JS, $location ?? self::ASSET_LOCATION_DEFAULT, $filename_or_url);

        return $this;
    }

    /**
     * @param string $filename_or_url
     * @return $this
     * @throws Exception
     */
    protected function addJavascriptAsync(string $filename_or_url) : self
    {
        $this->addAsset($this->{Resource::TYPE_ASSET_JS}, Resource::TYPE_ASSET_JS, self::ASSET_LOCATION_ASYNC, $filename_or_url);

        return $this;
    }

    /**
     * @param string $content
     * @param string|null $location
     * @return $this
     */
    protected function addJavascriptEmbed(string $content, string $location = null) : self
    {
        $this->js_embed[$content] = $location ?? self::ASSET_LOCATION_DEFAULT;

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    protected function addStructuredData(array $data) : self
    {
        $this->structured_data = array_replace($this->structured_data, $data);

        return $this;
    }

    /**
     * @param string $content
     * @param string|null $type
     * @return $this
     */
    protected function addJsTemplate(string $content, string $type = null) : self
    {
        $this->js_template[$content] = $type;

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

        $this->page(true)
            ->addContent($this->view)
            ->display();
    }

    /**
     * @return DataHtml
     * @throws Exception
     */
    public function snippet() : DataHtml
    {
        return new DataHtml($this->renderGet());
    }

    /**
     * @return array
     * @throws Exception
     */
    private function renderGet() : array
    {
        $this->render("get");
        return [
            "css"               => $this->css,
            "style"             => $this->style,
            "fonts"             => $this->fonts,
            "js"                => $this->js,
            "js_embed"          => $this->js_embed,
            "js_template"       => $this->js_template,
            "structured_data"   => $this->structured_data,
            "html"              => (
                $this->view
                ? $this->view->display()
                : null
            )
        ];
    }

    /**
     * @param string $widgetName
     * @param array|null $config
     * @throws Exception
     */
    protected function replaceWith(string $widgetName, array $config = null) : void
    {
        /**
         * @var Controller $controller
         */
        $controller = new $widgetName($config);
        $controller->render($this->method);
        $this->replaceAssetsWith($controller);

        $this->view = $controller->view;
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
                ->addContent(
                    $this->view()
                        ->assign($assign)
                );
    }

    /**
     * @param string|null $layout_name
     * @param bool $include_default_assets
     * @param string|null $theme_name
     * @return ControllerAdapter
     * @throws Exception
     */
    protected function layout(string $layout_name = null, bool $include_default_assets = false, string $theme_name = null) : ControllerAdapter
    {
        $this->page()->setLayout($this->getLayout($layout_name), $this->getTheme($theme_name));
        if ($layout_name && $include_default_assets) {
            $this->addStylesheet($layout_name);
            $this->addJavascriptAsync($layout_name);
        }

        return $this->page();
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
     * @param string|null $theme_name
     * @return View
     * @throws Exception
     * @todo da gestire il tema
     */
    private function loadView(string $template_name = null, string $theme_name = null) : View
    {
        $template                       = $this->getTemplate($template_name);

        if (!($file_path = Resource::get($template, Resource::TYPE_VIEWS))) {
            throw new Exception("View not Found: " . $template . " in " . Resource::TYPE_VIEWS, 500);
        }

        return $this->view = (new View())->fetch($file_path);
    }

    /**
     * @param string|null $template_name
     * @param string|null $theme_name
     * @return View
     * @throws Exception
     */
    protected function view(string $template_name = null, string $theme_name = null) : View
    {
        return $this->view ?? $this->loadView($template_name, $theme_name);
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
     * @todo da trovare un modo per renderla private
     */
    protected function parseAssets() : void
    {
        $this
            ->addStylesheet($this->class_name)
            ->addJavascriptAsync($this->class_name);
    }

    /**
     * @param bool $include_assets
     * @return ControllerAdapter
     */
    private function page(bool $include_assets = false)
    {
        if ($include_assets) {
            $this->adapter->includeAssets(
                $this->css,
                $this->style,
                $this->fonts,
                $this->js,
                $this->js_embed,
                $this->js_template,
                $this->structured_data,
                $this->meta,
                $this->body_class,
                $this->doc_type
            );
        }

        return $this->adapter;
    }
}
