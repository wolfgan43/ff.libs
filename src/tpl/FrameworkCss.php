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

class FrameworkCss extends Mappable
{
    protected $css                                          = array();
    protected $js                                           = array();
    protected $fonts                                        = array();
    protected $skip_resolution                              = true;
    protected $skip_resolution_full                         = true;
    protected $skip_prepost                                 = "nopadding";

    protected $container                                    = "container";
    protected $container_fluid                              = "container-fluid";
    protected $wrap                                         = "row";

    protected $row                                          = array();
    protected $col                                          = array();
    protected $push                                         = array();
    protected $pull                                         = array();
    protected $resolution                                   = array();
    protected $resolution_media                             = array();
    protected $buttons_style                                = array();
    protected $form                                         = array();
    protected $bar                                          = array();
    protected $sidenav                                      = array();
    protected $list                                         = array();
    protected $topbar                                       = array();
    protected $dropdown                                     = array();
    protected $panel                                        = array();
    protected $badge                                        = array();
    protected $callout                                      = array();
    protected $pagination                                   = array();
    protected $table                                        = array();
    protected $tab                                          = array();
    protected $collapse                                     = array();
    protected $tooltip                                      = array();
    protected $dialog                                       = array();
    protected $card                                         = array();
    protected $util                                         = array();
    protected $data                                         = array();
    /**
     * @var FontIcon
     */
    private $font_icon      = null;

    public function __construct($map_name, $font_icon, $buttons = null)
    {
        parent::__construct($map_name);

        $this->font_icon    = new FontIcon($font_icon, $this->buttons_style, $buttons);

        $this->css          = array_replace($this->css(), $this->font_icon->css());
        $this->fonts        = array_replace($this->fonts(), $this->font_icon->fonts());
    }

    public function css()
    {
        return $this->css;
    }
    public function js()
    {
        return $this->js;
    }
    public function fonts()
    {
        return $this->fonts;
    }
    /**
     * @param null|string $value
     * @param null|string $additional_class
     * @return string
     */
    public function row($value = null, $additional_class = null)
    {
        $res = array();

        if (isset($this->row[$value]) && strlen($this->row[$value])) {
            $res[] = $this->row[$value];
        } else {
            if (isset($this->row["prefix"]) && strlen($this->row["prefix"])) {
                $res[] = $this->row["prefix"];
            }

            if (strlen($value)) {
                $res[] = $value;
            }
        }

        if ($additional_class) {
            $res[] = $additional_class;
        }

        return implode(" ", $res);
    }

    /**
     * @param string $value
     * @param null|string $additional_class
     * @return string
     */
    public function col($value, $additional_class = null)
    {
        $res                                            = $this->getClassByResolution($value, "col");
        if ($additional_class) {
            $res[] = $additional_class;
        }

        return implode(" ", $res);
    }

    /**
     * @param string $value
     * @param null|string $additional_class
     * @return string
     */
    public function push($value, $additional_class = null)
    {
        $res                                            = $this->getClassByResolution($value, "push");
        if ($additional_class) {
            $res[] = $additional_class;
        }

        return implode(" ", $res);
    }

    /**
     * @param string $value
     * @param null|string $additional_class
     * @return string
     */
    public function pull($value, $additional_class = null)
    {
        $res                                            = $this->getClassByResolution($value, "pull");
        if ($additional_class) {
            $res[] = $additional_class;
        }

        return implode(" ", $res);
    }

    /**
     * @param null|string $additional_class
     * @return string
     */
    public function wrap($additional_class = null)
    {
        $res                                            = array();
        if ($this->wrap) {
            $res[] = $this->wrap;
        }
        if ($additional_class) {
            $res[] = $additional_class;
        }

        return implode(" ", $res);
    }

    /**
     * @param bool $fluid
     * @param null|string $additional_class
     * @return string
     */
    public function container($fluid = true, $additional_class = null)
    {
        $res                                            = array();
        $containerCurrent                               = (
            $fluid
                                                            ? $this->container_fluid
                                                            : $this->container
                                                        );
        if ($this->container) {
            $res[] = $containerCurrent;
        }
        if ($additional_class) {
            $res[] = $additional_class;
        }

        return implode(" ", $res);
    }
    public function resolutions()
    {
        return $this->resolution;
    }
    public function form($value, $additional_class = null)
    {
        return $this->getClass($this->form, $value, $additional_class);
    }

