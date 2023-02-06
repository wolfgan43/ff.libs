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

use ff\libs\cache\Cashable;
use ff\libs\Mappable;
use ff\libs\storage\dto\OrmControllers;
use ff\libs\storage\dto\OrmDef;
use ff\libs\storage\dto\OrmQuery;
use ff\libs\storage\dto\OrmResults;
use ff\libs\Exception;
use ff\libs\storage\dto\Schema;
use stdClass;

/**
 * Class Orm
 * @package ff\libs\storage
 */
class Orm extends Mappable
{
    use Cashable;
    use Hoockable;

    /**
     * @var Orm[]
     */
    private static $singleton                                                               = null;
    private static $logical_fields                                                          = null;

    public const ERROR_BUCKET                                                               = "orm";

    private const SCOPE_SELECT                                                              = "select";
    private const SCOPE_WHERE                                                               = "where";
    private const SCOPE_SORT                                                                = "sort";
    private const SCOPE_INSERT                                                              = "insert";
    private const SCOPE_SET                                                                 = "set";
    private const SCOPE_ACTION                                                              = "action";

    private const ACTION_READ                                                               = Database::ACTION_READ;
    private const ACTION_READONE                                                            = Database::ACTION_READ . "One";
    private const ACTION_INSERT                                                             = Database::ACTION_INSERT;
    private const ACTION_UPDATE                                                             = Database::ACTION_UPDATE;
    private const ACTION_DELETE                                                             = Database::ACTION_DELETE;
    private const ACTION_WRITE                                                              = Database::ACTION_WRITE;
    private const ACTION_CMD                                                                = Database::ACTION_CMD;
    private const ACTION_INSERT_UNIQUE                                                      = Database::ACTION_INSERT . "Unique";

    private const TNAME                                                                     = "name";
    private const TALIAS                                                                    = "alias";

    private const FREL_TABLE_NAME                                                           = "tbl";
    private const FREL_TABLE_KEY                                                            = "key";
    private const FREL_PRIMARY                                                              = "primary";
    private const FREL_EXTERNAL                                                             = "external";
    private const FREL_CONTROLLER                                                           = "collection";
    private const FREL_ONE_TO_ONE                                                           = "one2one";

    private const RESULT                                                                    = Database::RESULT;
    private const INDEX                                                                     = Database::INDEX;
    private const INDEX_PRIMARY                                                             = Database::INDEX_PRIMARY;
    private const COUNT                                                                     = Database::COUNT;

    private $default_table                                                                  = null;

    protected $collection                                                                   = null;
    protected $main_table                                                                   = null;

    protected $connectors                                                                   = [];
    protected $adapters                                                                     = [];

    protected $struct                                                                       = null;
    protected $relationship                                                                 = null;
    protected $indexes                                                                      = null;
    protected $tables                                                                       = null;

    /**
     * @var OrmControllers
     */
    private $services_by_data                                                               = null;
    /**
     * @var OrmQuery
     */
    private $main                                                                           = null;

    /**
     * @var OrmQuery[][]
     */
    private $subs                                                                           = null;
    private $rev                                                                            = [];
    private $rel                                                                            = [];
    private $rel_done                                                                       = [];

    private $result                                                                         = [];
    private $result_keys                                                                    = [];

    /**
     * @var Schema
     */
    private $schema                                                                         = null;
    private $map_class                                                                      = null;
    private $preserve_columns_order                                                         = false;

    private $count                                                                          = 0;

    /**
     * @param string|null $prefix_connection
     * @return DatabaseDriver
     */
    public static function sql(string $prefix_connection = null) : DatabaseDriver
    {
        $class_name = __NAMESPACE__ . "\\drivers\\MySqli";
        return new $class_name($prefix_connection);
    }

    /**
     * @param string|null $prefix_connection
     * @return DatabaseDriver
     */
    public static function noSql(string $prefix_connection = null) : DatabaseDriver
    {
        $class_name = __NAMESPACE__ . "\\drivers\\MongoDB";
        return new $class_name($prefix_connection);
    }

    /**
     * @param Schema $schema
     * @return static
     */
    public static function &model(Schema $schema) : self
    {
        return self::getInstance($schema->collection, $schema->table, $schema->mapclass)
            ->setSchema($schema);
    }

    /**
     * @param string $collection
     * @param string|null $mainTable
     * @param string|null $mapClass
     * @return Orm
     */
    public static function &getInstance(string $collection, string $mainTable = null, string $mapClass = null) : self
    {
        return self::orm($collection)
            ->setMainTable($mainTable)
            ->setMapClass($mapClass);
    }

    /**
     * @param string $collection
     * @param string|null $table
     * @return stdClass|null
     */
    public static function informationSchema(string $collection, string $table = null) : ?stdClass
    {
        $orm                                                                                = self::orm($collection);

        if (!$table) {
            $table                                                                          = $orm->main_table;
        }

        if (!isset($orm->struct[$table])) {
            return null;
        }

        return (object) [
            "collection"    => $orm->collection,
            "table"         => $table,
            "dtd"           => $orm->struct[$table]         ?? [],
            "schema"        => $orm->tables[$table]         ?? [],
            "relationship"  => $orm->relationship[$table]   ?? [],
            "indexes"       => $orm->indexes[$table]        ?? [],
            "key"           => $orm->primaryKey($table)
        ];
    }

