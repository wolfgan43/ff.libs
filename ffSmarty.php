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

/**
 * Created by PhpStorm.
 * User: crumma
 * Date: 30/10/2017
 * Time: 10:11
 */

class ffSmarty extends Smarty {
    public $tpl_file;

    public function __construct($file = null) {

        parent::__construct();

        $disk_path              = dirname(dirname(__DIR__));

        $this->tpl_file         = $file;


        $this->template_dir     = $disk_path . '/cache/smarty/templates';
        $this->compile_dir      = $disk_path . '/cache/smarty/templates_c';
        $this->config_dir       = $disk_path . '/cache/smarty/configs';
        $this->cache_dir        = $disk_path . '/cache/smarty/cache';

        $this->caching          = false;

        $this->cache_lifetime   = 10;
    }

    public function is_cached () {
        return $this->isCached($this->tpl_file);
    }

    public function process()
    {
        return $this->fetch($this->tpl_file);
    }
}

