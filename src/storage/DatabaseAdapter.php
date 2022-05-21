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
namespace ff\libs\storage;

use ff\libs\storage\dto\OrmDef;
use ff\libs\storage\dto\Schema;
use ff\libs\Exception;

/**
 * Class DatabaseAdapter
 * @package ff\libs\storage
 */
abstract class DatabaseAdapter implements Constant
{
    public const MAX_NUMROWS            = 10000;

    private const OPERATOR_COMPARISON   = [
                                            '$gt'       => self::FTYPE_NUMBER,
                                            '$gte'      => self::FTYPE_NUMBER,
                                            '$lt'       => self::FTYPE_NUMBER,
                                            '$lte'      => self::FTYPE_NUMBER,
                                            '$eq'       => false,
                                            '$regex'    => self::FTYPE_STRING,
                                            '$in'       => self::FTYPE_ARRAY,
                                            '$nin'      => self::FTYPE_ARRAY,
                                            '$ne'       => self::FTYPE_STRING,
                                            '$inset'    => self::FTYPE_ARRAY,
                                            '$inc'      => false,
                                            '$inc-'     => false,
                                            '$addToSet' => false,
                                            '$set'      => false
                                        ];

    protected const OR                  = '$or';
    protected const AND                 = '$and';
    protected const OP_ARRAY_DEFAULT    = '$in';
    protected const OP_DEFAULT          = '$eq';
    protected const OP_INC_DEC          = '$inc';
    protected const OP_ADD_TO_SET       = '$addToSet';
    protected const OP_SET              = '$set';

    private const ERROR_INSERT_IS_EMPTY = "insert is empty";
    private const ERROR_UPDATE_IS_EMPTY = "set and/or where are empty";
    private const ERROR_WRITE_IS_EMPTY  = "insert and/or set and/or where are empty";
    private const ERROR_DELETE_IS_EMPTY = "you cant truncate table. where is empty";

    protected const ENGINE              = null;
    protected const KEY_NAME            = null;
    protected const KEY_REL             = "ID_";
    protected const KEY_IS_INT          = false;

    protected const RESULT              = Database::RESULT;
    protected const INDEX               = Database::INDEX;

    private const COUNT                 = Database::COUNT;
    private const INDEX_PRIMARY         = Database::INDEX_PRIMARY;

    private $connection                 = [
                                            "host"          => null,
                                            "username"      => null,
                                            "secret"        => null,
                                            "name"          => null,
                                            "prefix"		=> null,
                                            "table"         => null,
                                            "key"           => null,
                                            "key_rel"       => null
                                        ];

    protected $prefix                   = null;
    protected $key_name                 = null;
    protected $key_rel_prefix           = null;
    protected $key_rel_suffix           = null;

    /**
     * @var OrmDef
     */
    protected $def                      = null;
    /**
     * @var Schema
     */
    protected $schema                   = null;

    private $index2query                = [];
    private $table_name                 = null;

    /**
     * @var DatabaseDriver
     */
    protected $driver                   = null;

    /**
     * @var DatabaseConverter
     */
    private $converter                  = null;

    /**
     * @param string|null $prefix
     * @return DatabaseDriver
     */
    abstract protected function driver(string $prefix = null) : DatabaseDriver;

    /**
     * @param string $value
     * @return string|int
     * @todo da tipizzare
     */
    abstract protected function convertFieldSort(string $value);

    /**
     * @param array $res
     * @param string $name
     * @param string|null $or
     */
    abstract protected function convertFieldWhere(array &$res, string $name, string $or = null) : void;

    /**
     * @param string $key_primary
     * @return string
     */
    abstract protected function convertKeyPrimary(string $key_primary) : string;

    /**
     * @param $value
     * @param string $struct_type
     * @param string|null $name
     * @param string|null $op
     * @param bool $castResult
     * @return mixed
     * @todo da tipizzare
     */
    abstract protected function fieldOperation($value, string $struct_type, string $name = null, string $op = null, bool $castResult = false);

    /**
     * @param string $struct_type
     * @param string $name
     * @param string|null $op
     * @return mixed
     * @todo da tipizzare
     */
    abstract protected function fieldOperationNULL(string $struct_type, string $name, string $op = null);

