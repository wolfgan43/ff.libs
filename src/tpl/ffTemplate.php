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

use phpformsframework\libs\Hook;
use phpformsframework\libs\Error;
use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\international\Translator;


if(!defined("FF_ENABLE_MULTILANG"))             define("FF_ENABLE_MULTILANG", true);
if(!defined("FF_ENABLE_MEM_TPL_CACHING"))       define("FF_ENABLE_MEM_TPL_CACHING", false);
if(!defined("FF_TEMPLATE_ENABLE_TPL_JS"))       define("FF_TEMPLATE_ENABLE_TPL_JS", false);

class ffTemplate extends Hook
{
    const REGEXP                            = '/\{([\w\:\=\-\|\.\s\?\!\\\'\"\,]+)\}/U';
    //const REGEXP                            = '/\{([\w\:\=\-\|\.]+)\}/U';

    const APPLET                            = '/\{\[(.+)\]\}/U';
    const COMMENTHTML                       = '/\{\{([\w\[\]\:\=\-\|\.]+)\}\}/U';

    var $root_element						= "main";

	var $BeginTag							= "Begin";
	var $EndTag								= "End";

	var $debug_msg							= false;
	var $display_unparsed_sect				= false;
	var $doublevar_to_commenthtml 			= FF_TEMPLATE_ENABLE_TPL_JS;

	var $DBlocks 							= array();			// initial data: files and blocks
	var $ParsedBlocks 						= array();		// result data and variables
	var $DVars 								= array();
	var $DApplets							= null;
	var $DBlockVars 						= array();

	var $minify								= false; /* can be: false, strip, strong_strip, minify
	 											 NB: minify require /library/minify (set CM_CSSCACHE_MINIFIER and CM_JSCACHE_MINIFIER too) */
	var $compress							= false;

	// FF enabled settings (u must have FF and use ::factory()
	var $force_mb_encoding					= "UTF-8"; // false or UTF-8 (require FF)

	// MultiLang SETTINGS
	var $MultiLang							= FF_ENABLE_MULTILANG; // enable support (require class ffDB_Sql)

	// PRIVATES
	private $useFormsFramework				= false;
    private $use_cache		                = FF_ENABLE_MEM_TPL_CACHING;

