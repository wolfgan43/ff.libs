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

use ff\libs\cache\Buffer;
use ff\libs\Constant;
use ff\libs\Debug;
use ff\libs\gui\Controller;
use ff\libs\gui\Resource;
use ff\libs\gui\View;
use ff\libs\Hook;
use ff\libs\international\Locale;
use ff\libs\international\Translator;
use ff\libs\Kernel;
use ff\libs\storage\FilemanagerFs;
use ff\libs\Exception;
use stdClass;

/**
 * Class ViewHtml
 * @package ff\libs\gui\adapters
 *
 *
 * {Controller::get}
 * {TopBar}
 * {include file="../shared/footer.tpl"}
 * {$content}
 *
 * <!--BeginSection-->  <!--BeginSection#2-->   [...]
 * <!--EndSection-->    <!--EndSection#2-->     [...]
 *
 * {user.display_name}
 * <!--BeginUser-->
 * {display_name}
 * {display_name["bob": "myCustomReplace", "carl": "MyCustomReplace2"]}
 *
 * {comments.first.user_uuid}
 * {comments.last.user_uuid}
 * {comments.1.user_uuid}
 * {comments.2.user_uuid}
 * {comments.n.user_uuid}
 * {comments.count}
 *  <!--BeginComments-->
 *      {id}
 *      <!--BeginId-->
 *          if Id not null diplay this row {user.display_name}
 *      <!--EndId-->
 *
 *      {index}
 *      {count}
 *      {oddeven}
 *      {first=active}
 *      {first=end}
 *      {odd=myClassOdd}
 *      {even=myClassEven}
 *      {index:1=myCustomClass1}
 *      {index:2=myCustomClass2}
 *      {index:n=myCustomClassN}
 *
 *      <!--BeginTest-->
 *          Infinite object nested
 *      <!--EndTest-->
 *
 *      <!--BeginId#2-->
 *          if Id not null diplay this row {user.display_name}
 *          {user.display_name["bob": "myCustomReplace", "carl": "MyCustomReplace2"]}
 *          index: {index} of {count} comments
 *      <!--EndId#2-->
 *  <!--EndComments-->
 * <!--EndUser-->
 */
class ViewHtml implements ViewAdapter
{
    //protected const REGEXP                      = '/\{([\w\:\=\-\+\|\.\s\?\!\\\'\"\,\$\#\[\]\/]+)\}/U';
    //protected const REGEXP_STRIP                = '/\{\$(.+)\}/U';

    protected const REGEXP                      = '/{([\w:=\-+|.\s?!\'",$#\[\]\/]+)}/U';
    protected const REGEXP_STRIP                = '/{\$(.+)}/U';

    private const ROOT_BLOCK                    = "main";
    private const BEGIN_TAG                     = "Begin";
    private const END_TAG                       = "End";

    private const ERROR_LANG_NOT_VALID          = "lang not valid";
    private const HOOK_ON_FETCH_CONTENT         = 'View::onFetchContent';
    private const TPL_NORMALIZE                 = ['../', '.tpl'];

    private $DBlocks 							= [];
    private $DVars 								= [];
    private $DBlockVars 					    = [];
    private $ParsedBlocks 						= [];

    private $cache                              = [];
    private $widget                             = null;
    private $lang				                = null;

    /**
     * @var null|string[strip|stripall]
     */
    public $minify								= null;

    private static $parentViewParsedBlocks      = [];
    private $parentParsedBlocks                 = [];

