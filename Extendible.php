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

abstract class Extendible {
    public function __construct($extension_name)
    {
        $this->loadExtension($extension_name);
    }

    private function getPrefix() {
        $arrClass = explode("\\", static::class);

        return strtolower(end($arrClass));
    }

    protected function loadExtension($name)
    {
        $extensions             = Config::getExtension(self::getPrefix(), $name);
        if(is_array($extensions) && count($extensions)) {
            $has                = get_object_vars($this);
            foreach ($has as $name => $oldValue) {
                $this->$name    = isset($extensions[$name]) ? $extensions[$name] : $oldValue;
            }
        } else {
            Error::register(basename(str_replace("\\", "/" , get_called_class())) . ": " . $name . " not found", "orm");
        }
    }
}
