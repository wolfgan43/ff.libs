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

use phpformsframework\libs\Mapping;
use stdClass;

/**
 * Class DataResponse
 * @package phpformsframework\libs\dto
 */
class DataResponse extends DataAdapter
{
    use Mapping;

    const CONTENT_TYPE              = "application/json";

    private $outputOnlyBody          = false;

    /**
     * @var array
     */
    private $data                    = array();

    /**
     * DataResponse constructor.
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        if (isset($data["data"])) {
            $this->autoMapping($data);
        } else {
            parent::__construct($data);
        }
    }

    /**
     * @param $object
     * @return $this
     */
    public function fillObject($object) : DataAdapter
    {
        $values = json_decode(json_encode($object), true);

        return  $this->fill($values);
    }

    /**
     * @param array $values
     * @return $this
     */
    public function fill(array $values) : DataAdapter
    {
        $this->data                 = array_replace($this->data, $values);

        return $this;
    }

    /**
     * @param array $values
     * @param string|null $bucket
     * @return $this
     */
    public function filter(array $values, string $bucket = null) : DataAdapter
    {
        if (isset($this->data[$bucket])) {
            $this->data[$bucket]    = array_intersect_key($this->data[$bucket], array_fill_keys($values, true));
        } elseif (!$bucket) {
            $this->data             = array_intersect_key($this->data, array_fill_keys($values, true));
        }
        return $this;
    }

    /**
     * @todo da tipizzare
     * @param string $key
     * @param string|array $value
     * @return $this
     */
    public function set(string $key, $value) : DataAdapter
    {
        $this->data[$key] = (
            is_object($value)
            ? (array) $value
            : $value
        );

        return $this;
    }

    /**
     * @param string $key
     * @return stdClass|array|string|null
     */
    public function get(string $key)
    {
        $res = (
            isset($this->data[$key])
            ? $this->data[$key]
            : null
        );

        return (is_array($res)
            ? (object) $res
            : $res
        );
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isset(string $key) : bool
    {
        return !empty($this->data[$key]);
    }

    /**
     * @param string $key
     * @return $this
     */
    public function unset(string $key) : DataAdapter
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * @param bool $onlyBody
     * @return DataAdapter
     */
    public function outputMode(bool $onlyBody = false) : DataAdapter
    {
        $this->outputOnlyBody = $onlyBody;

        return $this;
    }
    /**
     * @return string
     */
    public function output() : string
    {
        return $this->toJson();
    }
    /**
     * @return array
     */
    protected function getObjectVars() : array
    {
        return ($this->outputOnlyBody
            ? $this->data
            : [
                "data"      => $this->data,
                "error"     => $this->error,
                "status"    => $this->status
            ]
        );
    }
    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->data;
    }
    /**
     * @return stdClass
     */
    public function toObject() : ?stdClass
    {
        return (count($this->data)
            ? (object) $this->data
            : null
        );
    }

    /**
     * @return bool
     */
    public function isEmpty() : bool
    {
        return empty($this->data);
    }

}
