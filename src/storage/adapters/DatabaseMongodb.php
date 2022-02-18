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
namespace phpformsframework\libs\storage\adapters;

use phpformsframework\libs\storage\DatabaseAdapter;
use phpformsframework\libs\storage\DatabaseDriver;
use phpformsframework\libs\storage\drivers\MongoDB as nosql;
use phpformsframework\libs\Exception;

/**
 * Class DatabaseMongodb
 * @package phpformsframework\libs\storage\adapters
 */
class DatabaseMongodb extends DatabaseAdapter
{
    protected const ENGINE                              = nosql::ENGINE;
    protected const KEY_NAME                            = "_id";
    protected const KEY_REL                             = "_id";

    /**
     * @return nosql
     */
    protected function driver(string $prefix = null) : DatabaseDriver
    {
        return new nosql($prefix);
    }

    /**
     * @param array $connection
     * @return void
     */
    protected function setConnection(array $connection) : void
    {
        parent::setConnection($connection);
        $this->key_name                                 = static::KEY_NAME;
    }

    /**
     * @param string $value
     * @return int
     */
    protected function convertFieldSort(string $value) : int
    {
        return ($value === "-1" || $value === "DESC" || $value === "desc"
            ? -1
            : 1
        );
    }

    /**
     * @param array $res
     * @param string $name
     * @param string|null $or
     */
    protected function convertFieldWhere(array &$res, string $name, string $or = null) : void
    {
        if ($or) {
            $res[$or][][$name] = $res[$name];
            unset($res[$name]);
        }
    }

    /**
     * @param string $key_primary
     * @return string
     */
    protected function convertKeyPrimary(string $key_primary): string
    {
        return $this->key_name;
    }

    /**
     * @param $value
     * @param string $struct_type
     * @param string|null $name
     * @param string|null $op
     * @param bool $castResult
     * @return mixed
     * @throws Exception
     * @todo da tipizzare
     */
    protected function fieldOperation($value, string $struct_type, string $name = null, string $op = null, bool $castResult = false)
    {
        if ($name == $this->key_name) {
            $struct_type = DatabaseAdapter::FTYPE_PRIMARY;
        }

        return $this->driver->toSql($value, $struct_type, $castResult);
    }

    /**
     * @param string $struct_type
     * @param string $name
     * @param string|null $op
     * @return mixed|void
     */
    protected function fieldOperationNULL(string $struct_type, string $name, string $op = null)
    {
        return null;
    }

    /**
     * @param array $db
     * @param array|null $indexes
     * @param string|null $map_class
     */
    protected function convertRecordset(array &$db, array $indexes = null, string $map_class = null) : void
    {
        $use_control                                    =  !empty($indexes);
        foreach ($db[self::RESULT] as &$record) {
            if (isset($record[$this->key_name]) && is_object($record[$this->key_name])) {
                $record[$this->key_name]                = $record[$this->key_name]->__toString();
            }
            if ($use_control) {
                $index                                  = array_intersect_key($record, $indexes);
                if (isset($record[$this->key_name])) {
                    $index[$this->def->key_primary]     = $record[$this->key_name];
                }

                $db[self::INDEX][]                      = $index;
            }
            $record                                     = $this->fields2output($record);
        }
    }
}