    public function __construct(string $widget = null, string $minify = null, array &$cache = null)
    {
        $this->widget                           = $widget;
        $this->cache                            =& $cache;
        $this->minify                           = $minify;

        $this->parentParsedBlocks               = self::$parentViewParsedBlocks;
        self::$parentViewParsedBlocks           = [];
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
     * @param string $content
     * @return ViewAdapter
     * @throws Exception
     */
    public function fetchContent(string $content) : ViewAdapter
    {
        if (empty($content)) {
            throw new Exception("template empty", 500);
        }

        $this->DBlocks[self::ROOT_BLOCK] = $this->getDVars($content);

        $this->setBlocks();

        Hook::handle(self::HOOK_ON_FETCH_CONTENT, $this);

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
        $tpl_name = Translator::infoLangCode($this->lang) . "-" . str_replace(
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
            if ($cache_file = Translator::infoCacheFile($this->lang)) {
                $this->cache[$cache_file] = filemtime($cache_file);
            }

            $this->DBlocks[self::ROOT_BLOCK] = $this->include($template_path);

            $this->setBlocks();

            $cache->set($tpl_name, array(
                "DBlocks"       => $this->DBlocks,
                "DVars"         => $this->DVars,
                "DBlockVars"    => $this->DBlockVars,
            ), $this->cache);
        } else {
            $this->DBlocks      = $res["DBlocks"];
            $this->DVars        = $res["DVars"];
            $this->DBlockVars   = $res["DBlockVars"];
        }

        Hook::handle(self::HOOK_ON_FETCH_CONTENT, $this);

        Debug::stopWatch("tpl/" . $tpl_name);
    }

    /**
     * @param string $content
     * @return string
     * @throws Exception
     */
    private function getDVars(string $content) : string
    {
        $content                                            = preg_replace(self::REGEXP_STRIP, '{$1}', $content);

        $matches                                            = null;
        $rc                                                 = preg_match_all(static::REGEXP, $content, $matches);
        if ($rc && $matches) {
            $DVars                                          = array_fill_keys($matches[1], []);

            $theme_disk_path                                = Kernel::$Environment::getThemeDiskPath();
            $views                                          = Resource::views($this->widget);
            $translation                                    = new stdClass();
            foreach ($DVars as $nName => $count) {
                if (substr($nName, 0, 1) == "_") {
                    $translation->key[]                     = "{" . $nName . "}";
                    $translation->value[]                   = Translator::getWordByCode(substr($nName, 1), $this->lang);
                    unset($DVars[$nName]);
                } elseif (substr($nName, 0, 7) == "include" && substr_count($nName, '"') == 2) {
                    $template_file                          =  explode('"', $nName)[1];
                    $template                               = $views[str_replace(self::TPL_NORMALIZE, '', $template_file)] ?? str_replace('$theme_path', $theme_disk_path, $template_file);

                    $view                                   = new self($this->widget, $this->minify, $this->cache);
                    $include                                = $view->include($template);
                    $this->DVars                            = array_replace($this->DVars, $view->DVars);
                    $this->DBlockVars                       = array_replace($this->DBlockVars, $view->DBlockVars);

                    $this->cache[$template]                 = filemtime($template);

                    $content                                = str_replace("{" . $nName . "}", $include, $content);
                } elseif (strpos($nName, "::")  !== false
                    || strpos($nName, "[")      !== false
                    || strpos($nName, "#")      !== false
                ) {
                    $DVars[$nName]                          = $this->getComponentParams($nName);
                }
            }
            if (isset($translation->key)) {
                $content = str_replace($translation->key, $translation->value, $content);
            }

            $this->DVars                                    = array_replace($this->DVars, $DVars);
        }

        return $content;
    }
    private function getComponentParams(string $nName) : array
    {
        $config = [];
        if (strpos($nName, "[") !== false) {
            $params = explode("[", $nName, 2);
            $config = array_replace($config, $this->convertJson($params[1]));
            $nName = $params[0];
        }
        if (strpos($nName, "#") !== false) {
            $params = explode("#", $nName, 2);
            $config = array_replace($config, $this->convertLimit($params[1]));
            $nName = $params[0];
        }
        if (strpos($nName, "::") !== false) {
            $params = explode("::", $nName, 2);
            $component = $params[0];
            $method = $params[1];
        } else {
            $component = $nName;
            $method = "get";
        }

        return [
            "component"     => $component,
            "method"        => $method,
            "config"        => $config,
            "data"          => strtolower(str_replace("Controller", "", $component))
        ];
    }

    /**
     * @param string $string
     * @return array
     */
    private function convertLimit(string $string) : array
    {
        if (strpos($string, ":") !== false) {
            $limit = explode(":", $string);
            $start = $limit[0];
            $end = $limit[1];
        } elseif (strpos($string, "+") !== false) {
            $limit = explode("+", $string);
            $start = $limit[0];
            $end = null;
        } else {
            $start = $string;
            $end = $string;
        }
        return [
            "limit" => [
                "start" => $start,
                "end" => $end
            ]
        ];
    }
    /**
     * @param string $string
     * @return array
     */
    private function convertJson(string $string) : ?array
    {
        return json_decode("{" . rtrim($string, "]") . "}", true) ?? [];
    }

    /**
     * @param string $sTemplateName
     * @return string
     */
    private function nextDBlockName(string $sTemplateName) : string
    {
        $sTemplate = $this->DBlocks[$sTemplateName];
        $BTag = strpos($sTemplate, "<!--" . self::BEGIN_TAG);
        if ($BTag === false) {
            return "";
        } else {
            $ETag = strpos($sTemplate, "-->", $BTag);
            $sName = substr($sTemplate, $BTag + 9, $ETag - ($BTag + 9));
            if (strpos($sTemplate, "<!--" . self::END_TAG . $sName . "-->") > 0) {
                return $sName;
            } else {
                return "";
            }
        }
    }

    /**
     * @param string $sBlockName
     * @param string|null $sTplName
     */
    private function setBlocks(string $sBlockName = self::ROOT_BLOCK, string $sTplName = null) : void
    {
        if (!empty($sTplName)) {
            if (!isset($this->DBlocks[$sBlockName])) {
                $this->DBlocks[$sBlockName] = $this->getBlock($this->DBlocks[$sTplName], $sBlockName);
            }
            $this->DBlocks[$sTplName] = $this->replaceBlock($this->DBlocks[$sTplName], $sBlockName);
        }

        $nName = $this->nextDBlockName($sBlockName);
        while ($nName != "") {
            $this->setBlocks($nName, $sBlockName);
            $this->blockVars($nName);
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

        $BBlock = strpos($sTemplate, "<!--" . self::BEGIN_TAG . $sName . "-->");
        $EBlock = strpos($sTemplate, "<!--" . self::END_TAG . $sName . "-->");

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
        $BBlock = strpos($sTemplate, "<!--" . self::BEGIN_TAG . $sName . "-->");
        $EBlock = strpos($sTemplate, "<!--" . self::END_TAG . $sName . "-->");

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
        } elseif (is_object($tpl_var)) {
            $this->ParsedBlocks             = array_replace($this->ParsedBlocks, (array) $tpl_var);
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

        return $this->getRootBlock();
    }

    /**
     * @param string $template_path
     * @return string
     * @throws Exception
     */
    private function include(string $template_path) : string
    {
        if (!($content = FilemanagerFs::fileGetContents($template_path))) {
            throw new Exception("Unable to find the template: " . $template_path, 500);
        }
        return $this->getDVars($content);
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getRootBlock() : string
    {
        $this->parse(self::ROOT_BLOCK);

        return $this->minify($this->ParsedBlocks[self::ROOT_BLOCK]);
    }

    /**
     * @param string $content
     * @return string
     */
    private function minify(string $content) : string
    {
        switch ($this->minify) {
            case "strip":
                $res = preg_replace("/\n\s*/", "\n", $content, -1, $count);
                break;
            case "stripall":
                $res = preg_replace(
                    array(
                        '/>[^\S ]+/s',
                        '/[^\S ]+</s',
                        '/(\s)+/s'
                    ),
                    array(
                        '>',
                        '<',
                        '\\1'
                    ),
                    $content
                );
                break;
            default:
                $res = $content;
        }

        return $this->entitiesReplace($res);
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
            $vars = array_unique($matches[1]);

            $this->DBlockVars[$sTplName] = $vars;

            return $vars;
        } else {
            return null;
        }
    }

    /**
     * @param string $sTplName
     * @param array $parsedBlocks
     * @param int|null $index
     * @param int|null $count
     * @return string
     * @throws Exception
     */
    private function proceedTpl(string $sTplName, array $parsedBlocks = [], int $index = null, int $count = null) : string
    {
        $vars = $this->blockVars($sTplName);
        $sTpl = $this->DBlocks[$sTplName];
        if (!empty($vars)) {
            $search_for = [];
            $replace_with = [];
            $pBlocks = $parsedBlocks ?: $this->ParsedBlocks;

            if (!empty($pBlocks["@attributes"]) && ($attr_index = array_search("attributes", $vars)) !== false) {
                unset($vars[$attr_index]);
            }

            foreach ($vars as $key) {
                $search_for[] = "{" . $key . "}";
                $replace_with[] = $this->getBlockValue($pBlocks, $key, $index, $count)
                    ?? $this->proceedTplBlockVars($key, $pBlocks)
                    ?? $this->cleanVars($pBlocks, $key);
            }

            if (isset($pBlocks["@attributes"])) {
                $queryString = "";
                $search_for[] = "{attributes}";
                foreach ($pBlocks["@attributes"] as $key => $value) {
                    if (is_array($value)) {
                        continue;
                    }
                    $queryString .= ' ' . $key . '="' . $value . '"';
                }
                $replace_with[] = $queryString;
            }

            $sTpl = str_replace($search_for, $replace_with, $sTpl);
        }
        return $sTpl;
    }

    /**
     * @param array $parsedBlocks
     * @param string $key
     * @return string
     */
    private function cleanVars(array &$parsedBlocks, string $key) : string
    {
        foreach ($this->DBlockVars[$key] ?? [] as $var) {
            if (isset($parsedBlocks["@attributes"][strtolower($var)])) {
                unset($parsedBlocks["@attributes"][strtolower($var)]);
            }
        }

        return "";
    }

    /**
     * @param array $parsedBlocks
     * @param string $key
     * @param int|null $index
     * @param int|null $count
     * @return string|null
     * @throws Exception
     */
    private function getBlockValue(array &$parsedBlocks, string $key, int $index = null, int $count = null) : ?string
    {
        $json = null;
        $blockValue = null;
        if (isset($this->DVars[$key]["component"])) {
            if ($controller = (Resource::components()[$this->DVars[$key]["component"]] ?? null)) {
                /**
                 * set parentParsedBlocks for next Children View and Run Controller
                 */
                self::$parentViewParsedBlocks = $this->parentParsedBlocks;
                return (new $controller($this->DVars[$key]["config"]))
                    ->assign($parsedBlocks[$this->DVars[$key]["data"]] ?? [])
                    ->html($this->DVars[$key]["method"]);
            } else {
                /**
                 * Extract json data for replace or for config of Controller
                 */
                $json = $this->DVars[$key]["config"];
                $key = $this->DVars[$key]["component"];
            }
        } elseif (strpos($key, "+") !== false) {
            /**
             * Add always attribute in full Attributes es: {Class+myClass}
             */
            $arrKey = explode("+", $key, 2);
            $key = $arrKey[0];
            $parsedBlocks["@attributes"][strtolower($key)] = trim($arrKey[1] . " " . ($parsedBlocks["@attributes"][strtolower($key)] ?? ""));
        }

        if (isset($parsedBlocks[$key])) {
            $blockValue = $parsedBlocks[$key];
        } elseif (isset($parsedBlocks["@attributes"][$key])) {
            $blockValue = $parsedBlocks["@attributes"][$key];
            unset($parsedBlocks["@attributes"][$key]);
        } elseif (isset($this->parentParsedBlocks[$key])) {
            $blockValue = $this->parentParsedBlocks[$key];
        } elseif (($key_lower = strtolower($key)) && isset($parsedBlocks["@attributes"][$key_lower])) {
            if (isset($this->DBlocks[$key])) {
                /**
                 * Process specific attribute es: {class}
                 */
                if (!empty($parsedBlocks["@attributes"][$key_lower])) {
                    $blockValue = $this->proceedTpl($key, $parsedBlocks);
                }
                $this->cleanVars($parsedBlocks, $key);
            } else {
                /**
                 * Process full attributes es: {Class}
                 */
                $blockValue = $key_lower . '="' . ($json[$parsedBlocks["@attributes"][$key_lower]] ?? $parsedBlocks["@attributes"][$key_lower]) . '" ';
                unset($parsedBlocks["@attributes"][$key_lower]);
            }
        } elseif ($controller = (Resource::components()[$key] ?? null)) {
            self::$parentViewParsedBlocks = $this->parentParsedBlocks;
            $blockValue = (new $controller())
                ->assign($parsedBlocks[strtolower(str_replace("Controller", "", $key))] ?? [])
                ->html();
        }

        if (is_object($blockValue)) {
            if ($blockValue instanceof View || $blockValue instanceof Controller) {
                return $blockValue->html();
            } else {
                throw new Exception("bad value into template", 500);
            }
        } elseif (is_array($blockValue)) {
            return $this->proceedTplBlockVars($key, [$key => $blockValue]);
        }

        return $json[$blockValue]
            ?? $blockValue
            ?? $this->getBlockAction($parsedBlocks, $key, $index, $count);
    }

    /**
     * @param array $parsedBlocks
     * @param string $key
     * @param int|null $index
     * @param int|null $count
     * @return string|null
     */
    private function getBlockAction(array $parsedBlocks, string $key, int $index = null, int $count = null) : ?string
    {
        if ($key == "index") {
            return $index;
        } elseif ($key == "count") {
            return $count;
        } elseif ($key == "oddeven") {
            return $index % 2 === 0 ? "even" : "odd";
        } elseif (($index === 1 && strpos($key, "first=") === 0)
            || ($index === $count && strpos($key, "last=") === 0)
            || ($index % 2 !== 0 && strpos($key, "odd=") === 0)
            || ($index % 2 === 0 && strpos($key, "even=") === 0)
            || strpos($key, "index:" . $index . "=") === 0
        ) {
            return explode("=", $key, 2)[1];
        } elseif (count($keys = explode(".", $key)) > 1) {
            return $this->flatCall($parsedBlocks, $keys);
        }

        return null;
    }

    /**
     * @param array $data_arr
     * @param array $data_arr_call
     * @return string|null
     */
    public function flatCall(array $data_arr, array $data_arr_call) : ?string
    {
        foreach ($data_arr_call as $key) {
            if (!isset($data_arr[$key])) {
                if ($key == "first" && isset($data_arr[0])) {
                    $key = 0;
                } elseif ($key == "last" && $data_arr[count($data_arr) - 1]) {
                    $key = count($data_arr) - 1;
                } elseif ($key == "count") {
                    return count($data_arr);
                } else {
                    return null;
                }
            }
            $data_arr = $data_arr[$key];
        }

        return $data_arr;
    }


    /**
     * @param string $var
     * @param $parsedBlocks
     * @return string|null
     * @throws Exception
     */
    private function proceedTplBlockVars(string $var, $parsedBlocks) : ?string
    {
        $replace = null;
        if (isset($this->DBlockVars[$var])) {
            $params = explode("#", $var);
            $key = strtolower($params[0]);
            if (isset($parsedBlocks[$key])) {
                if (!is_array($parsedBlocks[$key])) {
                    $replace = $this->proceedTplBlockDisplay($params, $var, [$key => $parsedBlocks[$key]]);
                } elseif (isset($parsedBlocks[$key][0])) {
                    $count = count($parsedBlocks[$key]);
                    foreach ($parsedBlocks[$key] as $index => $parsedBlock) {
                        if (!is_int($index)) {
                            continue;
                        }
                        //set parentParsedBlocks for children
                        $this->parentParsedBlocks = array_replace($this->parentParsedBlocks, $parsedBlock);
                        $replace .= $this->proceedTplBlockDisplay($params, $var, $parsedBlock, $index + 1, $count);
                    }
                } else {
                    $replace = $this->proceedTplBlockDisplay($params, $var, $parsedBlocks[$key]);
                }
            }
        }
        return $replace;
    }

    /**
     * @param array $params
     * @param string $var
     * @param array $parsedBlock
     * @param int|null $index
     * @param int|null $count
     * @return string
     * @throws Exception
     */
    private function proceedTplBlockDisplay(array $params, string $var, array $parsedBlock, int $index = null, int $count = null) : string
    {
        return isset($params[1]) && isset($parsedBlock["@attributes"]) && empty($parsedBlock["@attributes"][$params[1]])
            ? ""
            : $this->proceedTpl($var, $parsedBlock, $index, $count);
    }
    
    /**
     *
     */
    private function setAssignDefault() : void
    {
        $this->assign("site_path", Kernel::$Environment::SITE_PATH);
    }

    /**
     * @param string|null $lang_code
     * @return ViewHtml
     * @throws Exception
     */
    public function setLang(string $lang_code = null) : ViewAdapter
    {
        if ($lang_code && !Locale::isAcceptedLanguage($lang_code)) {
            throw new Exception(self::ERROR_LANG_NOT_VALID, 400);
        }
        $this->lang                        = $lang_code;

        return $this;
    }
}
