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

namespace phpformsframework\libs\storage;

use phpformsframework\libs\Error;
use phpformsframework\libs\Mappable;

/**
 * Class OrmModel
 * @package phpformsframework\libs\storage
 */
class OrmModel extends Mappable
{
    protected $bucket                                                                       = null;
    protected $type                                                                         = null;
    protected $main_table                                                                   = null;

    protected $connectors                                                                   = array();

    protected $adapters                                                                     = array();
    protected $struct                                                                       = null;
    protected $relationship                                                                 = null;
    protected $indexes                                                                      = null;
    protected $tables                                                                       = null;
    protected $alias                                                                        = null;

    /**
     * OrmModel constructor.
     * @param string $map_name
     * @param string|null $main_table
     */
    public function __construct(string $map_name, string $main_table = null)
    {
        parent::__construct($map_name);

        if ($mainTable) {
            $this->main_table = $mainTable;
        }
        $this->setAdapters($databaseAdapters);
    }

    /**
     * @param string $table_name
     * @return array
     */
    public function getStruct($table_name)
    {
        $res                                                                                = array(
                                                                                                "mainTable"   => $this->main_table
                                                                                            );

        $res["table"]                                                                       = (
            isset($this->tables[$table_name])
                                                                                                ? $this->tables[$table_name]
                                                                                                : null
                                                                                            );
        if (!isset($table["name"])) {
            $table["name"]                                                                  = $table_name;
        }
        $res["struct"]                                                                      = (
            isset($this->struct[$table_name])
                                                                                                ? $this->struct[$table_name]
                                                                                                : null
                                                                                            );
        $res["indexes"]                                                                     = (
            isset($this->indexes[$table_name])
                                                                                                ? $this->indexes[$table_name]
                                                                                                : null
                                                                                            );
        $res["relationship"]                                                                = (
            isset($this->relationship[$table_name])
                                                                                                ? $this->relationship[$table_name]
                                                                                                : null
                                                                                            );
        $res["key_primary"]                                                                 = (
            $res["struct"]
                                                                                                ? (string) array_search(DatabaseAdapter::FTYPE_PRIMARY, $res["struct"])
                                                                                                : null
                                                                                            );
        return $res;
    }

    public function getMainTable()
    {
        return $this->main_table;
    }

    /**
     * @return string|null
     */
    public function getName() : ?string
    {
        return $this->type;
    }

    /**
     * @return OrmModel
     */
    public function getMainModel() : self
    {
        return ($this->type == $this->bucket
            ? $this
            : Orm::getInstance($this->bucket)
        );
    }

    /**
     * @param null|array $struct
     * @param bool $exts
     * @param bool $rawdata
     * @return Database
     */
    public function setStorage(array $struct = null, bool $exts = true, bool $rawdata = false) : Database
    {
        if (!$struct) {
            $struct                                                                         = $this->getStruct($this->getMainTable());
        }

        return Database::getInstance($this->adapters, $struct, $exts, $rawdata);
    }


    /**
     * @param null|array $fields
     * @param null|array $where
     * @param null|array $sort
     * @param null|array $limit
     * @return array|bool|null
     */
    public function read($fields = null, $where = null, $sort = null, $limit = null)
    {
        return Orm::read($where, $fields, $sort, $limit, $offset, $this);
    }
    /**
     * @param null|array $fields
     * @param null|array $where
     * @param null|array $sort
     * @param null|array $limit
     * @return array|bool|null
     */
    public function readRawData($fields = null, $where = null, $sort = null, $limit = null)
    {
        return Orm::readRawData($where, $fields, $sort, $limit, $offset, $this);
    }

    /**
     * @param array $data
     * @return array|bool|null
     */
    public function insertUnique($data)
    {
        return Orm::insertUnique($data, $this);
    }

    /**
         * @param array $data
         * @return array|bool|null
         */
    public function insert($data)
    {
        return Orm::insert($data, $this);
    }

    /**
     * @param array $set
     * @param array $where
     * @return array|bool|null
     */
    public function update($set, $where)
    {
        return Orm::update($set, $where, $this);
    }

    /**
     * @param array $where
     * @param null|array $set
     * @param null|array $insert
     * @return array|bool|null
     */
    public function write($where, $set = null, $insert = null)
    {
        return Orm::write($where, $set, $insert, $this);
    }

    /**
     * @param string $name
     * @param null|array $where
     * @param null|array $fields
     * @return array|bool|null
     */
    public function cmd($name, $where = null, $fields = null)
    {
        return Orm::cmd($name, $where, $fields, $this);
    }

    /**
     * @param array $where
     * @return array|bool|null
     */
    public function delete($where)
    {
        return Orm::delete($where, $this);
    }
}
