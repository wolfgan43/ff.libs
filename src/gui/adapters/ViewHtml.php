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

use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Error;
use phpformsframework\libs\gui\Resource;
use phpformsframework\libs\gui\View;
use phpformsframework\libs\Hook;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\storage\FilemanagerWeb;
use stdClass;
use Exception;

/**
 * Class ViewHtml
 * @package phpformsframework\libs\gui\adapters
 */
class ViewHtml implements ViewAdapter
{
    protected const REGEXP                      = '/\{([\w\:\=\-\|\.\s\?\!\\\'\"\,\$\/]+)\}/U';
    protected const REGEXP_STRIP                = '/\{\$(.+)\}/U';

    protected const APPLET                      = '/\{\[(.+)\]\}/U';

    //private static $cache                       = null;

    public $root_element						= "main";

    public $BeginTag							= "Begin";
    public $EndTag								= "End";

    public $debug_msg							= false;
    public $display_unparsed_sect				= false;

    private $DBlocks 							= [];
    private $DVars 								= [];
    private $DBlockVars 					    = [];
    private $ParsedBlocks 						= [];

    private $cache                              = [];
    /**
     * @var bool|string[strip|strong_strip|minify]
     */
    public $minify								= false;

    public function __construct(array &$cache = null)
    {
        $this->cache =& $cache;
    }

