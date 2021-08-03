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
namespace phpformsframework\libs\gui;

use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\dto\DataTableResponse;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\AdapterManager;
use phpformsframework\libs\Exception;
use stdClass;

/**
 * Class View
 * @package phpformsframework\libs\gui
 * @property adapters\ViewAdapter $adapter
 */
class View
{
    use AdapterManager;

    private const ERROR_BUCKET                      = "view";

    /**
     * @var Controller
     */
    private $controller                             = null;
    private $config                                 = null;

    /**
     * View constructor.
     * @param string $template_disk_path
     * @param string|null $templateAdapter
     * @param Controller|null $controller
     * @param string|null $widget
     * @param stdClass|null $config
     */
    public function __construct(string $template_disk_path, string $templateAdapter = null, Controller $controller = null, string $widget = null, stdClass $config = null)
    {
        $this->config                               = $config;
        $this->controller                           =& $controller;
        $this->setAdapter($templateAdapter ?? Kernel::$Environment::TEMPLATE_ADAPTER, [$widget]);
        $this->adapter->fetch($template_disk_path);
    }

    /**
     * @param string $sectionName
     * @param bool $repeat
     * @return $this
     */
    public function parse(string $sectionName, bool $repeat = false) : View
    {
        $this->adapter->parse($sectionName, $repeat);

        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isset(string $name) : bool
    {
        return $this->adapter->isset($name);
    }

    /**
     * @param array|string|callable $data
     * @param null|string $value
     * @return $this
     * @throws Exception
     */
    public function assign($data, $value = null) : View
    {
        if ($value instanceof DataHtml) {
            $this->adapter->assign($data, $value->html);

            foreach ($value->js as $js) {
                $this->controller->addJavascriptDefer($js);
            }
            if ($value->js_embed) {
                $this->controller->addJavascriptEmbed($value->js_embed);
            }
            foreach ($value->css as $css) {
                $this->controller->addStylesheet($css);
            }
            foreach ($value->style as $style) {
                $this->controller->addStylesheetEmbed($value->$style);
            }
        } elseif ($data instanceof DataTableResponse) {
            foreach ($data->toArray() as $item) {
                foreach ($item as $key => $value) {
                    $this->adapter->assign($key, $value);
                }
                $this->adapter->parse("Sez" . $data->class, true);
            }
        } else {
            $this->adapter->assign($data, $value);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function html() : string
    {
        return $this->adapter->display();
    }

    /**
     * @return object
     */
    public function getConfig() : object
    {
        return $this->config ?? new stdClass();
    }
}
