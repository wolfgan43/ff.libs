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
        case 'phpformsframework\\libs\\international\\Locale':
            require ('Locale.php');
            break;
        case 'phpformsframework\\libs\\international\\Translator':
            require ('Translator.php');
            break;
        case 'phpformsframework\\libs\\international\\translator\\Adapter':
            require ('TranslatorAdapter.php');
            break;
        case 'phpformsframework\\libs\\international\\Data':
            require ('Data.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Adapters':
            require ('DataAdapter.php');
            break;
        case 'phpformsframework\\libs\\international\\translator\\Google':
            require ('adapters' . DIRECTORY_SEPARATOR . 'translator_google.php');
            break;
        case 'phpformsframework\\libs\\international\\translator\\Translated':
            require ('adapters' . DIRECTORY_SEPARATOR . 'translator_translated.php');
            break;
        case 'phpformsframework\\libs\\international\\translator\\Transltr':
            require ('adapters' . DIRECTORY_SEPARATOR . 'translator_transltr.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Ara':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_ara.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Chn':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_chn.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Dan':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_dan.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Deu':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_deu.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Eng':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_eng.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Esp':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_esp.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Fra':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_fra.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Iso9075':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_iso9075.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Ita':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_ita.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Jpn':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_jpn.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Mssql':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_mssql.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Ned':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_ned.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Por':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_por.php');
            break;
        case 'phpformsframework\\libs\\international\\data\\Rus':
            require ('adapters' . DIRECTORY_SEPARATOR . 'data_rus.php');
            break;
    }
});


