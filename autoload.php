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
    $name_space                         = 'phpformsframework\\libs\\';
    $class_files                        = array(
        $name_space . 'App'             => 'App.php'
        , $name_space . 'DirStruct'     => 'DirStruct.php'
        , $name_space . 'Env'           => 'Env.php'
        , $name_space . 'Hook'          => 'Hook.php'
        , $name_space . 'Config'        => 'Config.php'
        , $name_space . 'Configurable'  => 'Configurable.php'
        , $name_space . 'Dumpable'      => 'Dumpable.php'
        , $name_space . 'Debug'         => 'Debug.php'
        , $name_space . 'Error'         => 'Error.php'
        , $name_space . 'ErrorHandler'  => 'ErrorHandler.php'
        , $name_space . 'Log'           => 'Log.php'
        , $name_space . 'Request'       => 'Request.php'
        , $name_space . 'Response'      => 'Response.php'
        , $name_space . 'Router'        => 'Router.php'
    );

    if(isset($class_files[$class])) {
        require($class_files[$class]);
    }
});


