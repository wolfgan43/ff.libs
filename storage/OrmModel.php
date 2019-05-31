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

namespace phpformsframework\libs\storage\models;

use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Error;
use phpformsframework\libs\storage\Database;
use phpformsframework\libs\storage\database\Adapter;
use phpformsframework\libs\storage\Orm;

abstract class Model {
    const BUCKET                                                                            = NULL;
    const TYPE                                                                              = NULL;
    const MAIN_TABLE                                                                        = NULL;

    const CONNECTORS                                                                        = array();

    protected $adapters                                                                     = null;
    protected $struct                                                                       = null;
    protected $relationship                                                                 = null;
    protected $indexes                                                                      = null;
    protected $tables                                                                       = null;
    protected $alias                                                                        = null;

    public function __construct($databaseAdapters = null)
    {
        $this->setAdapters($databaseAdapters);
    }



    public function setAdapters($databaseAdapters = null) {
        if(is_array($databaseAdapters)) {
            foreach($databaseAdapters AS $adapter => $connector) {
                if(is_numeric($adapter) && strlen($connector)) {
                    $adapter                                                                = $connector;
                    $connector                                                              = null;
                }

                $this->adapters[$adapter]                                                   = (is_array($connector)
                                                                                                ? $connector
                                                                                                : $this->setConnector($adapter)
                                                                                            );
            }
        } elseif($databaseAdapters) {
            $this->adapters[$databaseAdapters]                                              = $this->setConnector($databaseAdapters);
        } else {
            $this->adapters                                                                 = array_intersect_key($this->adapters, static::CONNECTORS);
        }

        return $this;
    }

    private function setConnector($adapter) {
        $res                                                                                = null;
        $connectors                                                                         = static::CONNECTORS;
        if(isset($connectors[$adapter])) {
            $res                                                                            = $connectors[$adapter];
            //, "prefix"			=> "DB_" . self::BUCKET . "_" . strtoupper($adapter)
        } else {
            Error::register("Adapter not found. The adapters available are: " . implode(", ", array_keys($connectors)), "orm");
        }

        return $res;
    }


    /**
     * @param string $table_name
     * @return array
     */
    public function getStruct($table_name)
    {
        $res                                                                                = array(
                                                                                                "mainTable"   => static::MAIN_TABLE
                                                                                            );

        $res["table"]                                                                       = (isset($this->tables[$table_name])
                                                                                                ? $this->tables[$table_name]
                                                                                                : null
                                                                                            );
        if(!isset($table["name"]))                                                          { $table["name"]    = $table_name; }
        $res["struct"]                                                                      = (isset($this->struct[$table_name])
                                                                                                ? $this->struct[$table_name]
                                                                                                : null
                                                                                            );
        $res["indexes"]                                                                     = (isset($this->indexes[$table_name])
                                                                                                ? $this->indexes[$table_name]
                                                                                                : null
                                                                                            );
        $res["relationship"]                                                                = (isset($this->relationship[$table_name])
                                                                                                ? $this->relationship[$table_name]
                                                                                                : null
                                                                                            );
        $res["alias"]                                                                       = (isset($this->alias[$table_name])
                                                                                                ? $this->alias[$table_name]
                                                                                                : null
                                                                                            );
        return $res;
    }

    public function getMainTable() {
        return $this::MAIN_TABLE;
    }

    public function getName() {
        return $this::TYPE;
    }

    public function getMainModel() {
        return ($this::TYPE == $this::BUCKET
            ? $this
            : Orm::getInstance($this::BUCKET)
        );
    }

    /**
     * @param null|array $struct
     * @param null|array $opt
     * @return Adapter
     */
    public function setStorage($struct = null, $opt = null)
    {
        if(!$struct)                                                                        { $struct = $this->getStruct($this->getMainTable()); }

        $struct["exts"]                                                                     = (isset($opt["exts"])
                                                                                                ? $opt["exts"]
                                                                                                : true
                                                                                            );

        $struct["rawdata"]                                                                  = (isset($opt["rawdata"])
                                                                                                ? $opt["rawdata"]
                                                                                                : false
                                                                                            );
        return Database::getInstance($this->adapters, $struct);
    }


    /**
     * @param null|array $fields
     * @param null|array $where
     * @param null|array $sort
     * @param null|array $limit
     * @return array|bool|null
     */
    public function read($fields = null, $where = null, $sort = null, $limit = null) {
        if(!$where && !$sort && !$limit) {
            $where                                                                          = $fields;
            $fields                                                                         = null;
        }

        return Orm::read($where, $fields, $sort, $limit, $this);
    }
    /**
     * @param null|array $fields
     * @param null|array $where
     * @param null|array $sort
     * @param null|array $limit
     * @return array|bool|null
     */
    public function readRawData($fields = null, $where = null, $sort = null, $limit = null) {
        if(!$where && !$sort && !$limit) {
            $where                                                                          = $fields;
            $fields                                                                         = null;
        }

        return Orm::readRawData($where, $fields, $sort, $limit, $this);
    }
/**
     * @param array $data
     * @return array|bool|null
     */
    public function insert($data) {
        return Orm::insert($data, $this);
    }

    /**
     * @param array $set
     * @param array $where
     * @return array|bool|null
     */
    public function update($set, $where) {
        return Orm::update($set, $where, $this);


    }

    /**
     * @param array $where
     * @param null|array $set
     * @param null|array $insert
     * @return array|bool|null
     */
    public function write($where, $set = null, $insert = null) {
        return Orm::write($where, $set, $insert, $this);


    }

    /**
     * @param string $name
     * @param null|array $where
     * @param null|array $fields
     * @return array|bool|null
     */
    public function cmd($name, $where = null, $fields = null) {
        return Orm::cmd($name, $where, $fields, $this);
    }

    /**
     * @param array $where
     * @return array|bool|null
     */
    public function delete($where) {
        return Orm::delete($where, $this);
    }


}