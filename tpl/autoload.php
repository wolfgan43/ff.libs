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

spl_autoload_register(function ($class) {
    $name_space                                             = 'phpformsframework\\libs\\tpl\\';
    $name_space_gridsystem                                  = $name_space. 'gridsystem\\';

    $class_files                                            = array(
        $name_space . 'PageHtml'                          => 'adapters' . DIRECTORY_SEPARATOR . 'page_html.php'
        , $name_space_gridsystem . 'FontIcon'               => 'FontIcon.php'
        , $name_space_gridsystem . 'FrameworkCss'           => 'FrameworkCss.php'
        , $name_space . 'ffTemplate'                        => 'ffTemplate.php'
        , $name_space . 'ffSmarty'                          => 'ffSmarty.php'
        , $name_space . 'Gridsystem'                        => 'Gridsystem.php'
        , $name_space . 'Widget'                            => 'Widget.php'
        , $name_space . 'Page'                              => 'Page.php'

    );

    if(isset($class_files[$class])) {
        require($class_files[$class]);
    }
});
