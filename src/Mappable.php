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

/**
 * Class Mappable
 * @package phpformsframework\libs
 */
abstract class Mappable
{
    use Mapping;

    protected const ERROR_BUCKET          = "mappable";

    /**
     * @todo da tipizzare
     * Mappable constructor.
     * @param $map|null
     * @param string|null $prefix
     */
    public function __construct($map = null, string $prefix = null)
    {
        if (is_array($map)) {
            $this->autoMapping($map);
        } elseif ($map) {
            $this->loadMap($map, $prefix);
        }
    }

    /**
     * @param string|null $class_name
     * @return string
     */
    private function getPrefix(string $class_name = null) : string
    {
        if (!$class_name) {
            $class_name         = static::class;
        }
        $arrClass               = explode("\\", $class_name);

        return strtolower(end($arrClass));
    }

    /**
     * @param string $name
     * @param string|null $prefix
     */
    protected function loadMap(string $name, string $prefix = null) : void
    {
        Debug::stopWatch("mapping/" . $prefix . "_" . $name);

        $prefix                 = self::getPrefix($prefix);
        $map                    = Config::mapping($prefix, $name);
        if (is_array($map) && count($map)) {
            $this->autoMapping($map);
        } else {
            Error::register("Mapping: " . basename(str_replace("\\", DIRECTORY_SEPARATOR, get_called_class())) . ": " . $prefix . "_" . $name . " not found", static::ERROR_BUCKET);
        }

        Debug::stopWatch("mapping/" . $prefix . "_" . $name);
    }
}