    public function bar($value, $additional_class = null)
    {
        return $this->getClass($this->bar, $value, $additional_class);
    }
    public function sidenav($value, $additional_class = null)
    {
        return $this->getClass($this->sidenav, $value, $additional_class);
    }
    public function lists($value, $additional_class = null)
    {
        return $this->getClass($this->list, $value, $additional_class);
    }
    public function topbar($value, $additional_class = null)
    {
        return $this->getClass($this->topbar, $value, $additional_class);
    }
    public function dropdown($value, $additional_class = null)
    {
        return $this->getClass($this->dropdown, $value, $additional_class);
    }
    public function panel($value, $additional_class = null)
    {
        return $this->getClass($this->panel, $value, $additional_class);
    }
    public function badge($value, $additional_class = null)
    {
        return $this->getClass($this->badge, $value, $additional_class);
    }
    public function callout($value, $additional_class = null)
    {
        return $this->getClass($this->callout, $value, $additional_class);
    }
    public function pagination($value, $additional_class = null)
    {
        return $this->getClass($this->pagination, $value, $additional_class);
    }
    public function table($value, $additional_class = null)
    {
        return $this->getClass($this->table, $value, $additional_class);
    }
    public function tab($value, $additional_class = null)
    {
        return $this->getClass($this->tab, $value, $additional_class);
    }
    public function collapse($value, $additional_class = null)
    {
        return $this->getClass($this->collapse, $value, $additional_class);
    }
    public function tooltip($value, $additional_class = null)
    {
        return $this->getClass($this->tooltip, $value, $additional_class);
    }
    public function dialog($value, $additional_class = null)
    {
        return $this->getClass($this->dialog, $value, $additional_class);
    }
    public function card($value, $additional_class = null)
    {
        return $this->getClass($this->card, $value, $additional_class);
    }
    public function util($value, $additional_class = null)
    {
        return $this->getClass($this->util, $value, $additional_class);
    }
    /**
     * @param string $type
     * @param string $value
     * @return string
     */
    public function data($type, $value)
    {
        $res                                            = array();
        if (isset($this->data[$type])) {
            if (is_array($value)) {
                foreach ($value as $subvalue) {
                    if (isset($this->data[$type][$subvalue]) && strlen($this->data[$type][$subvalue])) {
                        $res[$this->data[$type][$subvalue]] = true;
                    }
                }
            } elseif (strlen($value)) {
                if (isset($this->data[$type][$value]) && strlen($this->data[$type][$value])) {
                    $res[$this->data[$type][$value]]        = true;
                }
            }
        }

        return (is_array($res) && count($res)
            ? " " . implode(" ", array_keys($res))
            : ""
        );
    }
    public function button($value, $additional_class = null)
    {
        return $this->button($value, array("class" => $additional_class));
    }

    public function icon($value, $additional_class = null)
    {
        return $this->font_icon->icon($value, $additional_class);
    }
    public function iconTag($value, $additional_class = null)
    {
        return $this->font_icon->iconTag($value, $additional_class);
    }
    public function iconAction($params)
    {
        return $this->font_icon->action($params);
    }

    public function get($value, $type, $additional_class = null)
    {
        switch ($type) {
            case "button":
            case "link":
                $res                                    = $this->button($value, $additional_class);
                break;
            case "icon-tag":
            case "icon-link-tag":
            case "icon-link-tag-default":
                if (substr($value, "0", 1) === "&") {
                    return $value;
                }

                $res                                    = $this->iconTag($value, $additional_class);
                break;
            case "icon":
            case "icon-default":
                $res                                    = $this->icon($value, $additional_class);
                break;
            case "container-fluid":
                $res                                    = $this->container($value, true);
                break;
            case "container":
                $res                                    = $this->container($value, false);
                break;
            case "col-default":
            case "col":
            case "col-fluid":
                $res                                    = $this->col($value, $additional_class);
                break;
            case "wrap-default":
            case "wrap":
            case "wrap-fluid":
                $res                                    = $this->wrap($value);
                break;
            case "push-default":
            case "push":
            case "push-fluid":
                $res                                    = $this->push($value, $additional_class);
                break;
            case "pull-default":
            case "pull":
            case "pull-fluid":
                $res                                    = $this->pull($value, $additional_class);
                break;
            case "row-default":
            case "row":
            case "row-fluid":
                $res                                    = $this->row($value, $additional_class);

                break;

            case "data":
                $res                                    = $this->data($additional_class, $value);
                break;
            default:
                $res                                    = $this->getClass($this->getProperty($type), $value, $additional_class);
        }

        return $res;
    }



