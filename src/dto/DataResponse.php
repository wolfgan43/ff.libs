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

namespace phpformsframework\libs\dto;

class DataResponse extends DataAdapter
{
    const CONTENT_TYPE = "application/json";
    /**
     * @var null|array
     */
    public $data = null;
    /**
     * @var null|int
     */
    public $page = null;
    /**
     * @var null|int
     */
    public $count = null;
    /**
     * @param array $values
     * @return $this
     */
    public function fill($values)
    {
        $this->data = $values;

        return $this;
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return mixed|string|null
     */
    public function get($key)
    {
        return (isset($this->data[$key])
            ? $this->data[$key]
            : null
        );
    }

    /**
     * @param string $key
     * @return $this
     */
    public function unset($key)
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * @return false|string
     */
    public function output()
    {
        return $this->toJson();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

}
