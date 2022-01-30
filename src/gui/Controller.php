<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace phpformsframework\libs\gui;

use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\dto\DataAdapter;
use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\Env;
use phpformsframework\libs\international\InternationalManager;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\microservice\Api;
use phpformsframework\libs\Response;
use phpformsframework\libs\security\User;
use phpformsframework\libs\security\UserData;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\FilemanagerFs;
use phpformsframework\libs\storage\Media;
use phpformsframework\libs\util\AdapterManager;
use phpformsframework\libs\Exception;

/**
 * Class Controller
 * @package phpformsframework\libs\gui
 */
abstract class Controller
{
    use AdapterManager;
    use InternationalManager;
    use ClassDetector;

    protected const LAYOUT                      = self::LAYOUT_DEFAULT;
    protected const TEMPLATE_ENGINE             = null;
    protected const CONTROLLER_ENGINE           = "html";
    protected const CONTROLLER_TYPE             = "responsive";
    protected const THEME                       = "default";

    protected const ERROR_VIEW                  = null;

    protected const TPL_ENGINE_DEFAULT          = "Html";
    protected const TPL_ENGINE_SMARTY           = "Smarty";
    protected const TPL_ENGINE_BLADE            = "Blade";

    protected const TPL_VAR_DEFAULT             = "content";
    private const TPL_VAR_PREFIX                = '$';
    private const CONTROLLER_PREFIX             = "controller";

    private const LAYOUT_DEFAULT                = '<main>{' . self::TPL_VAR_PREFIX . 'content}</main>';
    private const TPL_NORMALIZE                 = ['../', '.tpl'];

    private const ERROR_PAGE_NOT_FOUND_CODE         = 404;
    private const ERROR_PAGE_NOT_FOUND              = "Page not found";
    private const ERROR_COMPONENT_NOT_IMPLEMENTED   = "Component not implemented";

    private const COMPONENT_DATA_RECORD         = 'dr';
    private const COMPONENT_DATA_FIELD          = 'df';

    public const METHOD_DEFAULT                = "get";

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

    protected $error                            = null;

    protected $class_name                       = null;

    protected $requiredJs                       = [];
    protected $requiredCss                      = [];
    protected $requiredFonts                    = [];
    protected $assigns                          = [];

    protected $http_status_code                 = null;

    private $config                             = null;
    private $response                           = null;
    private $route                              = null;

    private $contentEmpty                       = true;
    private $layoutException                    = null;

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
     * @param string|null $method
     * @return array
     * @throws Exception
     */
    public static function toArray(array $config = null, string $method = null) : array
    {
        $controller = new static($config);

        return $controller->adapter->toArray($controller->html($method));
    }

    /**
     * @param string|null $destination
     * @param int|null $http_response_code
     * @param array|null $headers
     */
    protected static function redirect(string $destination = null, int $http_response_code = null, array $headers = null)
    {
        Response::redirect($destination, $http_response_code, $headers);
    }

    /**
     * Controller constructor.
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        $page                                   = Kernel::$Page;

        $this->class_name                       = str_replace(self::CONTROLLER_PREFIX, "", strtolower($this->getClassName()));
        $this->method                           = $page->method;
        $this->request                          = (object) $page->getRequest();
        $this->headers                          = (object) $page->getHeaders();
        $this->authorization                    = $page->getAuthorization();
        $this->script_path                      = $page->script_path;
        $this->path_info                        = $page->path_info;
        $this->isXhr                            = $page->isAjax();

        $this->config                           = $config;
        $this->response                         = new DataResponse();

        $this->layoutException                  = $page->layout_exception;

        if (!empty($page->status)) {
            $this->error                        = $page->error;
            $this->http_status_code             = $page->status;
        }

        $adapter                                = static::CONTROLLER_ENGINE ?? Kernel::$Environment::CONTROLLER_ADAPTER;
        $bucket                                 = $adapter . DIRECTORY_SEPARATOR . static::CONTROLLER_TYPE;
        if (!isset(self::$controllers[$bucket])) {
            self::$controllers[$bucket]        = $this->setAdapter($adapter, [$this->script_path, $page->layout_type ?? static::CONTROLLER_TYPE,  $page->layout ?? static::LAYOUT]);
        }

        $this->adapter                          =& self::$controllers[$bucket];

        if (!empty($page->title)) {
            $this->setTitle($page->title);
        }
    }

    /**
     * @param string $key
     * @param mixed|null $value
     * @return mixed|null
     */
    protected function env(string $key, $value = null)
    {
        if (isset($value)) {
            Env::set($key, $value, true);
        }

        return Env::get($key);
    }

