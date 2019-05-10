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
namespace phpformsframework\libs\tpl;

use phpformsframework\libs\DirStruct;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\storage\Media;

if (!defined("APPNAME"))                 { define("APPNAME", str_replace(" " , "", ucwords(str_replace(array(".", "-"), " ", $_SERVER["HTTP_HOST"])))); }
if (!defined("ENCODING"))                { define("ENCODING", "uft-8"); }

class PageHtml extends DirStruct {
    const NL                                    = "\n";
    const APPNAME                               = APPNAME;

    private $encoding                           = ENCODING;
    private $path                               = null;
    private $css                                = array();
    private $js                                 = array();
    private $fonts                              = array();
    private $title                              = null;
    private $description                        = null;
    private $lang                               = null;
    private $hreflang                           = null;
    private $canonical                          = null;
    private $next                               = null;
    private $prev                               = null;
    private $author                             = null;
    private $manifest                           = null;
    private $resources                          = array();
    private $meta                               = array(
                                                    "viewport"          => array(
                                                        "name"          => "viewport"
                                                        , "content"     => "width=device-width, initial-scale=1.0"
                                                    )
                                                    , "msapplication-TileColor" => array(
                                                        "name"          => "msapplication-TileColor"
                                                        , "content"     => "#da532c"
                                                    )
                                                    , "theme-color"     => array(
                                                        "name"          => "theme-color"
                                                        , "content"     => "#ffffff"
                                                    )
                                                    , "robots"          => array(
                                                        "name"          => "robots"
                                                        , "content"     => "index, follow"
                                                    )
                                                );
    private $favicons                           = array(
                                                    "favicon" => array(
                                                        "rel"           => "shortcut icon"
                                                        , "sizes"       => "16x16"
                                                        , "href"        => null
                                                    )
                                                    , "apple-touch-icon-57x57" => array(
                                                        "rel"           => "apple-touch-icon"
                                                        , "sizes"       => "57x57"
                                                        , "href"        => null
                                                    )
                                                    , "apple-touch-icon-60x60" => array(
                                                        "rel"           => "apple-touch-icon"
                                                        , "sizes"       => "60x60"
                                                        , "href"        => null
                                                    )
                                                    , "apple-touch-icon-72x72" => array(
                                                        "rel"           => "apple-touch-icon"
                                                        , "sizes"       => "72x72"
                                                        , "href"        => null
                                                    )
                                                    , "apple-touch-icon-76x76" => array(
                                                        "rel"           => "apple-touch-icon"
                                                        , "sizes"       => "76x76"
                                                        , "href"        => null
                                                    )
                                                    , "apple-touch-icon-114x114" => array(
                                                        "rel"           => "apple-touch-icon"
                                                        , "sizes"       => "114x114"
                                                        , "href"        => null
                                                    )
                                                    , "apple-touch-icon-120x120" => array(
                                                        "rel"           => "apple-touch-icon"
                                                        , "sizes"       => "120x120"
                                                        , "href"        => null
                                                    )
                                                    , "apple-touch-icon-144x144" => array(
                                                        "rel"           => "apple-touch-icon"
                                                        , "sizes"       => "144x144"
                                                        , "href"        => null
                                                    )
                                                    , "apple-touch-icon-152x152" => array(
                                                        "rel"           => "apple-touch-icon"
                                                        , "sizes"       => "152x152"
                                                        , "href"        => null
                                                    )
                                                    , "apple-touch-icon-180x180" => array(
                                                        "rel"           => "apple-touch-icon"
                                                        , "sizes"       => "180x180"
                                                        , "href"        => null
                                                    )
                                                    , "icon-192x192"    => array(
                                                        "rel"           => "icon"
                                                        , "type"        => "image/png"
                                                        , "sizes"       => "192x192"
                                                        , "href"        => null
                                                    )
                                                    , "icon-32x32"      => array(
                                                        "rel"           => "icon"
                                                        , "type"        => "image/png"
                                                        , "sizes"       => "32x32"
                                                        , "href"        => null
                                                    )
                                                    , "icon-96x96"      => array(
                                                        "rel"           => "icon"
                                                        , "type"        => "image/png"
                                                        , "sizes"       => "96x96"
                                                        , "href"        => null
                                                    )
                                                    , "icon-16x16"      => array(
                                                        "rel"           => "icon"
                                                        , "type"        => "image/png"
                                                        , "sizes"       => "16x16"
                                                        , "href"        => null
                                                    )
                                                );
    private $resource_rules                     = array(
                                                    "layouts"           => array(
                                                        "flag"          => Filemanager::SCAN_FILE
                                                        , "type"        => "layouts"
                                                        , "filter"      => array(
                                                                            "html"
                                                                            , "tpl"
                                                                        )
                                                    )
                                                    , "commons"         => array(
                                                        "flag"          => Filemanager::SCAN_FILE
                                                        , "type"        => "common"
                                                        , "filter"      => array(
                                                                            "html"
                                                                            , "tpl"
                                                                        )
                                                    )
                                                    /*, "widgets"       => array(
                                                        "flag"          => Filemanager::SCAN_FILE
                                                        , "type"        => "widgets"
                                                        , "filter"      => array(
                                                                            "html"
                                                                            , "tpl"
                                                                        )
                                                    )*/
                                                    , "css"             => array(
                                                        "flag"          => Filemanager::SCAN_FILE
                                                        , "type"        => "css"
                                                        , "filter"      => array(
                                                                            "css"
                                                                        )
                                                    )
                                                    , "js"              => array(
                                                        "flag"          => Filemanager::SCAN_FILE
                                                        , "type"        => "js"
                                                        , "filter"      => array(
                                                                            "js"
                                                                        )
                                                    )
                                                    , "images"          => array(
                                                        "flag"          => Filemanager::SCAN_FILE
                                                        , "type"        => "images"
                                                        , "filter"      => array(
                                                                            "jpg"
                                                                            , "png"
                                                                            , "svg"
                                                                            , "jpeg"
                                                                            , "gif"
                                                                        )
                                                    )
                                                    , "fonts"           => array(
                                                        "flag"          => Filemanager::SCAN_FILE
                                                        , "type"        => "fonts"
                                                        , "filter"      => array(
                                                                            "otf"
                                                                            , "eot"
                                                                            , "svg"
                                                                            , "ttf"
                                                                            , "woff"
                                                                            , "woff2"
                                                                        )
                                                    )
                                                    /*, "components"    => array(
                                                        "flag"          => Filemanager::SCAN_FILE
                                                        , "type"        => "components"
                                                        , "filter"      => array(
                                                            "html"      => true
                                                            , "tpl"     => true
                                                        )
                                                    )*/
                                                );

