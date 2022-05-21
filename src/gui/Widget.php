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

use ff\libs\storage\FilemanagerFs;
use ff\libs\storage\Media;
use ff\libs\Exception;
use stdClass;

/**
 * Class Widget
 * @package ff\libs\gui
 */
abstract class Widget extends Controller
{
    private const VIEW_DEFAULT                      = "index";

    protected const ERROR_BUCKET                    = "widget";

    private $config                                 = [];

    /**
     * @var View
     */
    private $views                                  = null;

    /**
     * Widget constructor.
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        parent::__construct($config);

        $this->config                               = $config ?? [];
    }

    /**
     * Utility Builder
     * ------------------------------------------------------------------------
     */

    /**
     * @param string $filename_or_url
     * @param string|null $mode
     * @return string
     * @throws Exception
     */
    public function getImageUrl(string $filename_or_url, string $mode = null): string
    {
        $resources = $this->getResources();
        if (!empty($resources->images[$filename_or_url])) {
            return Media::getUrl($resources->images[$filename_or_url], $mode);
        }

        return  parent::getImageUrl($filename_or_url, $mode);
    }

    /**
     * Assets Method
     * ------------------------------------------------------------------------
     */

    /**
     * @param string $filename_or_url
     * @param string|null $device
     * @param string|null $media_query
     * @return Controller
     * @throws Exception
     */
    public function addStylesheet(string $filename_or_url, string $device = null, string $media_query = null) : Controller
    {
        $resources = $this->getResources();
        if (!empty($resources->css[$filename_or_url])) {
            $filename_or_url = $resources->css[$filename_or_url];
        }

        return parent::addStylesheet($filename_or_url, $device, $media_query);
    }

    /**
     * @param string $filename_or_url
     * @param string|null $device
     * @param string|null $media_query
     * @return Controller
     * @throws Exception
     */
    public function addFont(string $filename_or_url, string $device = null, string $media_query = null) : Controller
    {
        $resources = $this->getResources();
        if (!empty($resources->font[$filename_or_url])) {
            $filename_or_url = $resources->font[$filename_or_url];
        }

        return parent::addFont($filename_or_url, $device, $media_query);
    }


    /**
     * @param string $filename_or_url
     * @param string|null $location
     * @return Controller
     * @throws Exception
     */
    public function addJavascript(string $filename_or_url, string $location = null): Controller
    {
        $resources = $this->getResources();
        if (!empty($resources->js[$filename_or_url])) {
            $filename_or_url = $resources->js[$filename_or_url];
        }

        return parent::addJavascript($filename_or_url, $location);
    }

    /**
     * Standard Method
     * ------------------------------------------------------------------------
     */


    /**
     * @param string|null $template_name
     * @param bool $include_template_assets
     * @return View
     * @throws Exception
     */
    private function loadView(string $template_name = null, bool $include_template_assets = true) : View
    {
        $template                                   = $template_name ?? self::VIEW_DEFAULT;
        if (!isset($this->views[$template])) {
            $resources                              = $this->getResources();

            if (empty($resources->tpl[$template])) {
                throw new Exception("Template: " . $template . " not found for Widget " . $this->class_name, 404);
            }

            if ($include_template_assets) {
                if (!empty($resources->js[$template])) {
                    $this->requiredJs[]             = $resources->js[$template];
                }
                if (!empty($resources->css[$template])) {
                    $this->requiredCss[]            = $resources->css[$template];
                }
            }

            $this->views[$template]                 = View::fetchFile($resources->tpl[$template], null, static::TEMPLATE_ENGINE, $this, $this->class_name, $this->config($resources->cfg[$template] ?? null));
        }

        return $this->view                          =& $this->views[$template];
    }

    /**
     * @param string|null $template_name
     * @param bool $include_template_assets
     * @return View
     * @throws Exception
     */
    protected function view(string $template_name = null, bool $include_template_assets = true) : View
    {
        return $this->view ?? $this->loadView($template_name, $include_template_assets);
    }

    /**
     * @param string $template_name
     * @return string|null
     * @throws Exception
     */
    protected function getTemplate(string $template_name) : ?string
    {
        return (!empty($file_disk_path = $this->getResources()->tpl[$template_name])
            ? FilemanagerFs::fileGetContents($file_disk_path)
            : parent::getTemplate($template_name)
        );
    }

    /**
     * @param string|null $config_name
     * @return object|null
     * @throws Exception
     */
    protected function getConfig(string $config_name = null) : ?object
    {
        return $this->config($this->getResources()->cfg[$config_name ?? self::VIEW_DEFAULT] ?? null);
    }

    /**
     * @param string|null $file_path
     * @return stdClass|null
     * @throws Exception
     */
    private function config(string $file_path = null) : ?stdClass
    {
        static $configs                             = null;

        if (!isset($configs[$file_path])) {
            if ($file_path) {
                $configs[$file_path]                = $this->loadConfig($file_path);
                foreach ($this->config as $key => $config) {
                    $configs[$file_path]->$key      = $config;
                }
            } else {
                $configs[$file_path]                = (object) $this->config;
            }
        }

        return $configs[$file_path] ?? null;
    }

    /**
     * @param string $file_path
     * @return stdClass|null
     * @throws Exception
     */
    private function loadConfig(string $file_path) : ?stdClass
    {
        return FilemanagerFs::fileGetContentsJson($file_path);
    }

    /**
     * @return stdClass
     */
    private function getResources() : stdClass
    {
        return (object) Resource::widget($this->class_name);
    }
}
