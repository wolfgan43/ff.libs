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
        case 'phpformsframework\\libs\\App':
            require('App.php');
            break;
        case 'phpformsframework\\libs\\DirStruct':
            require('DirStruct.php');
            break;
        case 'phpformsframework\\libs\\Env':
            require('Env.php');
            break;
        case 'phpformsframework\\libs\\Hook':
            require('Hook.php');
            break;
        case 'phpformsframework\\libs\\Config':
            require('Config.php');
            break;
        case 'phpformsframework\\libs\\Configurable':
            require('Configurable.php');
            break;
        case 'phpformsframework\\libs\\Debug':
            require('Debug.php');
            break;
        case 'phpformsframework\\libs\\Error':
            require('Error.php');
            break;
        case 'phpformsframework\\libs\\ErrorHandler':
            require('ErrorHandler.php');
            break;
        case 'phpformsframework\\libs\\Log':
            require('Log.php');
            break;
        case 'phpformsframework\\libs\\Request':
            require_once('Request.php');
            break;
        case 'phpformsframework\\libs\\Response':
            require_once('Response.php');
            break;
        case 'phpformsframework\\libs\\Router':
            require_once('Router.php');
            break;
        default:
    }
});


