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
namespace phpformsframework\libs\gui\adapters;

use phpformsframework\libs\Autoloader;
use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\gui\Controller;
use phpformsframework\libs\gui\ControllerAdapter;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\FilemanagerWeb;
use phpformsframework\libs\storage\Media;
use phpformsframework\libs\gui\Resource;
use phpformsframework\libs\gui\View;
use Exception;

/**
 * Class PageHtml
 * @package phpformsframework\libs\gui\adapters
 */
class ControllerHtml extends ControllerAdapter
{
    private const METHOD_DEFAULT                = Controller::METHOD_DEFAULT;

    private const TPL_VAR_PREFIX                = '$';
    private const TPL_VAR_DEFAULT               = "content";

    private const MAP_PREFIX                    = "layout";
    private const STATUS_OK                     = 0;

    private const MEDIA_DEVICE_DEFAULT          = self::MEDIA_DEVICE_ALL;
    private const JS_TEMPLATE_DEFAULT           = "text/x-template";

    private const DOC_TYPE                      = '<!DOCTYPE html>';
    private const PAGE_TAG                      = 'html';
    private const HEAD_TAG                      = 'head';
    private const BODY_TAG                      = 'body';

    private const NEWLINE                       = "\n";
    private const TITLE_DEFAULT                 = "Home";

    private $path_info                          = null;
    private $lang                               = null;
    private $region                             = null;

    private $preconnect                         = [];
    private $cache_time                         = null;

    public $title                               = null;
    public $description                         = null;
    public $css                                 = [];
    public $style                               = [];
    public $fonts                               = [];
    public $js                                  = [];
    public $js_embed                            = [];
    public $js_template                         = [];
    public $structured_data                     = [];

    public $meta                                = [];

    public $encoding                            = Constant::ENCODING;
    public $doc_type                            = null;
    public $body_class                          = null;

    public $layout                              = null;      //necessario per interagire con il controller

    /**
     * @todo da finire
     *
     * private $hreflang                            = null;
     * private $canonical                           = null;
     * private $next                                = null;
     * private $prev                                = null;
     * private $author                              = null;
     * private $manifest                            = null;
     * private $amp                                 = null;
     * private $rss                                 = null;
     */

    protected $favicons                         = array();

    private $contents                           = array();
    private $scripts                            = [
                                                    self::ASSET_LOCATION_HEAD           => null,
                                                    self::ASSET_LOCATION_BODY_TOP       => null,
                                                    self::ASSET_LOCATION_BODY_BOTTOM    => null,
                                                    self::ASSET_LOCATION_ASYNC          => null,
                                                    self::ASSET_LOCATION_DEFER          => null
                                                ];

    private $error                              = null;

    /**
     * PageHtml constructor.
     * @param string $path_info
     * @param string $controller_type
     * @param string|null $layout
     */
    public function __construct(string $path_info, string $controller_type, string $layout = null)
    {
        Debug::stopWatch("gui/controller/html");

        parent::__construct($controller_type, self::MAP_PREFIX);

        $this->lang                             = Locale::getCodeLang();
        $this->region                           = Locale::getCodeCountry();
        $this->path_info                        = $path_info;
        $this->layout                           = $layout;

        $this->cache_time                       = Kernel::useCache() ? null : "?" . time();
    }

    /**
     * @param string $tpl_var
     * @param string|DataHtml|View|Controller|null $content
     * @return ControllerAdapter
     * @throws Exception
     * @todo da tipizzare
     */
    public function assign(string $tpl_var, $content = null) : ControllerAdapter
    {
        $this->setContent($tpl_var, $this->getHtml($content));

        return $this;
    }
    /**
     * @param string|DataHtml|View|Controller $content
     * @return false|string|null
     * @throws Exception
     */
    private function getHtml($content) : ?string
    {
        if (is_object($content)) {
            $html                               = $this->getHtmlByObject($content);
        } elseif (is_array($content)) {
            $html                               = null;
        } elseif (!empty($content)) {
            $html                               = $this->getHtmlByString($content);
        } else {
            $html                               = null;
        }

        return $html;
    }

    /**
     * @param View|DataHtml $obj
     * @return string|null
     * @throws Exception
     */
    private function getHtmlByObject($obj) : ?string
    {
        $html                                   = null;
        if ($obj instanceof View || $obj instanceof Controller) {
            $html                               = $obj->html();
        } elseif ($obj instanceof DataHtml) {
            $this->css                          = $this->css    + $obj->css;
            $this->js                           = $this->js     + $obj->js;
            $this->fonts                        = $this->fonts  + $obj->fonts;

            $html                               = $obj->output();
        }

        return $html;
    }

