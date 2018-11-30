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
        case "Debug":
            require("Debug.php");
            break;
        case "ffDB_Sql":
        case "ffDb_Sql":
            require("ffDb_Sql/ffDb_Sql_mysqli.php");
            break;
        case "ffDB_MongoDB":
        case "ffDb_MongoDB":
            require("ffDB_Mongo/ffDb_MongoDB.php");
            break;
        case "ffGlobals":
            require("ffGlobals.php");
            break;
        case "ffCache":
            require("ffCache/ffCacheAdapter.php");
            require("ffCache/ffCache.php");
            break;
        case "ffTranslator":
            require("ffTranslator/ffTranslator.php");
            break;
        case "ffImage":
            require("ffImage/ffImage.php");
            break;
        case "ffCanvas":
            require("ffImage/ffCanvas.php");
            break;
        case "ffText":
            //require("ffImage/ffText.php");
            break;
        case "ffThumb":
            require("ffImage/ffThumb.php");
            break;
        case "ffMedia":
            require("ffMedia.php");
            break;
        case "ffTemplate":
            require("ffTemplate.php");
            break;
        case "ffSmarty":
            require("ffSmarty.php");
            break;
        default:
    }
});


