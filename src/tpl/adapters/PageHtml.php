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
namespace phpformsframework\libs\tpl\adapters;

use phpformsframework\libs\Constant;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Env;
use phpformsframework\libs\Error;
use phpformsframework\libs\Mappable;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Request;
use phpformsframework\libs\Response;
use phpformsframework\libs\storage\Media;
use phpformsframework\libs\tpl\ffTemplate;
use phpformsframework\libs\tpl\Gridsystem;
use phpformsframework\libs\tpl\Resource;

class PageHtml extends Mappable
{
    const NL                                    = "\n";
    const MAIN_CONTENT                          = "content";

    private $encoding                           = Constant::ENCODING;
    private $path                               = null;
    private $css                                = array();
    private $js                                 = array();
    private $fonts                              = array();
    private $title                              = null;
    private $description                        = null;
    private $lang                               = null;
    private $region                             = null;
    /**
     * @todo da finire
     *
     * private $hreflang                           = null;
     * private $canonical                          = null;
     * private $next                               = null;
     * private $prev                               = null;
     * private $author                             = null;
     * private $manifest                           = null;
     */

    protected $meta                             = array();
    protected $favicons                         = array();

    private $GridSystem                         = null;
    private $doctype                            = '<!DOCTYPE html>';
    private $layout                             = "<main>{content}</main>";
    private $contents                           = null;
    private $statusCode                         = 200;
    private $email_support                      = null;

