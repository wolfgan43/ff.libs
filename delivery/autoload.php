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
        case 'phpformsframework\libs\delivery\mailerLocalhost':
            require ('adapters\mailer_localhost.php');
            break;
        case 'phpformsframework\libs\delivery\mailerSendgrid':
            require ('adapters\mailer_sendgrid.php');
            break;
        case 'phpformsframework\libs\delivery\mailerSparkpost':
            require ('adapters\mailer_sparkpost.php');
            break;
        case 'phpformsframework\libs\delivery\noticeAdapterEmail':
            require ('adapters\notice_email.php');
            break;
        case 'phpformsframework\libs\delivery\noticeAdapterSms':
            require ('adapters\notice_sms.php');
            break;
        case 'phpformsframework\libs\delivery\smsAdapter':
            require ('drivers\Sms.php');
            break;
        case 'phpformsframework\libs\delivery\Sms':
            require ('drivers\Sms.php');
            break;
        case 'phpformsframework\libs\delivery\mailerAdapter':
            require ('drivers\Mailer.php');
            break;
        case 'phpformsframework\libs\delivery\Mailer':
            require ('drivers\Mailer.php');
            break;
        case 'phpformsframework\libs\delivery\noticeAdapter':
            require ('Notice.php');
            break;
        case 'phpformsframework\libs\delivery\Notice':
            require ('Notice.php');
            break;
    }
});
