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
namespace phpformsframework\libs\tpl\gridsystem;

abstract class FontIconAdapter {
    protected $css          = array();
    protected $fonts        = array();
    protected $prefix       = null;
    protected $suffix       = null;
    protected $append       = null;
    protected $prepend      = null;
    protected $buttons      = array(
            //ffRecord
                            "ActionButtonInsert"    => array(
                                                        "default"       => "success"
                                                        , "icon"        => "check"
                                                        , "class"       => "insert"
                                                    )
                            , "ActionButtonUpdate"  => array(
                                                        "default"       => "success"
                                                        , "icon"        => "check"
                                                        , "class"       => "update"
                                                    )
                            , "ActionButtonDelete"  => array(
                                                        "default"       => "danger"
                                                        , "icon"        => "trash-o"
                                                        , "class"       => "delete"
                                                    )
                            , "ActionButtonCancel"  => array(
                                                        "default"       => "link"
                                                        , "class"       => "cancel"
                                                    )
                            , "insert"              => array(
                                                        "default"       => "success"
                                                        , "class"       => "activebuttons"
                                                        , "icon"        => "check"
                                                    )
                            , "update"              => array(
                                                        "default"       => "success"
                                                        , "class"       => "activebuttons"
                                                        , "icon"        => "check"
                                                    )
                            , "delete"              => array(
                                                        "default"       => "danger"
                                                        , "class"       => "activebuttons"
                                                        , "icon"        => "trash-o"
                                                    )
                            , "cancel"              => array(
                                                        "default"       => "link"
                                                        , "icon"        => "times"
                                                    )
                            , "print"               => array(
                                                        "default"       => "default"
                                                        , "class"       => "print"
                                                        , "icon"        => "print"
                                                    )
                            //ffGrid
                            , "search"              => array(
                                                        "default"       => "primary"
                                                        , "class"       => "search"
                                                        , "icon"        => "search"
                                                    )
                            , "searchadv"           => array(
                                                        "default"       => "primary"
                                                        , "class"       => "search"
                                                        , "icon"        => "search"
                                                    )
                            , "searchdropdown"     => array(
                                                        "default"       => "secondary"
                                                        , "class"       => "more dropdown-toggle"
                                                    )
                            , "more"                => array(
                                                        "default"       => "link"
                                                        , "class"       => "more"
                                                        , "icon"        => "caret-down"
                                                    )
                            , "export"              => array(
                                                        "default"       => "default"
                                                        , "class"       => "export"
                                                        , "icon"        => "download"
                                                    )
                            , "sort"                => array(
                                                        "default"       => "link"
                                                        , "class"       => "sort"
                                                        , "icon"        => "sort"
                                                    )
                            , "sort-asc"            => array(
                                                        "default"       => "link"
                                                        , "class"       => "sort asc"
                                                        , "icon"        => "sort-asc"
                                                    )
                            , "sort-desc"           => array(
                                                        "default"       => "link"
                                                        , "class"       => "sort desc"
                                                        , "icon"        => "sort-desc"
                                                    )
                            , "addnew"              => array(
                                                        "default"       => "primary"
                                                        , "class"       => "addnew"
                                                        , "icon"        => "plus"
                                                    )
                            , "editrow"             => array(
                                                        "default"       => "link"
                                                        , "class"       => "edit"
                                                        , "icon"        => "pencil"
                                                    )
                            , "deleterow"           => array(
                                                        "default"       => "danger"
                                                        , "class"       => "delete"
                                                        , "icon"        => "trash-o"
                                                    )
                            , "deletetabrow"        => array(
                                                        "default"       => null
                                                        , "class"       => "delete"
                                                        , "icon"        => "trash-o"
                                                    )
                            //ffDetail
                            , "addrow"              => array(
                                                        "default"       => "primary"
                                                        , "icon"        => "plus"
                                                    )
                            //ffPageNavigator
                            , "first"               => array(
                                                        "default"       => "link"
                                                        , "class"       => "first"
                                                        , "icon"        => "step-backward"
                                                    )
                            , "last"                => array(
                                                        "default"       => "link"
                                                        , "class"       => "last"
                                                        , "icon"        => "step-forward"
                                                    )
                            , "prev"                => array(
                                                        "default"       => "link"
                                                        , "class"       => "prev"
                                                        , "icon"        => "play"
                                                        , "icon_params" => "flip-horizontal"
                                                    )
                            , "next"                => array(
                                                        "default"       => "link"
                                                        , "class"       => "next"
                                                        , "icon"        => "play"
                                                    )
                            , "prev-frame"          => array(
                                                        "default"       => "link"
                                                        , "class"       => "prev-frame"
                                                        , "icon"        => "backward"
                                                    )
                            , "next-frame"          => array(
                                                        "default"       => "link"
                                                        , "class"       => "next-frame"
                                                        , "icon"        => "forward"
                                                    )

                           //other
                            , "pdf"                 => array(
                                                        "default"       => "link"
                                                        , "class"       => "pdf"
                                                        , "icon"        => "file-pdf-o"
                                                    )
                            , "email"               => array(
                                                        "default"       => "link"
                                                        , "class"       => "email"
                                                        , "icon"        => "envelope-o"
                                                    )
                            , "preview"             => array(
                                                        "default"       => "link"
                                                        , "class"       => "preview"
                                                        , "icon"        => "search"
                                                    )
                            , "preview-email"       => array(
                                                        "default"       => "link"
                                                        , "class"       => "email"
                                                        , "icon"        => "envelope-o"
                                                    )
                            , "refresh"		        => array(
                                                        "default"       => "link"
                                                        , "class"       => "refresh"
                                                        , "icon"        => "refresh"
                                                    )
                            , "clone"               => array(
                                                        "default"       => "link"
                                                        , "class"       => "clone"
                                                        , "icon"        => "copy"
                                                    )
                            , "permissions"         => array(
                                                        "default"       => "link"
                                                        , "class"       => "permissions"
                                                        , "icon"        => "lock"
                                                    )
                            , "relationships"       => array(
                                                        "default"       => "link"
                                                        , "class"       => "relationships"
                                                        , "icon"        => "share-alt"
                                                    )
                            , "settings"            => array(
                                                        "default"       => "link"
                                                        , "class"       => "settings"
                                                        , "icon"        => "cog"
                                                    )
                            , "properties"          => array(
                                                        "default"       => "link"
                                                        , "class"       => "properties"
                                                        , "icon"        => "object-group"
                                                    )
                            , "help"                => array(
                                                        "default"       => "link"
                                                        , "class"       => "helper"
                                                        , "icon"        => "question-circle"
                                                    )
                            , "noimg"               => array(
                                                        "default"       => "link"
                                                        , "class"       => "noimg"
                                                        , "icon"        => "picture-o"
                                                    )
                            , "checked"        	    => array(
                                                        "default"       => "link"
                                                        , "class"       => "checked"
                                                        , "icon"        => "check-circle-o"
                                                    )
                            , "unchecked"           => array(
                                                        "default"       => "link"
                                                        , "class"       => "unchecked"
                                                        , "icon"        => "circle-o"
                                                    )
                            , "exanded"             => array(
                                                        "default"       => "link"
                                                        , "class"       => "exanded"
                                                        , "icon"        => "minus-square-o"
                                                    )
                            , "retracted"           => array(
                                                        "default"       => "link"
                                                        , "class"       => "retracted"
                                                        , "icon"        => "plus-square-o"
                                                    )


                            //CMS Ecommerce
                            , "history"             => array(
                                                        "default"       => "link"
                                                        , "class"       => "history"
                                                        , "icon"        => "history"
                                                    )
                            , "payments"            => array(
                                                        "default"       => "link"
                                                        , "class"       => "payments"
                                                        , "icon"        => "credit-card"
                                                    )
                            //CMS
                            , "vg-admin"            => array(
                                                        "default"       => "link"
                                                        , "class"       => "admin"
                                                        , "icon"        => "cog"
                                                        , "icon_params" => "2x"
                                                    )
                            , "vg-restricted"       => array(
                                                        "default"       => "link"
                                                        , "class"       => "restricted"
                                                        , "icon"        => "unlock-alt"
                                                        , "icon_params" => "2x"
                                                    )
                            , "vg-manage"           => array(
                                                        "default"       => "link"
                                                        , "class"       => "manage"
                                                        , "icon"        => "shopping-cart"
                                                        , "icon_params" => "2x"
                                                    )
                            , "vg-fontend"          => array(
                                                        "default"       => "link"
                                                        , "class"       => "fontend"
                                                        , "icon"        => "desktop"
                                                        , "icon_params" => "2x"
                                                    )
                            , "vg-static-menu"      => array(
                                                        "default"       => "link"
                                                        , "class"       => "static-menu"
                                                        , "icon"        => "static-menu"
                                                    )
                            , "vg-gallery-menu"     => array(
                                                        "default"       => "link"
                                                        , "class"       => "gallery-menu"
                                                        , "icon"        => "gallery-menu"
                                                    )
                            , "vg-vgallery-menu"    => array(
                                                        "default"       => "link"
                                                        , "class"       => "vgallery-menu"
                                                        , "icon"        => "vgallery-menu"
                                                    )
                            , "vg-vgallery-group"   => array(
                                                        "default"       => "link"
                                                        , "class"       => "vgallery-group"
                                                        , "icon"        => "vgallery-group"
                                                    )
                            , "vg-gallery"          => array(
                                                        "default"       => "link"
                                                        , "class"       => "gallery"
                                                        , "icon"        => "gallery"
                                                    )
                            , "vg-draft"            => array(
                                                        "default"       => "link"
                                                        , "class"       => "draft"
                                                        , "icon"        => "draft"
                                                    )
                            , "vg-file"             => array(
                                                        "default"       => "link"
                                                        , "class"       => "file"
                                                        , "icon"        => "file"
                                                    )
                            , "vg-virtual-gallery"  => array(
                                                        "default"       => "link"
                                                        , "class"       => "virtual-gallery"
                                                        , "icon"        => "virtual-gallery"
                                                    )
                            , "vg-publishing"       => array(
                                                        "default"       => "link"
                                                        , "class"       => "publishing"
                                                        , "icon"        => "publishing"
                                                    )
                            , "vg-vgallery-rel"     => array(
                                                        "default"       => "link"
                                                        , "class"       => "vgallery-rel"
                                                        , "icon"        => "vgallery-rel"
                                                    )
                            , "vg-cart"             => array(
                                                        "default"       => "link"
                                                        , "class"       => "cart"
                                                        , "icon"        => "cart"
                                                    )
                            , "vg-lang"             => array(
                                                        "default"       => "link"
                                                        , "class"       => "lang"
                                                        , "icon"        => "lang"
                                                    )
                            , "vg-search"           => array(
                                                        "default"       => "link"
                                                        , "class"       => "search"
                                                        , "icon"        => "search"
                                                    )
                            , "vg-login"            => array(
                                                        "default"       => "link"
                                                        , "class"       => "login"
                                                        , "icon"        => "login"
                                                    )
                            , "vg-breadcrumb"       => array(
                                                        "default"       => "link"
                                                        , "class"       => "breadcrumb"
                                                        , "icon"        => "breadcrumb"
                                                    )
                            , "vg-profile"          => array(
                                                        "default"       => "link"
                                                        , "class"       => "profile"
                                                        , "icon"        => "profile"
                                                    )
                            , "vg-modules"          => array(
                                                        "default"       => "link"
                                                        , "addClass"    => "module"
                                                        , "icon"        => "module"
                                                    )
                            , "vg-applets"          => array(
                                                        "default"       => "link"
                                                        , "class"       => "applets"
                                                        , "icon"        => "applets"
                                                    )
                            , "lay-addnew"          => array(
                                                        "default"       => "link"
                                                        , "class"       => "lay-addnew"
                                                        , "icon"        => "lay-addnew"
                                                    )
                            , "lay"                 => array(
                                                        "default"       => "link"
                                                        , "class"       => "lay-unknown"
                                                        , "icon"        => "lay"
                                                    )
                            , "lay-31"              => array(
                                                        "default"       => "link"
                                                        , "class"       => "lay-top"
                                                        , "icon"        => "lay-31"
                                                    )
                            , "lay-13"              => array(
                                                        "default"       => "link"
                                                        , "class"       => "lay-left"
                                                        , "icon"        => "lay-13"
                                                    )
                            , "lay-3133"            => array(
                                                        "default"       => "link"
                                                        , "class"       => "lay-right"
                                                        , "icon"        => "lay-3133"
                                                    )
                            , "lay-1333"            => array(
                                                        "default"       => "link"
                                                        , "class"       => "lay-right"
                                                        , "icon"        => "lay-1333"
                                                    )
                            , "lay-2233"            => array(
                                                        "default"       => "link"
                                                        , "class"       => "lay-content"
                                                        , "icon"        => "lay-2233"
                                                    )
                            , "js"                  => array(
                                                        "default"       => "link"
                                                        , "icon"        => "js"
                                                    )
                            , "css"                 => array(
                                                        "default"       => "link"
                                                         , "icon"       => "css"
                                                    )
                            , "seo"                 => array(
                                                        "default"       => "link"
                                                         , "icon"       => "seo"
                                                    )
                        );
    protected $actions      = array(
                                "xs"                    => "xs"
                                , "sm"                  => "sm"
                                , "lg"                  => "lg"
                                , "2x"                  => "2x"
                                , "3x"                  => "3x"
                                , "5x"                  => "5x"
                                , "7x"                  => "7x"
                                , "10x"                 => "10x"
                                , "rotate-90"           => "rotate-90"
                                , "rotate-180"          => "rotate-180"
                                , "rotate-270"          => "rotate-270"
                                , "flip-horizontal"     => "rotate-horizontal"
                                , "flip-vertical"       => "rotate-vertical"
                                , "flip-both"           => "rotate-both"
                                , "spin"                => "spin"
                                , "pulse"               => "spin"
                                , "border"              => "border"
                                , "inverse"             => "inverse"
                                , "transparent"         => "transparent"
                                , "stack"               => "stack"
                                , "stack-2x"            => "stack-2x"
                                , "stack-1x"            => "stack-1x"
                            );

