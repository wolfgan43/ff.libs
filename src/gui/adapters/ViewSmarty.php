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

use phpformsframework\libs\Constant;
use phpformsframework\libs\gui\ViewAdapter;
use Smarty;
use SmartyException;

/**
 * Class ViewSmarty
 * @package phpformsframework\libs\gui\adapters
 */
class ViewSmarty extends Smarty implements ViewAdapter
{
    const VIEW_PATH             = DIRECTORY_SEPARATOR . "smarty" . DIRECTORY_SEPARATOR;

    private $tpl_file;

    /**
     * ViewSmarty constructor.
     * @param null $file
     */
    public function __construct($file = null)
    {
        parent::__construct();

        $this->tpl_file         = $file;

        $this->template_dir     = Constant::CACHE_DISK_PATH . $this::VIEW_PATH . 'templates';
        $this->compile_dir      = Constant::CACHE_DISK_PATH . $this::VIEW_PATH . 'templates_c';
        $this->config_dir       = Constant::CACHE_DISK_PATH . $this::VIEW_PATH . 'configs';
        $this->cache_dir        = Constant::CACHE_DISK_PATH . $this::VIEW_PATH . 'cache';

        $this->caching          = false;

        $this->cache_lifetime   = 10;
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
     * @param string|null $file_disk_path
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return ViewAdapter
     * @throws SmartyException
     */
    public function fetch($file_disk_path = null, $cache_id = null, $compile_id = null, $parent = null): ViewAdapter
    {
        parent::fetch($file_disk_path);

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
        return parent::display();
    }

    /**
     * @param null $template
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return bool
     * @throws SmartyException
     */
    public function isCached($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        return parent::isCached($this->tpl_file);
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
}
