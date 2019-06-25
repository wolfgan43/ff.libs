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
namespace phpformsframework\libs\tpl;

use phpformsframework\libs\Constant;
use phpformsframework\libs\tpl\adapters\PageHtml;

class Page {
    const NAME_SPACE                            = 'phpformsframework\\libs\\tpl\\adapters\\';
    const ASSETS_PATH                           =  Constant::LIBS_FF_DISK_PATH . DIRECTORY_SEPARATOR . 'assets';

    private static $singleton                   = null;

    /**
     * @param string $type
     * @return PageHtml
     */
    public static function getInstance($type, $extension_name = "default") {
        if(!self::$singleton) {
            $class_name                         = self::NAME_SPACE . "Page" . ucfirst($type);
            self::$singleton                    = new $class_name($extension_name);
        }

        return self::$singleton;
    }
}