    /**
     * DatabaseAdapter constructor.
     * @param array $connection
     * @param OrmDef $def
     * @param Schema|null $schema
     * @throws Exception
     */
    public function __construct(array $connection, OrmDef $def, Schema $schema = null)
    {
        $this->def                                      = $def;
        $this->schema                                   = $schema;
        $this->converter                                = new DatabaseConverter($this->def, $this->schema);

        $this->setConnection($connection);
        $this->driver                                   = $this->driver($this->prefix);
    }

    /**
     * @param array $fields
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $calc_found_rows
     * @param string|null $table_name
     * @return array
     * @throws Exception
     */
    public function read(array $fields, array $where = null, array $sort = null, int $limit = null, int $offset = null, bool $calc_found_rows = false, string $table_name = null) : array
    {
        $res                                                    = null;
        $query                                                  = $this->getQueryRead($fields, $where, $sort, $limit, $offset, $calc_found_rows, $table_name);

        $res[self::RESULT]                                      = $this->processRead($query);
        $res[self::COUNT]                                       = $this->driver->numRows();
        if ($res[self::RESULT]) {
            $count_recordset                                    = (is_array($res[self::RESULT]) ? count($res[self::RESULT]) : null);
            if ($count_recordset && (!empty($query->limit) || $count_recordset < static::MAX_NUMROWS)) {
                $this->convertRecordset($res, array_flip($this->index2query));
            }
        }

        return $res;
    }

    /**
     * @param array $insert
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function insert(array $insert, string $table_name = null) : ?array
    {
        if (empty($insert)) {
            throw new Exception(self::ERROR_INSERT_IS_EMPTY, 500);
        }

        return $this->processInsert($this->getQueryInsert($insert, $table_name));
    }

    /**
     * @param array $set
     * @param array|null $where
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function update(array $set, array $where = null, string $table_name = null) : ?array
    {
        if (empty($set)) {
            throw new Exception(self::ERROR_UPDATE_IS_EMPTY, 500);
        }

        return $this->processUpdate($this->getQueryUpdate($set, $where, $table_name));
    }

    /**
     * @param array $insert
     * @param array $set
     * @param array $where
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function upsert(array $insert, array $set, array $where, string $table_name = null) : ?array
    {
        if (empty($insert) || empty($set) || empty($where)) {
            throw new Exception(self::ERROR_WRITE_IS_EMPTY, 500);
        }

        return $this->processUpsert($this->getQueryUpsert($insert, $set, $where, $table_name));
    }

    /**
     * @param array $where
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function delete(array $where, string $table_name = null) : ?array
    {
        if (empty($where)) {
            throw new Exception(self::ERROR_DELETE_IS_EMPTY, 500);
        }
        //@todo da creare lo switch per gestire la cancellazione logica
        return $this->processDelete($this->getQueryDelete($where, $table_name));
    }

    /**
     * @param string $action
     * @param array|null $where
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function cmd(string $action = self::CMD_COUNT, array $where = null, string $table_name = null) : ?array
    {
        return $this->processCmd($this->getQueryCmd($where, $table_name), $action);
    }

    /**
     * @param array $connection
     */
    protected function setConnection(array $connection) : void
    {
        $this->prefix                           = $connection["prefix"]     ?? null;
        $this->key_name                         = $connection["key"]        ?? static::KEY_NAME;
        $key_rel                                = $connection["key_rel"]    ?? static::KEY_REL;
        if (strpos($key_rel, "_") === 0) {
            $this->key_rel_prefix               = $key_rel;
        } else {
            $this->key_rel_suffix               = $key_rel;
        }
    }

    /**
     * @param array $db
     * @param array|null $indexes
     */
    protected function convertRecordset(array &$db, array $indexes = null) : void
    {
        $use_control                                    = !empty($indexes);
        if ($use_control || $this->converter->issetTo()) {
            foreach ($db[self::RESULT] as &$record) {
                if ($use_control) {
                    $index                              = array_intersect_key($record, $indexes);

                    if (isset($record[$this->key_name])) {
                        $index[$this->def->key_primary] = $record[$this->key_name];
                    }

                    $db[self::INDEX][]                  = $index;
                }

                $record                                 = $this->fields2output($record);
            }
        }
    }

    /**
     * @param array $record
     * @return array
     */
    protected function fields2output(array $record) : array
    {
        return $this->converter->to($record);
    }

