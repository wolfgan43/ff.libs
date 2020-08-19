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

namespace phpformsframework\libs\storage\dto;

use phpformsframework\libs\dto\Mapping;

/**
 * Class OrmResult
 * @package phpformsframework\libs\storage\dto
 */
class OrmResults
{
    use Mapping;

    private $recordset              = array();
    private $countRecordset         = null;
    private $countTotal             = null;

    private $record_map_class       = null;
    private $key_name               = null;
    private $recordset_keys         = null;

    private $filter                 = null;

    /**
     * OrmResult constructor.
     * @param array|null $recordset
     * @param int|null $count
     * @param array|null $recordset_keys
     * @param string|null $primary_key
     * @param string|null $record_map_class
     */
    public function __construct(array $recordset = null, int $count = null, array $recordset_keys = null, string $primary_key = null, string $record_map_class = null)
    {
        $this->key_name             = $primary_key;
        $this->countTotal           = $count;
        $this->record_map_class     = $record_map_class;

        $counter                    = $recordset ?? $recordset_keys;
        $this->countRecordset       = (
            empty($counter)
            ? $this->countTotal
            : count($counter)
        );

        $this->recordset            = (array) $recordset;
        $this->recordset_keys       = $recordset_keys;
    }

    /**
     * @return string
     */
    public function getPrimaryKey() : string
    {
        return $this->key_name;
    }
    /**
     * @return int|null
     */
    public function countRecordset() : ?int
    {
        return $this->countRecordset;
    }
    /**
     * @return int|null
     */
    public function countTotal() : ?int
    {
        return $this->countTotal;
    }

    /**
     * @param array $fields
     * @return OrmResults
     */
    public function filter(array $fields) : self
    {
        $this->filter               = array_fill_keys($fields, true);

        return $this;
    }

    /**
     * @param string|null $class_name
     * @param bool $send_record_to_constructor
     * @return object|null
     */
    public function first(string $class_name = null, bool $send_record_to_constructor = false) : ?object
    {
        return $this->mapRecord($this->seek(0), $class_name, $send_record_to_constructor);
    }

    /**
     * @param string|null $class_name
     * @param bool $send_record_to_constructor
     * @return object|null
     */
    public function last(string $class_name = null, bool $send_record_to_constructor = false) : ?object
    {
        return $this->mapRecord($this->seek($this->countRecordset - 1), $class_name, $send_record_to_constructor);
    }

    /**
     * @param $callback
     * @param string|null $class_name
     * @param bool $use_record_to_constructor
     */
    public function each($callback, string $class_name = null, bool $use_record_to_constructor = false) : void
    {
        $map_class_name             = $this->getMapClassName($class_name);

        foreach ($this->recordset as $record) {
            $callback($this->mapRecord($record, $map_class_name, $use_record_to_constructor));
        }
    }

    /**
     * @param string|null $key_name
     * @return array
     */
    public function keys(string $key_name = null) : array
    {
        return array_column($this->recordset_keys ?? $this->recordset, $key_name ?? $this->key_name) ?? [];
    }

    /**
     * @param int $offset
     * @return string|null
     */
    public function key(int $offset) : ?string
    {
        return $this->keys()[$offset] ?? null;
    }

    /**
     * @param int $offset
     * @param string|null $class_name
     * @param bool $send_record_to_constructor
     * @return object|null
     */
    public function get(int $offset, string $class_name = null, bool $send_record_to_constructor = false) : ?object
    {
        return $this->mapRecord($this->seek($offset), $class_name, $send_record_to_constructor);
    }

    /**
     * @param int $offset
     * @return array|null
     */
    public function getArray(int $offset) : ?array
    {
        return $this->seek($offset);
    }

    /**
     * @return array
     */
    public function getAllArray() : array
    {
        return $this->recordset;
    }

    /**
     * @param string $class_name
     * @param bool $use_record_to_constructor
     * @return object[]
     */
    public function getAllObject(string $class_name = null, bool $use_record_to_constructor = false) : array
    {
        $map_class_name             = $this->getMapClassName($class_name);
        return ($map_class_name
            ? $this->mapRecordset($map_class_name, $use_record_to_constructor)
            : json_decode(json_encode($this->recordset))
        );
    }

    /**
     * @param int $offset
     * @return array|null
     */
    public function seek(int $offset) : ?array
    {
        return $this->recordset[$offset] ?? null;
    }

    /**
     * @param string|null $class_name
     * @return string|null
     */
    private function getMapClassName(string $class_name = null) : ?string
    {
        return $class_name ?? $this->record_map_class;
    }

    /**
     * @param string $class_name
     * @param bool $send_record_to_constructor
     * @return object[]
     */
    private function mapRecordset(string $class_name, bool $send_record_to_constructor = false) : array
    {
        $recordset                  = array();
        foreach ($this->recordset as $record) {
            $recordset[]            = $this->mapRecord($record, $class_name, $send_record_to_constructor);
        }

        return $recordset;
    }

    /**
     * @param string $class_name
     * @param bool $send_record_to_constructor
     * @param array $record
     * @return object[]
     */
    private function mapRecord(array $record = null, string $class_name = null, bool $send_record_to_constructor = false) : ?object
    {
        $obj                        = null;
        if (!empty($record)) {
            $record                 = $this->reduce($record);
            if (!$class_name) {
                $obj                = json_decode(json_encode($record));
            } elseif ($send_record_to_constructor) {
                $obj                = new $class_name($record);
            } else {
                $obj                = new $class_name();
                $this->autoMapping($record, $obj);
            }
        }
        return $obj;
    }

    /**
     * @param array $record
     * @return array
     */
    private function reduce(array $record) : array
    {
        return ($this->filter
            ? array_intersect_key($record, $this->filter)
            : $record
        );
    }
}
