<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\App;
use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\Error;
use phpformsframework\libs\gui\adapters\ControllerHtml;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\AdapterManager;
use Exception;

/**
 * Class Controller
 * @package phpformsframework\libs\gui
 */
abstract class Controller
{
    use AdapterManager;
    use ClassDetector;

    protected const ERROR_BUCKET        = "controller";
    private const TEMPLATE_DEFAULT      = "responsive";

    private $adapterName                = null;
    private $method                     = null;

    protected $headers                  = null;
    protected $request                  = null;
    protected $script_path              = null;
    protected $path_info                = null;
    protected $xhr                      = null;

    protected $template                 = self::TEMPLATE_DEFAULT;
    protected $layout                   = null;
    protected $theme                    = null;

    protected $http_status_code         = 200;
    protected $error                    = null;

    abstract public function get()      : void;
    abstract public function post()     : void;
    abstract public function put()      : void;
    abstract public function delete()   : void;
    abstract public function patch()    : void;

    /**
     * Controller constructor.
     * @param string|null $controllerAdapter
     */
    public function __construct(string $controllerAdapter = null)
    {
        $page               = App::configuration()->page;

        $this->adapterName  = $controllerAdapter ?? Kernel::$Environment::CONTROLLER_ADAPTER;
        $this->method       = $page->method;
        $this->request      = (object) $page->getRequest();
        $this->headers      = (object) $page->getHeaders();
        $this->script_path  = $page->script_path;
        $this->path_info    = $page->path_info;
        $this->xhr          = $page->isAjax;
    }

    /**
     * @param string|null $template_type
     * @return ControllerHtml
     */
    private function adapter(string $template_type = null) : ControllerHtml
    {
        if (!$this->adapter) {
            $this->setAdapter($this->adapterName, [$this->path_info, $this->http_status_code, $template_type ?? $this->template]);
        }

        return $this->adapter;
    }

    /**
     * @param int $status
     * @param string|null $msg
     * @return $this
     */
    public function error(int $status, string $msg = null) : self
    {
        $this->http_status_code = $status;
        $this->error            = $msg;

        return $this;
    }

    /**
     *
     */
    public function display() : void
    {
        $this->{$this->method}();
    }

    /**
     * @param array|null $assign
     * @throws Exception
     */
    protected function default(array $assign = null) : void
    {
        $controller_name = str_replace("controller", "", strtolower($this->getClassName()));
        $this->layout()
            ->addJs($controller_name)
            ->addCss($controller_name)
            ->addContent(
                $this->view()
                ->assign($assign)
            )->display();
    }

    /**
     * @param string|null $layout_name
     * @param string|null $theme_name
     * @param string|null $template_type
     * @return ControllerHtml
     * @throws Exception
     */
    protected function layout(string $layout_name = null, string $theme_name = null, string $template_type = null)
    {
        return $this->adapter($template_type)->setLayout($this->getLayout($layout_name), $this->getTheme($theme_name));
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
        $template               = $this->getTemplate($template_name);
        $theme                  = $theme_name ?? "common";
        if (!($file_path = Resource::get($template, $theme))) {
            Error::register("View not Found: " . $template . " in " . $theme, static::ERROR_BUCKET);
        }
        return (new View())
                ->fetch($file_path);
    }

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
}
