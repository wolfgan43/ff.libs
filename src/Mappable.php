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

abstract class Mappable
{
    const ERROR_BUCKET          = "mappable";
    public function __construct($map_name)
    {
        $this->loadMap($map_name);
    }

    private function getPrefix()
    {
        $arrClass               = explode("\\", static::class);

        return strtolower(end($arrClass));
    }

    protected function loadMap($name)
    {
        $prefix                 = self::getPrefix();
        Debug::stopWatch("mapping/" . $prefix . "_" . $name);

        $extensions             = Config::mapping($prefix, $name);
        if (is_array($extensions) && count($extensions)) {
            $has                = get_object_vars($this);
            foreach ($has as $key => $oldValue) {
                $this->$key    = isset($extensions[$key]) ? $extensions[$key] : $oldValue;
            }
        } else {
            Error::register(basename(str_replace("\\", DIRECTORY_SEPARATOR, get_called_class())) . ": " . $name . " not found", static::ERROR_BUCKET);
        }

        Debug::stopWatch("mapping/" . $prefix . "_" . $name);
    }
}
