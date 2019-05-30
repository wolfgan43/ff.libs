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
    $name_space                                 = 'phpformsframework\\libs\\international\\';
    $name_space_translator                      = 'phpformsframework\\libs\\international\\translator\\';
    $name_space_data                            = 'phpformsframework\\libs\\international\\data\\';

    $class_files                                = array(
        $name_space . 'Locale'                  => 'Locale.php'
        , $name_space . 'Translator'            => 'Translator.php'
        , $name_space . 'Locale'                => 'Locale.php'
        , $name_space_translator . 'Adapter'    => 'TranslatorAdapter.php'
        , $name_space . 'Data'                  => 'Data.php'
        , $name_space_data . 'Adapters'         => 'DataAdapter.php'
        , $name_space_translator . 'Google'     => 'adapters' . DIRECTORY_SEPARATOR . 'translator_google.php'
        , $name_space_translator . 'Translated' => 'adapters' . DIRECTORY_SEPARATOR . 'translator_translated.php'
        , $name_space_translator . 'Transltr'   => 'adapters' . DIRECTORY_SEPARATOR . 'translator_transltr.php'
        , $name_space_data . 'Ara'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_ara.php'
        , $name_space_data . 'Chn'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_chn.php'
        , $name_space_data . 'Dan'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_dan.php'
        , $name_space_data . 'Deu'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_deu.php'
        , $name_space_data . 'Eng'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_eng.php'
        , $name_space_data . 'Esp'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_esp.php'
        , $name_space_data . 'Fra'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_fra.php'
        , $name_space_data . 'Iso9075'          => 'adapters' . DIRECTORY_SEPARATOR . 'data_iso9075.php'
        , $name_space_data . 'Ita'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_ita.php'
        , $name_space_data . 'Jpn'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_jpn.php'
        , $name_space_data . 'Jpn'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_jpn.php'
        , $name_space_data . 'Jpn'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_jpn.php'
        , $name_space_data . 'Mssql'            => 'adapters' . DIRECTORY_SEPARATOR . 'data_mssql.php'
        , $name_space_data . 'Ned'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_ned.php'
        , $name_space_data . 'Por'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_por.php'
        , $name_space_data . 'Rus'              => 'adapters' . DIRECTORY_SEPARATOR . 'data_rus.php'
    );

    if(isset($class_files[$class])) {
        require($class_files[$class]);
    }
});


