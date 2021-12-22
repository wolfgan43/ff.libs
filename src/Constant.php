<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace phpformsframework\libs;

define("DOCUMENT_ROOT", explode(Constant::LIBS_PATH, __DIR__)[0]);
define("SITE_PATH", !empty($_SERVER["DOCUMENT_ROOT"]) ? str_replace(rtrim($_SERVER["DOCUMENT_ROOT"], "/"), "", DOCUMENT_ROOT) : null);

/**
 * Class Constant
 * @package phpformsframework\libs
 */
class Constant
{
    const CONFIG_PATHS                      = array(
        self::CONFIG_FF_PATH                => array("flag" => 8, "filter" => array("xml", "map"))
    );
    const CONFIG_APP_PATHS                  = array(
        self::PROJECT_DOCUMENT_ROOT . DIRECTORY_SEPARATOR . self::RESOURCE_CONF => array("flag" => 4, "filter" => array("xml", "map"))
    );
    const DOCUMENT_ROOT                     = DOCUMENT_ROOT;
    const SITE_PATH                         = SITE_PATH;

    const APPNAME                           = null;
    const APPID                             = null; //secret

    const API_SIGNATURE                     = null;
    const API_ISSUER                        = null; //usata forse non serve
    const API_SCOPE_OP                      = "/*";
    const API_SERVER                        = [];
    const API_KEY                           = null;

    /**
     * Reference struct folder Assets
     */
    const RESOURCE_ASSETS                   = "assets";
    const RESOURCE_ASSET_CSS                    = "css";
    const RESOURCE_ASSET_JS                     = "js";
    const RESOURCE_ASSET_IMAGES                 = "images";
    const RESOURCE_ASSET_FONTS                  = "fonts";

    /**
     * Reference struct folder MVC
     */
    const RESOURCE_EMAIL                    = "email";
    const RESOURCE_EMAIL_IMAGES                 = "images";
    const RESOURCE_LAYOUTS                  = "layouts";
    const RESOURCE_VIEWS                    = "views";
    const RESOURCE_CONTROLLERS              = "controllers";
    const RESOURCE_WIDGETS                  = "widgets";

    /**
     * Reference struct folder Framework
     */
    const RESOURCE_APP                      = "app";
    const RESOURCE_API                      = "api";
    const RESOURCE_CACHE                    = "cache";
    const RESOURCE_CACHE_ASSETS                 = self::RESOURCE_ASSETS;
    const RESOURCE_CACHE_THUMBS                 = "thumbs";
    const RESOURCE_CACHE_LOGS                   = "logs";
    const RESOURCE_CACHE_ERROR                  = "errors";
    const RESOURCE_CACHE_BUFFER                 = "buffer";
    const RESOURCE_CONF                     = "conf";
    const RESOURCE_LIBS                     = "vendor";
    const RESOURCE_UPLOADS                  = "uploads";
    const RESOURCE_THEMES                   = "themes";

    const THEME_NAME                        = "default";
    const MEMORY_LIMIT                      = '128M';

    /**
     * Disk Settings
     */
    const LIBS_PATH                         = DIRECTORY_SEPARATOR . self::RESOURCE_LIBS;
    const CACHE_PATH                        = DIRECTORY_SEPARATOR . self::RESOURCE_CACHE;
    const UPLOAD_PATH                       = DIRECTORY_SEPARATOR . self::RESOURCE_UPLOADS;
    const THEME_PATH                        = DIRECTORY_SEPARATOR . self::RESOURCE_THEMES;

    /**
     * Project settings
     */
    const PROJECT_DOCUMENT_ROOT             = DIRECTORY_SEPARATOR . self::RESOURCE_APP;
    const PROJECT_DISK_PATH                 = self::DISK_PATH . self::PROJECT_DOCUMENT_ROOT;
    const PROJECT_ASSETS_DISK_PATH          = self::PROJECT_DISK_PATH . self::THEME_PATH . DIRECTORY_SEPARATOR . self::THEME_NAME . DIRECTORY_SEPARATOR . self::RESOURCE_ASSETS . DIRECTORY_SEPARATOR . "dist";
    const PROJECT_THEME_DISK_PATH           = self::PROJECT_DISK_PATH . self::THEME_PATH . DIRECTORY_SEPARATOR . self::THEME_NAME;

    const VENDOR_LIBS_DIR                   = self::LIBS_PATH . DIRECTORY_SEPARATOR . "phpformsframework" . DIRECTORY_SEPARATOR . "libs";

