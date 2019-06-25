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

use phpformsframework\libs\Mappable;

class FontIcon extends Mappable {
    protected $button_type  = null;
    protected $css          = array();
    protected $fonts        = array();
    protected $prefix       = null;
    protected $suffix       = null;
    protected $append       = null;
    protected $prepend      = null;
    protected $actions      = array();

    private $buttons        = array();
    private $buttons_style  = array();

    public function __construct($map_name, $buttons_style = null, $buttons = null)
    {
        parent::__construct($map_name);
        $this->loadButtons();

        if($buttons_style)                              { $this->buttons_style  = $buttons_style; }
        if(is_array($buttons))                          { $this->buttons        = array_replace($this->buttons, $buttons); }
    }

    public function css() {
        return $this->css;
    }
    public function fonts() {
        return $this->fonts;
    }


    private function loadButtons() {
        if($this->button_type) {
            $this->loadMap("buttons_" . $this->button_type);
        }
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
            if (isset($this->buttons_style["color"][$type])) {
                $res["type"]                            = $this->buttons_style["color"][$type];
            }
            if (strlen($class)) {
                $res["class"]                           = $class;
            }
            if (strlen($icon)) {
                $res["icon"]                            = $this->icon($icon, $icon_params);
            }

            if(is_array($button) && count($button)) {
                foreach ($button as $btn_key => $btn_value) {
                    if(isset($this->buttons_style[$btn_key][$btn_value])) {
                        $res[$btn_key . $btn_value]     = $this->buttons_style[$btn_key][$btn_value];
                    }
                }
            }


            if(is_array($res) && count($res)) {
                if(strlen($this->buttons_style["prepend"])) {
                    $res["prepend"]                     = $this->buttons_style["prepend"];
                }
                if(strlen($this->buttons_style["append"])) {
                    $res["append"]                      = $this->buttons_style["append"];
                }
            }
        }

        return (is_array($res)
            ? implode(" " , $res)
            : null
        );
    }
}

