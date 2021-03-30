<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Debug;
use phpformsframework\libs\storage\Media;
use Exception;
use stdClass;

/**
 * Class Widget
 * @package phpformsframework\libs\gui
 */
abstract class Widget extends Controller
{
    private const VIEW_DEFAULT                      = "index";

    protected const ERROR_BUCKET                    = "widget";

    private $config                                 = [];
    private $resources                              = null;

    /**
     * Widget constructor.
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        parent::__construct($config);

        $this->config                               = $config ?? [];
    }

    /**
     * Utility Builder
     * ------------------------------------------------------------------------
     */

    /**
     * @param string $filename_or_url
     * @param string|null $mode
     * @return string
     * @throws Exception
     */
    public function getImageUrl(string $filename_or_url, string $mode = null): string
    {
        $resources = $this->getResources();
        if (!empty($resources->images[$filename_or_url])) {
            return Media::getUrl($resources->images[$filename_or_url], $mode);
        }

        return  parent::getImageUrl($filename_or_url, $mode);
    }

    /**
     * Assets Method
     * ------------------------------------------------------------------------
     */

    /**
     * @param string $filename_or_url
     * @param string|null $device
     * @param string|null $media_query
     * @return Controller
     * @throws Exception
     */
    protected function addStylesheet(string $filename_or_url, string $device = null, string $media_query = null) : Controller
    {
        $resources = $this->getResources();
        if (!empty($resources->css[$filename_or_url])) {
            $filename_or_url = $resources->css[$filename_or_url];
        }

        return parent::addStylesheet($filename_or_url, $device, $media_query);
    }

    /**
     * @param string $filename_or_url
     * @param string|null $device
     * @param string|null $media_query
     * @return Controller
     * @throws Exception
     */
    protected function addFont(string $filename_or_url, string $device = null, string $media_query = null) : Controller
    {
        $resources = $this->getResources();
        if (!empty($resources->font[$filename_or_url])) {
            $filename_or_url = $resources->font[$filename_or_url];
        }

        return parent::addFont($filename_or_url, $device, $media_query);
    }


    /**
     * @param string $filename_or_url
     * @param string|null $location
     * @return Controller
     * @throws Exception
     */
    protected function addJavascript(string $filename_or_url, string $location = null): Controller
    {
        $resources = $this->getResources();
        if (!empty($resources->js[$filename_or_url])) {
            $filename_or_url = $resources->js[$filename_or_url];
        }

        return parent::addJavascript($filename_or_url, $location);
    }

    /**
     * @param string $filename_or_url
     * @param bool $async
     * @return Controller
     * @throws Exception
     */
    protected function addJavascriptDefer(string $filename_or_url, bool $async = false): Controller
    {
        $resources = $this->getResources();
        if (!empty($resources->js[$filename_or_url])) {
            $filename_or_url = $resources->js[$filename_or_url];
        }

        return parent::addJavascriptDefer($filename_or_url, $async);
    }

    /**
     * Standard Method
     * ------------------------------------------------------------------------
     */


    /**
     * @param string|null $template_name
     * @param bool $include_template_assets
     * @return View
     * @throws Exception
     */
    protected function view(string $template_name = null, bool $include_template_assets = true) : View
    {
        if (!empty($this->view)) {
            return $this->view;
        }

        $template                                   = $template_name ?? self::VIEW_DEFAULT;
        $resources                                  = $this->getResources();

        if (empty($resources->tpl[$template])) {
            throw new Exception("Template: " . $template . " not found for Widget " . $this->class_name, 404);
        }

        if ($include_template_assets) {
            if (!empty($resources->js[$template])) {
                $this->requiredJs[]                 = $resources->js[$template];
            }
            if (!empty($resources->css[$template])) {
                $this->requiredCss[]                = $resources->css[$template];
            }
        }

        return $this->view = (new View($this->config($resources->cfg[$template] ?? null)))->fetch($resources->tpl[$template]);
    }

    /**
     * @param string|null $config_name
     * @return object|null
     */
    protected function getConfig(string $config_name = null) : ?object
    {
        return $this->config($this->getResources()->cfg[$config_name ?? self::VIEW_DEFAULT] ?? null);
    }

    /**
     * @param string|null $file_path
     * @return stdClass|null
     */
    private function config(string $file_path = null) : ?stdClass
    {
        static $configs                             = null;

        if (!isset($configs[$file_path])) {
            if ($file_path) {
                $configs[$file_path]                = $this->loadConfig($file_path);
                foreach ($this->config as $key => $config) {
                    $configs[$file_path]->$key      = $config;
                }
            } else {
                $configs[$file_path]                = (object) $this->config;
            }
        }

        return $configs[$file_path] ?? null;
    }

    /**
     * @param string $file_path
     * @return stdClass
     */
    private function loadConfig(string $file_path) : stdClass
    {
        //@todo da finire inserendo in FilemanagerFS una chiamata semplice senza controlli
        return json_decode(file_get_contents($file_path));

        $bucket                                     = $this->class_name . "/" . basename($file_path);

        Debug::stopWatch(static::ERROR_BUCKET . "/map/" . $bucket);
        $cache                                      = Buffer::cache("widget");
        $config                                     = $cache->get($bucket);
        if (!$config) {
            $config                                 = json_decode(file_get_contents($file_path));

            $cache->set($bucket, $config, [$file_path => filemtime($file_path)]);
        }

        Debug::stopWatch(static::ERROR_BUCKET . "/map/". $bucket);

        return $config;
    }
    /**
     * @return stdClass
     */
    private function getResources() : stdClass
    {
        if (!isset($this->resources[$this->class_name])) {
            $this->resources[$this->class_name]                 = Resource::widget($this->class_name);
        }

        return (object) ($this->resources[$this->class_name] ?? null);
    }
}
