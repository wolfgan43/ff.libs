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

use phpformsframework\libs\Debug;
use phpformsframework\libs\Hook;
use phpformsframework\libs\Error;
use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\international\Translator;

if (!defined("FF_ENABLE_MULTILANG")) {
    define("FF_ENABLE_MULTILANG", true);
}
if (!defined("FF_TEMPLATE_ENABLE_TPL_JS")) {
    define("FF_TEMPLATE_ENABLE_TPL_JS", false);
}

class ffTemplate extends Hook
{
    const ERROR_BUCKET                      = "tpl";
    const REGEXP                            = '/\{([\w\:\=\-\|\.\s\?\!\\\'\"\,]+)\}/U';
    //const REGEXP                            = '/\{([\w\:\=\-\|\.]+)\}/U';

    const APPLET                            = '/\{\[(.+)\]\}/U';
    const COMMENTHTML                       = '/\{\{([\w\[\]\:\=\-\|\.]+)\}\}/U';

    public $root_element						= "main";

    public $BeginTag							= "Begin";
    public $EndTag								= "End";

    public $debug_msg							= false;
    public $display_unparsed_sect				= false;
    public $doublevar_to_commenthtml 			= FF_TEMPLATE_ENABLE_TPL_JS;

    public $DBlocks 							= null;
    public $DVars 								= null;
    public $DBlockVars 						= null;
    public $ParsedBlocks 						= array();
    public $DApplets							= null;

    public $minify								= false; /* can be: false, strip, strong_strip, minify
                                                  NB: minify require /library/minify (set CM_CSSCACHE_MINIFIER and CM_JSCACHE_MINIFIER too) */
    public $compress							= false;

    // FF enabled settings (u must have FF and use ::factory()
    //var $force_mb_encoding					= "UTF-8"; // false or UTF-8 (require FF)

    // MultiLang SETTINGS
    public $MultiLang							= FF_ENABLE_MULTILANG; // enable support (require class ffDB_Sql)

    public static function _get_word_by_code($code, $language = null)
    {
        return Translator::get_word_by_code($code, $language);
    }

    /**
     * This method istantiate a ffTemplate instance based on dir path. When using this
     * function, the resulting object will deeply use Forms Framework.
     *
     * @param string $template_file
     * @return ffTemplate
     */
    public static function fetch($template_file)
    {
        $tmp = new ffTemplate();
        $tmp->load_file($template_file);

        return $tmp;
    }


    public static function factory()
    {
        $tmp = new ffTemplate();

        self::handle("on_factory_done", $tmp);

        return $tmp;
    }

    // CONSTRUCTOR
    public function __construct()
    {
    }