    private function getClassByResolution($resolution = array(), $type = "col")
    {
        $arrRes                                         = array();
        if (is_array($resolution) && count($resolution)) {
            if (is_array($this->resolution) && count($this->resolution)) {
                $diff_resolution = count($resolution) - count($this->resolution);
                if ($diff_resolution > 0) {
                    $resolution                         = array_slice($resolution, $diff_resolution, count($this->resolution));
                }
            }

            $count_res_value                            = array_count_values($resolution);
            if ($count_res_value[0] == count($resolution)) {
                if ($this->skip_resolution_full) {
                    $resolution                         = array();
                } else {
                    $resolution                         = array_fill(0, count($resolution), 12);
                }
            } elseif ($count_res_value[12] == count($resolution)) {
                if ($this->skip_resolution_full) {
                    $resolution                         = array();
                }
            }

            if (is_array($resolution) && count($resolution)) {
                if ($this->skip_resolution) {
                    $resolution                         = array_reverse($resolution);
                }

                $arrType                                = $this->getProperty($type);
                $i                                      = 0;
                $prev_num                               = "";
                $real_prefix                            = "";
                foreach ($resolution as $res_num) {
                    if ($res_num !== $prev_num || $res_num == 0) {
                        $real_prefix                    = (
                            $res_num
                                                            ? "append"
                                                            : "hidden"
                                                        );
                        if (isset($arrType[$real_prefix]) && strlen($arrType[$real_prefix])) {
                            $arrRes[$i]                 .= $arrType[$real_prefix];
                            if ($i == 0 && isset($arrType[$real_prefix . "-smallest"])) {
                                $arrRes[$i]             .= $arrType[$real_prefix . "-smallest"];
                            }
                        }

                        if ($res_num || isset($arrType[$real_prefix])) {
                            if (!$this->skip_resolution
                                && is_array($this->resolution) && count($this->resolution)
                                && isset($this->resolution[$i])
                            ) {
                                $arrRes[$i]         .= $this->resolution[$i] . ($this->resolution[$i] && $res_num ? "-" : "");
                            }
                            if (isset($arrType["prepend"]) && strlen($arrType["prepend"])) {
                                $arrRes[$i]             .= $arrType["prepend"];
                            }

                            if ($res_num) {
                                $arrRes[$i]             .= $res_num;
                            }
                        }

                        $prev_num                       = $res_num;
                    }

                    if ($this->skip_resolution) {
                        break;
                    }
                    $i++;
                }
                if (is_array($arrRes) && count($arrRes)) {
                    if (array_key_exists(count($resolution) - 1, $arrRes) && isset($arrType[$real_prefix . "-largest"])) {
                        $arrRes[count($resolution) - 1]     .= $arrType[$real_prefix . "-largest"];
                    }

                    if (isset($arrType["prefix"])) {
                        $arrRes[] = $arrType["prefix"];
                    }
                    if (isset($arrType["suffix"])) {
                        $arrRes[] = $arrType["suffix"];
                    }
                }
            }
        }

        if ($this->skip_prepost) {
            $arrRes[] = $this->skip_prepost;
        }

        return $arrRes;
    }

    private function getProperty($name)
    {
        return (method_exists($this, $name)
            ? $this->$name
            : array()
        );
    }

    private function getClass($data, $value, $additional_class = null)
    {
        $res                                            = array();
        if (is_array($value)) {
            foreach ($value as $subvalue) {
                if (isset($data[$subvalue]) && strlen($data[$subvalue])) {
                    $res[$data[$subvalue]]              = $data[$subvalue];
                }
            }
        } elseif (isset($data[$value]) && strlen($data[$value])) {
            $res[$data[$value]]                         = $data[$value];
        }

        if ($additional_class) {
            $res[$additional_class] = $additional_class;
        }

        return implode(" ", $res);
    }
}
