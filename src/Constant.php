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
namespace phpformsframework\libs;

define("DOCUMENT_ROOT", str_replace(Constant::VENDOR_LIBS_DIR . DIRECTORY_SEPARATOR . "src", "", __DIR__));
define("SITE_PATH", isset($_SERVER["DOCUMENT_ROOT"]) ? str_replace($_SERVER["DOCUMENT_ROOT"], "", DOCUMENT_ROOT) : null);

class Constant
{
    const NAME_SPACE                        = "phpformsframework\\libs\\";
    const CONFIG_DISK_PATHS                 = array(
        self::CONFIG_FF_DISK_PATH           => array("filter" => array("xml", "map"))
    );
    const DOCUMENT_ROOT                     = DOCUMENT_ROOT;
    const SITE_PATH                         = SITE_PATH;
    const APPID                             = null;
    const APPNAME                           = null;

    /**
     * Disk Settings
     */
    const LIBS_PATH                         = DIRECTORY_SEPARATOR . "vendor";
    const CACHE_PATH                        = DIRECTORY_SEPARATOR . "cache";
    const UPLOAD_PATH                       = DIRECTORY_SEPARATOR . "uploads";
    const VENDOR_LIBS_DIR                   = self::LIBS_PATH . DIRECTORY_SEPARATOR . "phpformsframework" . DIRECTORY_SEPARATOR . "libs";

    const DISK_PATH                         = self::DOCUMENT_ROOT . self::SITE_PATH;
    const LIBS_DISK_PATH                    = self::DOCUMENT_ROOT . self::LIBS_PATH;
    const LIBS_FF_PATH                      = DIRECTORY_SEPARATOR . "phpformsframework" . DIRECTORY_SEPARATOR . "libs";
    const LIBS_FF_DISK_PATH                 = self::LIBS_DISK_PATH . self::LIBS_FF_PATH;
    const CONFIG_FF_DISK_PATH               = self::LIBS_FF_DISK_PATH . DIRECTORY_SEPARATOR . 'conf';
    const CACHE_DISK_PATH                   = self::DISK_PATH . self::CACHE_PATH;
    const UPLOAD_DISK_PATH                  = self::DISK_PATH . self::UPLOAD_PATH;

    /**
     * App Settings
     */
    const PROFILING                         = false;
    const DEBUG                             = true;
    const DISABLE_CACHE                     = false;
    const ACCEPTED_LANG                     = array("en");

    /**
     * Connection Credential
     */
    const DATABASE_HOST                     = null;
    const DATABASE_NAME                     = null;
    const DATABASE_USER                     = null;
    const DATABASE_PASSWORD                 = null;

    const SMTP_HOST                         = null;
    const SMTP_AUTH                         = false;
    const SMTP_USER                         = null;
    const SMTP_PASSWORD                     = null;
    const SMTP_PORT                         = 25;
    const SMTP_SECURE                       = false;

    const FROM_EMAIL                        = 'noreply@' . self::APPNAME;
    const FROM_NAME                         = self::APPNAME;
    const DEBUG_EMAIL                       = null;

    const SMS_SID                           = null;
    const SMS_TOKEN                         = null;
    const SMS_FROM                          = self::APPNAME;

    const SUPERADMIN_USERNAME               = null; //todo: da togliere
    const SUPERADMIN_PASSWORD               = null; //todo: da togliere


    /**
     * Access Credential
     */
    const HTTP_AUTH_USERNAME                = null;
    const HTTP_AUTH_PASSWORD                = null;

    const FTP_USERNAME                      = null;
    const FTP_PASSWORD                      = null;

    const SESSION_NAME                      = self::APPNAME;
    const SESSION_SAVE_PATH                 = DIRECTORY_SEPARATOR . "tmp";
    const SESSION_PERMANENT                 = true;
    const SESSION_SHARE                     = true;

    /**
     * Adapter settings
     */
    const MESSENGER_ADAPTER                 = "Twilio";
    const TRANSLATOR_ADAPTER                = "Google";
    const DATABASE_ADAPTER                  = "mysqli";
    const CACHE_MEM_ADAPTER                 = "fs";

    /**
     * System settings
     */
    const PHP_EXT                           = "php";
    const ENCODING                          = "utf-8";
    const FRAMEWORK_CSS                     = "bootstrap4";
    const FONT_ICON                         = "fontawesome4";

}