    /**
     * @param array $fields
     * @param bool $use_control
     * @return array|string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function querySelect(array $fields, bool $use_control = true)
    {
        $res                                = array();
        if ($use_control) {
            $this->setIndex2Query();
        }

        foreach ($fields as $dbField => $keyField) {
            if (!isset($this->def->struct[$dbField])) {
                continue;
            }

            if (is_bool($keyField) || $dbField == $keyField) {
                $keyField                  = $dbField;
                $this->converter->set($dbField);
            } else {
                $this->converter->set($keyField, $dbField);
            }

            if ($this->key_name != $this->def->key_primary && $dbField == $this->def->key_primary) {
                $dbField = $this->convertKeyPrimary($dbField);
            }
            $res[$dbField]                 = $keyField;
        }

        $this->converter->fields(array_flip($res), empty($this->def->table["preserve_columns_order"]));

        return $res + $this->index2query;
    }

    /**
     * @param array|null $fields
     * @return array|string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function querySort(array $fields = null)
    {
        $res                            = array();
        if ($fields) {
            foreach ($fields as $name => $value) {
                $this->converter->set($name);

                $res[$name]             = $this->convertFieldSort($value);
            }
        }
        return $res;
    }

    /**
     * @param array|null $fields
     * @param string|null $delete_logical_field
     * @return array|string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function queryWhere(array $fields = null, string $delete_logical_field = null)
    {
        $res                            = array();

        if (isset($fields[self::OR])) {
            $res[static::OR]            = self::queryWhere($fields[self::OR]);

            unset($fields[self::OR]);
        }

        if (isset($fields[self::AND])) {
            $fields = $fields[self::AND];
        }

        if ($fields) {
            foreach ($fields as $name => $value) {
                $struct_type            = $this->converter->set($name);
                if (is_array($value)) {
                    if (isset($value[self::OR])) {
                        $this->fieldWhere($res, $value[self::OR], $struct_type, $name, static::OR);
                        unset($value[self::OR]);
                        if (empty($value)) {
                            continue;
                        }
                    }
                    if (isset($value[self::AND])) {
                        $value          = $value[self::AND];
                    }
                }

                $this->fieldWhere($res, $value, $struct_type, $name);
            }

            if ($delete_logical_field && !isset($fields[$delete_logical_field])) {
                $this->fieldWhere($res, false, self::FTYPE_BOOLEAN, $delete_logical_field);
            }
        }

        return $res;
    }

    /**
     * @param array|null $fields
     * @return array
     * @throws Exception
     */
    protected function queryInsert(array $fields = null) : array
    {
        $res                            = array();
        if ($fields) {
            foreach ($fields as $name => $value) {
                if (!isset($this->def->struct[$name])) {
                    continue;
                }

                $struct_type            = $this->converter->set($name);

                $res[$name]             = (
                    $value === null
                    ? $this->fieldOperationNULL($struct_type, $name)
                    : $this->driver->toSql($this->converter->in($name, $value), $struct_type, true)
                );
            }
        }
        ksort($res);

        return $res;
    }

    /**
     * @param array|null $fields
     * @return array|string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function queryUpdate(array $fields = null)
    {
        $res                                                = [];
        if ($fields) {
            foreach ($fields as $name => $value) {
                if (!isset($this->def->struct[$name])) {
                    continue;
                }

                $struct_type                                = $this->converter->set($name);
                if ($value === "++") {
                    $res[self::OP_INC_DEC][$name]           = $this->fieldOperation($this->converter->in($name, $value), $struct_type, $name, self::OP_INC_DEC, true);
                } elseif ($value === "--") {
                    $res[self::OP_INC_DEC][$name]           = $this->fieldOperation($this->converter->in($name, $value), $struct_type, $name, self::OP_INC_DEC . '-', true);
                } elseif ($value === "+") {
                    $res[self::OP_ADD_TO_SET][$name]        = $this->fieldOperation($this->converter->in($name, $value), $struct_type, $name, self::OP_ADD_TO_SET, true);
                } else {
                    $res[self::OP_SET][$name]               = $this->fieldOperation($this->converter->in($name, $value), $struct_type, $name, self::OP_SET, true);
                }
            }
        }
        return $res;
    }

    /**
     * @param DatabaseQuery $query
     * @return array|null
     * todo da tipizzare
     */
    private function processRead(DatabaseQuery $query): ?array
    {
        return ($this->driver->read($query)
            ? $this->driver->getRecordset()
            : null
        );
    }