    private $GridSystem                         = null;
    private $doctype                            = '<!DOCTYPE html>';
    private $layout                             = "{content}";
    private $contents                           = null;

    public function __construct($path = null)
    {
        $this->GridSystem                       = Gridsystem::getInstance();
        $this->lang                             = Locale::getLang("tiny_code");

        $this->js                               = $this->GridSystem->js();
        $this->css                              = $this->GridSystem->css();
        $this->fonts                            = $this->GridSystem->fonts();
        $this->path                             = ($path
                                                    ? $path
                                                    : $_SERVER["PATH_INFO"]
                                                );
        $this->loadResources();
    }
    public function setEncoding($encoding) {
        $this->encoding = $encoding;

        return $this;
    }
    public function addAssets($js = null, $css = null, $fonts = null) {
        if(is_array($js) && count($js)) {
            foreach ($js as $key => $url) {
                $this->addJs($key, $url);
            }
        }
        if(is_array($css) && count($css)) {
            foreach ($css as $key => $url) {
                $this->addCss($key, $url);
            }
        }
        if(is_array($fonts) && count($fonts)) {
            foreach ($fonts as $key => $url) {
                $this->addFont($key, $url);
            }
        }

        return $this;
    }

    public function addJs($key, $url = null) {
        $this->js[$key]                         = $this->mask($url);

        return $this;
    }
    public function addCss($key, $url = null) {
        $this->css[$key]                        = $this->mask($url);

        return $this;
    }
    public function addFont($key, $url) {
        $this->fonts[$key]                      = $this->mask($url);

        return $this;
    }

    private function mask($url) {
        return (strpos($url, $this::$disk_path) === 0
            ? Media::getUrl($url)
            : $url
        );
    }

    public function addMeta($key, $content, $type = "name") {
        $this->meta[$key]                       = array(
                                                    $type => $key
                                                    , "content" => $content
                                                );

        return $this;
    }

    public function setLayout($name) {
        $this->layout                           = $this->getAsset($name, "layout");

        return $this;
    }

    public function addContent($content, $where = "content") {
        $this->contents["{" . $where . "}"]     = $content;

        return $this;
    }

    private function getTitle($include_appname = true) {
        $title                                  = ($this->title
                                                    ? $this->title
                                                    : ucfirst(basename($this->path))
                                                );
        if($include_appname) {
            $title                              .= " - " . static::APPNAME;
        }
        return $title;
    }
    private function parseTitle() {
        return $this::NL . '<title>' . $this->getTitle() . '</title>';
    }