    public function __construct($map_name = "default")
    {
        parent::__construct($map_name);

        $this->GridSystem                       = Gridsystem::getInstance();
        $this->lang                             = Locale::getLang("tiny_code");
        $this->region                           = Locale::getCountry("tiny_code");
        $this->js                               = $this->GridSystem->js();
        $this->css                              = $this->GridSystem->css();
        $this->fonts                            = $this->GridSystem->fonts();
        $this->path                             = Request::pathinfo();
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    public function setStatus($code)
    {
        $this->statusCode                       = (
            $code > 0
                                                    ? $code
                                                    : 200
                                                );
        return $this;
    }

    public function addAssets($js = null, $css = null, $fonts = null)
    {
        if (is_array($js) && count($js)) {
            foreach ($js as $key => $url) {
                $this->addJs($key, $url);
            }
        }
        if (is_array($css) && count($css)) {
            foreach ($css as $key => $url) {
                $this->addCss($key, $url);
            }
        }
        if (is_array($fonts) && count($fonts)) {
            foreach ($fonts as $key => $url) {
                $this->addFont($key, $url);
            }
        }

        return $this;
    }

    public function addJs($key, $url = null)
    {
        $this->js[$key]                         = $this->mask($url);

        return $this;
    }
    public function addCss($key, $url = null)
    {
        $this->css[$key]                        = $this->mask($url);

        return $this;
    }
    public function addFont($key, $url)
    {
        $this->fonts[$key]                      = $this->mask($url);

        return $this;
    }

    private function mask($url)
    {
        $env                                    = Env::get();
        $env["{"]                               = "";
        $env["}"]                               = "";

        $url                                    = str_ireplace(array_keys($env), array_values($env), $url);

        return (strpos($url, Constant::DISK_PATH) === 0
            ? Media::getUrl($url)
            : $url
        );
    }

    public function addMeta($key, $content, $type = "name")
    {
        $this->meta[$key]                       = array(
                                                    $type => $key
                                                    , "content" => $content
                                                );

        return $this;
    }

    public function setLayout($name)
    {
        $this->layout                           = file_get_contents(Constant::DISK_PATH . $this->getAsset($name, "layout"));

        return $this;
    }

    public function addContent($content, $where = self::MAIN_CONTENT)
    {
        $this->contents["{" . $where . "}"]     = $content;

        return $this;
    }

    private function getTitle($include_appname = true)
    {
        $title                                  = (
            $this->title
                                                    ? $this->title
                                                    : ucfirst(basename($this->path))
                                                );
        if ($include_appname) {
            $title                              .= " - " . Constant::APPNAME;
        }
        return $title;
    }
    private function parseTitle()
    {
        return $this::NL . '<title>' . $this->getTitle() . '</title>';
    }

    private function parseDescription()
    {
        $description                            = (
            $this->description
                                                    ? $this->description
                                                    : $this->getTitle(false)
                                                );


        return $this::NL .'<meta name="description" content="' . $description . '" />';
    }

    private function parseEncoding()
    {
        return '<meta http-equiv="Content-Type" content="text/html; charset=' . $this->encoding . '"/>';
    }

    private function parseMeta()
    {
        $res                                    = "";
        if (is_array($this->meta) && count($this->meta)) {
            foreach ($this->meta as $meta) {
                if (isset($meta["name"])) {
                    $res                        .= $this::NL . '<meta name="' . $meta["name"] . '" content="' . $meta["content"] . '">';
                } elseif (isset($meta["property"])) {
                    $res                        .= $this::NL . '<meta property="' . $meta["property"] . '" content="' . $meta["content"] . '">';
                }
            }
        }
        return $res;
    }

    private function parseFavicons()
    {
        $res                                    = "";
        $favicon = $this->getAsset("favicon", "images");
        if ($favicon) {
            foreach ($this->favicons as $properties) {
                $res                            .= $this::NL . '<link rel="' . $properties["rel"] . '" sizes="' . $properties["sizes"] . '" href="' . Media::getUrl($favicon, $properties["sizes"], "url") . '">';
            }
        }

        return $res;
    }
    private function parseFonts()
    {
        $res                                    = "";
        if (is_array($this->fonts) && count($this->fonts)) {
            foreach ($this->fonts as $font) {
                $res                            .= $this::NL .'<link rel="preload" as="font" type="font/' . pathinfo($font, PATHINFO_EXTENSION) . '" crossorigin="anonymous" href="' . $font . '" />';
            }
        }

        return $res;
    }
    private function parseCss()
    {
        $css_tag                                = $this::NL . '<link rel="stylesheet" type="text/css" crossorigin="anonymous" href="';

        return $css_tag . implode('" />' . $css_tag, $this->css) . '" />';
    }
    private function parseJs($async = false, $defer = true)
    {
        $async_attr                             = (
            $async
                                                    ? "async "
                                                    : ""
                                                );

        $defer_attr                             = (
            $defer
                                                    ? "defer "
                                                    : ""
                                                );

        $script_tag                             = $this::NL . '<script ' . $async_attr . $defer_attr . 'crossorigin="anonymous" src="';

        return $script_tag . implode('"></script>' . $script_tag, $this->js) . '"></script>';
    }

    private function getContent($name)
    {
        return (isset($this->contents["{" . $name . "}"])
            ? $this->contents["{" . $name . "}"]
            : null
        );
    }
    private function setContent($name, $value)
    {
        $this->contents["{" . $name . "}"]      = $value;
        return $this;
    }

    public function getPageError($title, $code = null, $description = null)
    {
        if ($code) {
            Response::code($code);
        }
        if (!$description) {
            $description = Error::getErrorMessage($code);
        }

        $tpl                                    = new ffTemplate();
        $tpl->load_file(Constant::DISK_PATH . $this->getAsset("error", "common"));
        $tpl->set_var("site_path", Constant::SITE_PATH);
        $tpl->set_var("title", Translator::get_word_by_code($title));
        $tpl->set_var("description", Translator::get_word_by_code($description));

        if ($this->email_support) {
            $tpl->set_var("email_support", $this->email_support);
            $tpl->parse("SezButtonSupport", false);
        }

        return $tpl->rpparse("main", false);
    }

    private function parseLayout()
    {
        if ($this->statusCode != 200) {
            $this->setContent(self::MAIN_CONTENT, $this->getPageError($this->getContent(self::MAIN_CONTENT), $this->statusCode));
        }

        return str_replace(
            array_keys($this->contents),
            array_values($this->contents),
            $this->layout
        );
    }

    private function parseHead()
    {
        return /** @lang text */ '<head>'
            . $this->parseEncoding()
            . $this->parseTitle()
            . $this->parseDescription()
            . $this->parseMeta()
            . $this->parseFavicons()
            . $this->parseFonts()
            . $this->parseCss()
            . $this->parseJs()
            . $this::NL
        . '</head>';
    }

    private function parseDebug()
    {
        return (Constant::DEBUG
            ? Debug::dump("", true) . $this::NL
            : ""
        );
    }
    private function parseBody()
    {
        return '<body>'
            . $this->parseLayout()
            . $this::NL
            . $this->parseDebug()
        . '</body>';
    }

    private function parseHtml()
    {
        $lang                                   = (
            $this->lang
                                                    ? ' lang="' . $this->lang . '"'
                                                    : ""
                                                );
        $region                                 = (
            $this->region
                                                    ? ' region="' . $this->region . '"'
                                                    : ""
                                                );
        return /** @lang text */ $this->doctype
            . $this::NL . '<html ' . $lang . $region . '>'
            . $this::NL . $this->parseHead()
            . $this::NL . $this->parseBody()
            . $this::NL . '</html>'
            ;
    }

    public function process()
    {
        //       \phpformsframework\cms\Cm::widget("SeoCheckUp", array("url" => "http://miodottore.it/ginecologo/milano"));
//        \phpformsframework\cms\Cm::widget("SeoCheckUp", array("url" => "https://paginemediche.it/medici-online/search/ginecologo/lombardia/mi/milano"));
        return $this->parseHtml();
    }



    private function getAsset($what, $type)
    {
        $asset = null;
        foreach ((array) $what as $name) {
            $asset = Resource::get($name, $type);
            if ($asset) {
                break;
            }
        }

        return str_replace(Constant::DISK_PATH, "", $asset);
    }
}
