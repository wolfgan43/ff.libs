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
namespace ff\libs\dto;

use ff\libs\Exception;
use stdClass;

/**
 * Class DataResponse
 * @package ff\libs\dto
 */
class DataResponse extends DataAdapter
{
    use Mapping;

    const CONTENT_TYPE              = "application/json";

    private $outputOnlyBody         = false;

    /**
     * @var array
     */
    protected $data                 = array();

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
     * @return $this
     */
    public function clear() : DataAdapter
    {
        $this->data                 = array();

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
     * @todo da tipizzare
     */
    public function get(string $key)
    {
        $res = $this->data[$key] ?? null;

        return (is_array($res) && !isset($res[0])
            ? (object) $res
            : $res
        );
    }

    /**
     * @param int $offset
     * @return array|string|null
     * @todo da tipizzare
     */
    public function getArray(int $offset)
    {
        return $this->data[$offset] ?? null;
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
     * @throws Exception
     */
    public function output() : string
    {
        return $this->toJson($this->outputOnlyBody);
    }
    /**
     * @return array
     */
    protected function getVars() : array
    {
        return ($this->outputOnlyBody
            ? $this->data
            : $this->getDefaultVars()
        );
    }

    /**
     * @return array
     */
    protected function getDefaultVars() : array
    {
        return [
            "data"      => $this->data,
            "error"     => $this->error,
            "status"    => $this->status
        ];
    }
    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->data;
    }
    /**
     * @return stdClass|array|null
     */
    public function toObject() /*: ?stdClass con array sequenziali da errore */
    {
        return (!empty($this->data)
            ? json_decode(json_encode($this->data))
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
