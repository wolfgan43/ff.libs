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
namespace ff\libs\gui\adapters;

use ff\libs\Autoloader;
use ff\libs\Constant;
use ff\libs\Debug;
use ff\libs\Dir;
use ff\libs\dto\DataHtml;
use ff\libs\gui\Controller;
use ff\libs\gui\ControllerAdapter;
use ff\libs\Kernel;
use ff\libs\international\Locale;
use ff\libs\Response;
use ff\libs\security\Validator;
use ff\libs\storage\FilemanagerFs;
use ff\libs\storage\Media;
use ff\libs\gui\Resource;
use ff\libs\gui\View;
use ff\libs\Exception;
use ff\libs\util\ServerManager;

/**
 * Class PageHtml
 * @package ff\libs\gui\adapters
 */
class ControllerHtml extends ControllerAdapter
{
    use ServerManager;

    protected const SEO_DESCRIPTION_LIMIT       = 260;

    private const LAYOUT_EXCEPTION              = "none";

    private const MAP_PREFIX                    = "layout";
    private const STATUS_OK                     = 0;

    private const MEDIA_DEVICE_DEFAULT          = self::MEDIA_DEVICE_ALL;
    private const JS_TPL_DEFAULT                = "text/x-template";

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

    public $canonicalUrl                        = null;
    public $title                               = null;
    public $description                         = null;
    public $css                                 = [];
    public $style                               = [];
    public $fonts                               = [];
    public $js                                  = [];
    public $js_embed                            = [];
    public $js_tpl                              = [];
    public $json_ld                             = [];

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
    private $debug                              = null;

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

