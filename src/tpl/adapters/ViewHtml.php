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
use phpformsframework\libs\Error;
use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\Hook;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\tpl\ViewAdapter;
use stdClass;

class ViewHtml implements ViewAdapter
{
    const REGEXP                                = '/\{([\w\:\=\-\|\.\s\?\!\\\'\"\,]+)\}/U';

    const APPLET                                = '/\{\[(.+)\]\}/U';
    const COMMENTHTML                           = '/\{\{([\w\[\]\:\=\-\|\.]+)\}\}/U';

    public $root_element						= "main";

    public $BeginTag							= "Begin";
    public $EndTag								= "End";

    public $debug_msg							= false;
    public $display_unparsed_sect				= false;
    public $doublevar_to_commenthtml 			= false;

    private $DBlocks 							= null;
    private $DVars 								= null;
    private $DBlockVars 					    = null;
    private $ParsedBlocks 						= array();
    private $DApplets							= null;

    /**
     * @var bool|string[strip|strong_strip|minify]
     */
    public $minify								= false;

    public function fetch($template_file)
    {
        $this->load_file($template_file);

        return $this;
    }

    public function isset($name)
    {
        return isset($this->DBlocks[$name]);
    }

    public function load_file($template_path, $root_element = null)
    {
        $tpl_name = Translator::checkLang() . "-" . str_replace(
            array(
            Constant::DISK_PATH . "/",
            "_",
            "/"

        ),
            array(
            "",
            "-",
            "_"
        ),
            $template_path
        );

        Debug::stopWatch("tpl/" . $tpl_name);

        $cache = Mem::getInstance("tpl");
        $res = $cache->get($tpl_name);
        if (!$res) {
            if ($root_element !== null) {
                $this->root_element = $root_element;
            }
            $this->DBlocks[$this->root_element] = Dir::loadFile($template_path);
            if ($this->DBlocks[$this->root_element] !== false) {
                $this->getDVars();
                $nName = $this->NextDBlockName($this->root_element);
                while ($nName != "") {
                    $this->SetBlock($this->root_element, $nName);
                    $this->blockVars($nName);
                    $nName = $this->NextDBlockName($this->root_element);
                }
            } else {
                Error::register("Unable to find the template", static::ERROR_BUCKET);
            }

            $cache->set($tpl_name, array(
                "DBlocks"       => $this->DBlocks
                , "DVars"       => $this->DVars
                , "DBlockVars"  => $this->DBlockVars
                , "root_element"=> $this->root_element
            ));
        } else {
            $this->DBlocks      = $res["DBlocks"];
            $this->DVars        = $res["DVars"];
            $this->DBlockVars   = $res["DBlockVars"];
            $this->root_element = $res["root_element"];
        }

        Hook::handle("on_loaded_data", $this);

        Debug::stopWatch("tpl/" . $tpl_name);

        return null;
    }

    public function load_content($content, $root_element = null)
    {
        if ($root_element !== null) {
            $this->root_element = $root_element;
        }

        $this->DBlocks[$this->root_element] = $content;
        $this->getDVars();
        $nName = $this->NextDBlockName($this->root_element);
        while ($nName != "") {
            $this->SetBlock($this->root_element, $nName);
            $nName = $this->NextDBlockName($this->root_element);
        }

        Hook::handle("on_loaded_data", $this);
    }

    private function getDVars()
    {
        if ($this->doublevar_to_commenthtml) {
            $this->DBlocks[$this->root_element] = preg_replace('/\{\{([\w\[\]\:\=\-\|\.]+)\}\}/U', "<!--{\{$1\}\}-->", $this->DBlocks[$this->root_element]);
        }

        $matches = null;
        $rc = preg_match_all(static::REGEXP, $this->DBlocks[$this->root_element], $matches);
        if ($rc && $matches) {
            $this->DVars = array_flip($matches[1]);
            $this->translateDocument();
        }
    }

    public function getDApplets()
    {
        if (!$this->DApplets) {
            $matches = null;
            $rc = preg_match_all(static::APPLET, $this->DBlocks[$this->root_element], $matches);
            if ($rc && $matches) {
                $applets = $matches[1];
                if (is_array($applets) && count($applets)) {
                    foreach ($applets as $applet) {
                        if (strpos($applet, "{") !== false) {
                            $matches = null;
                            $rc = preg_match_all(static::REGEXP, $applet, $matches);
                            if ($rc && $matches) {
                                $applet = str_replace($matches[0], array_intersect_key($this->ParsedBlocks, array_flip($matches[1])), $applet);
                            }
                        }

                        $this->setApplet($applet);
                    }
                }
            }

            $matches = null;
            $rc = preg_match_all(static::APPLET, implode(" ", $this->ParsedBlocks), $matches);
            if ($rc && $matches) {
                $applets = $matches[1];
                if (is_array($applets) && count($applets)) {
                    foreach ($applets as $applet) {
                        $this->setApplet($applet);
                    }
                }
            }
        }

        return $this->DApplets;
    }

    private function setApplet($applet)
    {
        $arrApplet = explode(":", $applet, 2);
        $appletid = "[" . $applet . "]";
        $this->DApplets[$appletid] = array();
        $this->DApplets[$appletid]["name"] = $arrApplet[0];

        parse_str(str_replace(":", "&", $arrApplet[1]), $this->DApplets[$appletid]["params"]);
    }

    private function NextDBlockName($sTemplateName)
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


