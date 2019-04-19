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

class Gridsystem
{
    const TYPE                                              = "gridsystem";

    private static $fonticon                                = null;
    private static $framework                               = null;
    private static $buttons                                 = null;
    private static $components                              = null;

    private static $singleton                               = null;


    public static function colsWrapper($bucket, &$buffer, $col, $content, $count) {
        static $cache = null;

        if(!$cache[$bucket]) {
            $cache[$bucket]         = array(
                "col"               => null
                //, "is_first"        => true
                , "is_wrapped"      => false
                , "count_contents"  => 0
                , "wrapper_count"   => 0
                , "wrapper"         => (strpos($bucket, "row-") === 0
                    ? self::getInstance()->get("", "row")
                    : self::getInstance()->get("wrap", "form")
                )
            );
        }

        $cache[$bucket]["count_contents"]++;
        if($content) {
            if ($col) {
                //$cache[$bucket]["is_wrapped"] = false;
                $cache[$bucket]["col"] = $cache[$bucket]["col"] + $col;
                if ($cache[$bucket]["col"] > 12) {
                    $buffer[] = '</div>';
                    $buffer[] = '<div class="' . $cache[$bucket]["wrapper"] . '">';
                    $cache[$bucket]["is_wrapped"] = true;
                    $cache[$bucket]["wrapper_count"]++;
                    $cache[$bucket]["col"] = 0;
                } elseif (!$cache[$bucket]["wrapper_count"] && $cache[$bucket]["col"] == $col) { //first
                    $buffer[] = '<div class="' . $cache[$bucket]["wrapper"] . '">';
                    $cache[$bucket]["is_wrapped"] = true;
                    //$cache[$bucket]["is_first"] = false;
                } elseif ($cache[$bucket]["col"] == 12 && $cache[$bucket]["wrapper_count"] && !$cache[$bucket]["is_wrapped"]) {
                    $buffer[] = '</div>';
                    $buffer[] = '<div class="' . $cache[$bucket]["wrapper"] . '">';
                    $cache[$bucket]["is_wrapped"] = true;
                    $cache[$bucket]["wrapper_count"]++;
                    $cache[$bucket]["col"] = 0;
                } elseif ($cache[$bucket]["col"]/* && $cache[$bucket]["wrapper_count"]*/) {
                    if(!$cache[$bucket]["is_wrapped"]) {
                        $buffer[] = '<div class="' . $cache[$bucket]["wrapper"] . '">';
                        $cache[$bucket]["is_wrapped"] = true;
                    }
                    //$cache[$bucket]["is_wrapped"] = true;
                }

                $buffer[] = $content;
                if ($cache[$bucket]["is_wrapped"] && $cache[$bucket]["count_contents"] == $count) {
                    $buffer[] = '</div>';
                    $cache[$bucket]["is_wrapped"] = false;
                    //$cache[$bucket]["wrapper_count"]++;
                    $cache[$bucket]["col"] = 0;
                }
            } else {
                if ($cache[$bucket]["col"] > 0 || $cache[$bucket]["is_wrapped"]) {
                    $buffer[] = '</div>';
                    $cache[$bucket]["is_wrapped"] = false;
                    $cache[$bucket]["wrapper_count"]++;
                    $cache[$bucket]["col"] = 0;
                }
                $buffer[] = $content;
            }
        }
    }


    public static function setResolution($resolution, $rev = false)
    {
        $res = null;
        if($resolution)
        {
            if(is_array($resolution)) {
                $num = 0;
                foreach($resolution AS $index => $num) {
                    $res[self::$framework["resolution"][$index]] = ($rev ? 12 - $num : $num);
                }
                if(count(self::$framework["resolution"]) > count($res)) {
                    for($i = count($res) + 1; $i <= count(self::$framework["resolution"]); $i++) {
                        $res[self::$framework["resolution"][$i]] = ($rev ? 12 - $num : $num);
                    }
                }
            } else {
                $res = array_combine(self::$framework["resolution"], array_fill(0, count(self::$framework["resolution"]), ($rev ? 12 - $resolution : $resolution)));
            }
        }

        return $res;
    }

    public static function extend($data, $what) {
        if(is_array($data) && $what) {
            switch ($what) {
                case "framework":
                    self::extendFramework($data);
                    return self::$framework;
                case "fonticon":
                    self::extendFontIcon($data);
                    return self::$fonticon;
                case "buttons":
                    self::extendButtons($data);
                    return self::$buttons;
                default:
                    self::extendComponents($data, $what);
                    return self::$components[$what];
            }
        }

        return null;
    }

    public static function &findComponent($name) {
        $ref = null;
        $arrName = explode(".", $name);
        if($arrName[0]) {
            if (self::$components["override"][$arrName[0]] && self::$components[$arrName[0]]) {
                self::$components[$arrName[0]] = array_replace_recursive(self::$components[$arrName[0]], self::$components["override"][$arrName[0]]);
                self::$components["override"][$arrName[0]] = null;
            }

            $ref =& self::$components;
            foreach ($arrName as $item) {
                if (isset($ref[$item])) {
                    $ref =& $ref[$item];
                } else {
                    $ref = null;
                    break;
                }
            }
        }

        return $ref;
    }

    public static function setComponent($name, $data) {
        $ref =& self::findComponent($name);

        $ref = $data;

        return $ref;
    }

