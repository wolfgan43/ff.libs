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

use phpformsframework\libs\Constant;

abstract class DataAdapter
{
    const CONTENT_TYPE                      = null;

    /**
     * @var string
     */
    public $error                           = "";
    /**
     * @var int
     */
    public $status                          = 0;
    /**
     * @var null|mixed
     */
    private $debug                           = null;

    abstract public function output();

    /**
     * @return array
     */
    protected function get_vars()
    {
        $vars                               = get_object_vars($this);
        if (!Constant::DEBUG) {
            unset($vars["debug"]);
        }

        return $vars;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->get_vars();
    }

    /**
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this->get_vars());
    }

    /**
     * @param $status
     * @param null|string $msg
     * @return $this
     */
    public function error($status, $msg = null)
    {
        $this->status                       = $status;
        $this->error                        = (
            $this->error
            ? $this->error . " "
            : ""
        ) . $msg;

        return $this;
    }

    /**
     * @param null|int $code
     * @return bool
     */
    public function isError($code = null)
    {
        return (bool) (
            $code
            ? isset($this->status[$code])
            : $this->status
        );
    }

    /**
     * @param mixed $data
     * @return DataAdapter
     */
    public function debug($data)
    {
        $this->debug                        = $data;

        return $this;
    }

    /**
     * @param array $values
     * @return DataAdapter
     */
    public function fill($values)
    {
        foreach ($values as $key => $value) {
            $this->$key                     = $value;
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->$key                         = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get($key)
    {
        return (isset($this->$key)
            ? $this->$key
            : null
        );
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isset($key)
    {
        return !empty($this->$key);
    }

    /**
     * @param string $key
     * @return DataAdapter
     */
    public function unset($key)
    {
        unset($this->$key);

        return $this;
    }
}
