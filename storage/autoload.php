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
    $name_space                                 = 'phpformsframework\\libs\\storage\\';
    $name_space_drivers                         = $name_space . 'drivers\\';
    $name_space_filemanager                     = $name_space . 'filemanager\\';
    $name_space_database                        = $name_space . 'database\\';

    $class_files                                = array(
        $name_space_drivers . 'Canvas'          => 'drivers' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'Canvas.php'
        , $name_space_drivers . 'Render'        => 'drivers' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'Render.php'
        , $name_space_drivers . 'Thumb'         => 'drivers' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'Thumb.php'
        , $name_space_drivers . 'Array2XML'     => 'drivers' . DIRECTORY_SEPARATOR . 'array2xml.php'
        , $name_space_drivers . 'MySqli'        => 'drivers' . DIRECTORY_SEPARATOR . 'MySqli.php'
        , $name_space_drivers . 'MongoDB'       => 'drivers' . DIRECTORY_SEPARATOR . 'MongoDB.php'
        , $name_space_filemanager . 'Html'      => 'adapters' . DIRECTORY_SEPARATOR . 'filemanager_html.php'
        , $name_space_filemanager . 'Json'      => 'adapters' . DIRECTORY_SEPARATOR . 'filemanager_json.php'
        , $name_space_filemanager . 'Php'       => 'adapters' . DIRECTORY_SEPARATOR . 'filemanager_php.php'
        , $name_space_filemanager . 'Xml'       => 'adapters' . DIRECTORY_SEPARATOR . 'filemanager_xml.php'
        , $name_space_database . 'Mongodb'      => 'adapters' . DIRECTORY_SEPARATOR . 'database_mongodb.php'
        , $name_space_database . 'Mysqli'       => 'adapters' . DIRECTORY_SEPARATOR . 'database_mysqli.php'
        , $name_space . 'Filemanager'           => 'Filemanager.php'
        , $name_space . 'FilemanagerAdapter'    => 'FilemanagerAdapter.php'
        , $name_space . 'Database'              => 'Database.php'
        , $name_space . 'DatabaseAdapter'       => 'DatabaseAdapter.php'
        , $name_space . 'DatabaseDriver'        => 'DatabaseDriver.php'
        , $name_space . 'Media'                 => 'Media.php'
        , $name_space . 'Orm'                   => 'Orm.php'
        , $name_space . 'OrmModel'              => 'OrmModel.php'
    );

    if(isset($class_files[$class])) {
        require_once(__DIR__ . DIRECTORY_SEPARATOR . $class_files[$class]);
    }
});
