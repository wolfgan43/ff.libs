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
use phpformsframework\libs\Dir;
use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\Error;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Mappable;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Request;
use phpformsframework\libs\Response;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\storage\Media;
use phpformsframework\libs\tpl\AssetsManager;
use phpformsframework\libs\tpl\Gridsystem;
use phpformsframework\libs\tpl\Resource;
use phpformsframework\libs\tpl\View;

/**
 * Class PageHtml
 * @package phpformsframework\libs\tpl\adapters
 */
class PageHtml extends Mappable
{
    use AssetsManager;

    protected const NEWLINE                     = "\n";
    protected const MAIN_CONTENT                = "content";
    protected const TITLE_DEFAULT               = "Home";


    private $encoding                           = Constant::ENCODING;
    private $path                               = null;
    private $title                              = null;
    private $description                        = null;
    private $lang                               = null;
    private $region                             = null;
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

    protected $meta                             = array();
    protected $favicons                         = array();

    private $GridSystem                         = null;
    private $doctype                            = '<!DOCTYPE html>';

    protected $layout                           = "<main>{content}</main>";
    private $contents                           = array();
    private $error                              = null;
    private $email_support                      = null;

    /**
     * PageHtml constructor.
     * @param string $map_name
     */
    public function __construct(string $map_name = "default")
    {
        parent::__construct($map_name, self::class);

        $this->GridSystem                       = Gridsystem::getInstance();
        $this->lang                             = Locale::getLang("tiny_code");
        $this->region                           = Locale::getCountry("tiny_code");
        $this->js                               = $this->GridSystem->js() + $this->js;
        $this->css                              = $this->GridSystem->css() + $this->css;
        $this->fonts                            = $this->GridSystem->fonts() + $this->fonts;
        $this->path                             = Request::pathinfo();
    }

    /**
     * @param string $encoding
     * @return PageHtml
     */
    public function setEncoding(string $encoding) : PageHtml
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * @param string $key
     * @param string $content
     * @param string $type
     * @return PageHtml
     */
    public function addMeta(string $key, string $content, string $type = "name") : PageHtml
    {
        $this->meta[$key]                       = array(
                                                    $type       => $key,
                                                    "content"   => $content
                                                );

        return $this;
    }

    /**
     * @param string $name
     * @return PageHtml
     */
    public function setLayout(string $name) : PageHtml
    {
        $this->layout                           = Resource::load($name, "layouts");

        return $this;
    }

    /**
     * @param string|DataHtml|View $content
     * @param string $where
     * @return PageHtml
     */
    public function addContent($content = null, string $where = self::MAIN_CONTENT) : PageHtml
    {
        $this->setContent($where, $this->getHtml($content));

        return $this;
    }

    /**
     * @param string|DataHtml|View $content
     * @return false|string|null
     */
    private function getHtml($content) : ?string
    {
        $html                                   = null;
        if (is_object($content)) {
            $html                               = $this->getHtmlByObject($content);
        } elseif (is_array($content)) {
            $html                               = null;
        } elseif (!empty($content)) {
            $html                               = $this->getHtmlByString($content);
        }

        return $html;
    }

    /**
     * @param View|DataHtml $obj
     * @return string|null
     */
    private function getHtmlByObject($obj) : ?string
    {
        $html                                   = null;
        if ($obj instanceof View) {
            $html                               = $obj->display();
        } elseif ($obj instanceof DataHtml) {
            $this->injectAssets($obj);
            $html                               = $obj->html;
        }

        return $html;
    }

    /**
     * @param string $string
     * @return string|null
     */
    private function getHtmlByString(string $string) : ?string
    {
        $html                                   = null;
        if (strpos($string, DIRECTORY_SEPARATOR) === 0) {
            if (strpos($string, Constant::DISK_PATH) !== 0) {
                $string                         = Dir::findAppPath("views") . $string;
            }
            if (pathinfo($string, PATHINFO_EXTENSION) == "php") {
                ob_start();
                Dir::autoload($string);
                $html                           = ob_get_contents();
                ob_end_clean();
            } else {
                $html                           = Filemanager::fileGetContent($string);
            }
        } elseif (0 && Validator::is($string, "url")) {
            $html                               = Filemanager::fileGetContent($string);
        } else {
            $html                               = $string;
        }
        return $html;
    }