    /**
     * @param string $collection
     * @param string $table
     * @return array|null
     */
    public static function dtd(string $collection, string $table) : ?array
    {
        return self::orm($collection)->struct[$table] ?? null;
    }

    /**
     * @param string $collection
     * @return Orm
     */
    private static function orm(string $collection) : Orm
    {
        if (!isset(self::$singleton[$collection])) {
            self::$singleton[$collection]                                                   = new Orm($collection);
        }

        return self::$singleton[$collection];
    }

    /**
     * Orm constructor.
     * @param string $collection
     */
    public function __construct(string $collection)
    {
        parent::__construct($collection);

        $this->adapters                                                                 = array_intersect_key($this->connectors, $this->adapters);
        $this->default_table                                                            = $this->main_table;
    }

    /**
     * @param string|null $main_table
     * @return Orm
     */
    private function &setMainTable(string $main_table = null) : self
    {
        $this->main_table                                                               = (
            $main_table && isset($this->tables[$main_table])
            ? $main_table
            : $this->default_table
        );

        return $this;
    }

    /**
     * @param array|null $logical_fields
     * @param bool $preserve_columns_order
     * @return Orm
     */
    public function setLogicalField(array $logical_fields = null, bool $preserve_columns_order = false) : self
    {
        self::$logical_fields[$this->main_table]                                            = $logical_fields;

        $this->preserve_columns_order                                                       = $preserve_columns_order;

        return $this;
    }

    /**
     * @param string|null $map_class
     * @return Orm
     */
    private function &setMapClass(string $map_class = null) : self
    {
        $this->map_class                                                                    = $map_class;

        return $this;
    }

    /**
     * @param Schema $schema
     * @return $this
     */
    private function &setSchema(Schema $schema) : self
    {
        $this->schema                                                                       = $schema;

        return $this;
    }

    /**
     * @param string $table_name
     * @return OrmDef
     * @throws Exception
     */
    private function getStruct(string $table_name) : OrmDef
    {
        $def                                                                                = new OrmDef($this->main_table);

        $def->table                                                                         = $this->extractData($this->tables, $table_name, "tables");
        $def->struct                                                                        = $this->extractData($this->struct, $table_name, "struct");
        $def->indexes                                                                       = $this->extractData($this->indexes, $table_name);
        $def->relationship                                                                  = $this->extractData($this->relationship, $table_name);
        $def->setKeyPrimary();

        return $def;
    }

    /**
     * @param array $def
     * @param string $key
     * @param null $error
     * @return array|null
     * @throws Exception
     */
    private function extractData(array $def, string $key, $error = null) : ?array
    {
        if ($error && !isset($def[$key])) {
            throw new Exception("missing Table: `" . $key . "` on Map: " . $error . " Model: " . $this->collection, 500);
        }

        return $def[$key] ?? null;
    }


    /**
     * @param string $table_name
     * @return string|null
     */
    private function getTableAlias(string $table_name) : ?string
    {
        return $this->tables[$table_name][self::TALIAS] ?? null;
    }

    /**
     * @return string|null
     */
    private function getMainTable() : ?string
    {
        return $this->main_table;
    }

    /**
     * @return string|null
     */
    private function getCollection() : ?string
    {
        return $this->collection;
    }

    /**
     * @return Orm
     */
    private function getMainModel() : self
    {
        return $this;
    }

    /**
     * @param OrmDef $def
     * @return Database
     */
    private function setStorage(OrmDef $def) : Database
    {
        $def->table["preserve_columns_order"]   = $this->preserve_columns_order;
        $def->hook                              =& $this->hook;

        return Database::getInstance($this->adapters, $def, $this->schema);
    }

    /**
     * @param string $table
     * @return string|null
     */
    public function primaryKey(string $table) : ?string
    {
        static $keys        = null;

        if (!isset($this->struct[$table])) {
            return null;
        }

        if (!isset($keys[$table])) {
            $keys[$table]   = array_search(Database::FTYPE_PRIMARY, $this->struct[$table]);
        }

        return $keys[$table];
    }

    /**
     * @param null|array $select
     * @param null|array $where
     * @param null|array $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $calc_found_rows
     * @return array|null
     * @throws Exception
     */
    public function read(array $select = null, array $where = null, array $sort = null, int $limit = null, int $offset = null, bool $calc_found_rows = true) : ?OrmResults
    {
        if ($this->cacheRequest(self::ACTION_READ, [$select, $where, $sort, $limit, $offset], $this->result, $this->main_table)) {
            $this->get($select, $where, $sort, $limit, $offset, $calc_found_rows);

            $this->cacheStore($this->result, $this->main_table);
        }

        return $this->getResult();
    }

    /**
     * @param null|array $select
     * @param null|array $where
     * @param null|array $sort
     * @param int|null $offset
     * @return object|null
     * @throws Exception
     */
    public function readOne(array $select = null, array $where = null, array $sort = null, int $offset = null) : ?object
    {
        if ($this->cacheRequest(self::ACTION_READONE, [$select, $where, $sort, $offset], $this->result, $this->main_table)) {
            $this->get($select, $where, $sort, 1, $offset);

            $this->cacheStore($this->result, $this->main_table);
        }

        return $this->getResult()->first();
    }

    /**
     * @param array $insert
     * @return OrmResults
     * @throws Exception
     */
    public function insertUnique(array $insert) : OrmResults
    {
        $this->cacheRequest(self::ACTION_INSERT_UNIQUE, [$insert]);

        $this->set($insert, null, $insert);

        $this->cacheUpdate();

        return $this->getResult();
    }