    /**
     * @return UserData
     * @throws Exception
     */
    protected function userSession() : UserData
    {
        return User::get();
    }

    /**
     * @param string $api_path
     * @param array $headers
     * @return Api
     */
    protected function api(string $api_path, array $headers = []) : Api
    {
        return new Api($api_path, $headers);
    }

    protected function redirectBack() : void
    {
        $this->redirect($this->request->redirect ?? dirname($this->script_path));
    }
    /**
     * Utility Builder
     * ------------------------------------------------------------------------
     */


    /**
     * @param string $title
     * @return $this
     */
    protected function setTitle(string $title) : self
    {
        $this->adapter->title                   = $title;

        return $this;
    }

    /**
     * @param string $abstract
     * @return $this
     */
    protected function setDescription(string $abstract) : self
    {
        $this->adapter->description             = $abstract;

        return $this;
    }

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
    protected function getWebUrl(string $relative_path) : string
    {
        return Kernel::$Environment::SITE_PATH . $this->maskEnv($relative_path);
    }

    /**
     * @param string $filename_or_url
     * @param string|null $mode
     * @return string
     * @throws Exception
     */
    protected function getImageUrl(string $filename_or_url, string $mode = null) : string
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
    protected function getImageTag(string $filename_or_url, string $mode = null, string $alt = null) : string
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
    private const ASSET_LOCATION_ASYNC          = ControllerAdapter::ASSET_LOCATION_ASYNC;
    private const ASSET_LOCATION_DEFER          = ControllerAdapter::ASSET_LOCATION_DEFER;
    private const ASSET_LOCATION_HEAD           = ControllerAdapter::ASSET_LOCATION_HEAD;
    private const ASSET_LOCATION_BODY_TOP       = ControllerAdapter::ASSET_LOCATION_BODY_TOP;
    private const ASSET_LOCATION_BODY_BOTTOM    = ControllerAdapter::ASSET_LOCATION_BODY_BOTTOM;
    private const ASSET_MIN                     = ".min";

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
        $key                                                                    = rtrim($key, "." . $type);
        $limit                                                                  = (
            substr($key, -4) === self::ASSET_MIN
            ? substr_count($key, ".")
            : PHP_INT_MAX
        );

