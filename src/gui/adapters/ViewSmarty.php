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
namespace phpformsframework\libs\gui\adapters;

if (!class_exists("Smarty")) {
    /**
     * Class ViewSmarty
     * @package phpformsframework\libs\gui\adapters
     */
    class ViewSmarty
    {
    }
    return null;
}

use phpformsframework\libs\gui\Controller;
use phpformsframework\libs\gui\Resource;
use phpformsframework\libs\Kernel;
use Smarty;
use SmartyException;

/**
 * Class ViewSmarty
 * @package phpformsframework\libs\gui\adapters
 */
class ViewSmarty extends Smarty implements ViewAdapter
{
    private const CACHE_LIFETIME        = 30;
    private const VIEW_PATH             = DIRECTORY_SEPARATOR . "smarty" . DIRECTORY_SEPARATOR;

    private static $components          = [];

    private $tpl_file                   = null;
    private $widget                     = null;

    /**
     * ViewSmarty constructor.
     * @param string|null $widget
     */
    public function __construct(string $widget = null)
    {
        parent::__construct();

        $this->template_dir             = Kernel::$Environment::PROJECT_THEME_DISK_PATH;
        $this->compile_dir              = Kernel::$Environment::CACHE_DISK_PATH . static::VIEW_PATH . 'templates_c';
        $this->config_dir               = Kernel::$Environment::CACHE_DISK_PATH . static::VIEW_PATH . 'configs';
        $this->cache_dir                = Kernel::$Environment::CACHE_DISK_PATH . static::VIEW_PATH . 'cache';

        $this->caching                  = !Kernel::$Environment::DISABLE_CACHE;

        $this->cache_lifetime           = static::CACHE_LIFETIME;

        $this->widget                   = $widget;
    }

    /**
     * @param array|callable|string $tpl_var
     * @param mixed|null $value
     * @param bool $nocache
     * @return ViewAdapter
     * @todo da tipizzare
     */
    public function assign($tpl_var, $value = null, $nocache = false) : ViewAdapter
    {
        parent::assign($tpl_var, $value, $nocache);

        return $this;
    }

    /**
     * @param string|null $template_disk_path
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return ViewAdapter
     */
    public function fetch($template_disk_path = null, $cache_id = null, $compile_id = null, $parent = null): ViewAdapter
    {
        $this->tpl_file                 = $template_disk_path;
        $this->template_dir             = dirname($template_disk_path);

        return $this;
    }

    /**
     * @param null $template
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return string
     * @throws SmartyException
     */
    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null) : string
    {
        $this->registered_plugins       = $this->getComponents();

        $this->assign("theme_path", Kernel::$Environment::PROJECT_THEME_DISK_PATH);

        $this->setAssignDefault();
        $this->assign(Resource::views($this->widget));

        return parent::fetch($this->tpl_file);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isset(string $name): bool
    {
        // TODO: Implement isset() method.
        return false;
    }

    /**
     * @param string $sectionName
     * @param bool $repeat
     * @param bool $appendBefore
     * @return bool
     */
    public function parse(string $sectionName, bool $repeat = false, bool $appendBefore = false): bool
    {
        // TODO: Implement parse() method.
        return false;
    }

    private function getComponents() : array
    {
        if (empty(self::$components)) {
            foreach (Resource::components() as $key => $component) {
                self::$components["function"][$key] = [
                    function () use ($component) {
                        /**
                         * @var Controller $controller
                         */
                        $controller = (new $component());

                        return $controller->html();
                    },
                    false,
                    []
                ];
            }
        }

        return self::$components;
    }

    /**
     *
     */
    private function setAssignDefault() : void
    {
        $this->assign("site_path", Kernel::$Environment::SITE_PATH);
    }
}
