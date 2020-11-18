<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\App;
use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\dto\DataAdapter;
use phpformsframework\libs\Env;
use phpformsframework\libs\Error;
use phpformsframework\libs\gui\adapters\ControllerHtml;
use phpformsframework\libs\international\InternationalManager;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Response;
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

    protected $method                           = null;
    protected $headers                          = null;
    protected $request                          = null;
    protected $script_path                      = null;
    protected $path_info                        = null;
    protected $isXhr                            = null;

    protected $controllerType                   = null;
    protected $templateType                     = self::TEMPLATE_DEFAULT;
    protected $theme                            = self::THEME_DEFAULT;
    protected $layout                           = null;

    protected $http_status_code                 = 200;
    protected $error                            = null;

    /**
     * @var View[]
     */
    private $views                              = [];

    /**
     * @var View
     */
    private $view                               = null;

    abstract protected function get()           : void;
    abstract protected function post()          : void;
    abstract protected function put()           : void;
    abstract protected function delete()        : void;
    abstract protected function patch()         : void;

    /**
     * Controller constructor.
     * @param string|null $templateType
     * @param string|null $controllerAdapter
     */
    public function __construct(string $templateType = null, string $controllerAdapter = null)
    {
        $page                       = App::configuration()->page;

        $this->method               = $page->method;
        $this->request              = (object) $page->getRequest();
        $this->headers              = (object) $page->getHeaders();
        $this->script_path          = $page->script_path;
        $this->path_info            = $page->path_info;
        $this->isXhr                = $page->isAjax;

        $this->setAdapter($controllerAdapter ?? $this->controllerType ?? Kernel::$Environment::CONTROLLER_ADAPTER ?? self::CONTROLLER_TYPE_DEFAULT, [$this->path_info, $this->http_status_code, $templateType ?? $this->templateType]);
    }

    /**
     * Utility Builder
     * ------------------------------------------------------------------------
     */

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

        $this->page()
            ->addContent($this->view)
            ->display();
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
        $controller_name            = str_replace("controller", "", strtolower($this->getClassName()));
        $this->layout(null, true)
            ->addJs($controller_name)
            ->addCss($controller_name)
            ->addContent(
                $this->view()
                ->assign($assign)
            );
    }

    /**
     * @param string|null $layout_name
     * @param bool $include_default_assets
     * @param string|null $theme_name
     * @return ControllerHtml
     * @throws Exception
     */
    protected function layout(string $layout_name = null, bool $include_default_assets = false, string $theme_name = null)
    {
        $this->page()->setLayout($this->getLayout($layout_name), $this->getTheme($theme_name));
        if ($layout_name && $include_default_assets) {
            $this->page()->addCss($layout_name);
            $this->page()->addJs($layout_name);
        }
        return $this->page();
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
        $template                       = $this->getTemplate($template_name);
        if (!($file_path = Resource::get($template, Resource::TYPE_VIEWS))) {
            Error::register("View not Found: " . $template . " in " . Resource::TYPE_VIEWS, static::ERROR_BUCKET);
        }

        if (!isset($this->views[$file_path])) {
            $this->views[$file_path]    = (new View())->fetch($file_path);
        }

        return $this->view              =& $this->views[$file_path];
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
     * @param string|null $template_name
     * @return string
     */
    private function getTemplate(string $template_name = null) : string
    {
        return strtolower($template_name ?? str_replace("Controller", "", $this->getClassName()));
    }

    private function render(string $method = null) : void
    {
        $this->{$method ?? $this->method}();
    }

    /**
     * @return ControllerHtml
     */
    private function page()
    {
        return $this->adapter;
    }
}