    public static function _get_word_by_code($code, $language = null) {
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
	function __construct()
	{
	}

	function load_file($filename, $root_element = null)
	{
        $res = null;
        $cache = null;

        if ($root_element !== null) {
            $this->root_element = $root_element;
        }

		$template_path = $filename;

		if ($this->useFormsFramework && $this->use_cache)
        {
            $cache = Mem::getInstance();
            $res = $cache->get($template_path, "/ff/template");
        }

        if (!$res)
		{
			$this->DBlocks[$this->root_element] = @file_get_contents($template_path);
			if ($this->DBlocks[$this->root_element] !== false)
			{
				if ($this->useFormsFramework && $this->force_mb_encoding !== false) {
                    $this->DBlocks[$this->root_element] = ffCommon_charset_encode($this->DBlocks[$this->root_element], $this->force_mb_encoding);
                }

				$this->getDVars();
				$nName = $this->NextDBlockName($this->root_element);
				while($nName != "")
				{
					$this->SetBlock($this->root_element, $nName);
					$this->blockVars($nName);
					$nName = $this->NextDBlockName($this->root_element);
				}
			}
			else
			{
				if ($this->useFormsFramework) {
                    Error::dump("Unable to find the template", E_USER_ERROR, null, get_defined_vars());
                } else {
				    return $template_path . ": Unable to load template";
                    //die("<br><b><u><font color=\"red\">Unable to find the template</font></u></b><br>");
                }
			}

            if ($cache) {
                $cache->set($template_path, array("DBlocks" => $this->DBlocks, "DVars" => $this->DVars, "DBlockVars" => $this->DBlockVars), "/ff/template");
            }
		}
		else
		{
			$this->DBlocks = $res["DBlocks"];
			$this->DVars = $res["DVars"];
			$this->DBlockVars = $res["DBlockVars"];
		}

		$this->handle("on_loaded_data", $this);

		return null;
	}

	function load_content($content, $root_element = null)
	{
		if ($root_element !== null) {
            $this->root_element = $root_element;
        }

		$this->DBlocks[$this->root_element] = $content;
		$this->getDVars();
		$nName = $this->NextDBlockName($this->root_element);
		while($nName != "")
		{
			$this->SetBlock($this->root_element, $nName);
			$nName = $this->NextDBlockName($this->root_element);
		}

        $this->handle("on_loaded_data", $this);
	}

	function getDVars()
	{
		if($this->doublevar_to_commenthtml) {
            $this->DBlocks[$this->root_element] = preg_replace('/\{\{([\w\[\]\:\=\-\|\.]+)\}\}/U', "<!--{\{$1\}\}-->", $this->DBlocks[$this->root_element]);// str_replace(array("{{", "}}"), array("<!--", "-->"), $this->DBlocks[$this->root_element]);
        }

        $matches = null;
        $rc = preg_match_all (ffTemplate::REGEXP, $this->DBlocks[$this->root_element], $matches);
		if ($rc && $matches) {
            $this->DVars = array_flip($matches[1]);
        }
	}

    function getDApplets() {
        if(!$this->DApplets) {
            $matches = null;
            $rc = preg_match_all(ffTemplate::APPLET, $this->DBlocks[$this->root_element], $matches);
            if ($rc && $matches) {
                $applets = $matches[1];
                if (is_array($applets) && count($applets)) {
                    foreach ($applets AS $applet) {
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
                    foreach ($applets AS $applet) {
                        $this->setApplet($applet);
                    }
                }
            }
        }

        return $this->DApplets;
    }

    private function setApplet($applet) {
        $arrApplet = explode(":", $applet, 2);
        $appletid = "[" . $applet . "]";
        $this->DApplets[$appletid] = array();
        $this->DApplets[$appletid]["name"] = $arrApplet[0];

        parse_str(str_replace(":", "&", $arrApplet[1]), $this->DApplets[$appletid]["params"]);
    }

	function NextDBlockName($sTemplateName)
	{
		$sTemplate = $this->DBlocks[$sTemplateName];
		$BTag = strpos($sTemplate, "<!--" . $this->BeginTag);
		if($BTag === false)
		{
			return "";
		}
		else
		{
			$ETag = strpos($sTemplate, "-->", $BTag);
			$sName = substr($sTemplate, $BTag + 9, $ETag - ($BTag + 9));
			if(strpos($sTemplate, "<!--" . $this->EndTag . $sName . "-->") > 0)
			{
				return $sName;
			}
			else
			{
				return "";
			}
		}
	}


	function SetBlock($sTplName, $sBlockName)
	{
		if(!isset($this->DBlocks[$sBlockName])) {
            $this->DBlocks[$sBlockName] = $this->getBlock($this->DBlocks[$sTplName], $sBlockName);
        }
		$this->DBlocks[$sTplName] = $this->replaceBlock($this->DBlocks[$sTplName], $sBlockName);

		$nName = $this->NextDBlockName($sBlockName);

		while($nName != "")
		{
			$this->SetBlock($sBlockName, $nName);
			$nName = $this->NextDBlockName($sBlockName);
		}
	}

	function getBlock($sTemplate, $sName)
	{
		$alpha = strlen($sName) + 12;

		$BBlock = strpos($sTemplate, "<!--" . $this->BeginTag . $sName . "-->");
		$EBlock = strpos($sTemplate, "<!--" . $this->EndTag . $sName . "-->");

		if($BBlock === false || $EBlock === false) {
            return "";
        } else {
            return substr($sTemplate, $BBlock + $alpha, $EBlock - $BBlock - $alpha);
        }
	}


	function replaceBlock($sTemplate, $sName)
	{
		$BBlock = strpos($sTemplate, "<!--" . $this->BeginTag . $sName . "-->");
		$EBlock = strpos($sTemplate, "<!--" . $this->EndTag . $sName . "-->");

		if($BBlock === false || $EBlock === false) {
            return $sTemplate;
        } else {
            return substr($sTemplate, 0, $BBlock) . "{" . $sName . "}" . substr($sTemplate, $EBlock + strlen("<!--End" . $sName . "-->"));
        }
	}

	function GetVar($sName)
	{
		return $this->DBlocks[$sName];
	}

	function set_var($sName, $sValue)
	{
		$this->ParsedBlocks[$sName] = $sValue;
		if (isset($this->DVars[$sName]) || isset($this->DBlocks[$sName]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function isset_var($sName)
	{
		if (isset($this->DVars[$sName]) || isset($this->DBlocks[$sName]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

    function isset_block($sName)
    {
        if ((bool)($this->ParsedBlocks[$sName]))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

	function set_regexp_var($sPattern, $sValue)
	{
		$rc = false;
		$tmp = array_keys($this->ParsedBlocks);
		foreach ($tmp as $key => $value)
		{
			if (preg_match($sPattern, $value))
			{
				$rc = true;
				$this->ParsedBlocks[$value] = $sValue;
			}
		}
		return $rc;
	}

	function parse_regexp($sPattern, $sValue)
	{
		$rc = false;
		$tmp = array_keys($this->DBlocks);
		foreach ($tmp as $key => $value)
		{
			if (preg_match($sPattern, $value))
			{
				$rc = true;
				$this->parse($value, $sValue);
			}
		}
		return $rc;
	}

	function print_var($sName)
	{
		echo $this->ParsedBlocks[$sName];
	}

	function parse($sTplName, $bRepeat, $bBefore = false)
	{
		if(isset($this->DBlocks[$sTplName]))
		{
			if($bRepeat && isset($this->ParsedBlocks[$sTplName]))
			{
				if ($bBefore)
					$this->ParsedBlocks[$sTplName] = $this->ProceedTpl($sTplName) . $this->ParsedBlocks[$sTplName];
				else
					$this->ParsedBlocks[$sTplName] .= $this->ProceedTpl($sTplName);
			}
			else
				$this->ParsedBlocks[$sTplName] = $this->ProceedTpl($sTplName);

			return true;
		}
		else if ($this->debug_msg)
		{
			echo "<br><b>Block with name <u><font color=\"red\">$sTplName</font></u> does't exist</b><br>";
		}

		return false;
	}

	function pparse($block_name, $is_repeat)
    {
        $ret = $this->rpparse($block_name, $is_repeat);
        if (0 && $this->compress) {
            ffTemplate::http_compress($ret);
        } else {
            echo $ret;
        }


	}

	function rpparse($block_name, $is_repeat)
	{
		$this->parse($block_name, $is_repeat);
		return $this->getBlockContent($block_name);
	}

	function getBlockContent($block_name, $minify = null)
	{
		$minify = ($minify === null ? $this->minify : $minify);

		if ($minify === false) {
            return (isset($this->ParsedBlocks[$block_name])
                ? $this->entities_replace($this->ParsedBlocks[$block_name])
                : null
            );
        } else if ($minify === "strip") {
            return $this->entities_replace(preg_replace("/\n\s*/", "\n", $this->ParsedBlocks[$block_name], -1, $count));
        } else if ($minify === "strong_strip") {
            return $this->entities_replace(preg_replace(
                array(
                    '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
                    '/[^\S ]+\</s',  // strip whitespaces before tags, except space
                    '/(\s)+/s'       // shorten multiple whitespace sequences
                )
                , array(
                    '>',
                    '<',
                    '\\1'
                )
                , $this->ParsedBlocks[$block_name]
                , -1
                , $count
            ));
        } else if ($minify === "minify") {
			if (!class_exists("Minify_HTML"))
				require FF_DISK_PATH . '/library/minify/min/lib/Minify/HTML.php';
			if (!class_exists("CSSmin"))
				require FF_DISK_PATH . '/library/minify/min/lib/CSSmin.php';
			if (!class_exists("JSMin"))
				require FF_DISK_PATH . '/library/minify/min/lib/JSMin.php';

			return $this->entities_replace(str_replace(chr(10), " ", Minify_HTML::minify(
						$this->ParsedBlocks[$block_name]
						, array(
							"cssMinifier" => "CSSmin::_minify"
							, "jsMinifier" => "JSMin::minify"
							, "jsCleanComments" => true
						))
					));
		} else if ($minify === "yui") {
			if (!class_exists("Minify_HTML"))
				require FF_DISK_PATH . '/library/minify/min/lib/Minify/HTML.php';
			if (!class_exists("Minify_YUICompressor"))
				require(FF_DISK_PATH . "/library/gminify/YUICompressor.php");
			Minify_YUICompressor::$jarFile = FF_DISK_PATH . "/library/gminify/yuicompressor-2.4.8.jar";
			if (!file_exists(CM_JSCACHE_DIR))
			{
				@mkdir(CM_JSCACHE_DIR, 0777, true);
			}
			Minify_YUICompressor::$tempDir = CM_JSCACHE_DIR;
			return $this->entities_replace(str_replace(chr(10), " ", Minify_HTML::minify(
						$this->ParsedBlocks[$block_name]
						, array(
							"cssMinifier" => "Minify_YUICompressor::minifyCss"
							, "jsMinifier" => "Minify_YUICompressor::minifyJs"
						))
					));
		} else {
			if ($this->useFormsFramework) {
                Error::dump("Unknown minify method", E_USER_ERROR, $this, get_defined_vars());
            } else {
                die("Unknown minify method");
            }
		}

		return null;
	}

	static function http_compress($data, $output_result = true, $method = null, $level = 9)
	{
		if ($method === null)
		{
			$encodings = array_flip(explode(",", $_SERVER["HTTP_ACCEPT_ENCODING"]));
			if (isset($encodings["gzip"])) // better gzip
				$method = "gzip";
			elseif (isset($encodings["deflate"]))
				$method = "deflate";
		}

		if ($method == "deflate")
		{
			if ($output_result)
			{
				header("Content-Encoding: deflate");
				echo gzdeflate($data, $level);
				/*gzcompress($this->tpl[0]->rpparse("main", false), 9);
				gzencode($this->tpl[0]->rpparse("main", false), 9, FORCE_DEFLATE);
				gzencode($this->tpl[0]->rpparse("main", false), 9, FORCE_GZIP);*/
			}
			else
				return array(
					"method" => "deflate"
					, "data" => gzdeflate($data, $level)
				);
		}
		elseif ($method == "gzip")
		{
			if ($output_result)
			{
				header("Content-Encoding: gzip");
				echo gzencode($data, $level);
			}
			else
				return array(
					"method" => "gzip"
					, "data" => gzencode($data, $level)
				);
		}
		else
		{
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

	function blockVars($sTplName)
	{
		if (isset($this->DBlockVars[$sTplName]))
			return $this->DBlockVars[$sTplName];

		$sTpl = $this->DBlocks[$sTplName];

		$matches = array();
		$rc = preg_match_all (ffTemplate::REGEXP, $sTpl, $matches);
		if ($rc)
		{
			$vars = $matches[1];

			// --- AUTOMATIC LANGUAGE LOOKUP FOR INTERNATIONALIZATION
            foreach ($vars as $nName)
            {
                if (substr($nName, 0, 1) == "_")
                {
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
		}
		else
			return false;
	}

	function ProceedTpl($sTplName)
	{
		$vars = $this->blockVars($sTplName);
		$sTpl = $this->DBlocks[$sTplName];

		if($vars)
		{
			$search_for = array();
			$replace_with = array();

			reset($vars);
			foreach($vars as $key => $value)
			{
				$tmp = (isset($this->ParsedBlocks[$value])
                    ? $this->ParsedBlocks[$value]
                    : null
                );
				if (is_object($tmp))
				{
					if ($this->useFormsFramework) {
                        Error::dump("bad value into template", E_USER_ERROR, $this, get_defined_vars());
                    } else {
                        die("bad value into template");
                    }
				}

				$search_for[] = "{" . $value . "}";
				if(isset($this->ParsedBlocks[$value]))
				{
					$replace_with[] = $this->ParsedBlocks[$value];
				}
				else if(isset($this->DBlocks[$value]) && $this->display_unparsed_sect)
				{
					$replace_with[] = $this->DBlocks[$value];
				}
				else
				{
					$replace_with[] = "";
				}
			}
			$sTpl = str_replace($search_for, $replace_with, $sTpl);
		}
		return $sTpl;
	}

	function PrintAll()
	{
		$res = "<table border=\"1\" width=\"100%\">";
		$res .= "<tr bgcolor=\"#C0C0C0\" align=\"center\"><td>Key</td><td>Value</td></tr>";
		$res .= "<tr bgcolor=\"#FFE0E0\"><td colspan=\"2\" align=\"center\">ParsedBlocks</td></tr>";
		reset($this->ParsedBlocks);
		foreach($this->ParsedBlocks as $key => $value)
		{
			$res .= "<tr><td><pre>" . ffCommon_specialchars($key) . "</pre></td>";
			$res .= "<td><pre>" . ffCommon_specialchars($value) . "</pre></td></tr>";
		}
		$res .= "<tr bgcolor=\"#E0FFE0\"><td colspan=\"2\" align=\"center\">DBlocks</td></tr>";
		reset($this->DBlocks);

		foreach($this->DBlocks as $key => $value)
		{
			$res .= "<tr><td><pre>" . ffCommon_specialchars($key) . "</pre></td>";
			$res .= "<td><pre>" . ffCommon_specialchars($value) . "</pre></td></tr>";
		}
		$res .= "</table>";
		return $res;
	}

    function entities_replace($text)
    {
        return str_replace(array("{\\","\\}"), array("{","}"), $text);
    }

	function get_word_by_code($code, $language = null)
	{
        return Translator::get_word_by_code($code, $language);
	}
}