    public function load_file($template_path, $root_element = null)
    {
        Debug::stopWatch("tpl" . $template_path);

        $cache = Mem::getInstance("tpl");
        $res = $cache->get($template_path);
        if (!$res) {
            if ($root_element !== null) {
                $this->root_element = $root_element;
            }
            $this->DBlocks[$this->root_element] = @file_get_contents($template_path);
            if ($this->DBlocks[$this->root_element] !== false) {
                /*if ($this->force_mb_encoding !== false) {
                    $this->DBlocks[$this->root_element] = htmlspecialchars($this->DBlocks[$this->root_element], ENT_COMPAT, $this->force_mb_encoding);
                }*/

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

            $cache->set($template_path, array(
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

        $this->handle("on_loaded_data", $this);

        Debug::stopWatch("tpl" . $template_path);

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

        $this->handle("on_loaded_data", $this);
    }

    public function getDVars()
    {
        if ($this->doublevar_to_commenthtml) {
            $this->DBlocks[$this->root_element] = preg_replace('/\{\{([\w\[\]\:\=\-\|\.]+)\}\}/U', "<!--{\{$1\}\}-->", $this->DBlocks[$this->root_element]);// str_replace(array("{{", "}}"), array("<!--", "-->"), $this->DBlocks[$this->root_element]);
        }

        $matches = null;
        $rc = preg_match_all(ffTemplate::REGEXP, $this->DBlocks[$this->root_element], $matches);
        if ($rc && $matches) {
            $this->DVars = array_flip($matches[1]);
        }
    }

    public function getDApplets()
    {
        if (!$this->DApplets) {
            $matches = null;
            $rc = preg_match_all(ffTemplate::APPLET, $this->DBlocks[$this->root_element], $matches);
            if ($rc && $matches) {
                $applets = $matches[1];
                if (is_array($applets) && count($applets)) {
                    foreach ($applets as $applet) {
                        if (strpos($applet, "{") !== false) {
                            $matches = null;
                            $rc = preg_match_all(ffTemplate::REGEXP, $applet, $matches);
                            if ($rc && $matches) {
                                $applet = str_replace($matches[0], array_intersect_key($this->ParsedBlocks, array_flip($matches[1])), $applet);
                            }
                        }

                        $this->setApplet($applet);
                    }
                }
            }

            $matches = null;
            $rc = preg_match_all(ffTemplate::APPLET, implode(" ", $this->ParsedBlocks), $matches);
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

    public function NextDBlockName($sTemplateName)
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


    public function SetBlock($sTplName, $sBlockName)
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

    public function getBlock($sTemplate, $sName)
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


    public function replaceBlock($sTemplate, $sName)
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

    public function set_var($sName, $sValue)
    {
        $this->ParsedBlocks[$sName] = $sValue;
        if (isset($this->DVars[$sName]) || isset($this->DBlocks[$sName])) {
            return true;
        } else {
            return false;
        }
    }

    public function isset_var($sName)
    {
        if (isset($this->DVars[$sName]) || isset($this->DBlocks[$sName])) {
            return true;
        } else {
            return false;
        }
    }

    public function isset_block($sName)
    {
        if ((bool)($this->ParsedBlocks[$sName])) {
            return true;
        } else {
            return false;
        }
    }

    public function set_regexp_var($sPattern, $sValue)
    {
        $rc = false;
        $tmp = array_keys($this->ParsedBlocks);
        foreach ($tmp as $value) {
            if (preg_match($sPattern, $value)) {
                $rc = true;
                $this->ParsedBlocks[$value] = $sValue;
            }
        }
        return $rc;
    }

    public function parse_regexp($sPattern, $sValue)
    {
        $rc = false;
        $tmp = array_keys($this->DBlocks);
        foreach ($tmp as $value) {
            if (preg_match($sPattern, $value)) {
                $rc = true;
                $this->parse($value, $sValue);
            }
        }
        return $rc;
    }

    public function print_var($sName)
    {
        echo $this->ParsedBlocks[$sName];
    }

    public function parse($sTplName, $bRepeat, $bBefore = false)
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
            echo "<br><b>Block with name <u><font color=\"red\">$sTplName</font></u> does't exist</b><br>";
        }

        return false;
    }

    public function pparse($block_name, $is_repeat)
    {
        $ret = $this->rpparse($block_name, $is_repeat);
        if (0 && $this->compress) {
            ffTemplate::http_compress($ret);
        } else {
            echo $ret;
        }
    }

    public function rpparse($block_name, $is_repeat)
    {
        $this->parse($block_name, $is_repeat);
        return $this->getBlockContent($block_name);
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

    public static function http_compress($data, $output_result = true, $method = null, $level = 9)
    {
        if ($method === null) {
            $encodings = array_flip(explode(",", $_SERVER["HTTP_ACCEPT_ENCODING"]));
            if (isset($encodings["gzip"])) {
                $method = "gzip";
            } elseif (isset($encodings["deflate"])) {
                $method = "deflate";
            }
        }

        if ($method == "deflate") {
            if ($output_result) {
                header("Content-Encoding: deflate");
                echo gzdeflate($data, $level);
            } else {
                return array(
                    "method" => "deflate",
                    "data" => gzdeflate($data, $level)
                );
            }
        } elseif ($method == "gzip") {
            if ($output_result) {
                header("Content-Encoding: gzip");
                echo gzencode($data, $level);
            } else {
                return array(
                    "method" => "gzip",
                    "data" => gzencode($data, $level)
                );
            }
        } else {
            if ($output_result) {
                echo $data;
            } else {
                return array(
                    "method" => null
                    , "data" => $data
                );
            }
        }

        return null;
    }

    public function blockVars($sTplName)
    {
        if (isset($this->DBlockVars[$sTplName])) {
            return $this->DBlockVars[$sTplName];
        }

        $sTpl = $this->DBlocks[$sTplName];

        $matches = array();
        $rc = preg_match_all(ffTemplate::REGEXP, $sTpl, $matches);
        if ($rc) {
            $vars = $matches[1];

            // --- AUTOMATIC LANGUAGE LOOKUP FOR INTERNATIONALIZATION
            foreach ($vars as $nName) {
                if (substr($nName, 0, 1) == "_") {
                    if ($this->MultiLang) {
                        $this->set_var($nName, $this->get_word_by_code(substr($nName, 1)));
                    } else {
                        $this->set_var($nName, "{" . substr($nName, 1) . "}");
                    }
                }
            }
            reset($vars);

            $this->DBlockVars[$sTplName] = $vars;

            return $vars;
        } else {
            return false;
        }
    }

    public function ProceedTpl($sTplName)
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

    public function PrintAll()
    {
        $res = "<table border=\"1\" width=\"100%\">";
        $res .= "<tr bgcolor=\"#C0C0C0\" align=\"center\"><td>Key</td><td>Value</td></tr>";
        $res .= "<tr bgcolor=\"#FFE0E0\"><td colspan=\"2\" align=\"center\">ParsedBlocks</td></tr>";
        reset($this->ParsedBlocks);
        foreach ($this->ParsedBlocks as $key => $value) {
            $res .= "<tr><td><pre>" . htmlspecialchars($key) . "</pre></td>";
            $res .= "<td><pre>" . htmlspecialchars($value) . "</pre></td></tr>";
        }
        $res .= "<tr bgcolor=\"#E0FFE0\"><td colspan=\"2\" align=\"center\">DBlocks</td></tr>";
        reset($this->DBlocks);

        foreach ($this->DBlocks as $key => $value) {
            $res .= "<tr><td><pre>" . htmlspecialchars($key) . "</pre></td>";
            $res .= "<td><pre>" . htmlspecialchars($value) . "</pre></td></tr>";
        }
        $res .= "</table>";
        return $res;
    }

    public function entities_replace($text)
    {
        return str_replace(array("{\\","\\}"), array("{","}"), $text);
    }

    public function get_word_by_code($code, $language = null)
    {
        return Translator::get_word_by_code($code, $language);
    }
}
