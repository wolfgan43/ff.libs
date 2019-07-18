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

class Constant
{
    public static $disable_cache            = DISABLE_CACHE;

    const NAME_SPACE                        = "phpformsframework\\libs\\";

    const VENDOR_LIBS_DIR                   = VENDOR_LIBS_DIR;
    const DOCUMENT_ROOT                     = DOCUMENT_ROOT;
    const SITE_PATH                         = SITE_PATH;
    const CONF_PATH                         = CONF_PATH;
    const LIBS_PATH                         = LIBS_PATH;
    const CACHE_PATH                        = "/cache";
    const UPLOAD_PATH                       = "/uploads";

    const DISK_PATH                         = self::DOCUMENT_ROOT . self::SITE_PATH;
    const LIBS_DISK_PATH                    = self::DOCUMENT_ROOT . self::LIBS_PATH;
    const LIBS_FF_PATH                      = DIRECTORY_SEPARATOR . "phpformsframework" . DIRECTORY_SEPARATOR . "libs";
    const LIBS_FF_DISK_PATH                 = self::LIBS_DISK_PATH . self::LIBS_FF_PATH;
    const CONFIG_FF_DISK_PATH               = self::LIBS_FF_DISK_PATH . DIRECTORY_SEPARATOR . 'conf';
    const CACHE_DISK_PATH                   = self::DISK_PATH . self::CACHE_PATH;
    const UPLOAD_DISK_PATH                  = self::DISK_PATH . self::UPLOAD_PATH;

    const HTTP_AUTH_USERNAME                = HTTP_AUTH_USERNAME;
    const HTTP_AUTH_PASSWORD                = HTTP_AUTH_PASSWORD;

    const PHP_EXT                           = "php";

    const APP_START                         = APP_START;
    const APPID                             = APPID;
    const APPNAME                           = APPNAME;
    const ENCODING                          = "utf-8";

    const PROFILING                         = DEBUG_PROFILING;
    const DEBUG                             = DEBUG_MODE;

    const ACCEPTED_LANG                     = array("en");
    const CACHE_MEM                         = CACHE_MEM;
    const MESSENGER_ADAPTER                 = "Twilio";
    const TRANSLATOR_ADAPTER                = "Google";

    const FTP_USERNAME                      = FTP_USERNAME;
    const FTP_PASSWORD                      = FTP_PASSWORD;

    const SESSION_NAME                      = SESSION_NAME;
    const SESSION_SAVE_PATH                 = SESSION_SAVE_PATH;
    const SESSION_PERMANENT                 = SESSION_PERMANENT;
    const SESSION_SHARE                     = SESSION_SHARE;
}