    private function parseDescription() {
        $description                            = ($this->description
                                                    ? $this->description
                                                    : $this->getTitle(false)
                                                );


        return $this::NL .'<meta name="description" content="' . $description . '" />';
    }

    private function parseEncoding() {
        return '<meta http-equiv="Content-Type" content="text/html; charset=' . $this->encoding . '"/>';
    }

    private function parseMeta() {
        $res                                    = "";
        if(is_array($this->meta) && count($this->meta)) {
            foreach ($this->meta as $meta) {
                if(isset($meta["name"])) {
                    $res                        .= $this::NL . '<meta name="' . $meta["name"] . '" content="' . $meta["content"] . '">';
                } elseif(isset($meta["property"])) {
                    $res                        .= $this::NL . '<meta property="' . $meta["property"] . '" content="' . $meta["content"] . '">';
                }
            }
        }
        return $res;
    }

    private function parseFavicons() {
        $res                                    = "";
        $favicon = $this->getAsset("favicon", "images");
        if($favicon) {
            foreach ($this->favicons AS $key => $properties) {
                $res                            .= $this::NL . '<link rel="' . $properties["rel"] . '" sizes="' . $properties["sizes"] . '" href="' . Media::getUrl($favicon, $properties["sizes"], "url") . '">';
            }
        }

        return $res;
    }
    private function parseFonts() {
        $res                                    = "";
        if(is_array($this->fonts) && count($this->fonts)) {
            foreach ($this->fonts as $font) {
                $res                            .= $this::NL .'<link rel="preload" as="font" type="font/' . pathinfo($font, PATHINFO_EXTENSION) . '" crossorigin="anonymous" href="' . $font . '" />';
            }
        }

        return $res;
    }
    private function parseCss() {
        $css_tag                                = $this::NL . '<link rel="stylesheet" type="text/css" crossorigin="anonymous" href="';

        return $css_tag . implode('" />' . $css_tag, $this->css) . '" />';
    }
    private function parseJs($async = false, $defer = true) {
        $async_attr                             = ($async
                                                    ? "async "
                                                    : ""
                                                );

        $defer_attr                             = ($defer
                                                    ? "defer "
                                                    : ""
                                                );

        $script_tag                             = $this::NL . '<script ' . $async_attr . $defer_attr . 'crossorigin="anonymous" src="';

        return $script_tag . implode('"></script>' . $script_tag, $this->js) . '"></script>';
    }

    private function parseLayout() {
        return str_replace(
            array_keys($this->contents)
            , array_values($this->contents)
            , $this->layout
        );
    }

    public function process() {
        return $this->doctype
            . $this::NL . '<html lang="' . $this->lang . '">'
            . $this::NL . '<head>'
                . $this->parseEncoding()
                . $this->parseTitle()
                . $this->parseDescription()
                . $this->parseMeta()
                . $this->parseFavicons()
                . $this->parseFonts()
                . $this->parseCss()
                . $this->parseJs()
            . $this::NL . '</head>'
            . $this::NL . '<body>'
                . $this->parseLayout()
            . $this::NL . '</body>'
            . $this::NL . '</html>'
        ;


    }


    protected function loadResources($patterns = null, $excludeDirname = null) {


        //self::getDiskPath("libs") . self::PROJECT_PATH  => array("filter" => array("xml"))
        if(is_array($this->resource_rules) && count($this->resource_rules)) {
            $base_dir = Page::ASSETS_PATH;

            foreach ($this->resource_rules as $key => $rule) {
                $patterns[$base_dir . "/assets/" . $key] = $rule;
                $patterns[self::getDiskPath($key, true)] = $rule;
            }
        }

        Filemanager::scanExclude($excludeDirname);
//print_r($patterns);
//die();
        $this->resources = Filemanager::scan($patterns);
    }

    private function resource($name, $type) {
        $pathinfo = $this->path;
        if($pathinfo) {
            do {
                $file = $this->resources[$type][$name . str_replace("/", "_", $pathinfo)];
                if ($file) {
                    break;
                }

                $pathinfo = dirname($pathinfo);
            } while ($pathinfo != DIRECTORY_SEPARATOR);
        }

        if(!$file) {
            $file = $this->resources[$type][$name];
        }

        return $file;
    }
    private function getAsset($what, $type) {
        foreach((array) $what AS $name) {
            $asset = $this->resource($name, $type);
            if($asset) {
                break;
            }
        }

        return str_replace($this::$disk_path, "", $asset);
    }

}