    private function SetBlock($sTplName, $sBlockName)
    {
        if (!isset($this->DBlocks[$sBlockName])) {
            $this->DBlocks[$sBlockName] = $this->getBlock($this->DBlocks[$sTplName], $sBlockName);
        }
        $this->DBlocks[$sTplName] = $this->replaceBlock($this->DBlocks[$sTplName], $sBlockName);

        $nName = $this->NextDBlockName($sBlockName);

        while ($nName != "") {
            $this->SetBlock($sBlockName, $nName);
            $nName = $this->NextDBlockName($sBlockName);
        }
    }

    private function getBlock($sTemplate, $sName)
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


    private function replaceBlock($sTemplate, $sName)
    {
        $BBlock = strpos($sTemplate, "<!--" . $this->BeginTag . $sName . "-->");
        $EBlock = strpos($sTemplate, "<!--" . $this->EndTag . $sName . "-->");

        if ($BBlock === false || $EBlock === false) {
            return $sTemplate;
        } else {
            return substr($sTemplate, 0, $BBlock) . "{" . $sName . "}" . substr($sTemplate, $EBlock + strlen("<!--End" . $sName . "-->"));
        }
    }

    public function GetVar($sName)
    {
        return $this->DBlocks[$sName];
    }

    /**
     * @param string $sName
     * @param string  $sValue
     * @return bool
     *
     * @todo Da eliminare
     */
    private function set_var($sName, $sValue)
    {
        $this->ParsedBlocks[$sName] = $sValue;

        return isset($this->DVars[$sName]) || isset($this->DBlocks[$sName]);
    }

    public function isset_var($sName)
    {
        return isset($this->DVars[$sName]) || isset($this->DBlocks[$sName]);
    }

    public function isset_block($sName)
    {
        return (bool)($this->ParsedBlocks[$sName]);
    }

    public function parse($sTplName, $bRepeat = false, $bBefore = false)
    {
        if (isset($this->DBlocks[$sTplName])) {
            if ($bRepeat && isset($this->ParsedBlocks[$sTplName])) {
                if ($bBefore) {
                    $this->ParsedBlocks[$sTplName] = $this->ProceedTpl($sTplName) . $this->ParsedBlocks[$sTplName];
                } else {
                    $this->ParsedBlocks[$sTplName] .= $this->ProceedTpl($sTplName);
                }
            } else {
                $this->ParsedBlocks[$sTplName] = $this->ProceedTpl($sTplName);
            }
            return true;
        } elseif ($this->debug_msg) {
            echo "<br><b>Block with name <u><span style=\"color: red; \">$sTplName</span></u> does't exist</b><br>";
        }

        return false;
    }

    /**
     * @deprecated
    public function pparse($block_name, $is_repeat)
    {
        echo $this->rpparse($block_name, $is_repeat);
    }
    private function rpparse($block_name, $is_repeat)
    {
        $this->parse($block_name, $is_repeat);
        return $this->getBlockContent($block_name);
    }
    */

    /**
     * @param array|string $data
     * @param null|mixed $value
     * @return $this
     */
    public function assign($data, $value = null)
    {
        if (is_array($data)) {
            $this->ParsedBlocks             = array_replace($this->ParsedBlocks, $data);
        } else {
            $this->ParsedBlocks[$data]      = $value;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function display()
    {
        $this->parse($this->root_element, false);
        return $this->getBlockContent($this->root_element);
    }
    public function getBlockContent($block_name, $minify = null)
    {
        $minify = ($minify === null ? $this->minify : $minify);

        if ($minify === false) {
            return (isset($this->ParsedBlocks[$block_name])
                ? $this->entities_replace($this->ParsedBlocks[$block_name])
                : null
            );
        } elseif ($minify === "strip") {
            return $this->entities_replace(preg_replace("/\n\s*/", "\n", $this->ParsedBlocks[$block_name], -1, $count));
        } elseif ($minify === "strong_strip") {
            return $this->entities_replace(preg_replace(
                array(
                    '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
                    '/[^\S ]+\</s',  // strip whitespaces before tags, except space
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

    private function blockVars($sTplName)
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
            return false;
        }
    }

    private function translateDocument()
    {
        $translation = new stdClass();
        foreach ($this->DVars as $nName => $count) {
            if (substr($nName, 0, 1) == "_") {
                $translation->key[]           = "{" . $nName . "}";
                $translation->value[]         = Translator::get_word_by_code(substr($nName, 1));
            }
        }
        if (isset($translation->key)) {
            $this->DBlocks[$this->root_element] = str_replace($translation->key, $translation->value, $this->DBlocks[$this->root_element]);
        }
    }
    private function ProceedTpl($sTplName)
    {
        $vars = $this->blockVars($sTplName);
        $sTpl = $this->DBlocks[$sTplName];

        if ($vars) {
            $search_for = array();
            $replace_with = array();

            reset($vars);
            foreach ($vars as $value) {
                $tmp = (
                    isset($this->ParsedBlocks[$value])
                    ? $this->ParsedBlocks[$value]
                    : null
                );
                if (is_object($tmp)) {
                    Error::register("bad value into template", static::ERROR_BUCKET);
                }

                $search_for[] = "{" . $value . "}";
                if (isset($this->ParsedBlocks[$value])) {
                    $replace_with[] = $this->ParsedBlocks[$value];
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

    private function entities_replace($text)
    {
        return str_replace(array("{\\","\\}"), array("{","}"), $text);
    }
}
