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
 * Class FontIcon
 * @package ff\libs\gui
 */
class FontIcon extends Mappable
{
    protected $button_type  = null;
    protected $prefix       = null;
    protected $suffix       = null;
    protected $append       = null;
    protected $prepend      = null;
    protected $actions      = array();

    private $buttons        = array();
    private $buttons_style  = array();

    /**
     * FontIcon constructor.
     * @param string $map_name
     * @param array|null $buttons_style
     * @param array|null $buttons
     */
    public function __construct(string $map_name, array $buttons_style = null, array $buttons = null)
    {
        parent::__construct($map_name);

        if ($this->button_type) {
            $this->loadMaps(["buttons_" . $this->button_type]);
        }
        
        if ($buttons_style) {
            $this->buttons_style  = $buttons_style;
        }
        if (is_array($buttons)) {
            $this->buttons        = array_replace($this->buttons, $buttons);
        }
    }

    /**
     * @param array|string $params
     * @param bool $use_source
     * @return array|null
     * @todo da tipizzare
     */
    public function action($params, bool $use_source = false) : ?array
    {
        $res                                            = null;
        if ($params) {
            if (!is_array($params)) {
                $params                                 = explode(" ", $params);
            }
            foreach ($params as $param) {
                if (isset($this->actions[$param])) {
                    $res[$this->actions[$param]]        = $this->actions[$param];
                } elseif ($use_source) {
                    $res[$param]                        = $param;
                }
            }
        }
        return $res;
    }

    /**
     * @param array $values
     * @param array|string|null $params
     * @return string|null
     * @todo da tipizzare
     */
    private function iconsTagStack(array $values, $params = null) : ?string
    {
        $items                                          = null;
        $container                                      = $this->action($params, true);
        $container[$this->actions["stack"]]             = $this->actions["stack"];

        if (!empty($values)) {
            foreach ($values as $i => $value) {
                $stack_size                             = (
                    isset($this->actions["stack-" . ($i + 1) . "x"])
                                                            ? $this->actions["stack-" . ($i + 1) . "x"]
                                                            : null
                                                        );

                $items[]                                = $this->iconTag($value, $stack_size);
            }
        }

        return (!empty($items)
            ? '<span class="' . implode(" ", $container) . '">' . implode("", $items) . '</span>'
            : null
        );
    }

    /**
     * @param array|string $value
     * @param array|string|null $params
     * @return string|null
     * @todo da tipizzare
     */
    public function iconTag($value, $params = null) : ?string
    {
        $res                                            = $this->icon($value, $params);

        return (!empty($res)
            ? '<span class="' . $res . '"></span>'
            : null
        );
    }

    /**
     * @param array|string $value
     * @param array|string|null $params
     * @return string|null
     * @todo da tipizzare
     */
    public function icon($value, $params = null) : ?string
    {
        $res                                            = null;

        if (is_array($value)) {
            $res                                        = $this->iconsTagStack($value, $params);
        } elseif ($value) {
            $res                                        = $this->action($params, true);

            if (!empty($value)) {
                $res[]                                  = $this->append . $value . $this->prepend;
            }

            if (!empty($res)) {
                if (!empty($this->prefix)) {
                    $res[$this->prefix]                 = $this->prefix;
                }
                if (!empty($this->suffix)) {
                    $res[$this->suffix]                 = $this->suffix;
                }
            }
        } else {
            $res                                        = $this->action($params);
        }

        return (is_array($res)
            ? implode(" ", $res)
            : null
        );
    }

    /**
     * @param array|string $value
     * @param array|string|null $params
     * @return string|null
     * @todo da tipizzare
     */
    public function button($value, $params = null) : ?string
    {
        $res                                            = null;
        if (isset($this->buttons[$value])) {
            $type                                       = $this->buttons[$value]["default"];
            $icon                                       = (
                isset($this->buttons[$value]["icon"])
                                                            ? $this->buttons[$value]["icon"]
                                                            : null
                                                        );
            $icon_params                                = (
                isset($this->buttons[$value]["icon_params"])
                                                            ? $this->buttons[$value]["icon_params"]
                                                            : null
                                                        );
            $class                                      = (
                isset($this->buttons[$value]["class"])
                                                            ? $this->buttons[$value]["class"]
                                                            : null
                                                        );
            $button                                     = (
                isset($this->buttons[$value]["button"])
                                                            ? $this->buttons[$value]["button"]
                                                            : null
                                                        );
        } else {
            $type                                       = $value;
            $icon                                       = (
                isset($params["icon"])
                                                            ? $params["icon"]
                                                            : null
                                                        );
            $icon_params                                = (
                isset($params["icon_params"])
                                                            ? $params["icon_params"]
                                                            : null
                                                        );
            $class                                      = (
                isset($params["class"])
                                                            ? $params["class"]
                                                            : null
                                                        );
            $button                                     = (
                isset($params["button"])
                                                            ? $params["button"]
                                                            : null
                                                        );
        }

        if ($type) {
            if (isset($this->buttons_style["color"][$type])) {
                $res["type"]                            = $this->buttons_style["color"][$type];
            }
            if (!empty($class)) {
                $res["class"]                           = $class;
            }
            if (!empty($icon)) {
                $res["icon"]                            = $this->icon($icon, $icon_params);
            }

            if (!empty($button)) {
                foreach ($button as $btn_key => $btn_value) {
                    if (isset($this->buttons_style[$btn_key][$btn_value])) {
                        $res[$btn_key . $btn_value]     = $this->buttons_style[$btn_key][$btn_value];
                    }
                }
            }


            if (!empty($res)) {
                if (!empty($this->buttons_style["prepend"])) {
                    $res["prepend"]                     = $this->buttons_style["prepend"];
                }
                if (!empty($this->buttons_style["append"])) {
                    $res["append"]                      = $this->buttons_style["append"];
                }
            }
        }

        return (is_array($res)
            ? implode(" ", $res)
            : null
        );
    }
}
