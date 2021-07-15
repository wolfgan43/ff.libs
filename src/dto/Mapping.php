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
namespace phpformsframework\libs\dto;

/**
 * Trait Mapping
 */
trait Mapping
{
    /**
     * @param array $map
     * @param object|null $obj
     */
    protected function autoMapping(array $map, object $obj = null) : void
    {
        if (!$obj) {
            $obj = $this;
        }

        foreach (array_intersect_key($this->removeNull($map), get_object_vars($obj))  as $key => $value) {
            $obj->$key = $value;
        }
    }


    /**
     * @param array $map
     * @param object|null $obj
     */
    protected function autoMappingMagic(array $map, object $obj = null) : void
    {
        if (!$obj) {
            $obj = $this;
        }

        foreach ($this->removeNull($map) as $key => $value) {
            $obj->$key = $value;
        }
    }

    private function removeNull(array $map) : array
    {
        return array_filter($map, function ($var) {
            return $var !== null;
        });
    }
}
