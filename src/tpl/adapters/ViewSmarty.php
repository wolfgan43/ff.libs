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
namespace phpformsframework\libs\tpl\adapters;

use phpformsframework\libs\Constant;
use phpformsframework\libs\tpl\ViewAdapter;
use Smarty;
use Exception;

class ViewSmarty extends Smarty implements ViewAdapter
{
    const PATH                  = DIRECTORY_SEPARATOR . "smarty" . DIRECTORY_SEPARATOR;

    private $tpl_file;

    public function __construct($file = null)
    {
        parent::__construct();

        $this->tpl_file         = $file;

        $this->template_dir     = Constant::CACHE_DISK_PATH . $this::PATH . 'templates';
        $this->compile_dir      = Constant::CACHE_DISK_PATH . $this::PATH . 'templates_c';
        $this->config_dir       = Constant::CACHE_DISK_PATH . $this::PATH . 'configs';
        $this->cache_dir        = Constant::CACHE_DISK_PATH . $this::PATH . 'cache';

        $this->caching          = false;

        $this->cache_lifetime   = 10;
    }

    public function is_cached()
    {
        try {
            $res = $this->isCached($this->tpl_file);
        } catch (Exception $exc) {
            $res = false;
        }
        return $res;
    }

    public function isset($name)
    {
        // TODO: Implement isset() method.
    }
    public function parse($sectionName, $repeat = false)
    {
        // TODO: Implement parse() method.
    }
}
