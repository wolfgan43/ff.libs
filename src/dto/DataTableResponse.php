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

    public $keys                = null;
    public $columns             = null;
    public $properties          = null;

    public $draw                = 0;
    public $recordsTotal        = 0;
    public $recordsFiltered     = 0;

    private $key                = null;

    public function __construct(array $data = [], string $key = null)
    {
        parent::__construct($data);

        $this->key              = $key;
        $this->draw             = 1;
        $this->recordsTotal     = count($data);
        $this->recordsFiltered  = $this->recordsTotal;
    }

    /**
     * @return array
     */
    protected function getDefaultVars() : array
    {
        $res = [
            "draw"              => $this->draw,
            "data"              => $this->data,
            "recordsTotal"      => $this->recordsTotal,
            "recordsFiltered"   => $this->recordsFiltered,
            "error"             => $this->error,
            "status"            => $this->status
        ];

        if (!empty($this->keys)) {
            $res["keys"]        = $this->keys;
        }
        if (!empty($this->columns)) {
            $res["columns"]     = $this->columns;
        }
        if (!empty($this->properties)) {
            $res["properties"]  = $this->properties;
        }

        return $res;
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
        return $this->columns ?? array_keys($this->data[0] ?? []);
    }

    public function sort(int $index, string $dir) : self
    {
        $this->setColumns();

        $column = $this->columns[$index];
        usort($this->data, function ($a, $b) use ($column, $dir) {
            return ($dir == "desc"
                ? $a[$column] < $b[$column]
                : $a[$column] > $b[$column]
            );
        });

        return $this;
    }

    public function toArray(): array
    {
        $this->setKeys();

        return parent::toArray();
    }

    /**
     * @return array|stdClass
     */
    public function toObject()
    {
        $this->setKeys();

        return parent::toObject() ?? [];
    }

    private function setKeys() : void
    {
        if (!empty($this->key)) {
            $this->keys = $this->getColumn($this->key);
        }
    }

    private function setColumns() : void
    {
        if (empty($this->columns) && !empty($this->data[0])) {
            $this->columns = array_keys($this->data[0]);
        }
    }
}
