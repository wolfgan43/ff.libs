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
        case 'phpformsframework\libs\storage\Canvas':
            require ('drivers\image\Canvas.php');
            break;
        case 'phpformsframework\libs\storage\Render':
            require ('drivers\image\Canvas.php');
            break;
        case 'phpformsframework\libs\storage\Thumb':
            require ('drivers\image\Thumb.php');
            break;
        case 'Array2XML':
            require ('drivers\array2xml.php');
            break;
        case 'MySqli':
            require ('drivers\MySqli.php');
            break;
        case 'MongoDB':
            require ('drivers\MongoDB.php');
            break;
        case 'phpformsframework\libs\storage\filemanagerHtml':
            require ('adapters\filemanager_html.php');
            break;
        case 'phpformsframework\libs\storage\filemanagerJson':
            require ('adapters\filemanager_json.php');
            break;
        case 'phpformsframework\libs\storage\filemanagerPhp':
            require ('adapters\filemanager_php.php');
            break;
        case 'phpformsframework\libs\storage\filemanagerXml':
            require ('adapters\filemanager_xml.php');
            break;
        case 'phpformsframework\libs\storage\databaseMongodb':
            require ('adapters\database_mongodb.php');
            break;
        case 'phpformsframework\libs\storage\databaseMysqli':
            require ('adapters\database_mysqli.php');
            break;
        case 'phpformsframework\libs\storage\Filemanager':
            require ('Filemanager.php');
            break;
        case 'phpformsframework\libs\storage\Database':
            require ('Database.php');
            break;
        default:
    }
});