    private $rules         = array(
                                "prefix"               => "btn"
                                , "suffix"              => ""
                                , "width"               => array(
                                    "full"              => "expand"
                                )
                                , "size"                => array(
                                    "large"             => "large"
                                    , "small"           => "small"
                                    , "tiny"            => "tiny"
                                )
                                , "state"               => array(
                                    "current"           => "current"
                                    , "disabled"        => "disabled"
                                )
                                , "corner"              => array(
                                    "round"             => "round"
                                    , "radius"          => "radius"
                                )
                                , "color"               => array(
                                    "default"           => ""
                                    , "primary"         => "primary"
                                    , "secondary"       => ""
                                    , "success"         => "success"
                                    , "info"            => "info"
                                    , "warning"         => "warning"
                                    , "danger"          => "danger"
                                    , "link"            => "link"
                                )
                            );

    public function __construct($rules = null, $buttons = null)
    {
        if($rules)               { $this->rules         = $rules; }
        if(is_array($buttons))   { $this->buttons       = array_replace($this->buttons, $buttons); }
    }

    public function css() {
        return $this->css;
    }
    public function fonts() {
        return $this->fonts;
    }

    public function action($params, $use_source = false) {
        $res                                            = null;
        if ($params) {
            if (!is_array($params)) {
                $params                                 = explode(" ", $params);
            }
            foreach ($params as $param) {
                if (isset($this->actions[$param])) {
                    $res[$this->actions[$param]]        = $this->actions[$param];
                } elseif($use_source) {
                    $res[$param]                        = $param;
                }

            }
        }
        return $res;
    }