    /**
     * @param string $string
     * @return string|null
     * @throws Exception
     */
    private function getHtmlByString(string $string) : ?string
    {
        $html                                   = null;
        if (strpos($string, DIRECTORY_SEPARATOR) === 0) {
            if (strpos($string, Constant::DISK_PATH) !== 0) {
                $string                         = Dir::findViewPath() . $string;
            }
            if (pathinfo($string, PATHINFO_EXTENSION) == "php") {
                ob_start();
                Autoloader::loadScript($string);
                $html                           = ob_get_contents();
                ob_end_clean();
            } else {
                $html                           = FilemanagerWeb::fileGetContents($string);
            }
        } elseif (Validator::isUrl($string)) {
            $html                               = FilemanagerWeb::fileGetContents($string);
        } else {
            $html                               = $string;
        }
        return $html;
    }

    /**
     * @return string
     */
    private function getTileDefault(): string
    {
        return ($this->path_info && $this->path_info != DIRECTORY_SEPARATOR
            ? ucfirst(basename($this->path_info))
            : static::TITLE_DEFAULT
        );
    }

    /**
     * @param bool $include_appname
     * @return string
     */
    private function getTitle(bool $include_appname = true) : string
    {
        $res                                    = (
            $this->title
                                                    ? $this->title
                                                    : $this->getTileDefault()
                                                );
        if ($include_appname && Kernel::$Environment::APPNAME) {
            $res                                .= " - " . Kernel::$Environment::APPNAME;
        }
        return $res;
    }

    /**
     * @return string
     */
    private function parseTitle() : string
    {
        return self::NEWLINE . '<title>' . $this->getTitle() . '</title>';
    }

    /**
     * @return string
     */
    private function parseDescription() : string
    {
        $res                                    = (
            $this->description
                                                    ? $this->description
                                                    : $this->getTitle(false)
                                                );


        return self::NEWLINE . '<meta name="description" content="' . $res . '" />';
    }

    /**
     * @return string
     */
    private function parseEncoding() : string
    {
        return self::NEWLINE . '<meta http-equiv="Content-Type" content="text/html; charset=' . $this->encoding . '"/>';
    }

