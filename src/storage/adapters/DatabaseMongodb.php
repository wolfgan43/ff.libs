<?php
/**
*   VGallery: CMS based on FormsFramework
    Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

 * @package VGallery
 * @subpackage core
 * @author Alessandro Stucchi <wolfgan@gmail.com>
 * @copyright Copyright (c) 2004, Alessandro Stucchi
 * @license http://opensource.org/licenses/gpl-3.0.html
 * @link https://github.com/wolfgan43/vgallery
 */

namespace phpformsframework\libs\storage\adapters;

use phpformsframework\libs\storage\DatabaseAdapter;
use phpformsframework\libs\storage\drivers\MongoDB as nosql;

/**
 * Class DatabaseMongodb
 * @package phpformsframework\libs\storage\adapters
 */
class DatabaseMongodb extends DatabaseAdapter
{
    protected const PREFIX                              = "MONGO_DATABASE_";
    protected const TYPE                                = "nosql";
    protected const KEY_NAME                            = "_id";

    /**
     * @return nosql
     */
    protected function getDriver() : nosql
    {
        return new nosql();
    }

    /**
     * @return array
     */
    protected function getConnector() : array
    {
        $connector                                      = parent::getConnector();
        $connector["key"]                               = self::KEY_NAME;

        return $connector;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function convertFieldSort(string $value): string
    {
        return ($value == "DESC"
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
     * @todo da tipizzare
     * @param $value
     * @param string $struct_type
     * @param string|null $name
     * @param string|null $op
     * @return mixed
     */
    protected function fieldOperation($value, string $struct_type, string $name = null, string $op = null)
    {
        if ($name == $this->key_name) {
            $struct_type = DatabaseAdapter::FTYPE_PRIMARY;
        }

        return $this->driver->toSql($value, $struct_type);
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
}