    /**
     * @return string
     */
    private function getTileDefault()
    {
        return ($this->path && $this->path != DIRECTORY_SEPARATOR
            ? ucfirst(basename($this->path))
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
        if ($include_appname) {
            $res                                .= " - " . Kernel::$Environment::APPNAME;
        }
        return $res;
    }

    /**
     * @return string
     */
    private function parseTitle() : string
    {
        return $this::NEWLINE . '<title>' . $this->getTitle() . '</title>';
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


        return $this::NEWLINE .'<meta name="description" content="' . $res . '" />';
    }

    /**
     * @return string
     */
    private function parseEncoding() : string
    {
        return '<meta http-equiv="Content-Type" content="text/html; charset=' . $this->encoding . '"/>';
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
                    $res                        .= $this::NEWLINE . '<meta name="' . $meta["name"] . '" content="' . $meta["content"] . '">';
                } elseif (isset($meta["property"])) {
                    $res                        .= $this::NEWLINE . '<meta property="' . $meta["property"] . '" content="' . $meta["content"] . '">';
                }
            }
        }
        return $res;
    }

    /**
     * @return string
     */
    private function parseFavicons() : string
    {
        $res                                    = "";
        $favicon = $this->getAsset("favicon", "images");
        if ($favicon) {
            foreach ($this->favicons as $properties) {
                $res                            .= $this::NEWLINE . '<link rel="' . $properties["rel"] . '" sizes="' . $properties["sizes"] . '" href="' . Media::getUrl($favicon, $properties["sizes"], "url") . '">';
            }
        }

        return $res;
    }

    /**
     * @return string
     */
    private function parseFonts() : string
    {
        $res                                    = "";
        if (!empty($this->fonts)) {
            foreach (array_unique($this->fonts) as $font) {
                $res                            .= $this::NEWLINE .'<link rel="preload" as="font" type="font/' . pathinfo($font, PATHINFO_EXTENSION) . '" crossorigin="anonymous" href="' . $font . '" />';
            }
        }

        return $res;
    }

    /**
     * @return string
     */
    private function parseCss() : string
    {
        $css_tag                                = $this::NEWLINE . '<link rel="stylesheet" type="text/css" crossorigin="anonymous" href="';

        return $css_tag . implode('" />' . $css_tag, array_unique($this->css)) . '" />';
    }

    /**
     * @param bool $async
     * @param bool $defer
     * @return string
     */
    private function parseJs(bool $async = false, bool $defer = true) : string
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

        $script_tag                             = $this::NEWLINE . '<script ' . $async_attr . $defer_attr . 'crossorigin="anonymous" src="';

        return $script_tag . implode('"></script>' . $script_tag, array_unique($this->js)) . '"></script>';
    }

    /**
     * @param string $name
     * @return string
     */
    private function getContent(string $name) : string
    {
        return (isset($this->contents["{" . $name . "}"])
            ? $this->contents["{" . $name . "}"]
            : null
        );
    }

    /**
     * @param string $key
     * @param string $content
     * @return PageHtml
     */
    private function setContent(string $key, string $content = null) : PageHtml
    {
        $this->contents["{" . $key . "}"]      = $content;
        return $this;
    }

    /**
     * @param string $title
     * @param int|null $code
     * @param string|null $description
     * @return string
     */
    public function getPageError(string $title, int $code = null, string $description = null) : string
    {
        if ($code) {
            Response::httpCode($code);
        }
        if (!$description) {
            $description = Error::getErrorMessage($code);
        }
        $this->error = $title . " (" . $description . ")";

        $tpl                                    = new View();
        $tpl->fetch(Constant::DISK_PATH . $this->getAsset("error", "common"));
        $tpl->assign("site_path", Constant::SITE_PATH);
        $tpl->assign("title", Translator::get_word_by_code($title));
        $tpl->assign("description", Translator::get_word_by_code($description));

        if ($this->email_support) {
            $tpl->assign("email_support", $this->email_support);
            $tpl->parse("SezButtonSupport", false);
        }

        return $tpl->display();
    }

    /**
     * @return string
     */
    private function parseLayout() : string
    {
        $res = str_replace(
            array_keys($this->contents),
            array_values($this->contents),
            $this->layout
        );
        $commons = array();
        $resources = Resource::type("common");
        foreach ($resources as $key => $content) {
            $tpl_key = "{" . $key . "}";
            if (strpos($res, $tpl_key) !== false) {
                $class_name = ucfirst($key);
                if (class_exists($class_name)) {
                    $content = new $class_name();
                }

                $commons[$tpl_key] = $this->getHtml($content);
            }
        }

        return str_replace(
            array_keys($commons),
            array_values($commons),
            $res
        );
    }

    /**
     * @return string
     */
    private function parseHead() : string
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
            . $this::NEWLINE
        . '</head>';
    }

    /**
     * @return string
     */
    private function parseDebug() : string
    {
        return (Kernel::$Environment::DEBUG
            ? Debug::dump($this->error, true) . $this::NEWLINE
            : ""
        );
    }

    /**
     * @return string
     */
    private function parseBody() : string
    {
        return '<body>'
            . $this->parseLayout()
            . $this::NEWLINE
            . $this->parseDebug()
            . '</body>';
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
     */
    private function parseHtml() : string
    {
        return /** @lang text */ $this->doctype
            . $this::NEWLINE . '<html ' . $this->parseHtmlLang() . $this->parseHtmlRegion() . '>'
            . $this::NEWLINE . $this->parseHead()
            . $this::NEWLINE . $this->parseBody()
            . $this::NEWLINE . '</html>'
            ;
    }

    /**
     * @return DataHtml
     */
    public function render() : DataHtml
    {

        return new DataHtml(["html" => $this->parseHtml()]);
    }

    /**
     * @param int $status
     * @param string $msg
     * @param string|null $description
     * @return DataHtml
     */
    public function renderError(int $status, string $msg, string $description = null) : DataHtml
    {
        $this->setContent(self::MAIN_CONTENT, $this->getPageError($msg, $status, $description));

        return $this->render();
    }

    /**
     * @param string $what
     * @param string $type
     * @return string
     */
    protected function getAsset(string $what, string $type) : string
    {
        return str_replace(Constant::DISK_PATH, "", Resource::get($what, $type));
    }
}
