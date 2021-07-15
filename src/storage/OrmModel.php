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

use phpformsframework\libs\ClassDetector;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\dto\Mapping;
use phpformsframework\libs\storage\dto\OrmResults;
use phpformsframework\libs\Exception;

/**
 * Class OrmModel
 * @package phpformsframework\libs\storage
 */
class OrmModel
{
    use ClassDetector;
    use Mapping;
    use OrmUtil;

    private $data                                                               = null;

    /**
     * OrmItem constructor.
     * @param string|null $model_name
     * @param OrmItem $ref
     * @param array|null $rawdata
     * @throws Exception
     */
    public function __construct(string $model_name, OrmItem &$ref, array $rawdata = null)
    {
        $this->loadModel($model_name);

        $informationSchema                                                      = $this->db->informationSchema();
        if (!$informationSchema) {
            throw new Exception("information schema not found in Class " . get_class($ref) . " for Model: " . $model_name, 500);
        }

        if (!property_exists($ref, $informationSchema->table)) {
            throw new Exception("missing relation Field: " . $informationSchema->table . " in Class " . get_class($ref) . " for Model: " . $model_name, 500);
        }

        if ($rawdata) {
            $this->fill($rawdata);
            $ref->{$informationSchema->table}                                   = $this->apply();
        }

        if (!empty($ref->{$informationSchema->table})) {
            $this->read([$informationSchema->key => $ref->{$informationSchema->table}]);
        }
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value) : void
    {
        if (array_key_exists($name, $this->data)) {
            $this->data[$name]                                                  = $value;
        }
    }

    /**
     * @param string|null $name
     * @throws Exception
     */
    private function loadModel(string $name = null)
    {
        $this->db                                                               = new Model();
        $this->db->loadModel($name);
        $this->data                                                             = $this->db->dtdModel();
    }

    /**
     * @param object $obj
     * @return OrmModel
     */
    public function fillByObject(object $obj) : self
    {
        $this->fill((array) $obj);

        return $this;
    }

    /**
     * @param array $fields
     * @return OrmModel
     */
    public function fill(array $fields) : self
    {
        $this->data                                                             = array_replace($this->data, array_intersect_key($fields, $this->data));

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function apply() : string
    {
        //$this->fieldConvert($this->data); //@todo da finire
        //$this->verifyRequire($this->data);
        //$this->verifyValidator($this->data);

        if ($this->recordKey) {
            $this->db->update($this->data, [$this->primaryKey => $this->recordKey]);
        } else {
            $item                                                               = $this->db->insert($this->data);
            $this->recordKey                                                    = $item->key(0);
            $this->primaryKey                                                   = $item->getPrimaryKey();
        }

        return $this->recordKey;
    }

    /**
     * @param array|null $where
     * @throws Exception
     */
    private function read(array $where = null) : void
    {
        if ($where) {
            $item                                                               = $this->db
                ->readOne($where);

            if ($item->countRecordset()) {
                $this->recordKey                                                = $item->key(0);
                $this->primaryKey                                               = $item->getPrimaryKey();

                $this->data                                                     = $item->getArray(0);
            }
        }
    }

    /**
     * @return DataResponse
     */
    public function toDataResponse() : DataResponse
    {
        $response                                                               = new DataResponse($this->data);
        if (!$this->recordKey) {
            $response->error(404, $this->db->getName() . " not stored");
        }

        return $response;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->data ?? [];
    }

    /**
     * @return OrmResults|null
     * @throws Exception
     */
    public function delete() : ?OrmResults
    {
        return ($this->primaryKey && $this->recordKey
            ? $this->db->delete([$this->primaryKey => $this->recordKey])
            : null
        );
    }

    /**
     * @return bool
     */
    public function isStored() : bool
    {
        return !empty($this->recordKey);
    }
}
