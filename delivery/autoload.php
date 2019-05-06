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
        case 'phpformsframework\\libs\\delivery\\messenger\\Twilio':
            require ('adapters' . DIRECTORY_SEPARATOR . 'messenger_twilio.php');
            break;
        case 'phpformsframework\\libs\\delivery\\mailer\\Localhost':
            require ('adapters' . DIRECTORY_SEPARATOR . 'mailer_localhost.php');
            break;
        case 'phpformsframework\\libs\\delivery\\mailer\\Sendgrid':
            require ('adapters' . DIRECTORY_SEPARATOR . 'mailer_sendgrid.php');
            break;
        case 'phpformsframework\\libs\\delivery\\mailer\\Sparkpost':
            require ('adapters' . DIRECTORY_SEPARATOR . 'mailer_sparkpost.php');
            break;
        case 'phpformsframework\\libs\\delivery\\notice\\Email':
            require ('adapters' . DIRECTORY_SEPARATOR . 'notice_email.php');
            break;
        case 'phpformsframework\\libs\\delivery\\notice\\Sms':
            require ('adapters' . DIRECTORY_SEPARATOR . 'notice_sms.php');
            break;
        case 'phpformsframework\\libs\\delivery\\messenger\\Adapter':
            require ('drivers' . DIRECTORY_SEPARATOR . 'MessengerAdapter.php');
            break;
        case 'phpformsframework\\libs\\delivery\\drivers\\Messenger':
            require ('drivers' . DIRECTORY_SEPARATOR . 'Messenger.php');
            break;
        case 'phpformsframework\\libs\\delivery\\mailer\\Adapter':
            require ('drivers' . DIRECTORY_SEPARATOR . 'MailerAdapter.php');
            break;
        case 'phpformsframework\\libs\\delivery\\drivers\\Mailer':
            require ('drivers' . DIRECTORY_SEPARATOR . 'Mailer.php');
            break;
        case 'phpformsframework\\libs\\delivery\\drivers\\Sender':
            require ('drivers' . DIRECTORY_SEPARATOR . 'Sender.php');
            break;
        case 'phpformsframework\\libs\\delivery\\drivers\\SenderSimple':
            require ('drivers' . DIRECTORY_SEPARATOR . 'SenderSimple.php');
            break;
        case 'phpformsframework\\libs\\delivery\\drivers\\SenderTemplate':
            require ('drivers' . DIRECTORY_SEPARATOR . 'SenderTemplate.php');
            break;
        case 'phpformsframework\\libs\\delivery\\notice\\Adapter':
            require ('NoticeAdapter.php');
            break;
        case 'phpformsframework\\libs\\delivery\\Notice':
            require ('Notice.php');
            break;
    }
});