    /**
     * @return string
     */
    private function parseMeta() : string
    {
        $res                                    = "";
        if (!empty($this->meta)) {
            foreach ($this->meta as $meta) {
                if (isset($meta["name"])) {
                    $res                        .= self::NEWLINE . '<meta name="' . $meta["name"] . '" content="' . $meta["content"] . '">';
                } elseif (isset($meta["property"])) {
                    $res                        .= self::NEWLINE . '<meta property="' . $meta["property"] . '" content="' . $meta["content"] . '">';
                }
            }
        }
        return $res;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function parseFavicons() : string
    {
        $res                                    = "";
        $favicon                                = Resource::image("favicon");
        if ($favicon) {
            foreach ($this->favicons as $properties) {
                $res                            .= self::NEWLINE . '<link rel="' . $properties["rel"] . '" sizes="' . $properties["sizes"] . '" href="' . Media::getUrl($favicon, $properties["sizes"]) . '">';
            }
        }

        return $res;
    }

    /**
     * @return string
     */
    private function parseFonts() : ?string
    {
        $res                                    = null;
        foreach ($this->fonts as $font => $media) {
            if (Validator::isUrl($font)) {
                $this->preconnect($font);

                $res                            .= self::NEWLINE . '<link rel="stylesheet"' . $this->attrCors($font) . ' href="' . $font . '" />';
            } else {
                $res                            .= self::NEWLINE . '<link rel="preload" as="font"' . $this->attrMedia($media) . ' type="font/' . pathinfo($font, PATHINFO_EXTENSION) . '"' . ' href="' . $font . '" />';
            }
        }

        return $res;
    }

    /**
     * @param string $url
     */
    private function preconnect(string $url) : void
    {
        $webUrl                                 = parse_url($url);
        $host                                   = $webUrl["scheme"] . "://" .  $webUrl["host"];
        $this->preconnect[$host]                = self::NEWLINE . '<link rel="preconnect" href="' . $host . '" />';
    }

    /**
     * @return string
     */
    private function parsePreconnect() : string
    {
        return implode(null, $this->preconnect);
    }

    /**
     * @return string
     */
    private function parseCss() : ?string
    {
        $res                                    = null;
        foreach ($this->css as $css => $media) {
            if (Validator::isUrl($css)) {
                $this->preconnect($css);
            }

            $res                                .= self::NEWLINE . '<link' . $this->attrMedia($media) . ' type="text/css" rel="stylesheet"' . $this->attrCors($css) . ' href="' . $css . '" />';
        }


        return $res;
    }

    private function parseStyle() : ?string
    {
        $res                                    = null;
        foreach ($this->style as $media => $styles) {
            $res                                .= self::NEWLINE .'<style' . $this->attrMedia($media) . ' type="text/css">' . implode(self::NEWLINE, $styles) . '</style>';
        }

        return $res;
    }

    private function renderJs(array $scripts, bool $embed = false) : void
    {
        foreach ($scripts as $js => $type) {
            $media                              = ucfirst($type);
            switch ($media) {
                case self::ASSET_LOCATION_HEAD:
                case self::ASSET_LOCATION_BODY_TOP:
                case self::ASSET_LOCATION_BODY_BOTTOM:
                    $script                     = self::NEWLINE . '<script';
                    break;
                case self::ASSET_LOCATION_ASYNC:
                    $script                     = self::NEWLINE . '<script async';
                    break;
                case self::ASSET_LOCATION_DEFER:
                default:
                    $media                      = self::ASSET_LOCATION_DEFER;
                    $script                     = self::NEWLINE . '<script defer';
            }

            $this->scripts[$media]              .= $script . ' type="application/javascript"' . (
                $embed
                ? '>' . $js . '</script>'
                : $this->attrCors($js) . ' src="' . $js . '"></script>'
            );
        }
    }

    /**
     * @param string $location
     * @return string
     */
    private function parseJs(string $location) : ?string
    {
        return $this->scripts[$location];
    }


    private function parseStructuredData() : ?string
    {
        return (!empty($this->structured_data)
            ? self::NEWLINE . '<script type="application/ld+json">' . json_encode($this->structured_data). '</script>'
            : null
        );
    }

    private function parseJsTemplate() : ?string
    {
        $res                                    = null;
        $i                                      = 0;
        foreach ($this->js_template as $template => $type) {
            $res                                .= self::NEWLINE . '<script type="'. ($type ?? self::JS_TEMPLATE_DEFAULT) . '" id="xtpl' . ++$i . '">' . $template . '</script>';
        }

        return $res;
    }



    /**
     * @param string $key
     * @param string|null $content
     * @return $this
     */
    private function setContent(string $key, string $content = null) : self
    {
        $this->contents['{' . self::TPL_VAR_PREFIX . $key . '}']      = $content;

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function parseLayout() : string
    {
        if (!$this->layout) {
            return self::parseLayoutVars("{" . self::TPL_VAR_PREFIX . self::TPL_VAR_DEFAULT . "}");
        } elseif (strpos($this->layout, "<") === 0) {
            return self::parseLayoutVars($this->layout);
        }

        Debug::stopWatch("layout/" . $this->layout);

        $cache = Buffer::cache("layout");
        $res = $cache->get($this->layout);
        if (!$res) {
            $views = [];
            $layout_file = Resource::get($this->layout, Resource::TYPE_LAYOUTS);
            if (!$layout_file || !($layout = FilemanagerWeb::fileGetContents($layout_file))) {
                throw new Exception("Layout not Found: " . $this->layout, 500);
            }

            $tpl_vars = [];
            if (preg_match_all('/{include file="\$theme_path(.+)"}/i', $layout, $tpl_vars)) {
                if (!empty($tpl_vars[1])) {
                    foreach ($tpl_vars[1] as $i => $tpl_var) {
                        $content_file = Kernel::$Environment::PROJECT_THEME_DISK_PATH . $tpl_var;
                        if ($content = FilemanagerWeb::fileGetContents($content_file)) {
                            $layout_files[$content_file] = filemtime($content_file);
                            $layout = str_replace($tpl_vars[0][$i], $content, $layout);
                        } else {
                            throw new Exception("Layout include not Found: " . $tpl_var, 500);
                        }
                    }
                }
            }

            $layout = preg_replace(
                '/{include file="[.\/]*([\w\/]+).*"}/i',
                '{' . self::TPL_VAR_PREFIX . '$1}',
                $layout
            );

            foreach (Resource::views() as $key => $view) {
                $tpl_key = '{' . self::TPL_VAR_PREFIX . $key . '}';

                if (strpos($layout, $tpl_key) !== false) {
                    if ($content = $this->getHtml($view)) {
                        $layout_files[$view] = filemtime($view);
                        $views[$tpl_key] = $content;
                        $layout = str_replace($tpl_key, $content, $layout);
                    } else {
                        throw new Exception("Layout include not Found: " . $key, 500);
                    }
                }
            }

            $layout_files[$layout_file] = filemtime($layout_file);

            $cache->set($this->layout, [
                "layout"    => $layout,
                "views"     => $views
            ], $layout_files);
        } else {
            $layout         = $res["layout"];
            $views          = $res["views"];
        }


        if (!empty($override = array_intersect_key($views, $this->contents))) {
            $layout = str_replace(
                $override,
                array_intersect_key($this->contents, $views),
                $layout
            );
        }

        Debug::stopWatch("layout/" . $this->layout);

        return self::parseLayoutVars($layout);
    }

    /**
     * @param string $layout
     * @return string
     * @throws Exception
     */
    private function parseLayoutVars(string $layout) : string
    {
        $this->setAssignDefault();
        $layout = str_replace(
            array_keys($this->contents),
            array_values($this->contents),
            $layout
        );


        foreach (Resource::components() as $key => $component) {
            $tpl_key = "{" . $key . "}";
            if (strpos($layout, $tpl_key) !== false) {
                /**
                 * @var Controller $controller
                 */
                $controller = (new $component());

                $layout = str_replace($tpl_key, $controller->html(self::METHOD_DEFAULT), $layout);
            }
        }

        return self::NEWLINE . $layout;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function parseHead() : string
    {
        return /** @lang text */ '<' . self::HEAD_TAG . '>'
            . $this->parseEncoding()
            . $this->parseTitle()
            . $this->parseDescription()
            . $this->parseMeta()
            . $this->parseFavicons()
            . $this->parseFonts()
            . $this->parseCss()
            . $this->parsePreconnect() //@todo da spostare sopra i fonts
            . $this->parseStyle()
            . $this->parseStructuredData()
            . $this->parseJsTemplate()
            . $this->parseJs(self::ASSET_LOCATION_ASYNC)
            . $this->parseJs(self::ASSET_LOCATION_DEFER)
            . $this->parseJs(self::ASSET_LOCATION_HEAD)
            . self::NEWLINE
        . '</' . self::HEAD_TAG . '>';
    }

    /**
     * @return string
     * @throws Exception
     */
    private function parseDebug() : string
    {
        return self::NEWLINE . Debug::dump($this->error, true);
    }

    /**
     * @return string
     * @throws Exception
     */
    private function parseBody() : string
    {
        return '<' . self::BODY_TAG . ($this->body_class ? ' class="'  . $this->body_class. '"' : null) . '>'
            . $this->parseJs(self::ASSET_LOCATION_BODY_TOP)
            . $this->parseLayout()
            . $this->parseDebug()
            . $this->parseJs(self::ASSET_LOCATION_BODY_BOTTOM)
            . self::NEWLINE
            . '</' . self::BODY_TAG . '>';
    }

    /**
     * @return string
     */
    private function parseHtmlLang() : string
    {
        return ($this->lang
            ? ' lang="' . $this->lang . '"'
            : ""
        );
    }

    /**
     * @return string
     */
    private function parseHtmlRegion() : string
    {
        return ($this->lang
            ? ' region="' . $this->region . '"'
            : ""
        );
    }

    /**
     * @return string
     * @throws Exception
     */
    private function parseHtml() : string
    {
        $this->renderJs($this->js);
        $this->renderJs($this->js_embed, true);

        return /** @lang text */ $this->doc_type ?? self::DOC_TYPE
            . self::NEWLINE . '<' . self::PAGE_TAG . $this->parseHtmlLang() . $this->parseHtmlRegion() . '>'
            . self::NEWLINE . $this->parseHead()
            . self::NEWLINE . $this->parseBody()
            . self::NEWLINE . '</' . self::PAGE_TAG .'>';
    }

    /**
     * @param string|null $media
     * @return string|null
     */
    private function attrMedia(string $media = null) : ?string
    {
        return (
            $media && $media != self::MEDIA_DEVICE_DEFAULT
            ? ' media="' . $media . '"'
            : null
        );
    }

    /**
     * @param string $path
     * @return string|null
     */
    private function attrCors(string &$path) : ?string
    {
        if (strpos($path, "http") === 0) {
            return ' crossorigin="anonymous"';
        } else {
            $path .= $this->cache_time;
            return null;
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    private function html() : string
    {
        Debug::stopWatch("gui/controller/html");

        return $this->parseHtml();
    }

    /**
     * @param int|null $http_status_code
     * @return DataHtml
     * @throws Exception
     */
    public function display(int $http_status_code = null) : DataHtml
    {
        return (new DataHtml($this->toArray()))
                    ->error($http_status_code ?? self::STATUS_OK);
    }

    /**
     * @param string|null $html
     * @return array
     * @throws Exception
     */
    public function toArray(string $html = null) : array
    {
        return [
            "pathname"          => $this->path_info,
            "title"             => $this->title ?? $this->getTileDefault(),
            "description"       => $this->description,
            "css"               => $this->css,
            "style"             => $this->style,
            "fonts"             => $this->fonts,
            "js"                => $this->js,
            "js_embed"          => $this->js_embed,
            "js_template"       => $this->js_template,
            "structured_data"   => $this->structured_data,
            "html"              => $html ?? $this->html()
        ];
    }

    /**
     * @param string|null $error
     * @return $this
     */
    public function debug(string $error = null) : self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @throws Exception
     */
    private function setAssignDefault() : void
    {
        $this->assign("site_path", Kernel::$Environment::SITE_PATH);
    }
}
