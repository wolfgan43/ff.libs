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
namespace phpformsframework\libs\util;

/**
 * Trait AdapterManager
 * @package phpformsframework\libs\util
 */
trait AdapterManager
{
    private $adapters                                       = array();
    private $adapter                                        = null;

    /**
     * @param string $adapterName
     * @param array|null $args
     * @param string $class_name
     * @return mixed
     */
    private static function loadAdapter(string $adapterName, array $args = array(), string $class_name = __CLASS__) : object
    {
        $class                                              = str_replace(array('\\drivers\\','\\'), array('\\', '/'), $class_name);
        $className                                          = basename($class);
        $nameSpace                                          = str_replace('/', '\\', dirname($class));
        $classNameAdapter                                   = $nameSpace . '\\adapters\\' . $className . ucfirst($adapterName);

        if (!class_exists($classNameAdapter)) {
            die($class_name . " Adapter not supported: " . $classNameAdapter);
        }

        return new $classNameAdapter(...$args);
    }

    /**
     * @param string $adapterName
     * @param array $args
     * @param string $class_name
     * @return object
     */
    private function setAdapter(string $adapterName, array $args = array(), string $class_name = __CLASS__) : object
    {
        $this->adapters[$adapterName]                       = $this->loadAdapter($adapterName, $args, $class_name);
        $this->adapter                                      =& $this->adapters[$adapterName];

        return $this->adapter;
    }
}