    const DISK_PATH                         = self::DOCUMENT_ROOT;
    const LIBS_DISK_PATH                    = self::DOCUMENT_ROOT . self::LIBS_PATH;
    const LIBS_FF_PATH                      = DIRECTORY_SEPARATOR . "phpformsframework" . DIRECTORY_SEPARATOR . "libs";
    const LIBS_FF_DISK_PATH                 = self::LIBS_DISK_PATH . self::LIBS_FF_PATH;
    const CONFIG_FF_PATH                    = self::VENDOR_LIBS_DIR . DIRECTORY_SEPARATOR . self::RESOURCE_CONF;
    const CACHE_DISK_PATH                   = self::DISK_PATH . self::CACHE_PATH;
    const UPLOAD_DISK_PATH                  = self::DISK_PATH . self::UPLOAD_PATH;

    const LOG_DISK_PATH                     = self::CACHE_DISK_PATH . DIRECTORY_SEPARATOR . self::RESOURCE_CACHE_LOGS;
    const ERROR_DISK_PATH                   = self::CACHE_DISK_PATH . DIRECTORY_SEPARATOR . self::RESOURCE_CACHE_ERROR;
    const BUFFER_DISK_PATH                  = self::CACHE_DISK_PATH . DIRECTORY_SEPARATOR . self::RESOURCE_CACHE_BUFFER;
    /**
     * App Settings
     */
    const PROFILING                         = true;
    const DEBUG                             = true;
    const DISABLE_CACHE                     = false;
    const CACHE_BUFFER                      = true;

    /**
     * Locale Settings
     */
    const LOCALE_ACCEPTED_LANGS             = ["en"];
    const LOCALE_LANG_CODE                  = "en";
    const LOCALE_COUNTRY_CODE               = "GB";
    const LOCALE_TIME_ZONE                  = "Europe/Rome";
    const LOCALE_TIME_LOC                   = "+1000";

    /**
     * Connection Credential
     */
    const DATABASE_HOST                     = null;
    const DATABASE_NAME                     = null;
    const DATABASE_USER                     = null;
    const DATABASE_SECRET                   = null;

    const SMTP_HOST                         = null;
    const SMTP_AUTH                         = false;
    const SMTP_USER                         = null;
    const SMTP_SECRET                       = null;
    const SMTP_PORT                         = 25;
    const SMTP_SECURE                       = false;

    const FROM_EMAIL                        = 'noreply@' . self::APPNAME;
    const FROM_NAME                         = self::APPNAME;
    const DEBUG_EMAIL                       = null;

    const SMS_SID                           = null;
    const SMS_TOKEN                         = null;
    const SMS_FROM                          = self::APPNAME;

    /**
     * Access Credential
     */
    const HTTP_AUTH_USERNAME                = null;
    const HTTP_AUTH_SECRET                  = null;

    const SSL_VERIFYPEER                    = true;
    const SSL_VERIFYHOST                    = true;

    const FTP_USERNAME                      = null;
    const FTP_SECRET                        = null;

    const SESSION_NAME                      = null;
    const SESSION_SAVE_PATH                 = null;
    const SESSION_PERMANENT                 = true;
    const SESSION_COOKIE_HTTPONLY           = true;

    /**
     * GUI Settings
     */
    const ASSET_LOCATION_DEFAULT           = "defer";


    /**
     * Adapters
     */

    /**
     * @var string[Twilio]
     */
    const MESSENGER_ADAPTER                 = "Twilio";
    /**
     * @var string[Google|Translated|Transltr]
     */
    const TRANSLATOR_ADAPTER                = "Google";
    /**
     * @var string[Mysqli|Mongodb]
     */
    const DATABASE_ADAPTER                  = "Mysqli";
    /**
     * @var string[Apc|Fs|Global|Memcached|Redis]
     */
    const CACHE_BUFFER_ADAPTER              = "Fs";
    /**
     * @var string[Native|ElasticSearch]
     */
    const CACHE_DATABASE_ADAPTER            = "Native";
    /**
     * @var string[Native|ElasticSearch]
     */
    const CACHE_MEDIA_ADAPTER               = "Fs";
    /**
     * @var string[Html|Amp|Smarty|Blade]
     */
    const TEMPLATE_ADAPTER                  = "Html";
    /**
     * @var string[Soap|JsonWsp]
     */
    const MICROSERVICE_ADAPTER              = "JsonWsp";
    /**
     * @var string[Bootstrap3|Bootstrap4|Foundation6]
     */
    const GRIDSYSTEM_ADAPTER                = "bootstrap4";
    /**
     * @var string[Glyphicons3|Fontawesome4|Fontawesome6]
     */
    const FONTICON_ADAPTER                  = "fontawesome6";
    /**
     * @var string[html]
     */
    const CONTROLLER_ADAPTER                = "html";


    /**
     * System settings
     */
    const PHP_EXT                           = "php";
    const ENCODING                          = "utf-8";
}
