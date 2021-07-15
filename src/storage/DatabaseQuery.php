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
namespace phpformsframework\libs\storage;

/**
 * Class DatabaseQuery
 * @package phpformsframework\libs\storage
 */
class DatabaseQuery
{
    public $action              = null;
    public $key_primary         = null;
    public $options             = array();

    public $from                = null;
    public $select              = null;
    public $sort                = null;
    public $where               = null;
    public $limit               = null;
    public $offset              = null;

    public $update              = null;
    public $insert              = null;

    private $calc_found_rows    = false;

    /**
     * DatabaseQuery constructor.
     * @param string $action
     * @param string $table
     * @param string $key_primary
     * @param bool $calc_found_rows
     */
    public function __construct(string $action, string $table, string $key_primary, bool $calc_found_rows)
    {
        $this->action           = $action;
        $this->from             = $table;
        $this->key_primary      = $key_primary;
        $this->calc_found_rows  = $calc_found_rows;
    }

    /**
     * @return bool
     */
    public function calcFoundRows() : bool
    {
        return $this->calc_found_rows && ($this->limit || $this->offset);
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return array_filter(get_object_vars($this));
    }

    /**
     * @return string
     */
    public function toJson() : string
    {
        return json_encode($this->toArray());
    }
}