    /**
     * @param array $insert
     * @return OrmResults
     * @throws Exception
     */
    public function insert(array $insert) : OrmResults
    {
        $this->cacheRequest(self::ACTION_INSERT, [$insert]);

        $this->set(null, null, $insert);

        $this->cacheUpdate();

        return $this->getResult();
    }

    /**
     * @param array $set
     * @param array|null $where
     * @return OrmResults
     * @throws Exception
     */
    public function update(array $set, array $where = null) : OrmResults
    {
        if (!empty($set)) {
            $this->cacheRequest(self::ACTION_UPDATE, [$set, $where]);

            $this->set($where, $set);

            $this->cacheUpdate();
        }

        return $this->getResult();
    }

    /**
     * @param array $where
     * @return OrmResults
     * @throws Exception
     */
    public function delete(array $where) : OrmResults
    {
        $this->set($where);

        return $this->getResult();
    }

    /**
     * @param array $where
     * @param null|array $set
     * @param null|array $insert
     * @return OrmResults
     * @throws Exception
     */
    public function upsert(array $where, array $set = null, array $insert = null) : OrmResults
    {
        $this->cacheRequest(self::ACTION_WRITE, [$where, $set, $insert]);

        $this->set($where, $set, $insert);

        $this->cacheUpdate();

        return $this->getResult();
    }

    /**
     * @param string $action
     * @param null|array $where
     * @return mixed
     * @throws Exception
     * todo: da tipizzare
     */
    public function cmd(string $action, array $where = null)
    {
        if ($this->cacheRequest(self::ACTION_CMD, [$action, $where], $this->result, $this->main_table)) {
            $this->clearResult();

            $this->resolveFieldsByScopes(array(
                self::SCOPE_WHERE       => $where
            ));

            $this->execSub($action);
            $this->cmdData($action);

            $this->cacheStore($this->result, $this->main_table);
        }

        return $this->getResult()->getArray(0)[$action];
    }

    /**
     * @param null|array $select
     * @param null|array $where
     * @param null|array $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $calc_found_rows
     * @throws Exception
     */
    private function get(array $select = null, array $where = null, array $sort = null, int $limit = null, int $offset = null, bool $calc_found_rows = false) : void
    {
        $this->clearResult();

        $single_service                                                                     = $this->resolveFieldsByScopes(array(
                                                                                                self::SCOPE_SELECT    => $select,
                                                                                                self::SCOPE_WHERE     => $where,
                                                                                                self::SCOPE_SORT      => $sort
                                                                                            ));

        if ($single_service) {
            $this->setCount($this->getDataSingle($this->services_by_data->last, $this->services_by_data->last_table, $limit, $offset, $calc_found_rows));
        } else {
            if (!$this->throwRunnerSubs(true)) {
                $this->throwRunnerMain($limit, $offset, $calc_found_rows);
            }
            $this->throwRunnerSubs();
            $this->throwRunnerMain($limit, $offset, $calc_found_rows);
            while($this->throwRunnerSubs() > 0);
        }
    }

    /**
     * Run Main query if where isset
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $calc_found_rows
     * @return void
     * @throws Exception
     */
    private function throwRunnerMain(int $limit = null, int $offset = null, bool $calc_found_rows = false) : void
    {
        if (!$this->main->runned) {
            $this->setCount($this->getData($this->main, $this->main->service, $this->main->def->mainTable, $limit, $offset, $calc_found_rows));
            $this->main->runned         = true;
        }
    }

    /**
     * Run Sub query if where isset
     * @param bool $unique
     * @return void
     * @throws Exception
     */
    private function throwRunnerSubs(bool $unique = false) : int
    {
        $counter = 0;
        if (!empty($this->subs)) {
            foreach ($this->subs as $controller => $tables) {
                foreach ($tables as $table => $sub) {
                    if (!$sub->runned && !empty($sub->where)
                        && (!$unique || empty($this->main->where) || $sub->uniqueIndex())
                    ) {
                        $sub->runned            = true;
                        if (!($counter += $this->getData($sub, $controller, $table)) && $unique && empty($this->main->where) && !$this->main->runned) {
                            $this->main->runned = true;
                        }
                    }
                }
            }
        }

        return $counter;
    }