        $this->cache_time                       = null;
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
            $this->css                          = $this->css                + $obj->css;
            $this->style                        = $this->style              + $obj->style;
            $this->fonts                        = $this->fonts              + $obj->fonts;
            $this->js                           = $this->js                 + $obj->js;
            $this->js_embed                     = $this->js_embed           + $obj->js_embed;
            $this->js_tpl                       = $this->js_tpl             + $obj->js_tpl;
            $this->json_ld                      = $this->json_ld            + $obj->json_ld;

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
                $html                           = FilemanagerFs::fileGetContents($string);
            }
        } elseif (Validator::isUrl($string)) {
            $html                               = FilemanagerFs::fileGetContents($string);
        } else {
            $html                               = $string;
        }
        return $html;
    }

    /**
     * @return string
     */
    private function getTitleDefault(): string
    {
        return ucwords(basename($this->pathInfo()) ?: static::TITLE_DEFAULT);
    }

    /**
     * @param bool $include_appname
     * @return string
     */
    private function getTitle(bool $include_appname = true) : string
    {
        $res                                    = $this->title ?: $this->getTitleDefault();
        if ($include_appname && Kernel::$Environment::APPNAME) {
            $res                                .= " - " . Kernel::$Environment::APPNAME;
        }
        return $res;
    }

    /**
     * @return string
     */
    private function getDescriptionDefault() : string
    {
        return trim(preg_replace("/\s+/", " ", strip_tags($this->contents[Controller::TPL_VAR_DEFAULT] ?? "")));
    }

    /**
     * @return string|null
     */
    private function parseCanonicalUrl() : ?string
    {
        return (!empty($this->canonicalUrl) || empty($this->error)
            ? self::NEWLINE . '<link rel="canonical" href="' . $this->encodeEntity($this->canonicalUrl ?: $this->canonicalUrl()) . '" />'
            : null
        );
    }

    /**
     * @return string
     */
    private function parseTitle() : string
    {
        return self::NEWLINE . '<title>' . $this->encodeEntity($this->getTitle()) . '</title>';
    }

    /**
     * @return string
     */
    private function parseDescription() : string
    {
        return self::NEWLINE . '<meta name="description" content="' . $this->encodeEntity(substr($this->description ?: $this->getDescriptionDefault(), 0, static::SEO_DESCRIPTION_LIMIT)) . '" />';
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
        return implode("", $this->preconnect);
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

    /**
     * @return string|null
     */
    private function parseStyle() : ?string
    {
        $res                                    = null;
        foreach ($this->style as $media => $styles) {
            $res                                .= self::NEWLINE .'<style' . $this->attrMedia($media) . ' type="text/css">' . implode(self::NEWLINE, $styles) . '</style>';
        }

        return $res;
    }

    /**
     * @param array $scripts
     * @param bool $embed
     * @throws Exception
     */
    private function renderJs(array $scripts, bool $embed = false) : void
    {
        foreach ($scripts as $js => $type) {
            $media                              = strtolower($type ?? Kernel::$Environment::ASSET_LOCATION_DEFAULT);
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
                    $script                     = self::NEWLINE . '<script defer';
                    break;
                default:
                    throw new Exception("Media " . $media . " not supported: " . $js);
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
     * @return string|null
     */
    private function parseJs(string $location) : ?string
    {
        return $this->scripts[$location];
    }

    /**
     * @return string|null
     */
    private function parseStructuredData() : ?string
    {
        return (!empty($this->json_ld)
            ? self::NEWLINE . '<script type="application/ld+json">' . json_encode($this->json_ld). '</script>'
            : null
        );
    }

    /**
     * @return string|null
     */
    private function parseJsTemplate() : ?string
    {
        $res                                    = null;
        foreach ($this->js_tpl as $id => $tpl) {
            $res                                .= self::NEWLINE . '<script type="'. ($tpl["type"] ?? self::JS_TPL_DEFAULT) . '" id="' . $id . '">' . $tpl["content"] . '</script>';
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
        $this->contents[$key]      = $content;

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function parseLayout() : string
    {
        if (!$this->layout || $this->layout === self::LAYOUT_EXCEPTION) {
            return implode(self::NEWLINE, $this->contents);
        } elseif (strpos($this->layout, "<") === 0) {
            return View::fetchContent($this->layout)
                ->html();
        } elseif ($layout_file = Resource::get($this->layout, Resource::TYPE_LAYOUTS)) {
            return View::fetchFile($layout_file)
                ->assign($this->contents)
                ->html();
        } else {
            Response::sendErrorPlain("Layout not Found: " . $this->layout, 500);
            exit;
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    private function parseHead() : string
    {
        return /** @lang text */ '<' . self::HEAD_TAG . '>'
            . $this->parseEncoding()
            . $this->parseCanonicalUrl()
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
     */
    private function parseDebug() : string
    {
        return self::NEWLINE . Debug::dump($this->debug, true);
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
        $body = $this->parseBody();

        $this->renderJs($this->js);
        $this->renderJs($this->js_embed, true);

        return /** @lang text */ $this->doc_type ?? self::DOC_TYPE
            . self::NEWLINE . '<' . self::PAGE_TAG . $this->parseHtmlLang() . $this->parseHtmlRegion() . '>'
            . self::NEWLINE . $this->parseHead()
            . self::NEWLINE . $body
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
        if (strpos($path, "http") !== 0) {
            $path .= $this->cache_time;
        }

        return null;
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
            "title"             => $this->title ?? $this->getTitleDefault(),
            "description"       => $this->description,
            "css"               => $this->css,
            "style"             => $this->style,
            "fonts"             => $this->fonts,
            "js"                => $this->js,
            "js_embed"          => implode(self::NEWLINE, array_keys($this->js_embed)),
            "js_tpl"            => $this->parseJsTemplate(),
            "json_ld"           => (empty($this->json_ld) ? null: json_encode($this->json_ld)),
            "html"              => $html ?? $this->html()
        ];
    }

    /**
     * @param string|null $error
     * @param string|null $debug
     * @return $this
     */
    public function debug(string $error = null, string $debug = null) : self
    {
        $this->error = $error;
        $this->debug = $debug ?? $error;

        return $this;
    }
}
