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
namespace ff\libs\gui;

use ff\libs\Mappable;

/**
 * Class FrameworkCss
 * @package ff\libs\gui
 */
class FrameworkCss extends Mappable
{
    protected $skip_resolution                          = true;
    protected $skip_resolution_full                     = true;
    protected $skip_prepost                             = "nopadding";

    protected $container                                = "container";
    protected $container_fluid                          = "container-fluid";
    protected $wrap                                     = "row";

    protected $row                                      = array();
    protected $col                                      = array();
    protected $push                                     = array();
    protected $pull                                     = array();
    protected $resolution                               = array();
    protected $resolution_media                         = array();
    protected $buttons_style                            = array();
    protected $form                                     = array();
    protected $bar                                      = array();
    protected $sidenav                                  = array();
    protected $list                                     = array();
    protected $topbar                                   = array();
    protected $dropdown                                 = array();
    protected $panel                                    = array();
    protected $badge                                    = array();
    protected $callout                                  = array();
    protected $pagination                               = array();
    protected $table                                    = array();
    protected $tab                                      = array();
    protected $collapse                                 = array();
    protected $tooltip                                  = array();
    protected $dialog                                   = array();
    protected $card                                     = array();
    protected $util                                     = array();
    protected $data                                     = array();
    /**
     * @var FontIcon
     */
    private $font_icon                                  = null;

    /**
     * FrameworkCss constructor.
     * @param string $map_name
     * @param string $font_icon
     * @param array|null $buttons
     */
    public function __construct(string $map_name, string $font_icon, array $buttons = null)
    {
        parent::__construct($map_name);

        $this->font_icon                                = new FontIcon($font_icon, $this->buttons_style, $buttons);
    }

    /**
     * @param null|string $value
     * @param null|string $additional_class
     * @return string
     */
    public function row(string $value = null, string $additional_class = null) : string
    {
        $res = array();

        if (!empty($this->row[$value])) {
            $res[] = $this->row[$value];
        } else {
            if (!empty($this->row["prefix"])) {
                $res[] = $this->row["prefix"];
            }

            if (!empty($value)) {
                $res[] = $value;
            }
        }

        if ($additional_class) {
            $res[] = $additional_class;
        }

        return implode(" ", $res);
    }

    /**
     * @param array $value
     * @param null|string $additional_class
     * @return string
     */
    public function col(array $value, string $additional_class = null) : string
    {
        $res                                            = $this->getClassByResolution($value, "col");
        if ($additional_class) {
            $res[] = $additional_class;
        }

        return implode(" ", $res);
    }

    /**
     * @param array $value
     * @param null|string $additional_class
     * @return string
     */
    public function push(array $value, string $additional_class = null) : string
    {
        $res                                            = $this->getClassByResolution($value, "push");
        if ($additional_class) {
            $res[] = $additional_class;
        }

        return implode(" ", $res);
    }

    /**
     * @param array $value
     * @param null|string $additional_class
     * @return string
     */
    public function pull(array $value, string $additional_class = null) : string
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
    public function wrap(string $additional_class = null) : string
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
    public function container(bool $fluid = true, string $additional_class = null) : string
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