    /**
     * @param OrmQuery $data
     * @param string $controller
     * @param string $table
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $calc_found_rows
     * @return int
     * @throws Exception
     */
    private function getData(OrmQuery $data, string $controller, string $table, int $limit = null, int $offset = null, bool $calc_found_rows = false) : int
    {
        $main_table                                                                         = $this->main_table;
        $Orm                                                                                = $this->getModel($controller);
        $regs                                                                               = $Orm
                                                                                                ->setStorage($data->getDef(false))
                                                                                                ->read(
                                                                                                    $data->select(true),
                                                                                                    $data->where(self::$logical_fields),
                                                                                                    $data->sort,
                                                                                                    $limit,
                                                                                                    $offset,
                                                                                                    $calc_found_rows
                                                                                                );

        $count                                                                              = $regs[self::COUNT];
        if (!empty($regs[self::RESULT])) {
            $thisTable                                                                      = $table;
            $aliasTable                                                                     = $data->def->table[self::TALIAS];
            $this->result[$thisTable]                                                       = $regs[self::RESULT];
            $this->result_keys[$thisTable]                                                  = $regs[self::INDEX]    ?? [];
            if (!empty($data->def->relationship)) {
                foreach ($data->def->relationship as $ref => $relation) {
                    unset($whereRef);
                    $manyToMany                                                             = false;
                    $oneToMany                                                              = isset($relation[self::FREL_TABLE_NAME]) && isset($data->def->struct[$ref]);
                    if (!$oneToMany && !isset($relation[self::FREL_EXTERNAL])) {
                        throw new Exception("Relation malformed: " . $thisTable . " => " . $ref  . " => " . print_r($relation, true), 500);
                    }
                    if ($oneToMany) {
                        $thisKey                                                            = $ref;

                        $relTable                                                           = $relation[self::FREL_TABLE_NAME];
                        $relKey                                                             = $relation[self::FREL_TABLE_KEY];

                        if ($relTable != $main_table && isset($data->def->relationship[$main_table])) {
                            $manyToMany                                                     = true;
                        }
                    } elseif (isset($data->def->struct[$relation[self::FREL_EXTERNAL]])) {
                        if ($ref != $main_table && isset($data->def->relationship[$main_table])) {
                            $manyToMany                                                     = true;
                        }
                        if (isset($data->def->relationship[$relation[self::FREL_EXTERNAL]])) {
                            $oneToMany                                                      = true;
                        }

                        $thisKey                                                            = $relation[self::FREL_EXTERNAL];

                        $relTable                                                           = $ref;
                        $relKey                                                             = $relation[self::FREL_PRIMARY];
                    } else {
                        $thisKey                                                            = $relation[self::FREL_PRIMARY];

                        $relTable                                                           = $ref;
                        $relKey                                                             = $relation[self::FREL_EXTERNAL];
                    }

                    if (isset($this->rel_done[$thisTable . "." . $thisKey][$relTable . "." . $relKey])) {
                        continue;
                    }

                    $relController                                                          = $relation[self::FREL_CONTROLLER] ?? $controller;
                    if ($main_table == $relTable) {
                        $whereRef                                                           =& $this->main;
                    } elseif (isset($this->subs[$relController][$relTable])) {
                        $whereRef                                                           =& $this->subs[$relController][$relTable];
                    } elseif (isset($this->services_by_data->tables[$relController . "." . $relTable])) {
                        throw new Exception("Relationship not found: " . $thisTable . "." . $thisKey . " => " . $relTable . "." . $relKey, 500);
                    }
                    $keyValue = array_column($regs[self::INDEX] ?? $regs[self::RESULT], $thisKey);
                    if (isset($whereRef) && count($keyValue)) {
                        $this->whereBuilder($whereRef, array_values(array_filter(array_unique($keyValue))), $relKey);
                    } elseif (isset($this->services_by_data->tables[$relController . "." . $relTable])) {
                        throw new Exception("Relationship found but missing keyValue in result. Check in configuration indexes: " . $thisTable . " => " . $thisKey . " (" . $relTable . "." . $relKey . ")", 500);
                    }

                    if (!empty($whereRef) && $relController != $controller && !isset($whereRef->def->relationship[$thisTable])) {
                        if ($oneToMany && !isset($whereRef->def->relationship[$thisTable])) {
                            $whereRef->def->relationship[$thisTable] = [
                                self::FREL_CONTROLLER   => $controller,
                                self::FREL_EXTERNAL     => $thisKey,
                                self::FREL_PRIMARY      => $relKey
                            ];
                        } elseif (!isset($whereRef->def->relationship[$relKey])) {
                            $whereRef->def->relationship[$thisKey] = [
                                self::FREL_CONTROLLER   => $controller,
                                self::FREL_TABLE_NAME   => $thisTable,
                                self::FREL_TABLE_KEY    => $thisKey
                            ];
                        }
                    }

                    $this->rel[$relTable][$thisTable] = $keyValue;
                    $this->rel_done[$thisTable . "." . $thisKey][$relTable . "." . $relKey] = true;

                    if (isset($this->result[$relTable])) {
                        foreach ($keyValue as $keyCounter => $keyID) {
                            if (isset($regs[self::INDEX][$keyCounter][$thisKey]) && $regs[self::INDEX][$keyCounter][$thisKey] == $keyID) {
                                $keyParents                                                                                 = array_keys($this->rel[$thisTable][$relTable], $keyID);
                                if ($main_table == $thisTable) {
                                    foreach ($keyParents as $keyParent) {
                                        /**
                                         * Remove if exist reference of Result in sub table for prevent circular references
                                         */
                                        unset($this->result[$relTable][$keyParent][$aliasTable]);

                                        if (!$oneToMany && !$manyToMany) {
                                            $this->result[$thisTable][$keyCounter][$relTable][]                             =& $this->result[$relTable][$keyParent];
                                        } else {
                                            $this->result[$thisTable][$keyCounter][$whereRef->def->table[self::TALIAS]]     =& $this->result[$relTable][$keyParent];
                                        }
                                    }
                                } elseif ($manyToMany) {
                                    foreach ($keyParents as $keyParent) {
                                        /**
                                         * Remove if exist reference of Result in sub table for prevent circular references
                                         */
                                        unset($this->result[$relTable][$keyParent][$aliasTable]);

                                        $this->result[$thisTable][$keyCounter][$Orm->getTableAlias($relTable)]              =& $this->result[$relTable][$keyParent];
                                    }
                                } else {
                                    /**
                                     * Remove if exist reference of Result in sub table for prevent circular references
                                     */
                                    unset($this->result[$thisTable][$keyCounter][$Orm->getTableAlias($relTable)]);

                                    foreach ($keyParents as $keyParent) {
                                        if ($oneToMany && empty($relation[self::FREL_ONE_TO_ONE])) {
                                            $this->result[$relTable][$keyParent][$aliasTable][]                             =& $this->result[$thisTable][$keyCounter];
                                        } else {
                                            $this->result[$relTable][$keyParent][$aliasTable]                               =& $this->result[$thisTable][$keyCounter];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $count;
    }

    /**
     * @param string $controller
     * @param string $table
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $calc_found_rows
     * @return int
     * @throws Exception
     */
    private function getDataSingle(string $controller, string $table, int $limit = null, int $offset = null, bool $calc_found_rows = false) : int
    {
        $data                                                                               = $this->subs[$controller][$table] ?? $this->main;
        if ($data) {
            $regs                                                                           = $this->getModel($data->getController($controller))
                                                                                                ->setStorage($data->getDef(false)) //@todo: da mettere a true
                                                                                                ->read(
                                                                                                    $data->select(true),
                                                                                                    $data->where(self::$logical_fields),
                                                                                                    $data->sort,
                                                                                                    $limit,
                                                                                                    $offset,
                                                                                                    $calc_found_rows
                                                                                                );

            $count                                                                          = $regs[self::COUNT];
            if (!empty($regs[self::RESULT])) {
                $this->result[$data->def->mainTable]                                        = $regs[self::RESULT];
                $this->result_keys[$data->def->mainTable]                                   = $regs[self::INDEX]    ?? [];
                $count                                                                      = $regs[self::COUNT];
            }
        } else {
            throw new Exception("normalize data is empty", 500);
        }

        return $count;
    }


    /**
     * @param null|array $where
     * @param null|array $set
     * @param null|array $insert
     * @throws Exception
     */
    private function set(array $where = null, array $set = null, array $insert = null) : void
    {
        $this->clearResult();

        $this->resolveFieldsByScopes(array(
            self::SCOPE_INSERT                                                              => $insert,
            self::SCOPE_SET                                                                 => $set,
            self::SCOPE_WHERE                                                               => $where
        ));

        $this->execSub();
        $this->setData();

        if (!empty($this->rev)) {
            foreach ($this->rev as $table => $controller) {
                $this->setData($controller, $table);
            }
        }

        if (isset($this->result_keys[$this->main->def->mainTable])) {
            $this->setCount(count($this->result_keys[$this->main->def->mainTable]));
        }
    }

    /**
     * @param string|null $controller
     * @param string|null $table
     * @throws Exception
     */
    private function setData(string $controller = null, string $table = null) : void
    {
        $insert_key                                                                         = null;
        $update_key                                                                         = null;
        $delete_key                                                                         = null;
        $data                                                                               = $this->getCurrentData($controller, $table);
        $modelName                                                                          = $data->getController($controller);
        $Orm                                                                                = $this->getModel($modelName);
        $storage                                                                            = $Orm->setStorage($data->def);
        $key_name                                                                           = $data->def->key_primary;

        if (!empty($data->insert) && empty($data->set) && !empty($data->where)) {
            /**
             * Insert unique
             */
            $regs                                                                           = $storage->read(array($key_name => true), $data->insert);
            if (is_array($regs)) {
                $this->setKeyRelationship($data, $key_name, array_column($regs[self::INDEX], $key_name), $controller);
            } else {
                $this->cacheRequest(self::ACTION_INSERT_UNIQUE, [$data->insert]);
                $regs                                                                       = $storage->insert($data->insert, $data->def->table[self::TNAME]);
                $this->cacheUpdate();

                if (is_array($regs)) {
                    $insert_key                                                             = $regs[self::INDEX_PRIMARY][$key_name];
                }
            }
        } elseif (!empty($data->insert) && empty($data->set) && empty($data->where)) {
            /**
             * Insert
             */
            $this->cacheRequest(self::ACTION_INSERT, [$data->insert]);
            $regs                                                                           = $storage->insert($data->insert, $data->def->table[self::TNAME]);
            $this->cacheUpdate();

            if (is_array($regs)) {
                $insert_key                                                                 = $regs[self::INDEX_PRIMARY][$key_name];
            }
        } elseif (empty($data->insert) && !empty($data->set) && empty($data->where)) {
            if (isset($data->def->relationship[$this->main->def->mainTable]) && !empty($this->main->where)) {
                $key_main_primary                                                           = $data->def->relationship[$this->main->def->mainTable][self::FREL_PRIMARY];
                if (!isset($this->main->where[$key_main_primary])) {
                    $regs                                                                   = $Orm
                                                                                                ->setStorage($this->main->def)
                                                                                                ->read(
                                                                                                    array($key_main_primary => true),
                                                                                                    $this->main->where,
                                                                                                    null,
                                                                                                    null,
                                                                                                    null,
                                                                                                    null,
                                                                                                    $this->main->def->table[self::TNAME]
                                                                                                );
                    if (is_array($regs)) {
                        $this->main->where[$key_main_primary]                               = array_column($regs[self::INDEX], $key_main_primary);
                    }
                }
                $external_name                                                              = $data->def->relationship[$this->main->def->mainTable][self::FREL_EXTERNAL];
                $primary_name                                                               = $data->def->relationship[$this->main->def->mainTable][self::FREL_PRIMARY];
                if (!isset($data->def->struct[$external_name])) {
                    if (!isset($this->main->where[$external_name])) {
                        $this->setMainIndexes($Orm);
                    }

                    $data->where[$primary_name]                                             = $this->main->where[$external_name];
                } elseif (isset($this->main->where[$primary_name])) {
                    $data->where[$external_name]                                            = $this->main->where[$primary_name];
                }
            }

            /**
             * updateAll
             */
            $this->cacheRequest(self::ACTION_UPDATE, [$data->set, $data->where]);
            $regs                                                                           = $storage->update($data->set, $data->where, $data->def->table[self::TNAME]);
            $this->cacheUpdate();

            $update_key                                                                     = $regs[self::INDEX_PRIMARY][$key_name];
        } elseif (empty($data->insert) && !empty($data->set) && !empty($data->where)) {
            /**
             * update
             */
            $this->cacheRequest(self::ACTION_UPDATE, [$data->set, $data->where]);
            $regs                                                                           = $storage->update($data->set, $data->where, $data->def->table[self::TNAME]);
            $this->cacheUpdate();

            $update_key                                                                     = $regs[self::INDEX_PRIMARY][$key_name];
        } elseif (empty($data->insert) && empty($data->set) && !empty($data->where)) {
            /**
             * Delete
             */
            if (!empty($data->def->relationship) && !empty(array_column($data->def->relationship, "external"))) {
                $select = $storage->read(array_values($data->def->struct), $data->where, null, null, null, false, $data->def->table[self::TNAME]);
                if (!empty($select["index"])) {
                    foreach ($data->def->relationship as $table => $rel) {
                        if (isset($rel["external"])) {
                            Orm::getInstance($rel["collection"] ?? $modelName, $table)->update(
                                [$rel["external"] => null],
                                [$rel["external"] => array_column($select["index"], $rel["primary"])]
                            );
                        }
                    }
                }
            }

            $this->cacheRequest(self::ACTION_DELETE, [$data->where]);
            $regs                                                                           = $storage->delete($data->where, $data->def->table[self::TNAME]);
            $this->cacheUpdate();

            $delete_key                                                                     = $regs[self::INDEX_PRIMARY][$key_name];
        } elseif (!empty($data->insert) && !empty($data->set) && !empty($data->where)) {
            /**
             * UpSert
             */
            $this->cacheRequest(self::ACTION_WRITE, [$data->where, $data->set, $data->insert]);
            $regs                                                                           = $storage->upsert(
                $data->insert,
                $data->set,
                $data->where,
                $data->def->table[self::TNAME]
            );
            $this->cacheUpdate();

            $insert_key                                                                     = $regs[self::INDEX_PRIMARY][$key_name];
        }

        /**
         * necessario quando il campo non è autoincrement
         */
        if (!empty($data->def->key_primary) && $insert_key === "0") {
            $insert_key                                                                     = $data->insert[$data->def->key_primary] ?? null;
        }

        $this->setKeyRelationship($data, $key_name, $insert_key, $controller);

        $this->setResultKeys($data, $insert_key);
        $this->setResultKeys($data, $update_key);
        $this->setResultKeys($data, $delete_key);
    }

    /**
     * @param OrmQuery $data
     * @param string|null $key
     */
    /**
     * @param OrmQuery $data
     * @param string|array|null $key
     */
    private function setResultKeys(OrmQuery $data, $key = null) : void
    {
        if ($key !== null) {
            if (empty($key)) {
                $this->result_keys[$data->table][]                                          = [$data->def->key_primary => ""];
            } elseif (is_array($key)) {
                foreach ($key as $k) {
                    $this->result_keys[$data->table][]                                      = [$data->def->key_primary => $k];
                }
            } else {
                $this->result_keys[$data->table][]                                          = [$data->def->key_primary => $key];
            }
        }
    }

    /**
     * @param Orm $Orm
     * @throws Exception
     */
    private function setMainIndexes(Orm $Orm) : void
    {
        $res                                                                                = $Orm
                                                                                                ->getMainModel()
                                                                                                ->setStorage($this->main->def)
                                                                                                ->read(
                                                                                                    array_keys($this->main->def->indexes),
                                                                                                    $this->main->where
                                                                                                );
        if (is_array($res)) {
            $this->main->where                                                              = array_replace($this->main->where, $res);
        }
    }

    /**
     * @param OrmQuery $data
     * @param string $key_name
     * @param string|array|null $key
     * @param string|null $controller
     * @throws Exception
     * @todo da tipizzare
     */
    private function setKeyRelationship(OrmQuery $data, string $key_name, $key = null, string $controller = null) : void
    {
        if ($key && !empty($data->def->relationship)) {
            if (!$controller) {
                $controller                                                                 = $this->main->service;
            }
            foreach ($data->def->relationship as $tbl => $rel) {
                if (isset($rel[self::FREL_EXTERNAL])) {
                    $field_ext                                                              = $rel[self::FREL_EXTERNAL];
                    if (isset($data->def->struct[$field_ext])) {
                        $field_ext                                                          = $rel[self::FREL_PRIMARY];
                    }

                    if ($key && $field_ext && $field_ext != $key_name) {
                        if (isset($this->subs[$controller][$tbl])) {
                            if (!isset($this->rev[$tbl])) {
                                throw new Exception("relationship missing Controller for table: " . $tbl . " from controller " . $controller, 500);
                            }
                            $rev_controller                                                 = $this->rev[$tbl];
                            $sub                                                            = $this->getSubs($rev_controller, $tbl);
                            if (!empty($sub->insert)) {
                                $sub->insert[$field_ext]                                    = $key;
                            }
                            if (!empty($sub->set)) {
                                $sub->where[$field_ext]                                     = $key;
                            }
                        } elseif ($tbl == $this->main->def->mainTable) {
                            if (!empty($this->main->insert)) {
                                $this->main->insert[$field_ext]                             = $key;
                            }
                            if (!empty($this->main->set)) {
                                $this->main->where[$field_ext]                              = $key;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string|null $cmd
     * @throws Exception
     */
    private function execSub(string $cmd = null) : void
    {
        if (isset($this->subs) && !empty($this->subs)) {
            foreach ($this->subs as $controller => $tables) {
                foreach ($tables as $table => $sub) {
                    $field_ext                                                              = $sub->def->relationship[$sub->def->mainTable][self::FREL_EXTERNAL]        ?? null;
                    $field_main_ext                                                         = $sub->def->relationship[$this->main->def->mainTable][self::FREL_EXTERNAL] ?? null;

                    $this->rev[$table]                                                      = $controller;
                    if (!isset($sub->def->struct[$field_ext]) && !isset($sub->def->struct[$field_main_ext])) {
                        if ($cmd) {
                            $this->cmdData($cmd, $controller, $table);
                        } else {
                            $this->setData($controller, $table);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $command
     * @param string|null $controller
     * @param string|null $table
     * @throws Exception
     */
    private function cmdData(string $command, string $controller = null, string $table = null) : void
    {
        $data                                                                               = $this->getCurrentData($controller, $table);
        $Orm                                                                                = $this->getModel($controller);
        $regs                                                                               = $Orm
                                                                                                ->setStorage($data->def)
                                                                                                ->cmd($command, $data->where(self::$logical_fields));
        $this->result[$data->table]                                                         = $regs;
    }

    /**
     * @param string|null $controller
     * @param string|null $table
     * @return OrmQuery
     */
    private function getCurrentData(string $controller = null, string $table = null) : OrmQuery
    {
        $data       = $this->main;
        if ($controller || $table) {
            $data   = ($this->subs[$controller][$table ?: $this->getModel($controller)->getMainTable()]);
        }

        return $data;
    }

    /**
     * @return OrmResults
     */
    private function getResult() : OrmResults
    {
        return new OrmResults($this->count, $this->result[$this->main_table] ?? [], $this->result_keys, $this->main_table, $this->main->def->key_primary, $this->map_class);
    }

    /**
     * @param array $scopes
     * @return bool
     * @throws Exception
     */
    private function resolveFieldsByScopes(array $scopes) : bool
    {
        $this->services_by_data                                                             = new OrmControllers();
        $this->main                                                                         = new OrmQuery();
        foreach ($scopes as $scope => $fields) {
            $this->resolveFields($scope, $fields);
        }

        if (!isset($this->services_by_data->services)) {
            throw new Exception("Query is empty", 500);
        }
        $is_single_service                                                                  = (count($this->services_by_data->services) == 1);
        if (empty($this->main->where) && empty($this->main->select) && empty($this->main->insert) && empty($this->main->set) && $is_single_service) {
            $subService                                                                     = $this->services_by_data->last;
            $is_single_table                                                                = (count($this->services_by_data->services[$subService]) == 1);
            $Orm                                                                            = $this->getModel($subService);
            $subTable                                                                       = (
                $is_single_table
                                                                                                ? $this->services_by_data->last_table
                                                                                                : $Orm->getMainTable()
            );

            if (isset($this->subs[$subService]) && isset($this->subs[$subService][$subTable])) {
                $this->main                                                                 = $this->subs[$subService][$subTable];
                $this->main->def->mainTable                                                 = $subTable;
            } else {
                $this->main->def                                                            = $Orm->getStruct($subTable);
            }
            $this->main->service                                                            = $subService;
            $this->main_table                                                               = $this->main->def->mainTable;
            unset($this->subs[$subService][$subTable]);
            if (!count($this->subs[$subService])) {
                unset($this->subs[$subService]);
            }
            if (!count($this->subs)) {
                unset($this->subs);
            }
        } else {
            $mainTable                                                                      = $this->getMainTable();

            $this->main->def                                                                = $this->getStruct($mainTable);
            $this->main->table                                                              = $mainTable;
            $this->main->service                                                            = $this->getCollection();

            if ($scopes[self::SCOPE_WHERE] === true) {
                $this->main->where = true;
            }
        }

        if (empty($this->main->select) || isset($this->main->select["*"])) {
            $this->main->getAllFields(true);
        }
        return (!$this->services_by_data->use_alias && is_array($this->services_by_data->tables) && count($this->services_by_data->tables) === 1);
    }

    /**
     * @param null $service
     * @param null $table
     * @return OrmQuery
     * @throws Exception
     */
    private function &getSubs($service = null, $table = null) : OrmQuery
    {
        if (!isset($this->subs[$service][$table])) {
            $this->subs[$service][$table]                                                   = new OrmQuery();
            $this->subs[$service][$table]->def                                              = $this->getModel($service)->getStruct($table);
            $this->subs[$service][$table]->table                                            = $table;
        }

        return $this->subs[$service][$table];
    }

    /**
     * @param string $scope
     * @param array|null $fields
     * @throws Exception
     */
    private function resolveFields(string $scope, array $fields = null) : void
    {
        if (!empty($fields)) {
            $mainService                                                                    = $this->getCollection();
            $mainTable                                                                      = $this->getMainTable();
            if ($scope == self::SCOPE_SELECT || $scope == self::SCOPE_WHERE || $scope == self::SCOPE_SORT || $scope == self::SCOPE_INSERT) {
                $this->services_by_data->last                                               = $mainService;
                $this->services_by_data->last_table                                         = $mainTable;
            }
            $is_or                                                                          = false;
            if (isset($fields['$or'])) {
                $fields                                                                     = $fields['$or'];
                $is_or                                                                      = true;
            }

            foreach ($fields as $key => $alias) {
                $table                                                                      = null;
                $fIndex                                                                     = null;
                $service                                                                    = $mainService;
                if (is_numeric($key)) {
                    $key                                                                    = $alias;
                    if ($scope != self::SCOPE_INSERT && $scope != self::SCOPE_SET) {
                        $alias = true;
                    }
                } elseif (is_null($alias)) {
                    $alias                                                                  = null;
                }

                if ($scope == self::SCOPE_SELECT && $alias && is_string($alias)) {
                    $this->services_by_data->use_alias++;
                }

                $parts                                                                      = explode(".", $key);
                switch (count($parts)) {
                    case "4":
                        throw new Exception("Orm Wrong Format: " . $key, 500);
                    case "3":
                        $service                                                            = $parts[0];
                        $table                                                              = $parts[1];
                        $fIndex                                                             = (
                            $service == $mainService && $table == $mainTable
                                                                                                ? -2
                                                                                                : 2
                        );
                        break;
                    case "2":
                        $table                                                              = $parts[0];
                        $fIndex                                                             = (
                            $table == $mainTable
                                                                                                ? -1
                                                                                                : 1
                        );
                        break;
                    case "1":
                        $table                                                              = $mainTable;
                        $fIndex                                                             = null;
                        // no break
                    default:
                }

                $this->services_by_data->services[$service][$table]                         = true;
                $this->services_by_data->tables[$service . "." . $table]                    = true;
                $this->services_by_data->scopes[$scope][$service]                           = true;
                if ($scope == self::SCOPE_SELECT || $scope == self::SCOPE_WHERE || $scope == self::SCOPE_SORT || $scope == self::SCOPE_INSERT) {
                    $this->services_by_data->last                                           = $service;
                    $this->services_by_data->last_table                                     = $table;
                }
                if ($fIndex === null || $fIndex < 0) {
                    $fValue                                                                 = $parts[abs($fIndex ?? 0)];
                    $this->main->set(
                        $scope,
                        $fValue,
                        (
                            $alias === true && $scope == self::SCOPE_SELECT
                            ? $fValue
                            : $alias
                        ),
                        $is_or
                    );
                    continue;
                }

                $subs = $this->getSubs($service, $table);
                if (!isset($subs->def->struct[$parts[$fIndex]])) {
                    if ($scope == self::SCOPE_SELECT && $parts[$fIndex] == "*") {
                        if (is_array($subs->def->struct)) {
                            $subs->getAllFields(true);
                        } else {
                            throw new Exception("Undefined Struct on Table: `" . $table . "` Model: `" . $service . "`", 500);
                        }
                    }
                    continue;
                }

                if ($scope == self::SCOPE_INSERT) {
                    $subs->insert[$parts[$fIndex]]                                          = $alias;
                } else {
                    $subs->set(
                        $scope,
                        $parts[$fIndex],
                        (
                            $alias === true && $scope == self::SCOPE_SELECT
                            ? $parts[$fIndex]
                            : $alias
                        ),
                        $is_or
                    );
                }
            }
        }
    }

    /**
     * @param string|null $model
     * @return Orm
     */
    private function getModel(string $model = null) : Orm
    {
        return ($model
            ? self::getInstance($model, $this->main_table, $this->map_class)
            : $this
        );
    }

    /**
     * @param OrmQuery $ref
     * @param array $keys
     * @param string $field
     */
    private static function whereBuilder(OrmQuery &$ref, array $keys, string $field) : void
    {
        if (empty($ref->where)) {
            $ref->where                                                                     = [];
        }

        if (!empty($keys) && empty($ref->where[$ref->def->key_primary])) {
            if (!isset($ref->where[$field])) {
                $ref->where[$field]                                                         = (
                    count($keys) == 1
                    ? $keys[key($keys)] //$keys[0] TODO da verificare
                    : $keys
                );
            } elseif (is_array($ref->where[$field])) {
                $ref->where[$field]                                                         = $ref->where[$field] + $keys;
            } else {
                $ref->where[$field]                                                         = array($ref->where[$field]) + $keys;
            }
        }
    }

    /**
     * @param int $count
     */
    private function setCount(int $count)
    {
        $this->count                                                        = $count;
    }

    private function clearResult() : void
    {
        $this->rev                                                          = [];
        $this->rel                                                          = [];
        $this->rel_done                                                     = [];
        $this->main                                                         = null;
        $this->subs                                                         = [];
        $this->result                                                       = [];
        $this->result_keys                                                  = [];
        $this->count                                                        = 0;
        $this->services_by_data                                             = null;
    }
}
