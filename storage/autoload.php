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
        case 'phpformsframework\\libs\\storage\\drivers\\Canvas':
            require ('drivers' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'Canvas.php');
            break;
        case 'phpformsframework\\libs\\storage\\drivers\\Render':
            require ('drivers' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'Render.php');
            break;
        case 'phpformsframework\\libs\\storage\\drivers\\Thumb':
            require ('drivers' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'Thumb.php');
            break;
        case 'phpformsframework\\libs\\storage\\drivers\\Array2XML':
            require ('drivers' . DIRECTORY_SEPARATOR . 'array2xml.php');
            break;
        case 'phpformsframework\\libs\\storage\\drivers\\MySqli':
            require ('drivers' . DIRECTORY_SEPARATOR . 'MySqli.php');
            break;
        case 'phpformsframework\\libs\\storage\\drivers\\MongoDB':
            require ('drivers' . DIRECTORY_SEPARATOR . 'MongoDB.php');
            break;
        case 'phpformsframework\\libs\\storage\\filemanager\\Html':
            require ('adapters' . DIRECTORY_SEPARATOR . 'filemanager_html.php');
            break;
        case 'phpformsframework\\libs\\storage\\filemanager\\Json':
            require ('adapters' . DIRECTORY_SEPARATOR . 'filemanager_json.php');
            break;
        case 'phpformsframework\\libs\\storage\\filemanager\\Php':
            require ('adapters' . DIRECTORY_SEPARATOR . 'filemanager_php.php');
            break;
        case 'phpformsframework\\libs\\storage\\filemanager\\Xml':
            require ('adapters' . DIRECTORY_SEPARATOR . 'filemanager_xml.php');
            break;
        case 'phpformsframework\\libs\\storage\\database\\Mongodb':
            require ('adapters' . DIRECTORY_SEPARATOR . 'database_mongodb.php');
            break;
        case 'phpformsframework\\libs\\storage\\database\\Mysqli':
            require ('adapters' . DIRECTORY_SEPARATOR . 'database_mysqli.php');
            break;
        case 'phpformsframework\\libs\\storage\\Filemanager':
            require ('Filemanager.php');
            break;
        case 'phpformsframework\\libs\\storage\\filemanager\\Adapter':
            require ('FilemanagerAdapter.php');
            break;
        case 'phpformsframework\\libs\\storage\\Database':
            require ('Database.php');
            break;
        case 'phpformsframework\\libs\\storage\\database\\Adapter':
            require ('DatabaseAdapter.php');
            break;
        case 'phpformsframework\\libs\\storage\\Media':
            require ('Media.php');
            break;
        case 'phpformsframework\\libs\\storage\\Orm':
            require ('Orm.php');
            break;
        case 'phpformsframework\\libs\\storage\\models\\Model':
            require ('OrmModel.php');
            break;
        default:
    }
});
