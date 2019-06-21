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


//SITE_PATH
//PHP_EXT
//asset delivery
//asset tpl


define("VENDOR_LIBS_DIR", DIRECTORY_SEPARATOR .  "vendor" . DIRECTORY_SEPARATOR . "phpformsframework" . DIRECTORY_SEPARATOR . "libs");
define("LIBS_DISK_PATH", __DIR__);
if(!defined("DOCUMENT_ROOT")) {
    define("DOCUMENT_ROOT", (isset($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"]
        ? $_SERVER["DOCUMENT_ROOT"]
        : str_replace(VENDOR_LIBS_DIR, "", __DIR__))
    );
}

if(!defined("SITE_PATH"))                                   { define("SITE_PATH", str_replace(array(DOCUMENT_ROOT, VENDOR_LIBS_DIR), "", __DIR__)); }
if(!defined("CONF_PATH"))                                   { define("CONF_PATH", "/conf"); }
if(!defined("LIBS_PATH"))                                   { define("LIBS_PATH", "/vendor"); }

if(!defined("APP_START"))               { define("APP_START", microtime(true)); }
if(!defined("DEBUG_PROFILING"))         { define("DEBUG_PROFILING", false); }
if(!defined("DEBUG_MODE"))              { define("DEBUG_MODE", false); }

if (!defined("CACHE_MEM"))                   { define("CACHE_MEM", false); }

if (!defined("CACHE_SERIALIZER"))                { define("CACHE_SERIALIZER", "PHP"); }
if (!defined("APPID"))                              { define("APPID", $_SERVER["HTTP_HOST"]); }

if (!defined("APPNAME"))                 { define("APPNAME", str_replace(" " , "", ucwords(str_replace(array(".", "-"), " ", $_SERVER["HTTP_HOST"])))); }

if (!defined("FF_ERRORS_MAXRECURSION"))           { define("FF_ERRORS_MAXRECURSION", NULL); }
if (!defined("FF_ERROR_TYPES"))				    { define("FF_ERROR_TYPES", E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE); }
if (!defined("FF_ERROR_HANDLER_HIDE"))			{ define("FF_ERROR_HANDLER_HIDE", false); }
if (!defined("FF_ERROR_HANDLER_MINIMAL"))		    { define("FF_ERROR_HANDLER_MINIMAL", false); }
if (!defined("FF_ERROR_HANDLER_CUSTOM_TPL"))		{ define("FF_ERROR_HANDLER_CUSTOM_TPL", ""); }
if (!defined("FF_ERROR_HANDLER_500"))			    { define("FF_ERROR_HANDLER_500", true); }
if (!defined("FF_ERROR_HANDLER_LOG"))			    { define("FF_ERROR_HANDLER_LOG", false); }
if (!defined("FF_ERROR_HANDLER_LOG_PATH"))		{ define("FF_ERROR_HANDLER_LOG_PATH", FF_DISK_PATH . "/ff_errors"); }


if (!defined("FF_CACHE_MEMCACHED_SERVER"))  { define("FF_CACHE_MEMCACHED_SERVER", "localhost"); }
if (!defined("FF_CACHE_MEMCACHED_PORT"))    { define("FF_CACHE_MEMCACHED_PORT", 11211); }