    /**
     * @param string $template_disk_path
     * @return ViewAdapter
     * @throws Exception
     */
    public function fetch(string $template_disk_path) : ViewAdapter
    {
        $this->loadFile($template_disk_path);

        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isset(string $name) : bool
    {
        return isset($this->DBlocks[$name]);
    }

    /**
     * @param string $template_path
     * @throws Exception
     */
    private function loadFile(string $template_path) : void
    {
        $tpl_name = Translator::infoLangCode() . "-" . str_replace(
            [
                Constant::DISK_PATH . "/",
                "_",
                "/"

            ],
            [
                "",
                "-",
                "_"
            ],
            $template_path
        );

        Debug::stopWatch("tpl/" . $tpl_name);

        $cache = Buffer::cache("tpl");

        $res = $cache->get($tpl_name);
        if (!$res) {
            $this->cache = [
                $template_path  => filemtime($template_path)
            ];
            if ($cache_file = Translator::infoCacheFile()) {
                $this->cache[$cache_file] = filemtime($cache_file);
            }

            $this->DBlocks[$this->root_element] = $this->include($template_path);

            $nName = $this->nextDBlockName($this->root_element);
            while ($nName != "") {
                $this->setBlock($this->root_element, $nName);
                $this->blockVars($nName);
                $nName = $this->nextDBlockName($this->root_element);
            }

            $cache->set($tpl_name, array(
                "DBlocks"       => $this->DBlocks,
                "DVars"         => $this->DVars,
                "DBlockVars"    => $this->DBlockVars,
                "root_element"  => $this->root_element
            ), $this->cache);
        } else {
            $this->DBlocks      = $res["DBlocks"];
            $this->DVars        = $res["DVars"];
            $this->DBlockVars   = $res["DBlockVars"];
            $this->root_element = $res["root_element"];
        }

        Hook::handle("on_loaded_data", $this);

        Debug::stopWatch("tpl/" . $tpl_name);
    }

    /**
     * @param string $content
     * @param string|null $root_element
     * @throws Exception
     */
    public function fetchContent(string $content, string $root_element = null) : void
    {
        if ($root_element !== null) {
            $this->root_element = $root_element;
        }

        if (!$content) {
            throw new Exception("template empty", 500);
        }

        $this->DBlocks[$this->root_element] = $this->getDVars($content);
        $nName = $this->nextDBlockName($this->root_element);
        while ($nName != "") {
            $this->setBlock($this->root_element, $nName);
            $nName = $this->nextDBlockName($this->root_element);
        }

        Hook::handle("on_loaded_data", $this);
    }

    /**
     * @param string $content
     * @return string
     * @throws Exception
     */
    private function getDVars(string $content) : string
    {
        $content = preg_replace(self::REGEXP_STRIP, '{$1}', $content);

        $matches = null;
        $rc = preg_match_all(static::REGEXP, $content, $matches);
        if ($rc && $matches) {
            $this->DVars = array_flip($matches[1]);

            $views = Resource::views();
            $translation = new stdClass();
            foreach ($this->DVars as $nName => $count) {
                if (substr($nName, 0, 1) == "_") {
                    $translation->key[]           = "{" . $nName . "}";
                    $translation->value[]         = Translator::getWordByCode(substr($nName, 1));
                    unset($this->DVars[$nName]);
                } elseif (substr($nName, 0, 7) == "include" && substr_count($nName, '"') == 2) {
                    $view =  explode('"', $nName)[1];

                    $template = $views[str_replace(['../', '.tpl', '.html'], '', $view)] ?? str_replace('$theme_path', Kernel::$Environment::PROJECT_THEME_DISK_PATH, $view);

                    $include = (new self($this->cache))->include($template);
                    $this->cache[$template] = filemtime($template);

                    $content = str_replace("{" . $nName . "}", $include, $content);
                }
            }
            if (isset($translation->key)) {
                $content = str_replace($translation->key, $translation->value, $content);
            }
        }

        return $content;
    }

    /**
     * @param string $sTemplateName
     * @return string
     */
    private function nextDBlockName(string $sTemplateName) : string
    {
        $sTemplate = $this->DBlocks[$sTemplateName];
        $BTag = strpos($sTemplate, "<!--" . $this->BeginTag);
        if ($BTag === false) {
            return "";
        } else {
            $ETag = strpos($sTemplate, "-->", $BTag);
            $sName = substr($sTemplate, $BTag + 9, $ETag - ($BTag + 9));
            if (strpos($sTemplate, "<!--" . $this->EndTag . $sName . "-->") > 0) {
                return $sName;
            } else {
                return "";
            }
        }
    }

    /**
     * @param string $sTplName
     * @param string $sBlockName
     */
    private function setBlock(string $sTplName, string $sBlockName) : void
    {
        if (!isset($this->DBlocks[$sBlockName])) {
            $this->DBlocks[$sBlockName] = $this->getBlock($this->DBlocks[$sTplName], $sBlockName);
        }
        $this->DBlocks[$sTplName] = $this->replaceBlock($this->DBlocks[$sTplName], $sBlockName);

        $nName = $this->nextDBlockName($sBlockName);

        while ($nName != "") {
            $this->setBlock($sBlockName, $nName);
            $nName = $this->nextDBlockName($sBlockName);
        }
    }

    /**
     * @param string $sTemplate
     * @param string $sName
     * @return string
     */
    private function getBlock(string $sTemplate, string $sName) : string
    {
        $alpha = strlen($sName) + 12;

        $BBlock = strpos($sTemplate, "<!--" . $this->BeginTag . $sName . "-->");
        $EBlock = strpos($sTemplate, "<!--" . $this->EndTag . $sName . "-->");

        if ($BBlock === false || $EBlock === false) {
            return "";
        } else {
            return substr($sTemplate, $BBlock + $alpha, $EBlock - $BBlock - $alpha);
        }
    }

    /**
     * @param string $sTemplate
     * @param string $sName
     * @return string
     */
    private function replaceBlock(string $sTemplate, string $sName) : string
    {
        $BBlock = strpos($sTemplate, "<!--" . $this->BeginTag . $sName . "-->");
        $EBlock = strpos($sTemplate, "<!--" . $this->EndTag . $sName . "-->");

        if ($BBlock === false || $EBlock === false) {
            return $sTemplate;
        } else {
            return substr($sTemplate, 0, $BBlock) . "{" . $sName . "}" . substr($sTemplate, $EBlock + strlen("<!--End" . $sName . "-->"));
        }
    }

    /**
     * @param string $sName
     * @return string
     */
    public function getVar(string $sName) : string
    {
        return $this->DBlocks[$sName];
    }

    /**
     * @param string $sName
     * @return bool
     */
    public function issetVar(string $sName) : bool
    {
        return isset($this->DVars[$sName]) || isset($this->DBlocks[$sName]);
    }

    /**
     * @param string $sName
     * @return bool
     */
    public function issetBlock(string $sName) : bool
    {
        return (bool)($this->ParsedBlocks[$sName]);
    }

    /**
     * @param string $sectionName
     * @param bool $repeat
     * @param bool $appendBefore
     * @return bool
     * @throws Exception
     */
    public function parse(string $sectionName, bool $repeat = false, bool $appendBefore = false) : bool
    {
        if (isset($this->DBlocks[$sectionName])) {
            if ($repeat && isset($this->ParsedBlocks[$sectionName])) {
                if ($appendBefore) {
                    $this->ParsedBlocks[$sectionName] = $this->proceedTpl($sectionName) . $this->ParsedBlocks[$sectionName];
                } else {
                    $this->ParsedBlocks[$sectionName] .= $this->proceedTpl($sectionName);
                }
            } else {
                $this->ParsedBlocks[$sectionName] = $this->proceedTpl($sectionName);
            }
            return true;
        } elseif ($this->debug_msg) {
            echo "<br><strong>Block with name <u><span style=\\";
        }

        return false;
    }

    /**
     * @param array|string $tpl_var
     * @param mixed|null $value
     * @return $this
     */
    public function assign($tpl_var, $value = null) : ViewAdapter
    {
        if (is_array($tpl_var)) {
            $this->ParsedBlocks             = array_replace($this->ParsedBlocks, $tpl_var);
        } else {
            $this->ParsedBlocks[$tpl_var]   = $value;
        }

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function display() : string
    {
        $this->setAssignDefault();

        foreach (array_intersect_key(Resource::components(), $this->DVars)  as $key => $component) {
            $this->assign($key, $component::html());
        }

        $this->parse($this->root_element);
        return $this->getBlockContent($this->root_element);
    }

    /**
     * @param string $template_path
     * @return string
     * @throws Exception
     */
    private function include(string $template_path) : string
    {
        if (!$content = FilemanagerWeb::fileGetContents($template_path)) {
            throw new Exception("Unable to find the template: " . $template_path, 500);
        }

        return $this->getDVars($content);
    }

    /**
     * @param string $block_name
     * @param string|null $minify
     * @return string|null
     * @throws Exception
     */
    private function getBlockContent(string $block_name, string $minify = null) : ?string
    {
        $minify = ($minify === null ? $this->minify : $minify);

        if ($minify === false) {
            return (isset($this->ParsedBlocks[$block_name])
                ? $this->entitiesReplace($this->ParsedBlocks[$block_name])
                : null
            );
        } elseif ($minify === "strip") {
            return $this->entitiesReplace(preg_replace("/\n\s*/", "\n", $this->ParsedBlocks[$block_name], -1, $count));
        } elseif ($minify === "strong_strip") {
            return $this->entitiesReplace(preg_replace(
                array(
                    '/>[^\S ]+/s',  // strip whitespaces after tags, except space
                    '/[^\S ]+</s',  // strip whitespaces before tags, except space
                    '/(\s)+/s'       // shorten multiple whitespace sequences
                ),
                array(
                    '>',
                    '<',
                    '\\1'
                ),
                $this->ParsedBlocks[$block_name],
                -1,
                $count
            ));
        } else {
            Error::register("Unknown minify method", static::ERROR_BUCKET);
        }

        return null;
    }

    /**
     * @param string $sTplName
     * @return array|null
     */
    private function blockVars(string $sTplName) : ?array
    {
        if (isset($this->DBlockVars[$sTplName])) {
            return $this->DBlockVars[$sTplName];
        }

        $sTpl = $this->DBlocks[$sTplName];

        $matches = array();
        $rc = preg_match_all(static::REGEXP, $sTpl, $matches);
        if ($rc) {
            $vars = $matches[1];

            $this->DBlockVars[$sTplName] = $vars;

            return $vars;
        } else {
            return null;
        }
    }

    /**
     * @param string $sTplName
     * @return string
     * @throws Exception
     */
    private function proceedTpl(string $sTplName) : string
    {
        $vars = $this->blockVars($sTplName);
        $sTpl = $this->DBlocks[$sTplName];

        if ($vars) {
            $search_for = array();
            $replace_with = array();

            reset($vars);
            foreach ($vars as $value) {
                $search_for[] = "{" . $value . "}";
                if (isset($this->ParsedBlocks[$value])) {
                    if (is_object($this->ParsedBlocks[$value])) {
                        if ($this->ParsedBlocks[$value] instanceof View) {
                            $replace_with[] = $this->ParsedBlocks[$value]->display();
                        } else {
                            Error::register("bad value into template", static::ERROR_BUCKET);
                        }
                    } else {
                        $replace_with[] = $this->ParsedBlocks[$value];
                    }
                } elseif (isset($this->DBlocks[$value]) && $this->display_unparsed_sect) {
                    $replace_with[] = $this->DBlocks[$value];
                } else {
                    $replace_with[] = "";
                }
            }
            $sTpl = str_replace($search_for, $replace_with, $sTpl);
        }
        return $sTpl;
    }

    /**
     * @param string $text
     * @return string
     */
    private function entitiesReplace(string $text) : string
    {
        return str_replace(array("{\\","\\}"), array("{","}"), $text);
    }


    /**
     *
     */
    private function setAssignDefault() : void
    {
        $this->assign("site_path", Kernel::$Environment::SITE_PATH);
    }
}