        $assets                                                                 = explode(".", $key, $limit);
        foreach ($assets as $asset) {
            $asset_name                                                         .= $asset;
            if (($asset_url = Resource::get($asset_name, $type)) && filesize($asset_url)) {
                $ref[Media::getUrlRelative($asset_url)]                         = $media;
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
        if (strpos($filename_or_url, "data:") === 0 || Validator::isUrl($filename_or_url)) {
            $ref[$this->maskEnv($filename_or_url)]                                      = $media;
        } elseif (Validator::isFile($filename_or_url)) {
            if (strpos($filename_or_url, DIRECTORY_SEPARATOR) === 0 && Dir::checkDiskPath($filename_or_url)) {
                if (filesize($filename_or_url)) {
                    $ref[Media::getUrlRelative($filename_or_url)]                       = $media;
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
     * @param string $encoding
     * @return $this
     */
    protected function setEncoding(string $encoding) : self
    {
        $this->adapter->encoding                = $encoding;

        return $this;
    }

    /**
     * @param string $key
     * @param string $content
     * @param string $type
     * @param string $type_content
     * @return $this
     */
    public function addMeta(string $key, string $content, string $type = "name", string $type_content = "content") : self
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
    public function addStylesheet(string $filename_or_url, string $device = null, string $media_query = null) : self
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
    public function addStylesheetEmbed(string $content, string $device = null, string $media_query = null) : self
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
    public function addFont(string $filename_or_url, string $device = null, string $media_query = null) : self
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
    public function addJavascript(string $filename_or_url, string $location = null) : self
    {
        $this->addAsset($this->adapter->js, Resource::TYPE_ASSET_JS, $location ?? Kernel::$Environment::ASSET_LOCATION_DEFAULT, $filename_or_url);

        return $this;
    }

    /**
     * @param string $content
     * @param string|null $location
     * @return $this
     * @throws Exception
     */
    public function addJavascriptEmbed(string $content, string $location = null) : self
    {
        if (!$location && Kernel::$Environment::ASSET_LOCATION_DEFAULT == self::ASSET_LOCATION_DEFER) {
            $this->addJavascript("data:text/javascript;base64," . base64_encode($content), self::ASSET_LOCATION_DEFER);
        } else {
            $this->adapter->js_embed[$content] = $location ?? Kernel::$Environment::ASSET_LOCATION_DEFAULT;
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function addStructuredData(array $data) : self
    {
        $this->adapter->json_ld             = array_replace($this->adapter->json_ld, $data);

        return $this;
    }

    /**
     * @param string $id
     * @param string $content
     * @param string|null $type
     * @return $this
     */
    public function addJsTemplate(string $id, string $content, string $type = null) : self
    {
        $this->adapter->js_tpl[$id]         = [
            "content"   => $content,
            "type"      => $type
        ];

        return $this;
    }

    /**
     * @return DataResponse
     */
    private function &response() : DataResponse
    {
        return $this->response;
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
    protected function error(int $status, string $msg = null) : self
    {
        $this->debug($msg);

        $this->http_status_code     = $status;
        $this->error                = $msg;

        return $this;
    }

    /**
     * @param string|null $method
     * @return DataAdapter
     * @throws Exception
     */
    public function display(string $method = null) : DataAdapter
    {
        if ($this->error) {
            if ($this->isXhr) {
                return $this->response()->error($this->http_status_code, $this->error);
            } elseif (!empty(static::ERROR_VIEW)) {
                $this->{static::ERROR_VIEW}();

                return $this->layout();
            } else {
                throw new Exception($this->error, $this->http_status_code);
            }
        }

        if ($this->isXhr && !empty($this->request->component)) {
            $component = explode(":", $this->request->component, 2);
            if (class_exists($component[0])) {
                $this->send($component[0]::xhr($component[1]));
            } else {
                throw new Exception(self::ERROR_COMPONENT_NOT_IMPLEMENTED, 501);
            }
        }

        $this->route = $method ?? self::METHOD_DEFAULT;

        try {
            $this->render($method);
        } catch (\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }

        if ($this->isXhr) {
            if (!$this->view) {
                $this->{static::ERROR_VIEW ?? $this->route}();
            }

            return ($this->view && !$this->error
                ? $this->response()->fill($this->adapter->toArray($this->view->html()))
                : $this->response()->error($this->http_status_code ?? self::ERROR_PAGE_NOT_FOUND_CODE, $this->error ?? self::ERROR_PAGE_NOT_FOUND)
            );
        }

        if ($this->error) {
            if (!empty(static::ERROR_VIEW)) {
                $this->view = null;
                $this->{static::ERROR_VIEW}();
            } elseif (!$this->view) {
                $this->{$this->route}();
            } else {
                throw new Exception($this->error, $this->http_status_code);
            }
        }

        return $this->layout();
    }

    /**
     * @return DataHtml
     * @throws Exception
     */
    private function layout() : DataHtml
    {
        if ($this->contentEmpty) {
            if (!$this->view) {
                throw new Exception(self::ERROR_PAGE_NOT_FOUND, self::ERROR_PAGE_NOT_FOUND_CODE);
            }

            $this->assign(self::TPL_VAR_DEFAULT, $this->view);
        }

        if ($this->http_status_code >= 400 && $this->layoutException) {
            $this->adapter->layout = $this->layoutException;
        }

        if (!empty($this->adapter->layout)) {
            $this->addStylesheet($this->adapter->layout);
            $this->addJavascript($this->adapter->layout);
        }

        $this->addStylesheet("main");
        $this->addJavascript("main");

        return $this->adapter
            ->display($this->http_status_code);
    }

    /**
     * @param int $status
     * @param string|null $msg
     * @return DataAdapter
     * @throws Exception
     */
    public function displayException(int $status, string $msg = null) : DataAdapter
    {
        if ($this->layoutException) {
            $this->adapter->layout = $this->layoutException;
        }

        return $this
            ->error($status, $msg)
            ->display();
    }

    /**
     * @param string|null $method
     * @return string|null
     * @throws Exception
     */
    public function html(string $method = null) : ?string
    {
        $this->render($method);
        return ($this->view
            ? $this->view->html()
            : null
        );
    }

    /**
     * @param string|null $method
     * @return DataHtml
     * @throws Exception
     */
    public function snippet(string $method = null) : DataHtml
    {
        return new DataHtml($this->adapter->toArray($this->html($method)));
    }


    /**
     * @param string|null $layout_name
     * @return Controller
     * @throws Exception
     */
    protected function setLayout(string $layout_name = null) : self
    {
        $this->adapter->layout  = (
            empty($layout_name)
            ? null
            : $layout_name
        );

        return $this;
    }

    /**
     * @param string $tpl_var
     * @param string|DataHtml|View|Controller|null $value
     * @return $this
     */
    protected function assign(string $tpl_var, $value = null) : self
    {
        $this->adapter->assign($tpl_var, $value);

        if ($tpl_var == self::TPL_VAR_DEFAULT && !empty($value)) {
            $this->contentEmpty = false;
        }

        return $this;
    }

    protected function default() : View
    {
        $this->{$this->route}();

        return $this->view;
    }

    protected function debug(string $msg = null) : self
    {
        $this->adapter->debug($msg);

        return $this;
    }

    /**
     * @param string $widgetName
     * @param array|null $config
     * @param string|null $method
     * @throws Exception
     */
    protected function replaceWith(string $widgetName, array $config = null, string $method = null) : void
    {
        $this->clearAssets();
        /**
         * @var Controller $controller
         */

        $controller             = new $widgetName($config);
        $controller->render($method ?? self::METHOD_DEFAULT);
        $this->view             = $controller->view;
    }


    /**
     * @param DataAdapter|array|string $data
     * @param array $headers
     * @throws Exception
     * @todo da tipizzare
     */
    protected function send($data, array $headers = []) : void
    {
        $response = null;
        if ($data instanceof DataAdapter) {
            $response = $data;
        } elseif (is_array($data)) {
            $response = new DataResponse($data);
        } elseif (class_exists($data)) {
            $obj = new $data();
            if ($obj instanceof Controller) {
                $response = $obj->display();
            }
        } else {
            $response = new DataResponse((array) json_decode($data, true));
        }

        if (!$response) {
            throw new Exception(static::class . "::" . $this->method . ": response must be instanceOf DataAdapter", 500);
        }

        Response::send($response, $headers);
    }

    /**
     * @param string|null $template_name
     * @return string
     */
    private function getTemplateName(string $template_name = null) : string
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
        $template                       = $this->getTemplateName($template_name);
        if (!($file_path = Resource::get(str_replace(self::TPL_NORMALIZE, '', $template), Resource::TYPE_VIEWS))) {
            throw new Exception("View not Found: " . $template . " in " . static::class, 500);
        }

        if ($include_assets) {
            $this->addStylesheet($template);
            $this->addJavascript($template);
        }

        return $this->view              = (new View($file_path, static::TEMPLATE_ENGINE, $this))
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
     * @param string $template_name
     * @return string|null
     * @throws Exception
     */
    protected function getTemplate(string $template_name) : ?string
    {
        return (!empty($file_disk_path = Resource::get(str_replace(self::TPL_NORMALIZE, '', $template_name), Resource::TYPE_VIEWS))
            ? FilemanagerFs::fileGetContents($file_disk_path)
            : null
        );
    }

    /**
     * Private Method
     * ------------------------------------------------------------------------
     */

    /**
     * @param string|null $method
     * @throws Exception
     */
    private function render(string $method = null) : void
    {
        $bucket = self::CONTROLLER_PREFIX . "/" . $this->class_name;
        Debug::stopWatch($bucket);

        $method2lower = strtolower($this->method);
        if ($method && $method != $method2lower && $method2lower != self::METHOD_DEFAULT && method_exists($this, $method . $this->method)) {
            $method .= $this->method;
        }

        $callback = $method ?? $this->method;
        if (!method_exists($this, $callback)) {
            throw new Exception("Method " . $callback . " not found in class " . $this->class_name, 501);
        }
        $this->$callback();
        $this->parseAssets();

        Debug::stopWatch($bucket);
    }

    /**
     * @throws Exception
     */
    private function parseAssets() : void
    {
        foreach ($this->requiredJs as $js) {
            $this->addJavascript($js);
        }
        foreach ($this->requiredCss as $css) {
            $this->addStylesheet($css);
        }
        foreach ($this->requiredFonts as $font) {
            $this->addFont($font);
        }
    }

    /**
     *
     */
    private function clearAssets() : void
    {
        $this->requiredJs       = [];
        $this->requiredCss      = [];
        $this->requiredFonts    = [];
    }
}