    private static function extendFramework($framework) {
        self::$framework = array_replace_recursive(self::$framework, $framework);
    }
    private static function extendFontIcon($fonticon) {
        self::$fonticon = array_replace_recursive(self::$fonticon, $fonticon);
    }
    private static function extendButtons($buttons) {
        self::$buttons = array_replace_recursive(self::$buttons, $buttons);
    }
    private static function extendComponents($component, $key) {
        self::$components[$key] = array_replace_recursive((array) self::$components[$key], $component);
    }
    private static function getIcon($value, $type, $params, $addClass, $font_icon, $skip_default = false) {
        if(is_array($font_icon)) {
            $res = array();
            $arrFFButton = array();
            $skip_fix = false;

            if(!$skip_default)
                $arrFFButton = self::getButtons();

            if(!strlen($value)) {
                $arrName = $params;
                $skip_fix = true;
            } elseif(is_array($params)) {
                $arrName = array_merge(explode(" ", $value), $params);
            } else {
                $arrName = explode(" ", $value);
            }

            foreach($arrName AS $single_value) {
                if(strlen($single_value)) {
                    if(!$skip_default && isset($arrFFButton[$single_value])) {
                        if(strpos($type, "-default") !== false)
                            continue;

                        $res[$font_icon["prepend"] . $arrFFButton[$single_value]["icon"] . $font_icon["append"]] = true;

                        if($type == "icon" && strlen($arrFFButton[$single_value]["addClass"])) {
                            $res[$arrFFButton[$single_value]["addClass"]] = true;
                        }
                    } else {
                        $res[$font_icon["prepend"] . $single_value . $font_icon["append"]] = true;
                    }

                }
            }
            if(is_array($addClass) && count($addClass)) {
                foreach($addClass AS $addClass_value) {
                    $res[$addClass_value] = true;
                }
            }
            if(!$skip_fix && count($res)) {
                if(strlen($font_icon["prefix"])) {
                    $res[$font_icon["prefix"]] = true;
                    //$res = array_merge(array($font_icon["prefix"] => true), $res);
                }
                if(strlen($font_icon["postfix"])) {
                    $res[$font_icon["postfix"]] = true;
                }
            }

            $res = implode(" ", array_keys($res));
        } else {
            $res = $value;
        }

        if(strlen($res) && strpos($type, "-tag") !== false)
            $res = '<i class="' . $res . '"></i>';

        return $res;
    }
    private static function buttons() {
        $arrFFButton = array(
            //ffRecord
            "ActionButtonInsert"     => array(
                                            "default" => "success"
                                            , "addClass" => ""
                                            , "icon" => "check"
                                            , "class" => "insert"
                                        )
            , "ActionButtonUpdate"    => array(
                                            "default" => "success"
                                            , "addClass" => ""
                                            , "icon" => "check"
                                            , "class" => "update"
                                        )
            , "ActionButtonDelete"    => array(
                                            "default" => "danger"
                                            , "addClass" => ""
                                            , "icon" => "trash-o"
                                            , "class" => "delete"
                                        )
            , "ActionButtonCancel"    => array(
                                            "default" => "link"
                                            , "addClass" => ""
                                            , "icon" => ""
                                            , "class" => "cancel"
                                        )
            , "insert"         => array(
                                    "default" => "success"
                                    , "addClass" => "activebuttons"
                                    , "icon" => "check"
                                )
            , "update"         => array(
                                    "default" => "success"
                                    , "addClass" => "activebuttons"
                                    , "icon" => "check"
                                )
            , "delete"         => array(
                                    "default" => "danger"
                                    , "addClass" => "activebuttons"
                                    , "icon" => "trash-o"
                                )
            , "cancel"         => array(
                                    "default" => "link"
                                    , "addClass" => ""
                                    , "icon" => "times"
                                )
            , "print"         => array(
                                    "default" => "default"
                                    , "addClass" => "print"
                                    , "icon" => "print"
                                )
            //ffGrid
            , "search"         => array(
                                    "default" => "primary"
                                    , "addClass" => "search"
                                    , "icon" => "search"
                                )
            , "searchadv"         => array(
                                    "default" => "primary"
                                    , "addClass" => "search"
                                    , "icon" => "search"
                                )
            , "searchdropdown"     => array(
                                    "default" => "secondary"
                                    , "addClass" => "more dropdown-toggle"
                                )
            , "more"         => array(
                                    "default" => "link"
                                    , "addClass" => "more"
                                    , "icon" => "caret-down"
                                )
            , "export"         => array(
                                    "default" => "default"
                                    , "addClass" => "export"
                                    , "icon" => "download"
                                )
            , "sort"    => array(
                                    "default" => "link"
                                    , "addClass" => "sort"
                                    , "icon" => "sort"
                                )
            , "sort-asc"    => array(
                                    "default" => "link"
                                    , "addClass" => "sort asc"
                                    , "icon" => "sort-asc"
                                )
            , "sort-desc"   => array(
                                    "default" => "link"
                                    , "addClass" => "sort desc"
                                    , "icon" => "sort-desc"
                                )
            , "addnew"        => array(
                                    "default" => "primary"
                                    , "addClass" => "addnew"
                                    , "icon" => "plus"
                                )
            , "editrow"     => array(
                                    "default" => "link"
                                    , "addClass" => "edit"
                                    , "icon" => "pencil"
                                )
            , "deleterow"    => array(
                                    "default" => "danger"
                                    , "addClass" => "delete"
                                    , "icon" => "trash-o"
                                )
            , "deletetabrow"    => array(
                                    "default" => null
                                    , "addClass" => "delete"
                                    , "icon" => "trash-o"
                                )
            //ffDetail
            , "addrow"         => array(
                                    "default" => "primary"
                                    , "addClass" => ""
                                    , "icon" => "plus"
                                )
            //ffPageNavigator
            , "first"         => array(
                                    "default" => "link"
                                    , "addClass" => "first"
                                    , "icon" => "step-backward"
                                )
            , "last"         => array(
                                    "default" => "link"
                                    , "addClass" => "last"
                                    , "icon" => "step-forward"
                                )
            , "prev"         => array(
                                    "default" => "link"
                                    , "addClass" => "prev"
                                    , "icon" => "play"
                                    , "params" => "flip-horizontal"
                                )
            , "next"         => array(
                                    "default" => "link"
                                    , "addClass" => "next"
                                    , "icon" => "play"
                                )
            , "prev-frame"   => array(
                                    "default" => "link"
                                    , "addClass" => "prev-frame"
                                    , "icon" => "backward"
                                )
            , "next-frame"   => array(
                                    "default" => "link"
                                    , "addClass" => "next-frame"
                                    , "icon" => "forward"
                                )

           //other
            , "pdf"          => array(
                                    "default" => "link"
                                    , "addClass" => "pdf"
                                    , "icon" => "file-pdf-o"
                                )
            , "email"        => array(
                                    "default" => "link"
                                    , "addClass" => "email"
                                    , "icon" => "envelope-o"
                                )
            , "preview"      => array(
                                    "default" => "link"
                                    , "addClass" => "preview"
                                    , "icon" => "search"
                                )
            , "preview-email"=> array(
                                    "default" => "link"
                                    , "addClass" => "email"
                                    , "icon" => "envelope-o"
                                    , "params" => ""
                                )
            , "refresh"		=> array(
                                    "default" => "link"
                                    , "addClass" => "refresh"
                                    , "icon" => "refresh"
                                )
            , "clone"        => array(
                                    "default" => "link"
                                    , "addClass" => "clone"
                                    , "icon" => "copy"
                                )
            , "permissions"        => array(
                                    "default" => "link"
                                    , "addClass" => "permissions"
                                    , "icon" => "lock"
                                )
            , "relationships"        => array(
                                    "default" => "link"
                                    , "addClass" => "relationships"
                                    , "icon" => "share-alt"
                                )
            , "settings"        => array(
                                    "default" => "link"
                                    , "addClass" => "settings"
                                    , "icon" => "cog"
                                )
            , "properties"      => array(
                                    "default" => "link"
                                    , "addClass" => "properties"
                                    , "icon" => "object-group"
                                )
            , "help"            => array(
                                    "default" => "link"
                                    , "addClass" => "helper"
                                    , "icon" => "question-circle"
                                )
            , "noimg"           => array(
                                    "default" => "link"
                                    , "addClass" => "noimg"
                                    , "icon" => "picture-o"
                                )
            , "checked"        	=> array(
                                    "default" => "link"
                                    , "addClass" => "checked"
                                    , "icon" => "check-circle-o"
                                )
            , "unchecked"       => array(
                                    "default" => "link"
                                    , "addClass" => "unchecked"
                                    , "icon" => "circle-o"
                                )
            , "exanded"           => array(
                                    "default" => "link"
                                    , "addClass" => "exanded"
                                    , "icon" => "minus-square-o"
                                )
            , "retracted"           => array(
                                    "default" => "link"
                                    , "addClass" => "retracted"
                                    , "icon" => "plus-square-o"
                                )


            //CMS Ecommerce
            , "history"      => array(
                                    "default" => "link"
                                    , "addClass" => "history"
                                    , "icon" => "history"
                                )
            , "payments"     => array(
                                    "default" => "link"
                                    , "addClass" => "payments"
                                    , "icon" => "credit-card"
                                )
            //CMS
            , "vg-admin"     => array(
                                    "default" => "link"
                                    , "addClass" => "admin"
                                    , "icon" => "cog"
                                    , "params" => "2x"
                                )
            , "vg-restricted"=> array(
                                    "default" => "link"
                                    , "addClass" => "restricted"
                                    , "icon" => "unlock-alt"
                                    , "params" => "2x"
                                )
            , "vg-manage"    => array(
                                    "default" => "link"
                                    , "addClass" => "manage"
                                    , "icon" => "shopping-cart"
                                    , "params" => "2x"
                                )
            , "vg-fontend"   => array(
                                    "default" => "link"
                                    , "addClass" => "fontend"
                                    , "icon" => "desktop"
                                    , "params" => "2x"
                                )
            , "vg-static-menu"    => array(
                                    "default" => "link"
                                    , "addClass" => "static-menu"
                                    , "icon" => "static-menu"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-gallery-menu"    => array(
                                    "default" => "link"
                                    , "addClass" => "gallery-menu"
                                    , "icon" => "gallery-menu"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-vgallery-menu"    => array(
                                    "default" => "link"
                                    , "addClass" => "vgallery-menu"
                                    , "icon" => "vgallery-menu"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-vgallery-group"    => array(
                                    "default" => "link"
                                    , "addClass" => "vgallery-group"
                                    , "icon" => "vgallery-group"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-gallery"    => array(
                                    "default" => "link"
                                    , "addClass" => "gallery"
                                    , "icon" => "gallery"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-draft"    => array(
                                    "default" => "link"
                                    , "addClass" => "draft"
                                    , "icon" => "draft"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-file"    => array(
                                    "default" => "link"
                                    , "addClass" => "file"
                                    , "icon" => "file"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-virtual-gallery"    => array(
                                    "default" => "link"
                                    , "addClass" => "virtual-gallery"
                                    , "icon" => "virtual-gallery"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-publishing"    => array(
                                    "default" => "link"
                                    , "addClass" => "publishing"
                                    , "icon" => "publishing"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-vgallery-rel"    => array(
                                    "default" => "link"
                                    , "addClass" => "vgallery-rel"
                                    , "icon" => "vgallery-rel"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-cart"    => array(
                                    "default" => "link"
                                    , "addClass" => "cart"
                                    , "icon" => "cart"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-lang"    => array(
                                    "default" => "link"
                                    , "addClass" => "lang"
                                    , "icon" => "lang"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-search"    => array(
                                    "default" => "link"
                                    , "addClass" => "search"
                                    , "icon" => "search"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-login"    => array(
                                    "default" => "link"
                                    , "addClass" => "login"
                                    , "icon" => "login"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-breadcrumb"    => array(
                                    "default" => "link"
                                    , "addClass" => "breadcrumb"
                                    , "icon" => "breadcrumb"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-profile"    => array(
                                    "default" => "link"
                                    , "addClass" => "profile"
                                    , "icon" => "profile"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-modules"    => array(
                                    "default" => "link"
                                    , "addClass" => "module"
                                    , "icon" => "module"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "vg-applets"    => array(
                                    "default" => "link"
                                    , "addClass" => "applets"
                                    , "icon" => "applets"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "lay-addnew"    => array(
                                    "default" => "link"
                                    , "addClass" => "lay-addnew"
                                    , "icon" => "lay-addnew"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "lay"    => array(
                                    "default" => "link"
                                    , "addClass" => "lay-unknown"
                                    , "icon" => "lay"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "lay-31"    => array(
                                    "default" => "link"
                                    , "addClass" => "lay-top"
                                    , "icon" => "lay-31"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "lay-13"    => array(
                                    "default" => "link"
                                    , "addClass" => "lay-left"
                                    , "icon" => "lay-13"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "lay-3133"    => array(
                                    "default" => "link"
                                    , "addClass" => "lay-right"
                                    , "icon" => "lay-3133"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "lay-1333"    => array(
                                    "default" => "link"
                                    , "addClass" => "lay-right"
                                    , "icon" => "lay-1333"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "lay-2233"    => array(
                                    "default" => "link"
                                    , "addClass" => "lay-content"
                                    , "icon" => "lay-2233"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "js"    => array(
                                    "default" => "link"
                                    , "icon" => "js"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "css"    => array(
                                    "default" => "link"
                                     , "icon" => "css"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
            , "seo"    => array(
                                    "default" => "link"
                                     , "icon" => "seo"
                                    , "font_icon" => array("prepend" => "vg-", "prefix" => "vg")
                                )
        );
        return $arrFFButton;
    }

    private static function getButtons() {
        if(!self::$buttons) {
            self::$buttons = self::buttons();
        }

        return self::$buttons;
    }

    public function __construct()
    {
        //if(!self::$config) {
           // $fs                                             = Filemanager::getInstance("xml");
           // self::$config                                   = $fs->read(dirname(__DIR__) . "/conf/frameworkcss.xml");
        //}
    }

    /**
     * @return Gridsystem
     */
    public static function getInstance() {
        return self::$singleton;
    }
    public static function factory($framework, $fonticon) {
        self::getFramework($framework);
        self::getFontIcon($fonticon);
        self::getButtons();

        self::$singleton = new Gridsystem();

        return self::$singleton;
    }
    public function get($value, $type, $params = array(), $framework_css = null, $font_icon = null) {
        $res = "";
        if($value === false)
            return $res;

        if($framework_css === null) {
            $framework_css = $this->getFramework();
        }
        if($font_icon === null) {
            $font_icon = $this->getFontIcon();
        }

        if(!is_array($font_icon)) {
            $font_icon = array();
        }
        if($params && !is_array($params))
            $params = array($params);

        if(!is_array($params))
            $params = array();

        switch($type) {
            case "button":
            case "link":
                $is_strict = false;
                $arrButton = array();
                if(is_array($value) && !count($params)) {
                    $params = $value;
                    $value = false;
                }


                if(is_array($params) && (array_key_exists("strict", $params) || array_search("strict", $params) !== false)) {
                    if(array_key_exists("strict", $params))
                        $is_strict = true;
                    else
                        $is_strict = array_search("strict", $params);

                    if($is_strict !== false ) {
                        if($is_strict === true)
                            unset($params["strict"]);
                        else
                            unset($params[$is_strict]);
                    }
                }

                $arrFFButton = $this->getButtons();
                $skip_default = false;
                $res = array();
                if(!$is_strict) {
                    $arrButton = $framework_css["button"]["color"];
                    $skip_default = $framework_css["button"]["skip-default"];
                    if($framework_css["button"]["base"]) {
                        $res[$framework_css["button"]["base"]] = true;
                    }
                }

                if(array_key_exists($value, $arrFFButton)) {
                    if(is_array($arrButton) && strlen($arrButton[$arrFFButton[$value]["default"]]))
                        $res[$arrButton[$arrFFButton[$value]["default"]]] = true;
                    if(strlen($arrFFButton[$value]["addClass"]))
                        $res[$arrFFButton[$value]["addClass"]] = true;
                    if(isset($arrFFButton[$value]["params"])) {
                        if(is_array($arrFFButton[$value]["params"]))
                            $params = array_merge($params, $arrFFButton[$value]["params"]);
                        else
                            $params = array_merge($params, array($arrFFButton[$value]["params"]));
                    }
                } elseif($type == "button" && isset($arrButton[$value])) {
                    if($arrButton[$value]) $res[$arrButton[$value]] = true;
                } elseif($type == "button" && isset($arrButton["default"])) {
                    if($arrButton["default"]) $res[$arrButton["default"]] = true;
                } elseif($type == "link" && isset($arrButton["link"])) {
                    if($arrButton["link"]) $res[$arrButton["link"]] = true;
                }

                if(is_array($params) && count($params)) {
                    foreach($params AS $params_key => $params_value) {
                        if($framework_css["button"][$params_key][$params_value]) {
                            $res[$framework_css["button"][$params_key][$params_value]] = true;
                        } elseif(is_array($font_icon)) {
                            switch($params_value) {
                                case "stack":
                                case "stack-equal":
                                    switch($font_icon["name"]) {
                                        case "base":
                                            $res["icon-stack"] = true;
                                            break;
                                        case "fontawesome":
                                            $res["fa-stack"] = true;
                                            break;
                                        case "glyphicons":
                                                $res["icon-stack"] = true;
                                            break;
                                        default:
                                            $res[$params_value] = true;
                                    }
                                    break;
                                default:
                                    $res[$params_value] = true;
                            }
                        } else {
                            $res[$params_value] = true;
                        }
                    }
                }
                //print_r($params);
                if(!$skip_default && !$is_strict)
                    if(isset($arrFFButton[$value]["class"]))
                        $res[$arrFFButton[$value]["class"]] = true;
                    else
                        $res[$value] = true;

                $res = implode(" ", array_keys($res));
                break;
            case "icon-tag":
            case "icon-link-tag":
            case "icon-link-tag-default":
                if(substr($value, "0", 1) === "&") {
                    return $value;
                }
            case "icon":
            case "icon-default":
    /* Params:
        stack
        stack-equal
        rotate-90
        rotate-180
        rotate-270
        flip-horizontal
        flip-vertical
        transparent
        inverse
        spin
        pull-left
        pull-right
        border
        lg
        2x
        3x
        4x
        5x

    */

                $addClass = null;
                $is_stack = false;
                $is_stack_equal = false;
                $default_loaded = false;
                if(!is_array($value) && strlen($value)) {
                    $arrFFButton = $this->getButtons();
                    if(isset($arrFFButton[$value])) {
                        if(isset($arrFFButton[$value]["params"])) {
                            if(is_array($arrFFButton[$value]["params"]))
                                $params = array_merge($params, $arrFFButton[$value]["params"]);
                            else
                                $params = array_merge($params, array($arrFFButton[$value]["params"]));
                        }

                        if($type == "icon" && strlen($arrFFButton[$value]["addClass"])) {
                            $addClass[] = $arrFFButton[$value]["addClass"];
                        }

                        if(is_array($arrFFButton[$value]["font_icon"])) {
                            $font_icon = array_replace_recursive($font_icon, $arrFFButton[$value]["font_icon"]);
                        }
                        $value = $arrFFButton[$value]["icon"];
                        $default_loaded = true;
                    }
                }

                if(is_array($params)) {
                    $is_stack = array_search("stack", $params) !== false;
                    $is_stack_equal = array_search("stack-equal", $params) !== false;
                    if(isset($params["class"])) {
                        $addClass[] = $params["class"];
                        unset($params["class"]);
                    }
                }

                if($value === null && is_array($params)) {
                    $real_params = $params;

                    switch($font_icon["name"]) {
                        case "base":
                            if($is_stack_equal !== false)
                                $real_params[$is_stack_equal] = "stack";
                            break;
                        case "fontawesome":
                            if($is_stack_equal !== false)
                                $real_params[$is_stack_equal] = "stack";
                            break;
                        case "glyphicons":
                            if($is_stack_equal !== false)
                                $real_params[$is_stack_equal] = "stack";
                            break;
                        default:
                    }

                    $res = $this->getIcon(null, $type, $real_params, $addClass, $font_icon, $default_loaded);
                } elseif(is_array($value)) {
                    foreach($value AS $count_value => $real_value) {
                        $real_params = $params;

                        switch($font_icon["name"]) {
                            case "base":
                                if($is_stack !== false)
                                    $real_params[$is_stack] = "stack-" . ($count_value + 1) . "x";
                                elseif($is_stack_equal !== false)
                                    $real_params[$is_stack_equal] = "stack-2x";
                                break;
                            case "fontawesome":
                                if($is_stack !== false)
                                    $real_params[$is_stack] = "stack-" . ($count_value + 1) . "x";
                                elseif($is_stack_equal !== false)
                                    $real_params[$is_stack_equal] = "stack-2x";
                                break;
                            case "glyphicons":
                                if($is_stack !== false && !$count_value)
                                    $real_params[$is_stack] = "icon-stack-base";
                                elseif($is_stack_equal !== false)
                                    $real_params[$is_stack_equal] = "icon-stack-base";
                                break;
                            default:
                        }

                        $res[] = $this->getIcon($real_value, "icon-tag", $real_params, $addClass, $font_icon, $default_loaded);
                    }
                } else {
                    $res = $this->getIcon($value, $type, $params, $addClass, $font_icon, $default_loaded);
                }

                break;
            case "icon-button-tag":
            case "icon-button-tag-default":
                break;
            case "col-default":
            case "col":
            case "col-fluid":
                $is_fluid = null;
                if($type == "col-fluid" && !$framework_css["is_fluid"])
                    $is_fluid = true;
                else if($type == "col" && $framework_css["is_fluid"])
                    $is_fluid = false;

                $res = $this->getCol($value, $is_fluid, $params, $framework_css);
                if(isset($params["class"]) && is_array($params["class"]) && count($params["class"]))
                    $res .= " " . implode(" ", $params["class"]);
                if(count($params) && array_key_exists("0", $params) && strlen($params[0]))
                    $res .= " " . $params[0];

                break;
            case "wrap-default":
            case "wrap":
            case "wrap-fluid":
                $framework_css_settings = $this->frameworks($framework_css["name"]);

                if(is_array($params) && count($params))
                    $res = array_fill_keys($params, true);
                else
                    $res = array();

                if($value)
                    $res[$value] = true;

                if(is_array($framework_css)) {
                    if($type == "wrap-default")
                        $res[$framework_css_settings["class" . ($framework_css["is_fluid"] ? "-fluid" : "")]["wrap"]] = true;
                    else if($type == "wrap-fluid")
                        $res[$framework_css_settings["class-fluid"]["wrap"]] = true;
                    else if($type == "wrap")
                        $res[$framework_css_settings["class"]["wrap"]] = true;
                } else {
                    $res["wrap"] = true;
                }

                $res = implode(" ", array_keys($res));
                break;
            case "push-default":
            case "push":
            case "push-fluid":
                $is_fluid = null;
                if($type == "push-fluid" && !$framework_css["is_fluid"])
                    $is_fluid = true;
                else if($type == "push" && $framework_css["is_fluid"])
                    $is_fluid = false;

                $res = $this->getCol($value, $is_fluid, $params, $framework_css, "push");
                if(isset($params["class"]) && is_array($params["class"]) && count($params["class"]))
                    $res .= " " . implode(" ", $params["class"]);
                if(count($params) && array_key_exists("0", $params) && strlen($params[0]))
                    $res .= " " . $params[0];

                break;
            case "pull-default":
            case "pull":
            case "pull-fluid":
                $is_fluid = null;
                if($type == "pull-fluid" && !$framework_css["is_fluid"])
                    $is_fluid = true;
                else if($type == "pull" && $framework_css["is_fluid"])
                    $is_fluid = false;

                $res = $this->getCol($value, $is_fluid, $params, $framework_css, "pull");
                if(isset($params["class"]) && is_array($params["class"]) && count($params["class"]))
                    $res .= " " . implode(" ", $params["class"]);
                if(count($params) && array_key_exists("0", $params) && strlen($params[0]))
                    $res .= " " . $params[0];

                break;
            case "row-default":
            case "row":
            case "row-fluid":
                $framework_css_settings = $this->frameworks($framework_css["name"]);

                if(is_array($params) && count($params))
                    $res = array_fill_keys($params, true);
                else
                    $res = array();

                if($value === true) {
                    $type = "row-default";
                    $value = null;
                }

                if(is_array($framework_css)) {
                    if($type == "row-default") {
                        $framework_css["class"] = $framework_css_settings["class" . ($framework_css["is_fluid"] ? "-fluid" : "")];
                    } else if($type == "row-fluid") {
                        $framework_css["class"] = $framework_css_settings["class-fluid"];
                    } else if($type == "row") {
                        $framework_css["class"] = $framework_css_settings["class"];
                    }
                    if($framework_css["class"]["row-" . $value]) {
                        $res[$framework_css["class"]["row-" . $value]] = true;
                    } else {
                        if($value) {
                            $res[$value] = true;
                        }

                        if(strlen($framework_css["class"]["row-prefix"])) {
                            $res[$framework_css["class"]["row-prefix"]] = true;
                        }
                        if(strlen($framework_css["class"]["row-postfix"])) {
                            $res[$framework_css["class"]["row-postfix"]] = true;
                        }
                    }
                } else {
                    if($value) {
                        $res[$value] = true;
                    }
                    $res["line"] = true;
                }

                $res = implode(" ", array_keys($res));
                break;
            case "form":
            case "callout":
            case "pagination":
            case "bar":
            case "list":
            case "card":
                if(isset($params["exclude"])) {
                    $exclude = $params["exclude"];

                    if(is_array($framework_css[$type][$value . "-exclude"])
                        && count($framework_css[$type][$value . "-exclude"])
                        && array_search($exclude, $framework_css[$type][$value . "-exclude"]) !== false
                    ) {
                        break;
                    }

                    unset($params["exclude"]);
                }

                if(is_array($params) && count($params))
                    $res = array_fill_keys($params, true);
                else
                    $res = array();

                if(is_array($framework_css)) {
                    if(is_array($value)) {
                        foreach ($value AS $subvalue) {
                            if(strlen($framework_css[$type][$subvalue])) {
                                $res[$framework_css[$type][$subvalue]] = true;
                            }
                        }
                    } elseif(strlen($framework_css[$type][$value])) {
                        $res[$framework_css[$type][$value]] = true;
                    }

                }
                $res = implode(" ", array_keys($res));
                break;
            case "util":
            case "table":
            case "tab":
            case "collapse":
            case "topbar":
            case "panel":
            case "badge":
            case "dialog":
               $res = array();

                if(is_array($params) && count($params)) {
                    $res = array_fill_keys($params, true);
                }
                if(is_array($framework_css)) {
                    if(is_array($value)) {
                        foreach($value AS $subvalue) {
                            if(strlen($framework_css[$type][$subvalue])) {
                                $res[$framework_css[$type][$subvalue]] = true;
                            }
                        }
                    } else {
                        if(strlen($framework_css[$type][$value])) {
                            $res[$framework_css[$type][$value]] = true;
                        }
                    }
                }

                $res = (is_array($res) && count($res)
                    ? implode(" ", array_keys($res))
                    : ""
                );
                break;
            case "data":
                $res = array();
                $subtype = $params[0];
                if(is_array($framework_css)) {
                    if(is_array($value)) {
                        foreach($value AS $subvalue) {
                            if(strlen($framework_css[$type][$subtype][$subvalue])) {
                                $res[$framework_css[$type][$subtype][$subvalue]] = true;
                            }
                        }
                    } else {
                        if(strlen($framework_css[$type][$subtype][$value])) {
                            $res[$framework_css[$type][$subtype][$value]] = true;
                        }
                    }
                }

                $res = (is_array($res) && count($res)
                    ? " " . implode(" ", array_keys($res))
                    : ""
                );
                break;
            default;
                $res = $value;
                if(is_array($params) && count($params))
                    $res .= " " . implode(" ", $params);
        }
        return $res;
    }

    private static function frameworks($name = null) {
        $framework_css_setting = array(
            "base" => array(
                "params" => array(
                    "css" => ""
                    , "js" => ""
                    , "js_init" => ""
                )
                , "class" => array(
                    "container" => "container"
                    , "wrap" => "row"
                    , "skip-full" => false
                    , "row-prefix" => "row"
                    , "row-start" => "row"
                    , "row-end" => "row"
                    , "row-center" => "row"
                    , "row-between" => "row"
                    , "row-around" => "row"
                    , "row-padding" => "row padding"
                    , "col-append" => "col-"
                    , "col-hidden" => "hidden-"
                    , "col-hidden-smallest" => ""
                    , "col-hidden-largest" => ""
                    , "push-append" => "push-"
                    , "push-prepend" => ""
                    , "pull-append" => "pull-"
                    , "pull-prepend" => ""
                    , "skip-resolution" => true
                    , "skip-prepost" => "nopadding"
                )
                , "class-fluid" => array(
                    "container" => "container-fluid"
                    , "wrap" => "row-fluid clearfix"
                    , "skip-full" => false
                    , "row-prefix" => ""
                    , "row-start" => ""
                    , "row-end" => ""
                    , "row-center" => ""
                    , "row-between" => ""
                    , "row-around" => ""
                    , "row-padding" => "padding"
                    , "col-append" => "col-"
                    , "col-hidden" => "hidden-"
                    , "col-hidden-smallest" => ""
                    , "col-hidden-largest" => ""
                    , "push-append" => "push-"
                    , "push-prepend" => ""
                    , "pull-append" => "pull-"
                    , "pull-prepend" => ""
                    , "skip-resolution" => true
                    , "skip-prepost" => "nopadding"
                )
                , "resolution" => array()
                , "resolution-media" => array()
                , "button" => array(
                    "base"              => "btn"
                    , "skip-default"    => false
                    , "width"       => array(
                        "full"          => "expand"
                    )
                    , "size"        => array(
                        "large"         => "large"
                        , "small"       => "small"
                        , "tiny"        => "tiny"
                    )
                    , "state"       => array(
                        "current"       => "current"
                        , "disabled"    => "disabled"
                    )
                    , "corner"      => array(
                        "round"         => "round"
                        , "radius"      => "radius"
                    )
                    , "color"       => array(
                        "default"       => ""
                        , "primary"     => "primary"
                        , "secondary"   => ""
                        , "success"     => "success"
                        , "info"        => "info"
                        , "warning"     => "warning"
                        , "danger"      => "danger"
                        , "link"        => "link"
                    )
                )
                , "form" => array(
                    "component" => ""
                    , "component-inline" => ""
                    , "row" => "row"
                    , "row-inline" => "row inline"
                    , "row-check" => "row check"
                    , "row-padding" => "row padding"
                    , "row-full" => "row"
                    , "group" => "row-smart"
                    , "group-sm" => "row-smart small"
                    , "group-padding" => "row-smart padding"
                    , "label" => ""
                    , "label-inline" => "inline"
                    , "label-check" => "inline-check"
                    , "control" => ""
                    , "control-check" => ""
                    , "control-file" => ""
                    , "control-plaintext" => "label"
                    , "size-sm" => "small"
                    , "size-lg" => "large"
                    , "control-exclude" => array()
                    , "control-prefix" => "prefix"
                    , "control-postfix" => "postfix"
                    , "control-text" => ""
                    , "control-feedback" => "postfix-feedback"
                    , "wrap" => "row"
                    , "wrap-padding" => "row padding"
                    //, "wrap-addon" => false
                )
                , "bar" => array(
                    "topbar" => "topbar"
                    , "navbar" => "navbar"
                )
                , "sidenav" => array( ///classsi bootstrap da convertire
                    "container" => "side-nav in"
                    , "item" => "side-nav-item"
                    , "link" => "side-nav-link"
                    , "secondlevelmenu" => "side-nav-second-level"
                    , "thirdlevelmenu" => "side-nav-third-level"
                    , "header" => "side-nav-title side-nav-item"
                 )
                , "list" => array( ///classsi bootstrap da convertire
                    "group" => "list-group"
                    , "group-horizontal" => "list-group list-group-horizontal"
                    , "item" => "list-group-item"
                    , "item-button" => "list-group-item-action"
                    , "item-success" => "list-group-item-success"
                    , "item-info" => "list-group-item-info"
                    , "item-warning" => "list-group-item-warning"
                    , "item-danger" => "list-group-item-danger"
                    , "current" => "active"
                    , "disabled" => "disabled"
                )
                , "topbar" => array( // DA FARE CORRISPINDENZA QUESTO E BOOTSTRAP
                    "container" => "navbar navbar-default"
                    , "container-fixed-top" => "navbar navbar-default navbar-fixed-top"
                    , "container-fixed-bottom" => "navbar navbar-default navbar-fixed-bottom"
                    , "header" => "navbar-header"
                    , "nav-brand" => "navbar-brand"
                    , "nav-form" => "navbar-form navbar-left"
                    , "nav-right" => "navbar-right"
                    , "dropdown" => "dropdown-toggle"
                    , "dropdown-menu" => "dropdown-menu"
                    , "current" => "active"
                    , "hamburger" => "navbar-toggle"
                )
                , "dropdown" => array(  // DA FARE CORRISPINDENZA QUESTO E BOOTSTRAP
                    "container" 	=> "dropdown"
                    , "menu" 		=> "dropdown-menu"
                    , "sub-menu" 	=> "dropdown-submenu"
                    , "header" 		=> "dropdown-header"
                    , "sep" 		=> "divider"
                    , "opened"		=> "open"
                    , "current" 	=> "active"
                )
                , "panel" => array(  // DA FARE CORRISPINDENZA QUESTO E BOOTSTRAP
                    "container" => "panel panel-default"
                    , "container-primary" => "panel-primary"
                    , "container-success" => "panel-success"
                    , "container-info" => "panel-info"
                    , "container-warning" => "panel-warning"
                    , "container-danger" => "panel-danger"
                    , "heading" => "panel-heading"
                    , "body" => "panel-body"
                    , "footer" => "panel-footer"

                )
                , "badge" => array(  // DA FARE CORRISPINDENZA QUESTO E BOOTSTRAP
                    "default"       => "badge"
                    , "primary"     => "badge badge-primary"
                    , "success"     => "badge badge-success"
                    , "info"        => "badge badge-info"
                    , "warning"     => "badge badge-warning"
                    , "danger"      => "badge badge-danger"
                )
                , "callout" => array(
                    "default"       => "callout"
                    , "primary"     => "callout callout-primary"
                    , "success"     => "callout callout-success"
                    , "info"        => "callout callout-info"
                    , "warning"     => "callout callout-warning"
                    , "danger"      => "callout callout-danger"
                )

                /*da trovare e gestire:
                show        bs
                radius         fd
                round        fd
                active        bs
                disabled        bs
                img-rounded        bs
                img-circle        bs
                img-thumbnail        bs

                center-block    bs

                clearfix        bs
                invisible        bs
                text-hide        bs


                */
                , "pagination" => array(
                    "align-left" => "text-left"
                    , "align-center" => "text-center"
                    , "align-right" => "text-right"
                    , "pages" => "pagination"
                    , "arrows" => "arrow"
                    , "page" => ""
                    , "page-link" => ""
                    , "current" => "current"
                )
                , "table" => array(
                    "container" => ""
                    , "inverse" => "table-dark"
                    , "compact" => "table-condensed"
                    , "small" => "table table-sm"
                    , "hover" => "table-hover"
                    , "border" => "table-bordered"
                    , "oddeven" => "table-striped"
                    , "responsive" => "table-responsive"
                    , "sorting" => "sorting"
                    , "sorting_asc" => "sorting_asc"
                    , "sorting_desc" => "sorting_desc"
                )
                , "tab" => array(
                    "menu" => "nav-tab"
                    , "menu-pills" => "nav nav-pills"
                    , "menu-pills-justified" => "nav nav-pills nav-justified"
                    , "menu-bordered" => "nav nav-tabs nav-bordered"
                    , "menu-bordered-justified" => "nav nav-tabs nav-bordered nav-justified"
                    , "menu-vertical" => "nav-tab vertical"
                    , "menu-vertical-right" => "nav-tab vertical right"
                    , "menu-item" => ""
                    , "menu-item-link" => ""
                    , "menu-current" => "current"
                    , "pane" => "tab-content"
                    , "pane-item" => "tab-pane"
                    , "pane-current" => ""
                    , "pane-item-effect" => "tab-pane fade"
                    , "pane-current-effect" => ""
                )
                , "collapse" => array(
                    "pane" 				=> "collapse"
                    , "current" 		=> "opened"
                    , "menu" 			=> "collapsed"
                )
                , "tooltip" => array(
                    "elem" => "has-tip"
                )
                , "dialog" => array(
                    "overlay" => "modal"
                    , "window" => "modal-dialog"
                    , "window-center" => "modal-dialog-centered"
                    , "window-small" => "modal-sm"
                    , "window-large" => "modal-lg"
                    , "window-huge" => "modal-full"
                    , "container" => "modal-content"
                    , "header" => "modal-header"
                    , "content" => "modal-body"
                    , "footer" => "modal-footer"
                    , "title" => "modal-title"
                    , "subtitle" => "modal-subtitle"
                    , "button" => "close"
                    , "effect" => "fade"
                )
                , "card" => array(
                    "container" => "card"
                    , "cover-top" => "card-img-top"
                    , "header" => "card-header"
                    , "body" => "card-body"
                    , "footer" => "card-footer"
                    , "title" => "card-title"
                    , "sub-title" => "card-subtitle"
                    , "text" => "card-text"
                    , "link" => "card-link"
                    , "list-group" => "list-group list-group-flush"
                    , "list-group-item" => "list-group-item"
                )
                , "util" => array(
                    "left"                          => "left"
                    , "right"                       => "right"
                    , "hide"                        => "hidden"
                    , "align-left"                  => "align-left"
                    , "align-center"                => "align-center"
                    , "align-right"                 => "align-right"
                    , "align-justify"               => "align-justify"
                    , "text-nowrap"                 => "text-nowrap"
                    , "text-overflow"               => "text-overflow"
                    , "text-lowercase"              => "text-lowercase"
                    , "text-uppercase"              => "text-uppercase"
                    , "text-capitalize"             => "text-capitalize"

                    , "text-muted"                  => "text-muted"
                    , "text-primary"                => "text-primary"
                    , "text-success"                => "text-success"
                    , "text-info"                   => "text-info"
                    , "text-warning"                => "text-warning"
                    , "text-danger"                 => "text-danger"

                    , "bg-primary"                  => "bg-primary"
                    , "bg-success"                  => "bg-success"
                    , "bg-info"                     => "bg-info"
                    , "bg-warning"                  => "bg-warning"
                    , "bg-danger"                   => "bg-danger"

                    , "current" => "current"
                    , "equalizer-row" => "data-equalizer"
                    , "equalizer-col" => "data-equalizer-watch"
                    , "corner-radius" => "radius"
                    , "corner-round" => "round"
                    , "corner-circle" => "circle"
                    , "corner-thumbnail" => "thumbnail"
                    , "clear" => "clearfix"
                )
                , "data" => array(
                    "tab" => array(
                        "menu" => 'data-tab'
                        , "menu-link" => null
                        , "pane" => null
                        , "pane-item" => null
                    )
                    , "tooltip" => array(
                        "elem" => null
                    )
                    , "collapse" => array(
                        "link" => 'data-toggle'
                    )
                    , "button" => array(
                        "toggle" => 'data-toggle'
                    )
                )
            )
            , "bootstrap" => array(
                "params" => array(
                    "css" => "http" . ($_SERVER["HTTPS"] ? "s": "") . "://netdna.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css"
                    , "js" => "http" . ($_SERVER["HTTPS"] ? "s": "") . "://netdna.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"
                    , "js_init" => ""
                )
                , "class" => array(
                    "container" => "container"
                    , "wrap" => "container"
                    , "skip-full" => false
                    , "row-prefix" => "row"
                    , "row-start" => "row"
                    , "row-end" => "row text-right"
                    , "row-center" => "row"
                    , "row-between" => "row"
                    , "row-around" => "row"
                    , "row-padding" => "row padding"
                    , "col-prefix" => ""
                    , "col-append" => "col-"
                    , "col-hidden" => "hidden-"
                    , "col-hidden-smallest" => ""
                    , "col-hidden-largest" => ""
                    , "push-append" => "col-"
                    , "push-prepend" => "push-"
                    , "pull-append" => "col-"
                    , "pull-prepend" => "pull-"
                    , "skip-prepost" => "nopadding"
                )
                , "class-fluid" => array(
                    "container" => "container-fluid"
                    , "wrap" => "row"
                    , "skip-full" => false
                    , "row-prefix" => "row"
                    , "row-start" => "row"
                    , "row-end" => "row"
                    , "row-center" => "row"
                    , "row-between" => "row"
                    , "row-around" => "row"
                    , "row-padding" => "row padding"
                    , "col-prefix" => ""
                    , "col-append" => "col-"
                    , "col-hidden" => "hidden-"
                    , "col-hidden-smallest" => ""
                    , "col-hidden-largest" => ""
                    , "push-append" => "col-"
                    , "push-prepend" => "push-"
                    , "pull-append" => "col-"
                    , "pull-prepend" => "pull-"
                    , "skip-prepost" => "nopadding"
                )
                , "resolution" => array(
                    "xs"
                    , "sm"
                    , "md"
                    , "lg"
                )
                , "resolution-media" => array(
                    "xs" => "(min-width:34em)"
                    , "sm" => "(min-width:48em)"
                    , "md" => "(min-width:62em)"
                    , "lg" => "(min-width:75em)"
                )
                , "button" => array(
                    "base"              => "btn"
                    , "skip-default"    => true
                    , "width"       => array(
                        "full"          => "btn-block"
                    )
                    , "size"        => array(
                        "large"         => "btn-lg"
                        , "small"       => "btn-sm"
                        , "tiny"        => "btn-xs"
                    )
                    , "state"       => array(
                        "current"       => "active"
                        , "disabled"    => "disabled"
                    )
                    , "corner"      => array(
                        "round"         => false
                        , "radius"      => false
                    )
                    , "color"       => array(
                        "default"       => "btn-default"
                        , "primary"     => "btn-primary"
                        , "secondary"   => "btn-default"
                        , "success"     => "btn-success"
                        , "info"        => "btn-info"
                        , "warning"     => "btn-warning"
                        , "danger"      => "btn-danger"
                        , "link"        => "btn-link"
                    )
                )
                , "form" => array(
                    "component" => ""
                    , "component-inline" => "form-horizontal"
                    , "row" => "form-group clearfix"
                    , "row-inline" => "form-group row"
                    , "row-check" => "form-check"
                    , "row-padding" => "form-group clearfix padding"
                    , "row-full" => "form-group clearfix"
                    , "group" => "input-group"
                    , "group-sm" => "input-group input-group-sm"
                    , "group-padding" => "input-group padding"
                    , "label" => ""
                    , "label-inline" => "col-form-label"
                    , "label-check" => "form-check-label"
                    , "control" => "form-control"
                    , "control-check" => "form-check-control"
                    , "control-file" => "form-control-file"
                    , "control-plaintext" => "form-control-plaintext"
                    , "size-sm" => "form-control-sm"
                    , "size-lg" => "form-control-lg"
                    , "control-exclude" => array("checkbox", "radio")
                    , "control-prefix" => "input-group-addon"
                    , "control-postfix" => "input-group-addon"
                    , "control-text" => "input-group-text"
                    , "control-feedback" => "form-control-feedback"
                    , "wrap" => "row"
                    , "wrap-padding" => "row padding"
                    //, "wrap-addon" => false
                )
                , "bar" => array(
                    "topbar" => "nav navbar-nav"
                    , "navbar" => "nav nav-pills nav-stacked"
                    , "sidebar" => array(
                        ///todo: da fare il merge con il tema custom
                    )
                )
                , "sidenav" => array( ///classsi bootstrap da convertire
                    "container" => "side-nav in"
                    , "item" => "side-nav-item"
                    , "link" => "side-nav-link"
                    , "secondlevelmenu" => "side-nav-second-level"
                    , "thirdlevelmenu" => "side-nav-third-level"
                    , "header" => "side-nav-title side-nav-item"
                 )
                , "list" => array(
                    "group" => "list-group"
                    , "group-horizontal" => "list-group list-group-horizontal"
                    , "item" => "list-group-item"
                    , "item-button" => "list-group-item-action"
                    , "item-success" => "list-group-item-success"
                    , "item-info" => "list-group-item-info"
                    , "item-warning" => "list-group-item-warning"
                    , "item-danger" => "list-group-item-danger"
                    , "current" => "active"
                    , "disabled" => "disabled"
                )
                , "topbar" => array(
                    "container" => "navbar navbar-default"
                    , "container-fixed-top" => "navbar navbar-default navbar-fixed-top"
                    , "container-fixed-bottom" => "navbar navbar-default navbar-fixed-bottom"
                    , "header" => "navbar-header"
                    , "nav-brand" => "navbar-brand"
                    , "nav-form" => "navbar-form navbar-left"
                    , "nav-right" => "navbar-right"
                    , "dropdown" => "dropdown-toggle"
                    , "dropdown-menu" => "dropdown-menu"
                    , "current" => "active"
                    , "hamburger" => "navbar-toggle"
                )
                , "dropdown" => array(  // DA FARE CORRISPINDENZA QUESTO E BOOTSTRAP
                    "container" 	=> "dropdown"
                    , "menu" 		=> "dropdown-menu"
                    , "sub-menu" 	=> "dropdown-submenu"
                    , "header" 		=> "dropdown-header"
                    , "sep" 		=> "divider"
                    , "opened"		=> "open"
                    , "current" 	=> "active"
                )
                , "panel" => array(
                    "container" => "panel panel-default"
                    , "container-primary" => "panel-primary"
                    , "container-success" => "panel-success"
                    , "container-info" => "panel-info"
                    , "container-warning" => "panel-warning"
                    , "container-danger" => "panel-danger"
                    , "heading" => "panel-heading"
                    , "body" => "panel-body"
                    , "footer" => "panel-footer"

                )
                , "badge" => array(
                    "default"       => "badge"
                    , "primary"     => "badge badge-primary"
                    , "success"     => "badge badge-success"
                    , "info"        => "badge badge-info"
                    , "warning"     => "badge badge-warning"
                    , "danger"      => "badge badge-danger"
                )
                , "callout" => array(
                    "default"       => "bs-callout"
                    , "primary"     => "bs-callout bs-callout-primary"
                    , "success"     => "bs-callout bs-callout-success"
                    , "info"        => "bs-callout bs-callout-info"
                    , "warning"     => "bs-callout bs-callout-warning"
                    , "danger"      => "bs-callout bs-callout-danger"
                )
                , "pagination" => array(
                    "align-left" => "text-left"
                    , "align-center" => "text-center"
                    , "align-right" => "text-right"
                    , "pages" => "pagination"
                    , "arrows" => ""
                    , "page" => ""
                    , "page-link" => ""
                    , "current" => "active"
                )
                , "table" => array(
                    "container" => "table"
                    , "inverse" => "table-dark"
                    , "compact" => "table-condensed"
                    , "small" => "table table-sm"
                    , "hover" => "table-hover"
                    , "border" => "table-bordered"
                    , "oddeven" => "table-striped"
                    , "responsive" => "table-responsive"
                    , "sorting" => "sorting"
                    , "sorting_asc" => "sorting_asc"
                    , "sorting_desc" => "sorting_desc"
                )
                , "tab" => array(
                    "menu" => "nav nav-tabs"
                    , "menu-pills" => "nav nav-pills"
                    , "menu-pills-justified" => "nav nav-pills nav-justified"
                    , "menu-bordered" => "nav nav-tabs nav-bordered"
                    , "menu-bordered-justified" => "nav nav-tabs nav-bordered nav-justified"
                    , "menu-vertical" => "nav nav-tabs tabs-left"
                    , "menu-vertical-right" => "nav nav-tabs tabs-right"
                    , "menu-item" => ""
                    , "menu-item-link" => ""
                    , "menu-current" => "active"
                    , "pane" => "tab-content"
                    , "pane-item" => "tab-pane"
                    , "pane-current" => "active"
                    , "pane-item-effect" => "tab-pane fade"
                    , "pane-current-effect" => "active in"
                )
                , "collapse" => array(
                    "pane" 				=> "collapse"
                    , "current" 		=> "in"
                    , "menu" 			=> "collapsed"
                )
                , "tooltip" => array(
                    "elem" => "has-tip" //da trovare analogo per bootstrap
                )
                , "dialog" => array(
                    "overlay" => "modal"
                    , "window" => "modal-dialog"
                    , "window-center" => "modal-dialog-centered"
                    , "window-small" => "modal-sm"
                    , "window-large" => "modal-lg"
                    , "window-huge" => "modal-full"
                    , "container" => "modal-content"
                    , "header" => "modal-header"
                    , "content" => "modal-body"
                    , "footer" => "modal-footer"
                    , "title" => "modal-title"
                    , "subtitle" => "modal-subtitle"
                    , "button" => "close"
                    , "effect" => "fade"
                )
                , "card" => array(
                    "container" => "card"
                    , "cover-top" => "card-img-top"
                    , "header" => "card-header"
                    , "body" => "card-body"
                    , "footer" => "card-footer"
                    , "title" => "card-title"
                    , "sub-title" => "card-subtitle"
                    , "text" => "card-text"
                    , "link" => "card-link"
                    , "list-group" => "list-group list-group-flush"
                    , "list-group-item" => "list-group-item"
                )
                , "util" => array(
                    "left" => "pull-left"
                    , "right" => "pull-right"
                    , "hide" => "hidden"
                    , "align-left" => "text-left"
                    , "align-center" => "text-center"
                    , "align-right" => "text-right"
                    , "align-justify" => "text-justify"
                    , "text-nowrap" => "text-nowrap"
                    , "text-overflow" => "text-overflow"
                    , "text-lowercase" => "text-lowercase"
                    , "text-uppercase" => "text-uppercase"
                    , "text-capitalize" => "text-capitalize"

                    , "text-muted"                  => "text-muted"
                    , "text-primary"                => "text-primary"
                    , "text-success"                => "text-success"
                    , "text-info"                   => "text-info"
                    , "text-warning"                => "text-warning"
                    , "text-danger"                 => "text-danger"

                    , "bg-primary"                  => "bg-primary"
                    , "bg-success"                  => "bg-success"
                    , "bg-info"                     => "bg-info"
                    , "bg-warning"                  => "bg-warning"
                    , "bg-danger"                   => "bg-danger"

                    , "current"                     => "active"
                    , "equalizer-row" => "data-equalizer"
                    , "equalizer-col" => "data-equalizer-watch"
                    , "corner-radius" => "border-radius"
                    , "corner-round" => "img-rounded"
                    , "corner-circle" => "img-circle"
                    , "corner-thumbnail" => "img-thumbnail"
                    , "clear" => "clearfix"
                )
                , "data" => array(
                    "tab" => array(
                        "menu" => null
                        , "menu-link" => 'data-toggle="tab"'
                        , "pane" => null
                        , "pane-item" => null
                    )
                    , "tooltip" => array(
                        "elem" => 'data-toggle="tooltip"'
                    )
                    , "collapse" => array(
                        "link" => 'data-toggle="collapse"'
                    )
                    , "button" => array(
                        "toggle" => 'data-toggle="button"'
                    )
                )
            )
            , "bootstrap4" => array(
                "params" => array(
                    "css" => "http" . ($_SERVER["HTTPS"] ? "s": "") . "://netdna.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
                    , "js" => "http" . ($_SERVER["HTTPS"] ? "s": "") . "://netdna.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"
                    , "js_init" => ""
                )
                , "class" => array(
                    "container" => "container"
                    , "wrap" => "row"
                    , "skip-full" => false
                    , "row-prefix" => "row"
                    , "row-start" => "row justify-content-start"
                    , "row-end" => "row justify-content-end"
                    , "row-center" => "row justify-content-center"
                    , "row-between" => "row justify-content-between"
                    , "row-around" => "row justify-content-around"
                    , "row-padding" => "row mb-3"
                    , "col-prefix" => ""
                    , "col-append" => "col-"
                    , "col-hidden" => "hidden-"
                    , "col-hidden-smallest" => ""
                    , "col-hidden-largest" => ""
                    , "push-append" => "col-"
                    , "push-prepend" => "push-"
                    , "pull-append" => "col-"
                    , "pull-prepend" => "pull-"
                    , "skip-prepost" => "nopadding"
                )
                , "class-fluid" => array(
                    "container" => "container-fluid"
                    , "wrap" => "row"
                    , "skip-full" => false
                    , "row-prefix" => "row"
                    , "row-start" => "row justify-content-start"
                    , "row-end" => "row justify-content-end"
                    , "row-center" => "row justify-content-center"
                    , "row-between" => "row justify-content-between"
                    , "row-around" => "row justify-content-around"
                    , "row-padding" => "row mb-3"
                    , "col-prefix" => ""
                    , "col-append" => "col-"
                    , "col-hidden" => "hidden-"
                    , "col-hidden-smallest" => ""
                    , "col-hidden-largest" => ""
                    , "push-append" => "col-"
                    , "push-prepend" => "push-"
                    , "pull-append" => "col-"
                    , "pull-prepend" => "pull-"
                    , "skip-prepost" => "nopadding"
                )
                , "resolution" => array(
                    ""
                    , "sm"
                    , "md"
                    , "lg"
                    , "xl"
                )
                , "resolution-media" => array(
                    "" => "(min-width:34em)"
                    , "sm" => "(min-width:48em)"
                    , "md" => "(min-width:62em)"
                    , "lg" => "(min-width:75em)"
                    , "xl" => "(min-width:85em)"
                )
                , "button" => array(
                    "base"              => "btn"
                    , "skip-default"    => true
                    , "width"       => array(
                        "full"          => "btn-block"
                    )
                    , "size"        => array(
                        "large"         => "btn-lg"
                        , "small"       => "btn-sm"
                        , "tiny"        => "btn-xs"
                    )
                    , "state"       => array(
                        "current"       => "active"
                        , "disabled"    => "disabled"
                    )
                    , "corner"      => array(
                        "round"         => false
                        , "radius"      => false
                    )
                    , "color"       => array(
                        "default"       => "btn-default"
                        , "primary"     => "btn-primary"
                        , "secondary"   => "btn-secondary"
                        , "success"     => "btn-success"
                        , "info"        => "btn-info"
                        , "warning"     => "btn-warning"
                        , "danger"      => "btn-danger"
                        , "link"        => "btn-link"
                    )
                )
                , "form" => array(
                    "component" => ""
                    , "component-inline" => ""
                    , "row" => "form-group"
                    , "row-inline" => "form-group row"
                    , "row-check" => "form-group form-check"
                    , "row-padding" => "form-group padding"
                    , "row-full" => "form-group"
                    , "group" => "input-group"
                    , "group-sm" => "input-group input-group-sm"
                    , "group-padding" => "input-group padding"
                    , "label" => ""
                    , "label-inline" => "col-form-label"
                    , "label-check" => "form-check-label"
                    , "control" => "form-control"
                    , "control-check" => "form-check-control"
                    , "control-file" => "form-control-file"
                    , "control-plaintext" => "form-control-plaintext"
                    , "size-sm" => "form-control-sm"
                    , "size-lg" => "form-control-lg"
                    , "control-exclude" => array("checkbox", "radio")
                    , "control-prefix" => "input-group-prepend"
                    , "control-postfix" => "input-group-append"
                    , "control-text" => "input-group-text"
                    , "control-feedback" => "form-control-feedback"
                    , "wrap" => "form-row"
                    , "wrap-padding" => "form-row pl-1 pr-2"
                    //, "wrap-addon" => false
                )
                , "bar" => array(
                    "topbar" => "nav navbar-nav"
                    , "navbar" => "nav nav-pills nav-stacked"
                    , "sidebar" => array(
                        ///todo: da fare il merge con il tema custom
                    )
                )
                , "sidenav" => array( ///classsi bootstrap da convertire
                    "container" => "side-nav in"
                    , "item" => "side-nav-item"
                    , "link" => "side-nav-link"
                    , "secondlevelmenu" => "side-nav-second-level"
                    , "thirdlevelmenu" => "side-nav-third-level"
                    , "header" => "side-nav-title side-nav-item"
                 )
                , "list" => array(
                    "group" => "list-group"
                    , "group-horizontal" => "list-group list-group-horizontal"
                    , "item" => "list-group-item"
                    , "item-button" => "list-group-item-action"
                    , "item-success" => "list-group-item-success"
                    , "item-info" => "list-group-item-info"
                    , "item-warning" => "list-group-item-warning"
                    , "item-danger" => "list-group-item-danger"
                    , "current" => "active"
                    , "disabled" => "disabled"
                )
                , "topbar" => array(
                    "container" => "navbar navbar-default"
                    , "container-fixed-top" => "navbar navbar-default navbar-fixed-top"
                    , "container-fixed-bottom" => "navbar navbar-default navbar-fixed-bottom"
                    , "header" => "navbar-header"
                    , "nav-brand" => "navbar-brand"
                    , "nav-form" => "navbar-form navbar-left"
                    , "nav-right" => "navbar-right"
                    , "dropdown" => "dropdown-toggle"
                    , "dropdown-menu" => "dropdown-menu"
                    , "current" => "active"
                    , "hamburger" => "navbar-toggle"
                )
                , "dropdown" => array(  // DA FARE CORRISPINDENZA QUESTO E BOOTSTRAP
                    "container" 	=> "dropdown"
                    , "menu" 		=> "dropdown-menu"
                    , "sub-menu" 	=> "dropdown-submenu"
                    , "header" 		=> "dropdown-header"
                    , "sep" 		=> "divider"
                    , "opened"		=> "open"
                    , "current" 	=> "active"
                )
                , "panel" => array(
                    "container" => "panel panel-default"
                    , "container-primary" => "panel-primary"
                    , "container-success" => "panel-success"
                    , "container-info" => "panel-info"
                    , "container-warning" => "panel-warning"
                    , "container-danger" => "panel-danger"
                    , "heading" => "panel-heading"
                    , "body" => "panel-body"
                    , "footer" => "panel-footer"

                )
                , "badge" => array(
                    "default"       => "badge"
                    , "primary"     => "badge badge-primary"
                    , "success"     => "badge badge-success"
                    , "info"        => "badge badge-info"
                    , "warning"     => "badge badge-warning"
                    , "danger"      => "badge badge-danger"
                )
                , "callout" => array(
                    "default"       => "alert alert-secondary"
                    , "primary"     => "alert alert-primary"
                    , "success"     => "alert alert-success"
                    , "info"        => "alert alert-info"
                    , "warning"     => "alert alert-warning"
                    , "danger"      => "alert alert-danger"
                )
                , "pagination" => array(
                    "align-left" => "text-left"
                    , "align-center" => "text-center"
                    , "align-right" => "text-right"
                    , "pages" => "pagination"
                    , "arrows" => ""
                    , "page" => "page-item"
                    , "page-link" => "page-link"
                    , "current" => "active"
                )
                , "table" => array(
                    "container" => "table"
                    , "inverse" => "table-dark"
                    , "compact" => "table-condensed"
                    , "small" => "table-sm"
                    , "hover" => "table-hover"
                    , "border" => "table-bordered"
                    , "oddeven" => "table-striped"
                    , "responsive" => "table-responsive"
                    , "sorting" => "sorting"
                    , "sorting_asc" => "sorting_asc"
                    , "sorting_desc" => "sorting_desc"
                )
                , "tab" => array(
                    "menu" => "nav nav-tabs"
                    , "menu-pills" => "nav nav-pills"
                    , "menu-pills-justified" => "nav nav-pills nav-justified"
                    , "menu-bordered" => "nav nav-tabs nav-bordered"
                    , "menu-bordered-justified" => "nav nav-tabs nav-bordered nav-justified"
                    , "menu-vertical" => "nav flex-column nav-pills"
                    , "menu-vertical-right" => "nav flex-column nav-pills"
                   // , "menu-vertical-wrap" => true
                    , "menu-item" => "nav-item"
                    , "menu-item-link" => "nav-link"
                    , "menu-current" => "active show"
                    , "pane" => "tab-content"
                    , "pane-item" => "tab-pane"
                    , "pane-current" => "active show"
                    , "pane-item-effect" => "tab-pane fade"
                    , "pane-current-effect" => "active show"
                )
                , "collapse" => array(
                    "pane" 				=> "collapse"
                    , "current" 		=> "in"
                    , "menu" 			=> "collapsed"
                )
                , "tooltip" => array(
                    "elem" => "has-tip" //da trovare analogo per bootstrap
                )
                , "dialog" => array(
                    "overlay" => "modal"
                    , "window" => "modal-dialog"
                    , "window-center" => "modal-dialog-centered"
                    , "window-small" => "modal-sm"
                    , "window-large" => "modal-lg"
                    , "window-huge" => "modal-xl"
                    , "container" => "modal-content"
                    , "header" => "modal-header"
                    , "body" => "modal-body"
                    , "footer" => "modal-footer"
                    , "title" => "modal-title"
                    , "subtitle" => "modal-subtitle"
                    , "button" => "close"
                    , "effect" => "fade"
                )
                , "card" => array(
                    "container" => "card"
                    , "cover-top" => "card-img-top"
                    , "header" => "card-header"
                    , "body" => "card-body"
                    , "footer" => "card-footer"
                    , "title" => "card-title"
                    , "sub-title" => "card-subtitle"
                    , "text" => "card-text"
                    , "link" => "card-link"
                    , "list-group" => "list-group list-group-flush"
                    , "list-group-item" => "list-group-item"
                )
                , "util" => array(
                    "left" => "pull-left"
                    , "right" => "pull-right"
                    , "hide" => "d-none"
                    , "align-left" => "text-left"
                    , "align-center" => "text-center"
                    , "align-right" => "text-right"
                    , "align-justify" => "text-justify"
                    , "text-nowrap" => "text-nowrap"
                    , "text-overflow" => "text-overflow"
                    , "text-lowercase" => "text-lowercase"
                    , "text-uppercase" => "text-uppercase"
                    , "text-capitalize" => "text-capitalize"

                    , "text-muted"                  => "text-muted"
                    , "text-primary"                => "text-primary"
                    , "text-success"                => "text-success"
                    , "text-info"                   => "text-info"
                    , "text-warning"                => "text-warning"
                    , "text-danger"                 => "text-danger"

                    , "bg-primary"                  => "bg-primary"
                    , "bg-success"                  => "bg-success"
                    , "bg-info"                     => "bg-info"
                    , "bg-warning"                  => "bg-warning"
                    , "bg-danger"                   => "bg-danger"

                    , "current"                     => "active"
                    , "equalizer-row" => "data-equalizer"
                    , "equalizer-col" => "data-equalizer-watch"
                    , "corner-radius" => "border-radius"
                    , "corner-round" => "img-rounded"
                    , "corner-circle" => "img-circle"
                    , "corner-thumbnail" => "img-thumbnail"
                    , "clear" => "clearfix"
                )
                , "data" => array(
                    "tab" => array(
                        "menu" => null
                        , "menu-link" => 'data-toggle="tab"'
                        , "pane" => null
                        , "pane-item" => null
                    )
                    , "tooltip" => array(
                        "elem" => 'data-toggle="tooltip"'
                    )
                    , "collapse" => array(
                        "link" => 'data-toggle="collapse"'
                    )
                    , "button" => array(
                        "toggle" => 'data-toggle="button"'
                    )
                )
            )
            , "foundation" => array(
                "params" => array(
                    "css" 				=> "http" . ($_SERVER["HTTPS"] ? "s": "") . "://cdnjs.cloudflare.com/ajax/libs/foundation/6.2.3/foundation.min.css"
                    , "js" 				=> "http" . ($_SERVER["HTTPS"] ? "s": "") . "://cdnjs.cloudflare.com/ajax/libs/foundation/6.2.3/foundation.min.js"
                    , "js_init" 		=> 'jQuery(function() { jQuery(document).foundation(); });'//non funziona con la cache
                )
                , "class" => array(
                    "container" => "container"
                    , "wrap" => "row"
                    , "skip-full" => true
                    , "row-prefix" => "row"
                    , "row-start" => "row"
                    , "row-end" => "row"
                    , "row-center" => "row"
                    , "row-between" => "row"
                    , "row-around" => "row"
                    , "row-padding" => "row padding"
                    , "col-prefix" => "columns"
                    , "col-hidden" => "hide-for-"
                    , "col-hidden-smallest" => ""
                    , "col-hidden-largest" => "-up"
                    , "push-append" => ""
                    , "push-prepend" => "push-"
                    , "pull-append" => ""
                    , "pull-prepend" => "pull-"
                    , "skip-resolution" => false
                    , "skip-prepost" => "nopadding"
                )
                , "class-fluid" => array(
                    "container" => "container-fluid"
                    , "wrap" => "row-fluid clearfix"
                    , "skip-full" => true
                    , "row-prefix" => ""
                    , "row-start" => ""
                    , "row-end" => ""
                    , "row-center" => ""
                    , "row-between" => ""
                    , "row-around" => ""
                    , "row-padding" => "padding"
                    , "col-prefix" => "columns"
                    , "col-hidden" => "hide-for-"
                    , "col-hidden-smallest" => ""
                    , "col-hidden-largest" => "-up"
                    , "push-append" => ""
                    , "push-prepend" => "push-"
                    , "pull-append" => ""
                    , "pull-prepend" => "pull-"
                    , "skip-resolution" => false
                    , "skip-prepost" => "nopadding"
                )
                , "resolution" => array(
                    "small"
                    , "medium"
                    , "large"
                )
                , "resolution-media" => array(
                    "small" => "(max-width: 40em)"
                    , "medium" => "(min-width: 40.063em)"
                    , "large" => "(min-width: 64.063em)"
                )
                , "button" => array(
                    "base"   => "button"
                    , "skip-default"    => true
                    , "width"     => array(
                        "full"          => "expand"
                    )
                    , "size"    => array(
                        "large"         => "large"
                        , "small"       => "small"
                        , "tiny"        => "tiny"
                    )
                    , "state"   => array(
                        "current"       => "current"
                        , "disabled"    => "disabled"
                    )
                    , "corner"  => array(
                        "round"         => "round"
                        , "radius"      => "radius"
                    )
                    , "color"   => array(
                        "default"       => "secondary"
                        , "primary"     => "primary"
                        , "secondary"   => "primary"
                        , "success"     => "success"
                        , "info"        => "secondary"
                        , "warning"     => "alert"
                        , "danger"      => "alert"
                        , "link"        => "secondary"
                    )
                )
                , "form" => array(
                    "component" => ""
                    , "component-inline" => ""
                    , "row" => "row"
                    , "row-inline" => "row"
                    , "row-check" => "row"
                    , "row-padding" => "row padding"
                    , "row-full" => "columns"
                    , "group" => "row collapse"
                    , "group-sm" => "row"
                    , "group-padding" => "row collapse padding"
                    , "label" => ""
                    , "label-inline" => "inline right"
                    , "label-check" => "inline"
                    , "control" => ""
                    , "control-check" => ""
                    , "control-file" => ""
                    , "control-plaintext" => ""
                    , "size-sm" => "small"
                    , "size-lg" => "large"
                    , "control-exclude" => array()
                    , "control-prefix" => "prefix"
                    , "control-postfix" => "postfix"
                    , "control-text" => ""
                    , "control-feedback" => "postfix-feedback"
                    , "wrap" => "row"
                    , "wrap-padding" => "row padding"
                    //, "wrap-addon" => true
                )
                , "bar" => array(
                    "topbar" => "top-bar top-bar-section"
                    , "navbar" => "side-nav"
                )
                , "sidenav" => array( ///classsi bootstrap da convertire
                    "container" => "side-nav in"
                    , "item" => "side-nav-item"
                    , "link" => "side-nav-link"
                    , "secondlevelmenu" => "side-nav-second-level"
                    , "thirdlevelmenu" => "side-nav-third-level"
                    , "header" => "side-nav-title side-nav-item"
                 )
                , "list" => array( ///classsi bootstrap da convertire
                    "group" => "list-group"
                    , "group-horizontal" => "list-group list-group-horizontal"
                    , "item" => "list-group-item"
                    , "item-button" => "list-group-item-action"
                    , "item-success" => "list-group-item-success"
                    , "item-info" => "list-group-item-info"
                    , "item-warning" => "list-group-item-warning"
                    , "item-danger" => "list-group-item-danger"
                    , "current" => "active"
                    , "disabled" => "disabled"
                )
                , "topbar" => array( // DA FARE CORRISPINDENZA QUESTO E BOOTSTRAP
                    "container" => "navbar navbar-default"
                    , "container-fixed-top" => "navbar navbar-default navbar-fixed-top"
                    , "container-fixed-bottom" => "navbar navbar-default navbar-fixed-bottom"
                    , "header" => "navbar-header"
                    , "nav-brand" => "navbar-brand"
                    , "nav-form" => "navbar-form navbar-left"
                    , "nav-right" => "navbar-right"
                    , "dropdown" => "dropdown-toggle"
                    , "dropdown-menu" => "dropdown-menu"
                    , "current" => "active"
                    , "hamburger" => "navbar-toggle"
                )
                , "dropdown" => array(  // DA FARE CORRISPINDENZA QUESTO E BOOTSTRAP
                    "container" 	=> "dropdown"
                    , "menu" 		=> "dropdown-menu"
                    , "sub-menu" 	=> "dropdown-submenu"
                    , "header" 		=> "dropdown-header"
                    , "sep" 		=> "divider"
                    , "opened"		=> "open"
                    , "current" 	=> "active"
                )
                , "panel" => array(  // DA FARE CORRISPINDENZA QUESTO E BOOTSTRAP
                    "container" => "panel panel-default"
                    , "container-primary" => "panel-primary"
                    , "container-success" => "panel-success"
                    , "container-info" => "panel-info"
                    , "container-warning" => "panel-warning"
                    , "container-danger" => "panel-danger"
                    , "heading" => "panel-heading"
                    , "body" => "panel-body"
                    , "footer" => "panel-footer"

                )
                , "badge" => array(
                    "default"       => "badge"
                    , "primary"     => "badge primary"
                    , "success"     => "badge success"
                    , "info"        => "badge info"
                    , "warning"     => "badge warning"
                    , "danger"      => "badge danger"
                )
                , "callout" => array(
                    "default"       => "panel"
                    , "primary"     => "alert-box"
                    , "success"     => "alert-box success"
                    , "info"        => "panel callout"
                    , "warning"     => "alert-box warning"
                    , "danger"      => "alert-box alert"
                )
                , "pagination" => array(
                    "align-left" => "text-left"
                    , "align-center" => "pagination-centered" //"text-center"
                    , "align-right" => "text-right"
                    , "pages" => "pagination"
                    , "arrows" => "arrow"
                    , "page" => ""
                    , "page-link" => ""
                    , "current" => "current"
                )
                , "table" => array(
                    "container" => ""
                    , "inverse" => "table-dark"
                    , "compact" => "table-condensed"
                    , "small" => "table table-sm"
                    , "hover" => "table-hover"
                    , "border" => "table-bordered"
                    , "oddeven" => "table-striped"
                    , "responsive" => ""
                    , "sorting" => "sorting"
                    , "sorting_asc" => "sorting_asc"
                    , "sorting_desc" => "sorting_desc"
                )
                , "tab" => array(
                    "menu" => "tabs"
                    , "menu-pills" => "pills"
                    , "menu-pills-justified" => "pills justified"
                    , "menu-bordered" => "tabs bordered"
                    , "menu-bordered-justified" => "tabs bordered justified"
                    , "menu-vertical" => "tabs vertical"
                    , "menu-vertical-right" => "tabs vertical right"
                    , "menu-item" => "tabs-title"
                    , "menu-item-link" => ""
                    , "menu-current" => "is-active"
                    , "pane" => "tabs-content"
                    , "pane-item" => "tabs-panel"
                    , "pane-current" => "is-active"
                    , "pane-item-effect" => "tabs-panel fade"
                    , "pane-current-effect" => "is-active"
                )
                , "collapse" => array(
                    "pane" 				=> "collapse" // da trovare analogo per foundation
                    , "current" 		=> "in" // da trovare analogo per foundation
                    , "menu" 			=> "collapsed"
                )
                , "tooltip" => array(
                    "elem" => "has-tip"
                )
                , "dialog" => array(			//da trovare analogo per foundation
                    "overlay" => "reveal-overlay"
                    , "window" => "reveal"
                    , "window-center" => "centered"
                    , "window-small" => "tiny"
                    , "window-large" => "large"
                    , "window-huge" => "full"
                    , "container" => "modal-content"
                    , "header" => "modal-header"
                    , "content" => "modal-body"
                    , "footer" => "modal-footer"
                    , "title" => "modal-title"
                    , "subtitle" => "modal-subtitle"
                    , "button" => "close"
                    , "effect" => "fade"
                )
                , "card" => array(
                    "container" => "card"
                    , "cover-top" => "card-img-top"
                    , "header" => "card-header"
                    , "body" => "card-body"
                    , "footer" => "card-footer"
                    , "title" => "card-title"
                    , "sub-title" => "card-subtitle"
                    , "text" => "card-text"
                    , "link" => "card-link"
                    , "list-group" => "list-group list-group-flush"
                    , "list-group-item" => "list-group-item"
                )
                , "util" => array(
                    "left" => "float-left"
                    , "right" => "float-right"
                    , "hide" => "hide"
                    , "align-left" => "text-left"
                    , "align-center" => "text-center"
                    , "align-right" => "text-right"
                    , "align-justify" => "text-justify"
                    , "text-nowrap" => "text-nowrap"
                    , "text-overflow" => "text-overflow"         //custom
                    , "text-lowercase" => "text-lowercase"         //custom
                    , "text-uppercase" => "text-uppercase"         //custom
                    , "text-capitalize" => "text-capitalize"    //custom

                    , "text-muted"                  => "text-muted" //custom
                    , "text-primary"                => "text-primary" //custom
                    , "text-success"                => "text-success" //custom
                    , "text-info"                   => "text-info" //custom
                    , "text-warning"                => "text-warning" //custom
                    , "text-danger"                 => "text-danger" //custom

                    , "bg-primary"                  => "bg-primary" //custom
                    , "bg-success"                  => "bg-success" //custom
                    , "bg-info"                     => "bg-info" //custom
                    , "bg-warning"                  => "bg-warning" //custom
                    , "bg-danger"                   => "bg-danger" //custom

                    , "current" => "active"
                    , "equalizer-row" => "data-equalizer"
                    , "equalizer-col" => "data-equalizer-watch"
                    , "corner-radius" => "radius"
                    , "corner-round" => "round"
                    , "corner-circle" => "img-circle"
                    , "corner-thumbnail" => "img-thumbnail"
                    , "clear" => "clearfix"
                )
                , "data" => array(
                    "tab" => array(
                        "menu" => 'data-tabs'
                        , "menu-link" => null
                        , "pane" => 'data-tabs-content'
                        , "pane-item" => null
                    ),
                    "tooltip" => array(
                        "elem" => "data-tooltip"
                    )
                    , "collapse" => array(
                        "link" => 'data-toggle' // da trovare analogo per foundation
                    )
                    , "button" => array(
                        "toggle" => 'data-toggle'
                    )
                )
            )
        );

        return ($name
            ? $framework_css_setting[$name]
            : $framework_css_setting
        );


        /*return ($name
            ? self::$config["framework"][$name]
            : self::$config["framework"]
        );*/

    }
    public static function getFrameworkName() {
        return self::$framework["name"];
    }
    public static function getFramework($name = null) {
        if(is_array($name)) {
            self::$framework = $name;
        } else if($name === false) {
            self::$framework = null;
        } elseif(strlen($name)) {
            if(strpos($name, "-fluid") !== false) {
                $arrFrameworkCss = explode("-fluid", $name);
                $framework_css_settings = self::frameworks($arrFrameworkCss[0]);

                self::$framework = array(
                    "name" => $arrFrameworkCss[0]
                    , "is_fluid" => true
                    , "class" => $framework_css_settings["class-fluid"]
                );
                self::$framework = array_replace($framework_css_settings, self::$framework);
            } elseif(strpos($name, "-") !== false) {
                $arrFrameworkCss = explode("-", $name);
                $framework_css_settings = self::frameworks($arrFrameworkCss[0]);

                self::$framework = array(
                    "name" => $arrFrameworkCss[0]
                    , "is_fluid" => false
                    , "class" => $framework_css_settings["class"]
                );
                self::$framework = array_replace($framework_css_settings, self::$framework);
            } else {
                $framework_css_settings = self::frameworks($name);
                self::$framework = array(
                    "name" => $name
                    , "is_fluid" => false
                    , "class" => $framework_css_settings["class"]
                );
                self::$framework = array_replace($framework_css_settings, self::$framework);
            }
            unset(self::$framework["class-fluid"]);
        }

        return self::$framework;
    }

    private static function fontIcons($name = null) {
        $font_icon_setting = array(
            "base" => array(
                 "css" => ""
                , "prefix" => "icon"
                , "postfix" => ""
                , "prepend" => "ico-"
                , "append" => ""
            )
            , "glyphicons" => array(
                 "css" => "http" . ($_SERVER["HTTPS"] ? "s": "") . "://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-glyphicons.css"
                , "prefix" => "glyphicons"
                , "postfix" => ""
                , "prepend" => ""
                , "append" => ""
            )
            , "fontawesome" => array(
                 "css" => "http" . ($_SERVER["HTTPS"] ? "s": "") . "://netdna.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.css"
                , "prefix" => "fa"
                , "postfix" => ""
                , "prepend" => "fa-"
                , "append" => ""
            )
        );

        return ($name
            ? $font_icon_setting[$name]
            : $font_icon_setting
        );

        /*return ($name
            ? self::$config["fonticon"][$name]
            : self::$config["fonticon"]
        );*/
    }

    public static function getFontIconName() {
        return self::$fonticon["name"];
    }
    public static function getFontIcon($name = null) {
        if(is_array($name)) {
            self::$fonticon = $name;
        } elseif($name === false) {
            self::$fonticon = null;
        } elseif(strlen($name)) {
            self::$fonticon = self::fontIcons($name);
            self::$fonticon["name"] = $name;
        }

        return self::$fonticon;
    }

    public function getResolution($type = null, $skip_default = true, $large_to_small = true, $framework_css = null) {
        $res = null;

        if($framework_css === null)
            $framework_css = $this->getFramework();

        if(is_array($framework_css)) {
            $res = $framework_css["resolution" . ($type ? "-" . $type : "")];

            $default = array_pop($res);
            if(!$skip_default && $type)
                $res["default"] = $default;

            if($large_to_small)
                $res = array_reverse($res, ($type ? true : false));
        }

        return $res;
    }

    public function getClass($def, $custom = array(), $out_tag = false) {
        if(!is_array($def))
            return "";

        $res["class"] = $def["class"];
        unset($def["class"]);

        foreach($def AS $type => $data) {
            if(is_array($data) && isset($data["params"])) {
                $value = $data["value"];
                $params = $data["params"];
            } else {
                $value = $data;
                $params = array();
            }

            $res[$type] = $this->get($value, $type, $params);
        }
        if(is_array($custom) && count($custom))
            $res = array_replace($res, $custom);

        $res = implode(" ", array_filter($res));
        if($out_tag && $res) {
            $res =  'class="' . $res . '"';
        }

        return $res;
    }

    public function getComponent($name, $custom = array(), $out_tag = false) {
        $res = null;
        $ref =& $this->findComponent($name);
        if($ref) {
            $res = $this->getClass($ref, $custom, $out_tag);
        }

        return $res;
    }

    private function getCol($resolution = array(), $is_fluid = null, $params = array(), $framework_css = null, $prefix = "col") {
        $res = "";

        if($framework_css === null)
            $framework_css = $this->getFramework();

        if(is_array($framework_css))
        {
            $framework_css_settings = $this->frameworks($framework_css["name"]);

            if($is_fluid === true) {
                $framework_css["class"] = $framework_css_settings["class-fluid"];
            } elseif($is_fluid === false) {
                $framework_css["class"] = $framework_css_settings["class"];
            }

            $skip_full = (isset($params["skip-full"])
                ? $params["skip-full"]
                : $framework_css["class"]["skip-full"]
            );
            $skip_resolution = (isset($params["skip-resolution"])
                ? $params["skip-resolution"]
                : $framework_css["class"]["skip-resolution"]
            );

            $skip_prepost = (isset($params["skip-prepost"])
                ? $params["skip-prepost"]
                : false
            );

            $arrRes = array();
            if(is_array($resolution) && count($resolution))
            {
                if(count($framework_css["resolution"]))
                {
                    $diff_resolution = count($resolution) - count($framework_css["resolution"]);
                    if($diff_resolution > 0)
                    {
                        $resolution = array_slice($resolution, $diff_resolution, count($framework_css["resolution"]));
                    }
                }

                $count_res_value = array_count_values($resolution);
                if($count_res_value[0] == count($resolution)) {
                    if($skip_full)
                        $resolution = array();
                    else
                        $resolution = array_fill(0, count($resolution), 12);
                } elseif($count_res_value[12] == count($resolution)) {
                    if($skip_full)
                        $resolution = array();
                }

                if(count($resolution)) {
                    if($framework_css["class"]["skip-resolution"]) {
                        $resolution = array_reverse($resolution);
                    }
                    $i = 0;
                    $prev_num = "";
                    $real_prefix = "";
                    foreach($resolution AS $res_num)
                    {
                        if($res_num !== $prev_num || $res_num == 0) {
                            $real_prefix = ($res_num ? $prefix . "-append" : $prefix . "-hidden");
                            if(array_key_exists($real_prefix, $framework_css["class"])
                                && strlen($framework_css["class"][$real_prefix])
                            ) {
                                $arrRes[$i] .= $framework_css["class"][$real_prefix];
                                if($i == 0)
                                    $arrRes[$i] .= $framework_css["class"][$real_prefix . "-smallest"];
                            }

                            if($res_num || array_key_exists($real_prefix, $framework_css["class"])) {
                                if(!$skip_resolution) {
                                    if(array_key_exists("resolution", $framework_css)
                                        && is_array($framework_css["resolution"]) && count($framework_css["resolution"])
                                        && array_key_exists($i, $framework_css["resolution"])
                                    ) {
                                        $arrRes[$i] .= $framework_css["resolution"][$i] . ($framework_css["resolution"][$i] && $res_num ? "-" : "");
                                    }
                                }

                                $arrRes[$i] .= $framework_css["class"][$prefix . "-prepend"];

                                if($res_num)
                                    $arrRes[$i] .= $res_num;
                            }

                            $prev_num = $res_num;
                        }

                        if($skip_resolution) {
                            break;
                        }
                        $i++;
                    }

                    if(array_key_exists(count($resolution) - 1, $arrRes)) {
                        $arrRes[count($resolution) - 1] .= $framework_css["class"][$real_prefix . "-largest"];
                    }

                }
            }

            if(strlen($framework_css["class"][$prefix . "-prefix"])) {
                array_unshift($arrRes, $framework_css["class"][$prefix . "-prefix"]);
            }

            if(strlen($framework_css["class"][$prefix . "-postfix"])) {
                $arrRes[] = $framework_css["class"][$prefix . "-postfix"];
            }

            if($skip_prepost) {
                $arrRes[] = $framework_css["class"]["skip-prepost"];
            }

            $res = implode(" ", $arrRes);
        }

        return $res;
    }
}