    private function iconsTagStack($values, $params = null) {
        $res                                            = null;
        $items                                          = null;
        $container                                      = $this->action($params, true);
        $container[$this->actions["stack"]]             = $this->actions["stack"];

        if(is_array($values) && count($values)) {
            foreach ($values AS $i => $value) {
                $stack_size                             = (isset($this->actions["stack-" . ($i + 1) . "x"])
                                                            ? $this->actions["stack-" . ($i + 1) . "x"]
                                                            : null
                                                        );

                $items[]                                = $this->iconTag($value, $stack_size);
            }
        }

        return (is_array($items) && count($items)
            ? '<span class="' . implode(" ", $container) . '">' . implode("", $items) . '</span>'
            : null
        );
    }

    public function iconTag($value, $params = null) {
        $res                                            = $this->icon($value, $params);

        return (strlen($res)
            ? '<i class="' . $res . '"></i>'
            : null
        );
    }
    public function icon($value, $params = null) {
        $res                                            = null;

        if(is_array($value)) {
            $res                                        = $this->iconsTagStack($value, $params);
        } elseif($value) {
            $res                                        = $this->action($params, true);

            if(strlen($value)) {
                $res[]                                  = $this->append . $value . $this->prepend;
            }

            if(is_array($res) && count($res)) {
                if(strlen($this->prefix)) {
                    $res[$this->prefix]                 = $this->prefix;
                }
                if(strlen($this->suffix)) {
                    $res[$this->suffix]                 = $this->suffix;
                }
            }
        } else {
            $res                                        = $this->action($params);
        }

        return (is_array($res)
            ? implode(" " , $res)
            : null
        );
    }