    /**
     * @param DatabaseQuery $query
     * @return array
     */
    private function processInsert(DatabaseQuery $query) : array
    {
        return array(
            self::INDEX_PRIMARY => array(
                $this->def->key_primary => (
                    $this->driver->insert($query)
                    ? $this->driver->getInsertID()
                    : null
                )
            )
        );
    }

    /**
     * @param DatabaseQuery $query
     * @return array
     */
    private function processUpdate(DatabaseQuery $query) : array
    {
        return array(
            self::INDEX_PRIMARY => array(
                $this->def->key_primary => (
                    $this->driver->update($query)
                    ? $this->driver->getUpdatedIDs()
                    : null
                )
            )
        );
    }

    /**
     * @param DatabaseQuery $query
     * @return array
     */
    private function processDelete(DatabaseQuery $query) : array
    {
        return array(
            self::INDEX_PRIMARY => array(
                $this->def->key_primary => (
                    $this->driver->delete($query)
                    ? $this->driver->getDeletedIDs()
                    : null
                )
            )
        );
    }

    /**
     * @param DatabaseQuery $query
     * @return array|null
     * @throws Exception
     */
    private function processUpsert(DatabaseQuery $query) : ?array
    {
        //todo: da valutare se usare REPLACE INTO. Necessario test benckmark
        $res                                            = null;
        $keys                                           = array();
        $query_read                                     = clone $query;
        $query_read->select                             = $this->querySelect([$this->def->key_primary => true], false);
        if ($this->driver->read($query_read)) {
            $keys                                       = array_column((array) $this->driver->getRecordset(), $this->key_name);
        }

        if (!empty($keys)) {
            $query_update                               = clone $query;
            $this->driver->update($query_update);
            $res                                        = array(
                                                            self::INDEX_PRIMARY => array(
                                                                $this->def->key_primary => $keys
                                                            ),
                                                            "action"    => self::ACTION_UPDATE
                                                        );
        } elseif (!empty($query->insert)) {
            $query_insert                           = clone $query;
            $res                                    = array(
                                                        self::INDEX_PRIMARY => array(
                                                            $this->def->key_primary => (
                                                                $this->driver->insert($query_insert)
                                                                ? $this->driver->getInsertID()
                                                                : null
                                                            ),
                                                            "action"    => self::ACTION_INSERT
                                                        )
                                                    );
        }

        return $res;
    }

    /**
     * @param DatabaseQuery $query
     * @param string $action
     * @return array|null
     */
    private function processCmd(DatabaseQuery $query, string $action) : ?array
    {
        return $this->driver->cmd($query, $action);
    }

    /**
     * @return string|null
     */
    private function getTableName() : ?string
    {
        return $this->def->table["name"] ?? null;
    }

    /**
     *
     */
    private function setIndex2Query() : void
    {
        if (!empty($this->def->indexes)) {
            $indexes = array_keys($this->def->indexes);
            $this->index2query = array_combine($indexes, $indexes);
        }

        if ($this->def->key_primary) {
            $this->index2query[$this->def->key_primary]  = $this->def->key_primary;
        }
    }

