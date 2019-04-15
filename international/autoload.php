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
        case 'phpformsframework\libs\international\Locale':
            require ('Locale.php');
            break;
        case 'phpformsframework\libs\international\Translator':
            require ('Translator.php');
            break;
        case 'phpformsframework\libs\international\Data':
            require ('Data.php');
            break;
        case 'phpformsframework\libs\international\dataLang':
            require ('Data.php');
            break;
        case 'phpformsframework\libs\international\translatorGoogle':
            require ('adapters' . DIRECTORY_SEPARATOR . 'google.php');
            break;
        case 'phpformsframework\libs\international\translatorTranslated':
            require ('adapters' . DIRECTORY_SEPARATOR . 'translated.php');
            break;
        case 'phpformsframework\libs\international\translatorTransltr':
            require ('adapters' . DIRECTORY_SEPARATOR . 'transltr.php');
            break;
        case 'phpformsframework\libs\international\datalang_ara':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_ara.php');
            break;
        case 'phpformsframework\libs\international\datalang_chn':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_chn.php');
            break;
        case 'phpformsframework\libs\international\datalang_dan':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_dan.php');
            break;
        case 'phpformsframework\libs\international\datalang_deu':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_deu.php');
            break;
        case 'phpformsframework\libs\international\datalang_eng':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_eng.php');
            break;
        case 'phpformsframework\libs\international\datalang_esp':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_esp.php');
            break;
        case 'phpformsframework\libs\international\datalang_fra':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_fra.php');
            break;
        case 'phpformsframework\libs\international\datalang_iso9075':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_iso9075.php');
            break;
        case 'phpformsframework\libs\international\datalang_ita':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_ita.php');
            break;
        case 'phpformsframework\libs\international\datalang_jpn':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_jpn.php');
            break;
        case 'phpformsframework\libs\international\datalang_mssql':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_mssql.php');
            break;
        case 'phpformsframework\libs\international\datalang_ned':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_ned.php');
            break;
        case 'phpformsframework\libs\international\datalang_por':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_por.php');
            break;
        case 'phpformsframework\libs\international\datalang_rus':
            require ('adapters' . DIRECTORY_SEPARATOR . 'datalang_rus.php');
            break;
    }
});


