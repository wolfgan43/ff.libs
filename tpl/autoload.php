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
    switch ($class) {
        case 'phpformsframework\\libs\\tpl\\gridsystem\\Fontawesome4':
            require ('adapters' . DIRECTORY_SEPARATOR . 'fonticon_fontawesome4.php');
            break;
        case 'phpformsframework\\libs\\tpl\\gridsystem\\Fontawesome6':
            require ('adapters' . DIRECTORY_SEPARATOR . 'fonticon_fontawesome4.php');
            break;
        case 'phpformsframework\\libs\\tpl\\gridsystem\\Glyphicons3':
            require ('adapters' . DIRECTORY_SEPARATOR . 'fonticon_glyphicons3.php');
            break;
        case 'phpformsframework\\libs\\tpl\\gridsystem\\Bootstrap3':
            require ('adapters' . DIRECTORY_SEPARATOR . 'frameworkcss_bootstrap3.php');
            break;
        case 'phpformsframework\\libs\\tpl\\gridsystem\\Bootstrap4':
            require ('adapters' . DIRECTORY_SEPARATOR . 'frameworkcss_bootstrap4.php');
            break;
        case 'phpformsframework\\libs\\tpl\\gridsystem\\Foundation6':
            require ('adapters' . DIRECTORY_SEPARATOR . 'frameworkcss_foundation6.php');
            break;
        case 'phpformsframework\\libs\\tpl\\PageHtml':
            require ('adapters' . DIRECTORY_SEPARATOR . 'page_html.php');
            break;
        case 'phpformsframework\\libs\\tpl\\gridsystem\\FontIconAdapter':
            require ('FontIconAdapter.php');
            break;
        case 'phpformsframework\\libs\\tpl\\gridsystem\\FrameworkCssAdapter':
            require ('FrameworkCssAdapter.php');
            break;
        case 'phpformsframework\\libs\\tpl\\ffTemplate':
            require ('ffTemplate.php');
            break;
        case 'phpformsframework\\libs\\tpl\\ffSmarty':
            require ('ffSmarty.php');
            break;
        case 'phpformsframework\\libs\\tpl\\Gridsystem':
            require ('Gridsystem.php');
            break;
        case 'phpformsframework\\libs\\tpl\\Widget':
            require ('Widget.php');
            break;
        case 'phpformsframework\\libs\\tpl\\Page':
            require ('Page.php');
            break;
        default:
    }
});
