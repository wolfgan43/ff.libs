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

use stdClass;

/**
 * Class DataTableResponse
 * @package phpformsframework\libs\dto
 */
class DataTableResponse extends DataResponse
{
    public $class               = null;
    public $draw                = 0;
    public $recordsTotal        = 0;
    public $recordsFiltered     = 0;

    public function __construct(array $data = array())
    {
        parent::__construct($data);

        $this->draw             = 1;
        $this->recordsFiltered  = count($data);
        $this->recordsTotal     = count($data);
    }

    /**
     * @return array
     */
    protected function getDefaultVars() : array
    {
        return [
            "draw"              => $this->draw,
            "data"              => $this->data,
            "recordsTotal"      => $this->recordsTotal,
            "recordsFiltered"   => $this->recordsFiltered,
            "error"             => $this->error,
            "status"            => $this->status
        ];
    }

    /**
     * @param string $name
     * @return array
     */
    public function getColumn(string $name) : array
    {
        return array_column($this->data ?? [], $name);
    }

    public function columns() : array
    {
        return array_keys($this->data[0] ?? []);
    }

    /**
     * @return array|stdClass
     */
    public function toObject()
    {
        return parent::toObject() ?? [];
    }
}
