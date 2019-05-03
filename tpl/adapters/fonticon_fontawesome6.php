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
namespace phpformsframework\libs\tpl\gridsystem;

class Fontawesome5 extends FontIconAdapter {
    protected $css          = array(
                                "fontawesome" => "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.1/css/all.min.css"
                            );
    protected $fonts        = array(
                                /*"fontawesome.eot"       => "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.1/webfonts/fa-solid-900.eot"
                                , "fontawesome.svg"     => "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.1/webfonts/fa-solid-900.svg"
                                , "fontawesome.ttf"     => "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.1/webfonts/fa-solid-900.ttf"
                                , "fontawesome.woff"    => "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.1/webfonts/fa-solid-900.woff"
                                , "fontawesome.woff2"   => "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.1/webfonts/fa-solid-900.woff2"*/
                            );
    protected $prefix       = "fas";
    protected $suffix       = null;
    protected $append       = null;
    protected $prepend      = "fa-";
}