    /**
     * @param string $action
     * @param string|null $table_name
     * @param bool $calc_found_rows
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQuery(string $action, string $table_name = null, bool $calc_found_rows = false) : DatabaseQuery
    {
        $this->clearResult();

        $this->table_name = $table_name ?: $this->getTableName();
        if (!$this->key_name) {
            throw new Exception(static::ENGINE . " key missing", 500);
        }
        if (!$this->table_name) {
            throw new Exception(static::ENGINE . " table missing", 500);
        }

        return new DatabaseQuery($action, $this->table_name, $this->def->key_primary, $calc_found_rows);
    }

    /**
     * @param array $select
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $table_name
     * @param bool $calc_found_rows
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryRead(array $select, array $where = null, array $sort = null, int $limit = null, int $offset = null, bool $calc_found_rows = false, string $table_name = null) : DatabaseQuery
    {
        $query                  = $this->getQuery(self::ACTION_READ, $table_name, $calc_found_rows);

        $query->select          = $this->querySelect($select, empty($this->def->table["skip_control"]));
        $query->sort            = $this->querySort($sort);
        $query->where           = $this->queryWhere($where, $this->def->table["delete_logical_field"] ?? null);
        $query->limit           = $limit    < 0 ? null : $limit;
        $query->offset          = $offset   < 0 ? null : $offset;

        return $query;
    }

    /**
     * @param array $insert
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryInsert(array $insert, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_INSERT, $table_name);

        $query->insert          = $this->queryInsert($insert);

        return $query;
    }

    /**
     * @param array $set
     * @param array|null $where
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryUpdate(array $set, array $where = null, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_UPDATE, $table_name);

        $query->update          = $this->queryUpdate($set);
        $query->where           = $this->queryWhere($where);

        return $query;
    }

    /**
     * @param array $insert
     * @param array $set
     * @param array $where
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryUpsert(array $insert, array $set, array $where, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_WRITE, $table_name);

        $query->insert          = $this->queryInsert(array_replace($where, $insert));
        $query->update          = $this->queryUpdate($set);
        $query->where           = $this->queryWhere($where);

        return $query;
    }

    /**
     * @param array $where
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryDelete(array $where, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_DELETE, $table_name);

        $query->where           = $this->queryWhere($where);

        return $query;
    }

    /**
     * @param array|null $where
     * @param string|null $table_name
     * @return DatabaseQuery
     * @throws Exception
     */
    private function getQueryCmd(array $where = null, string $table_name = null) : DatabaseQuery
    {
        $query = $this->getQuery(self::ACTION_CMD, $table_name);

        $query->where           = $this->queryWhere($where);

        return $query;
    }

    /**
     * @param string $table
     */
    private function setTable(string $table) : void
    {
        $this->def->table                                   = array(
                                                                "name"      => $table,
                                                                "alias"     => $table,
                                                                "engine"    => "InnoDB",
                                                                "crypt"     => false,
                                                                "pairing"   => false,
                                                                "transfert" => false,
                                                                "charset"   => "utf8"
                                                            );
    }

    /**
     *
     */
    private function clearResult()
    {
        $this->table_name                                           = null;
        $this->index2query                                          = [];
        $this->prototype                                            = [];
    }

    /**
     * @todo da tipizzare
     * @param array $res
     * @param $value
     * @param string $struct_type
     * @param string|null $name
     * @param string|null $or
     */
    private function fieldWhere(array &$res, $value, string $struct_type, string $name = null, string $or = null) : void
    {
        if ($this->key_name != $this->def->key_primary && $name == $this->def->key_primary) {
            $name = $this->convertKeyPrimary($name);
        }

        if (is_array($value)) {
            $res[$name] = $this->fieldOperations($value, $struct_type, $name);
        } else {
            $res[$name][self::OP_DEFAULT] = $this->fieldOperation($this->converter->in($name, $value), $struct_type, $name, self::OP_DEFAULT);
        }

        $this->convertFieldWhere($res, $name, $or);
    }

    /**
     * @param array $values
     * @param string $struct_type
     * @param string $name
     * @return array|null
     */
    private function fieldOperations(array $values, string $struct_type, string $name) : ?array
    {
        $res                                    = [];
        foreach ($values as $op => $value) {
            if (isset(self::OPERATOR_COMPARISON[$op])) {
                $res[$op]          = $this->fieldOperation($this->converter->in($name, $value), $struct_type, $name, $op);
            }
        }

        if (empty($res)) {
            $this->fieldOperationSwitch($res, $struct_type, $name, $values);
        }

        return $res;
    }

    /**
     * @param array $ref
     * @param string $name
     * @param $value
     * @param string $struct_type
     */
    private function fieldOperationSwitch(array &$ref, string $struct_type, string $name, $value) : void
    {
        if (is_array($value) && count($value) > 1) {
            $ref[self::OP_ARRAY_DEFAULT]    = $this->fieldOperation($this->converter->in($name, $value), $struct_type, $name, self::OP_ARRAY_DEFAULT);
        } elseif (isset($value[0])) {
            $ref[self::OP_DEFAULT]          = $this->fieldOperation($this->converter->in($name, $value[0]), $struct_type, $name, self::OP_DEFAULT);
        } elseif (empty($value) && ($operation = $this->fieldOperationNULL($struct_type, $name, self::OP_DEFAULT))) {
            $ref[self::OP_DEFAULT]          = $operation;
        }
    }
}