    /**
     * @return array
     */
    public function resolutions() : array
    {
        return $this->resolution;
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function form($value, string $additional_class = null) : string
    {
        return $this->getClass($this->form, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function bar($value, string $additional_class = null) : string
    {
        return $this->getClass($this->bar, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function sidenav($value, string $additional_class = null) : string
    {
        return $this->getClass($this->sidenav, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function lists($value, string $additional_class = null) : string
    {
        return $this->getClass($this->list, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function topbar($value, string $additional_class = null) : string
    {
        return $this->getClass($this->topbar, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function dropdown($value, string $additional_class = null) : string
    {
        return $this->getClass($this->dropdown, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function panel($value, string $additional_class = null) : string
    {
        return $this->getClass($this->panel, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function badge($value, string $additional_class = null) : string
    {
        return $this->getClass($this->badge, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function callout($value, string $additional_class = null) : string
    {
        return $this->getClass($this->callout, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function pagination($value, string $additional_class = null) : string
    {
        return $this->getClass($this->pagination, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function table($value, string $additional_class = null) : string
    {
        return $this->getClass($this->table, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function tab($value, string $additional_class = null) : string
    {
        return $this->getClass($this->tab, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function collapse($value, string $additional_class = null) : string
    {
        return $this->getClass($this->collapse, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function tooltip($value, string $additional_class = null) : string
    {
        return $this->getClass($this->tooltip, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function dialog($value, string $additional_class = null) : string
    {
        return $this->getClass($this->dialog, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function card($value, string $additional_class = null) : string
    {
        return $this->getClass($this->card, $value, $additional_class);
    }

    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     */
    public function util($value, string $additional_class = null) : string
    {
        return $this->getClass($this->util, $value, $additional_class);
    }
    /**
     * @param string $type
     * @param string $value
     * @return string
     */
    public function data(string $type, string $value) : string
    {
        $res                                            = array();
        if (isset($this->data[$type])) {
            if (is_array($value)) {
                foreach ($value as $subvalue) {
                    if (!empty($this->data[$type][$subvalue])) {
                        $res[$this->data[$type][$subvalue]] = true;
                    }
                }
            } elseif (!empty($value)) {
                if (!empty($this->data[$type][$value])) {
                    $res[$this->data[$type][$value]]        = true;
                }
            }
        }

        return (empty($res)
            ? ""
            : " " . implode(" ", array_keys($res))
        );
    }
    /**
     * @param array|string $value
     * @param string|null $additional_class
     * @return string|null
     */
    public function button($value, $additional_class = null) : string
    {
        return $this->font_icon->button($value, array("class" => $additional_class));
    }

    /**
     * @param array|string $value
     * @param array|string|null $params
     * @return string|null
     */
    public function icon($value, $params = null) : ?string
    {
        return $this->font_icon->icon($value, $params);
    }

    /**
     * @param array|string $value
     * @param array|string|null $params
     * @return string|null
     */
    public function iconTag($value, $params = null) : ?string
    {
        return $this->font_icon->iconTag($value, $params);
    }
    /**
     * @param array|string|null $params
     * @return array|null
     */
    public function iconAction($params) : ?array
    {
        return $this->font_icon->action($params);
    }

    /**
     * @param array|string $value
     * @param string $type
     * @param array|string|null $additional_class
     * @return string|null
     */
    public function get($value, string $type, $additional_class = null) : string
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

    /**
     * @param array $resolution
     * @param string $type
     * @return array
     */
    private function getClassByResolution(array $resolution = array(), string $type = "col") : array
    {
        $arrRes                                         = array();
        if (!empty($resolution)) {
            if (!empty($this->resolution)) {
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

            if (!empty($resolution)) {
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
                        if (!empty($arrType[$real_prefix])) {
                            $arrRes[$i]                 .= $arrType[$real_prefix];
                            if ($i == 0 && isset($arrType[$real_prefix . "-smallest"])) {
                                $arrRes[$i]             .= $arrType[$real_prefix . "-smallest"];
                            }
                        }

                        if ($res_num || isset($arrType[$real_prefix])) {
                            if (!$this->skip_resolution
                                && !empty($this->resolution)
                                && isset($this->resolution[$i])
                            ) {
                                $arrRes[$i]         .= $this->resolution[$i] . ($this->resolution[$i] && $res_num ? "-" : "");
                            }
                            if (!empty($arrType["prepend"])) {
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
                if (!empty($arrRes)) {
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

    /**
     * @param string $name
     * @return array
     */
    private function getProperty(string $name) : array
    {
        return (method_exists($this, $name)
            ? $this->$name
            : array()
        );
    }

    /**
     * @param array $data
     * @param array|string $value
     * @param string|null $additional_class
     * @return string
     * @todo da tipizzare
     */
    private function getClass(array $data, $value, string $additional_class = null) : string
    {
        $res                                            = array();
        if (is_array($value)) {
            foreach ($value as $subvalue) {
                if (!empty($data[$subvalue])) {
                    $res[$data[$subvalue]]              = $data[$subvalue];
                }
            }
        } elseif (!empty($data[$value])) {
            $res[$data[$value]]                         = $data[$value];
        }

        if ($additional_class) {
            $res[$additional_class] = $additional_class;
        }

        return implode(" ", $res);
    }
}
