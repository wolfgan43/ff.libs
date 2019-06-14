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
    $name_space                         = 'phpformsframework\\libs\\delivery\\';
    $name_space_notice                  = $name_space . 'notice\\';
    $name_space_drivers                 = $name_space . 'drivers\\';





    $class_files                            = array(
        $name_space . 'Notice'                      => 'Notice.php'
        , $name_space_notice . 'Email'              => 'adapters' . DIRECTORY_SEPARATOR . 'notice_email.php'
        , $name_space_notice . 'Sms'                => 'adapters' . DIRECTORY_SEPARATOR . 'notice_sms.php'
        , $name_space_drivers . 'Messenger'         => 'drivers' . DIRECTORY_SEPARATOR . 'Messenger.php'
        , $name_space_drivers . 'MessengerAdapter'  => 'drivers' . DIRECTORY_SEPARATOR . 'MessengerAdapter.php'
        , $name_space_drivers . 'Mailer'            => 'drivers' . DIRECTORY_SEPARATOR . 'Mailer.php'
        , $name_space_drivers . 'MailerAdapter'     => 'drivers' . DIRECTORY_SEPARATOR . 'MailerAdapter.php'
        , $name_space_drivers . 'MailerSimple'      => 'drivers' . DIRECTORY_SEPARATOR . 'MailerSimple.php'
        , $name_space_drivers . 'MailerTemplate'    => 'drivers' . DIRECTORY_SEPARATOR . 'MailerTemplate.php'
        , $name_space_notice . 'Adapter'            => 'NoticeAdapter.php'

    );

    if(isset($class_files[$class])) {
        require_once(__DIR__ . DIRECTORY_SEPARATOR . $class_files[$class]);
    }
});