    public function button($value, $params = null) {
        $res                                            = null;
        if(isset($this->buttons[$value])) {
            $type                                       = $this->buttons[$value]["default"];
            $icon                                       = (isset($this->buttons[$value]["icon"])
                                                            ? $this->buttons[$value]["icon"]
                                                            : null
                                                        );
            $icon_params                                = (isset($this->buttons[$value]["icon_params"])
                                                            ? $this->buttons[$value]["icon_params"]
                                                            : null
                                                        );
            $class                                      = (isset($this->buttons[$value]["class"])
                                                            ? $this->buttons[$value]["class"]
                                                            : null
                                                        );
            $button                                     = (isset($this->buttons[$value]["button"])
                                                            ? $this->buttons[$value]["button"]
                                                            : null
                                                        );
        } else {
            $type                                       = $value;
            $icon                                       = (isset($params["icon"])
                                                            ? $params["icon"]
                                                            : null
                                                        );
            $icon_params                                = (isset($params["icon_params"])
                                                            ? $params["icon_params"]
                                                            : null
                                                        );
            $class                                      = (isset($params["class"])
                                                            ? $params["class"]
                                                            : null
                                                        );
            $button                                     = (isset($params["button"])
                                                            ? $params["button"]
                                                            : null
                                                        );

        }

        if($type) {
            if (isset($this->rules["color"][$type])) {
                $res["type"]                            = $this->rules["color"][$type];
            }
            if (strlen($class)) {
                $res["class"]                           = $class;
            }
            if (strlen($icon)) {
                $res["icon"]                            = $this->icon($icon, $icon_params);
            }

            if(is_array($button) && count($button)) {
                foreach ($button as $btn_key => $btn_value) {
                    if(isset($this->rules[$btn_key][$btn_value])) {
                        $res[$btn_key . $btn_value]     = $this->rules[$btn_key][$btn_value];
                    }
                }
            }


            if(is_array($res) && count($res)) {
                if(strlen($this->rules["prepend"])) {
                    $res["prepend"]                     = $this->rules["prepend"];
                }
                if(strlen($this->rules["append"])) {
                    $res["append"]                      = $this->rules["append"];
                }
            }
        }

        return (is_array($res)
            ? implode(" " , $res)
            : null
        );
    }
}

