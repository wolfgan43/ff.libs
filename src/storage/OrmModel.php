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

use phpformsframework\libs\cache\Cashable;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Error;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Mappable;
use phpformsframework\libs\storage\dto\OrmControllers;
use phpformsframework\libs\storage\dto\OrmDef;
use phpformsframework\libs\storage\dto\OrmQuery;
use phpformsframework\libs\storage\dto\OrmResults;

/**
 * Class OrmModel
 * @package phpformsframework\libs\storage
 */
class OrmModel extends Mappable
{
    use Cashable;

    /**
     * @var OrmModel[]
     */
    private static $singleton                                                               = null;

    protected const ERROR_BUCKET                                                            = "orm";

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

    private const RESULT                                                                    = Database::RESULT;
    private const INDEX                                                                     = Database::INDEX;
    private const INDEX_PRIMARY                                                             = Database::INDEX_PRIMARY;
    private const COUNT                                                                     = Database::COUNT;

    private $default_table                                                                  = null;

    protected $bucket                                                                       = null;
    protected $type                                                                         = null;
    protected $main_table                                                                   = null;

    protected $connectors                                                                   = array();

    protected $adapters                                                                     = array();
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
    private $rev                                                                            = array();
    private $rel                                                                            = array();
    private $rel_done                                                                       = array();

    private $result                                                                         = array();
    private $result_keys                                                                    = null;

    private $map_class                                                                      = null;

    public $count                                                                           = null;

    /**
     * @param string $bucket
     * @param string|null $mainTable
     * @param string|null $mapClass
     * @return OrmModel
     */
    public static function &getInstance(string $bucket = null, string $mainTable = null, string $mapClass = null) : self
    {
        if (!isset(self::$singleton[$bucket])) {
            self::$singleton[$bucket]                                                       = new OrmModel($bucket);
        }

        return self::$singleton[$bucket]
            ->setMainTable($mainTable)
            ->setMapClass($mapClass);
    }

    /**
     * OrmModel constructor.
     * @param string|null $map_name
     */
    public function __construct(string $map_name = null)
    {
        if ($map_name) {
            parent::__construct($map_name);

            $this->adapters                                                                 = array_intersect_key($this->connectors, $this->adapters);
            $this->default_table                                                            = $this->main_table;
        }
    }

    /**
     * @param string|null $main_table
     * @return OrmModel
     */
    private function &setMainTable(string $main_table = null) : self
    {
        if ($main_table && isset($this->tables[$main_table])) {
            $this->main_table                                                               = $main_table;
        } else {
            $this->main_table                                                               = $this->default_table;
        }

        return $this;
    }

    /**
     * @param string|null $map_class
     * @return OrmModel
     */
    private function &setMapClass(string $map_class = null) : self
    {
        $this->map_class                                                                    = $map_class;

        return $this;
    }

    /**
     * @param string $table_name
     * @return OrmDef
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
     */
    private function extractData(array $def, string $key, $error = null) : ?array
    {
        if ($error && !isset($def[$key])) {
            Error::register("missing Table: `" . $key . "` on Map: " . $error . " Model: " . $this->type, static::ERROR_BUCKET);
        }


        return (isset($def[$key])
            ? $def[$key]
            : null
        );
    }


    /**
     * @param string $table_name
     * @return string|null
     */
    private function getTableAlias(string $table_name) : ?string
    {
        return (isset($this->tables[$table_name][self::TALIAS])
            ? $this->tables[$table_name][self::TALIAS]
            : null
        );
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
    private function getName() : ?string
    {
        return $this->type;
    }

    /**
     * @return OrmModel
     */
    private function getMainModel() : self
    {
        return ($this->type == $this->bucket
            ? $this
            : self::getInstance($this->bucket, $this->main_table, $this->map_class)
        );
    }

    /**
     * @param OrmDef $struct
     * @return Database
     */
    private function setStorage(OrmDef $struct) : Database
    {
        return Database::getInstance($this->adapters, $struct->toArray());
    }

    /**
     * @param $query
     * @return array|null
     */
    public function rawQuery($query) : ?array
    {
        return Database::getInstance($this->adapters)->rawQuery($query);
    }

    /**
     * @param null|array $select
     * @param null|array $where
     * @param null|array $sort
     * @param int $limit
     * @param int|null $offset
     * @return array|null
     */
    public function read(array $select = null, array $where = null, array $sort = null, int $limit = null, int $offset = null) : ?OrmResults
    {
        if ($this->cacheRequest(self::ACTION_READ, [$select, $where, $sort, $limit, $offset], $this->result)) {
            $this->get($where, $select, $sort, $limit, $offset);

            $this->cacheStore($this->result);
        }

        return $this->getResult();
    }

    /**
     * @param null|array $select
     * @param null|array $where
     * @param null|array $sort
     * @param int|null $offset
     * @return object|null
     */
    public function readOne(array $select = null, array $where = null, array $sort = null, int $offset = null) : ?object
    {
        if ($this->cacheRequest(self::ACTION_READONE, [$select, $where, $sort, $offset], $this->result)) {
            $this->get($where, $select, $sort, 1, $offset);
            $this->cacheStore($this->result);
        }

        return $this->getResult()->first();
    }

    /**
     * @param array $insert
     * @return OrmResults
     */
    public function insertUnique(array $insert) : OrmResults
    {
        $this->cacheRequest(self::ACTION_INSERT_UNIQUE, [$insert]);

        $this->set($insert, null, $insert);

        $this->cacheUpdate();

        $this->setCount(count($this->result));

        return $this->getResult();
    }

    /**
     * @param array $insert
     * @return OrmResults
     */
    public function insert(array $insert) : OrmResults
    {
        $this->cacheRequest(self::ACTION_INSERT, [$insert]);

        $this->set(null, null, $insert);

        $this->cacheUpdate();

        $this->setCount(count($this->result_keys));

        return $this->getResult();
    }

    /**
     * @param array $set
     * @param array $where
     * @return OrmResults
     */
    public function update(array $set, array $where) : OrmResults
    {
        $this->cacheRequest(self::ACTION_UPDATE, [$set, $where]);

        $this->set($where, $set);

        $this->cacheUpdate();

        $this->setCount(count($this->result_keys));

        return $this->getResult();
    }

    /**
     * @param array $where
     * @param null|array $set
     * @param null|array $insert
     * @return OrmResults
     */
    public function write(array $where, array $set = null, array $insert = null) : OrmResults
    {
        $this->cacheRequest(self::ACTION_WRITE, [$where, $set, $insert]);

        $this->set($where, $set, $insert);

        $this->cacheUpdate();

        $this->setCount(count($this->result));

        return $this->getResult();
    }

    /**
     * @param string $action
     * @param null|array $where
     * @return object|null
     */
    public function cmd(string $action, array $where = null) : ?object
    {
        if ($this->cacheRequest(self::ACTION_CMD, [$action, $where], $this->result[self::ACTION_CMD])) {
            $this->clearResult();

            $this->resolveFieldsByScopes(array(
                self::SCOPE_WHERE       => $where
            ));

            $this->execSub($action);
            $this->cmdData($action);
        }

        return $this->getResult()->first();
    }

    /**
     * @param array $where
     * @return array|null
     */
    public function delete(array $where) : ?array
    {
        $this->cacheRequest(self::ACTION_DELETE, [$where]);

        $res                        = null;

        $this->cacheUpdate();

        return $res;
    }

    /**
     * @param null|array $where
     * @param null|array $select
     * @param null|array $sort
     * @param int $limit
     * @param int|null $offset
     */
    private function get(array $where = null, array $select = null, array $sort = null, int $limit = null, int $offset = null) : void
    {
        $this->clearResult();

        $single_service                                                                     = $this->resolveFieldsByScopes(array(
                                                                                                self::SCOPE_SELECT    => $select,
                                                                                                self::SCOPE_WHERE     => $where,
                                                                                                self::SCOPE_SORT      => $sort
                                                                                            ));

        if ($single_service) {
            $this->setCount($this->getDataSingle($this->services_by_data->last, $this->services_by_data->last_table, $limit, $offset));
        } else {
            $countRunner                                                                    = $this->throwRunnerSubs(true);
            while ($this->throwRunner($limit, $offset) > 0) {
                $countRunner++;
            }
        }
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return int
     */
    private function throwRunner(int $limit = null, int $offset = null) : int
    {
        $counter = 0;

        /**
         * Run Main query if where isset
         */
        if (!$this->main->runned && !empty($this->main->where)) {
            $this->setCount($this->getData($this->main, $this->main->service, $this->main->def->mainTable, $limit, $offset));
            $this->main->runned                                                 = true;
            $counter++;
        }

        $counter += $this->throwRunnerSubs();

        return $counter;
    }

    /**
     * @param bool $unique
     * @return int
     */
    private function throwRunnerSubs(bool $unique = false) : int
    {
        $counter = 0;
        /**
         * Run Sub query if where isset
         */
        if (!empty($this->subs)) {
            foreach ($this->subs as $controller => $tables) {
                foreach ($tables as $table => &$sub) {
                    if (!$sub->runned && !empty($sub->where)
                        && (!$unique || $sub->uniqueIndex())
                    ) {
                        $this->getData($sub, $controller, $table);
                        $sub->runned = true;
                        $counter++;
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
     * @param int $limit
     * @param int|null $offset
     * @return int|null
     */
    private function getData(OrmQuery $data, string $controller, string $table, int $limit = null, int $offset = null) : ?int
    {
        $count                                                                              = null;
        if (!empty($data->where)) {
            $main_table                                                                     = $data->def->mainTable;
            $ormModel                                                                       = $this->getModel($controller);
            $regs                                                                           = $ormModel
                                                                                                ->setStorage($data->def)
                                                                                                ->read(
                                                                                                    $data->where,
                                                                                                    $data->select(true),
                                                                                                    $data->sort,
                                                                                                    $limit,
                                                                                                    $offset
                                                                                                );
            if (!empty($regs[self::RESULT])) {
                $thisTable                                                              = $table;
                $aliasTable                                                             = $data->def->table[self::TALIAS];
                $this->result[$thisTable]                                               = $regs[self::RESULT];
                $this->result_keys[$thisTable]                                          = $regs[self::INDEX]    ?? null;
                $count                                                                  = $regs[self::COUNT];
                if (!empty($data->def->relationship)) {
                    foreach ($data->def->relationship as $ref => $relation) {
                        unset($whereRef);
                        $manyToMany             = false;
                        $oneToMany              = isset($relation[self::FREL_TABLE_NAME]) && isset($data->def->struct[$ref]);
                        if (!$oneToMany && !isset($relation[self::FREL_EXTERNAL])) {
                            Error::register("Relation malformed: " . $thisTable . " => " . $ref  . " => " . print_r($relation, true));
                        }
                        if ($oneToMany) {
                            $thisKey            = $ref;

                            $relTable           = $relation[self::FREL_TABLE_NAME];
                            $relKey             = $relation[self::FREL_TABLE_KEY];

                            if ($relTable != $main_table && isset($data->def->relationship[$main_table])) {
                                $manyToMany     = true;
                            }
                        } elseif (isset($data->def->struct[$relation[self::FREL_EXTERNAL]])) {
                            if ($ref != $main_table && isset($data->def->relationship[$main_table])) {
                                $manyToMany     = true;
                            }
                            if (isset($data->def->relationship[$relation[self::FREL_EXTERNAL]])) {
                                $oneToMany      = true;
                            }

                            $thisKey            = $relation[self::FREL_EXTERNAL];

                            $relTable           = $ref;
                            $relKey             = $relation[self::FREL_PRIMARY];
                        } else {
                            $thisKey            = $relation[self::FREL_PRIMARY];

                            $relTable           = $ref;
                            $relKey             = $relation[self::FREL_EXTERNAL];
                        }

                        if (isset($this->rel_done[$thisTable . "." . $thisKey][$relTable . "." . $relKey])) {
                            continue;
                        }

                        if ($main_table == $relTable) {
                            $whereRef           =& $this->main;
                        } elseif (isset($this->subs[$controller][$relTable])) {
                            $whereRef           =& $this->subs[$controller][$relTable];
                        } elseif (isset($this->services_by_data->tables[$controller . "." . $relTable])) {
                            Error::register("Relationship not found: " . $thisTable . "." . $thisKey . " => " . $relTable . "." . $relKey);
                        }

                        $keyValue = array_column($regs[self::INDEX], $thisKey);
                        if (isset($whereRef) && count($keyValue)) {
                            $this->whereBuilder($whereRef, $keyValue, $relKey);
                        } elseif (isset($this->services_by_data->tables[$controller . "." . $relTable])) {
                            Error::register("Relationship found but missing keyValue in result. Check in configuration indexes: " . $thisTable . " => " . $thisKey . " (" . $relTable . "." . $relKey . ")");
                        }

                        $this->rel[$relTable][$thisTable] = $keyValue;
                        $this->rel_done[$thisTable . "." . $thisKey][$relTable . "." . $relKey] = true;

                        if (isset($this->result[$relTable])) {
                            foreach ($keyValue as $keyCounter => $keyID) {
                                if (isset($regs[self::INDEX][$keyCounter][$thisKey]) && $regs[self::INDEX][$keyCounter][$thisKey] == $keyID) {
                                    if ($main_table == $thisTable) {
                                        $keyParents                                                                             = array_keys($this->rel[$thisTable][$relTable], $keyID);
                                        foreach ($keyParents as $keyParent) {
                                            /**
                                             * Remove if exist reference of Result in sub table for prevent circular references
                                             */
                                            unset($this->result[$relTable][$keyParent][$aliasTable]);

                                            if (!$oneToMany && !$manyToMany) {
                                                $this->result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)][]   =& $this->result[$relTable][$keyParent];
                                            } else {
                                                $this->result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)]     =& $this->result[$relTable][$keyParent];
                                            }
                                        }
                                    } elseif ($manyToMany) {
                                        $keyParents                                                                             = array_keys($this->rel[$thisTable][$relTable], $keyID);
                                        foreach ($keyParents as $keyParent) {
                                            /**
                                             * Remove if exist reference of Result in sub table for prevent circular references
                                             */
                                            unset($this->result[$relTable][$keyParent][$aliasTable]);

                                            $this->result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)]         =& $this->result[$relTable][$keyParent];
                                        }
                                    } else {
                                        $keyParents                                                                             = array_keys($this->rel[$thisTable][$relTable], $keyID);

                                        /**
                                         * Remove if exist reference of Result in sub table for prevent circular references
                                         */
                                        unset($this->result[$thisTable][$keyCounter][$ormModel->getTableAlias($relTable)]);

                                        foreach ($keyParents as $keyParent) {
                                            if ($oneToMany || $manyToMany) {
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
        }

        return $count;
    }

    /**
     * @param string $controller
     * @param string $table
     * @param int|null $limit
     * @param int|null $offset
     * @return int|null
     */
    private function getDataSingle(string $controller, string $table, int $limit = null, int $offset = null) : ?int
    {
        $count                                                                              = null;
        $data                                                                               = (
            isset($this->subs[$controller][$table])
                                                                                                ? $this->subs[$controller][$table]
                                                                                                : $this->main
                                                                                            );
        if ($data) {
            $data->setControl(true);
            $regs                                                                           = $this->getModel($data->getController($controller))
                                                                                                ->setStorage($data->def)
                                                                                                ->read(
                                                                                                    $data->where,
                                                                                                    $data->select(true),
                                                                                                    $data->sort,
                                                                                                    $limit,
                                                                                                    $offset
                                                                                                );
            if (is_array($regs) && $regs[self::RESULT]) {
                $this->result[$this->getMainTable()]                                        = $regs[self::RESULT];
                $this->result_keys[$this->getMainTable()]                                   = $regs[self::INDEX]    ?? null;
                $count                                                                      = $regs[self::COUNT];
            }
        } else {
            Error::register("normalize data is empty", static::ERROR_BUCKET);
        }

        return $count;
    }


    /**
     * @param null|array $where
     * @param null|array $set
     * @param null|array $insert
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
    }

    /**
     * @param string|null $controller
     * @param string|null $table
     */
    private function setData(string $controller = null, string $table = null) : void
    {
        $insert_key                                                                         = null;
        $update_key                                                                         = null;
        $data                                                                               = $this->getCurrentData($controller, $table);
        $modelName                                                                          = $data->getController($controller);
        $ormModel                                                                           = $this->getModel($modelName);
        $storage                                                                            = $ormModel->setStorage($data->def);
        $key_name                                                                           = $data->def->key_primary;

        if (!empty($data->insert) && empty($data->set) && !empty($data->where)) {
            $regs                                                                           = $storage->read($data->insert, array($key_name => true));
            if (is_array($regs)) {
                $this->setKeyRelationship($data, $key_name, array_column($regs[self::INDEX], $key_name), $controller);
            } else {
                $regs                                                                       = $storage->insert($data->insert, $data->def->table[self::TNAME]);
                if (is_array($regs)) {
                    $insert_key                                                             = $regs[self::INDEX_PRIMARY][$key_name];
                }
            }
        } elseif (!empty($data->insert) && empty($data->set) && empty($data->where)) {
            $regs                                                                           = $storage->insert($data->insert, $data->def->table[self::TNAME]);

            if (is_array($regs)) {
                $insert_key                                                                 = $regs[self::INDEX_PRIMARY][$key_name];
            }
        } elseif (empty($data->insert) && !empty($data->set) && empty($data->where)) {
            if (isset($data->def->relationship[$this->main->def->mainTable]) && !empty($this->main->where)) {
                $key_main_primary                                                           = $data->def->relationship[$this->main->def->mainTable][self::FREL_PRIMARY];
                if (!isset($this->main->where[$key_main_primary])) {
                    $regs                                                                   = $this->getModel($modelName)
                                                                                                ->setStorage($this->main->def)
                                                                                                ->read($this->main->where, array($key_main_primary => true), null, null, null, $this->main->def->table[self::TNAME]);
                    if (is_array($regs)) {
                        $this->main->where[$key_main_primary]                               = array_column($regs[self::INDEX], $key_main_primary);
                    }
                }
                $external_name                                                              = $data->def->relationship[$this->main->def->mainTable][self::FREL_EXTERNAL];
                $primary_name                                                               = $data->def->relationship[$this->main->def->mainTable][self::FREL_PRIMARY];
                if (!isset($data->def->struct[$external_name])) {
                    if (!isset($this->main->where[$external_name])) {
                        $this->setMainIndexes($ormModel);
                    }

                    $data->where[$primary_name]                                             = $this->main->where[$external_name];
                } elseif (isset($this->main->where[$primary_name])) {
                    $data->where[$external_name]                                            = $this->main->where[$primary_name];
                }
            }
            //@todo da far tornare se possibile l'id dei record aggiornati
            $update_key                                                                     = !empty($data->where) && (bool) $storage->update($data->set, $data->where, $data->def->table[self::TNAME]);
        } elseif (empty($data->insert) && !empty($data->set) && !empty($data->where)) {
            //@todo da far tornare se possibile l'id dei record aggiornati
            $update_key                                                                     = (bool) $storage->update($data->set, $data->where, $data->def->table[self::TNAME]);
        } elseif (empty($data->insert) && empty($data->set) && !empty($data->where)) {
            Error::register("Catrina: data not managed", static::ERROR_BUCKET);
        } elseif (!empty($data->insert) && !empty($data->set) && !empty($data->where)) {
            $regs                                                                           = $storage->write(
                $data->insert,
                $data->set,
                $data->where,
                $data->def->table[self::TNAME]
            );
            if (isset($regs[self::SCOPE_ACTION]) && $regs[self::SCOPE_ACTION] == self::ACTION_INSERT) {
                $insert_key                                                                 = $regs[self::INDEX_PRIMARY][$key_name];
            } elseif ($regs[self::SCOPE_ACTION] == self::ACTION_UPDATE) {
                $update_key                                                                 = $regs[self::INDEX_PRIMARY][$key_name];
            }
        }

        $this->setKeyRelationship($data, $key_name, $insert_key, $controller);

        if ($insert_key !== null) {
            $this->result_keys[$data->def->mainTable][]                                     = [$data->def->key_primary => $insert_key];
        }

        if ($update_key !== null) {
            $this->result_keys[$data->def->mainTable][]                                     = [$data->def->key_primary => $insert_key];
        }
    }

    /**
     * @param OrmModel $ormModel
     */
    private function setMainIndexes(OrmModel $ormModel) : void
    {
        $res                                                                                = $ormModel
                                                                                                ->getMainModel()
                                                                                                ->setStorage($this->main->def)
                                                                                                ->read(
                                                                                                    $this->main->where,
                                                                                                    array_keys($this->main->def->indexes)
                                                                                                );
        if (is_array($res)) {
            $this->main->where                                                              = array_replace($this->main->where, $res);
        }
    }

    /**
     * @todo da tipizzare
     * @param OrmQuery $data
     * @param string $key_name
     * @param string|array|null $key
     * @param string|null $controller
     */
    private function setKeyRelationship(OrmQuery $data, string $key_name, $key = null, string $controller = null) : void
    {
        if ($key && !empty($data->def->relationship)) {
            if (!$controller) {
                $controller                                                                 = $this->main->service;
            }
            foreach ($data->def->relationship as $tbl => $rel) {
                if (isset($rel[self::FREL_EXTERNAL]) && isset($this->subs[$controller][$tbl])) {
                    $field_ext                                                              = $rel[self::FREL_EXTERNAL];
                    if (isset($data->def->struct[$field_ext])) {
                        $field_ext                                                          = $rel[self::FREL_PRIMARY];
                    }
                    if ($key && $field_ext && $field_ext != $key_name) {
                        if ($tbl != $this->main->def->mainTable) {
                            $rev_controller                                                 = $this->rev[$tbl];
                            $sub = $this->getSubs($rev_controller, $tbl);
                            if (!empty($sub->insert)) {
                                $sub->insert[$field_ext]                                    = $key;
                            }
                            if (!empty($sub->set)) {
                                $sub->where[$field_ext]                                     = $key;
                            }
                        } else {
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
     */
    private function execSub(string $cmd = null) : void
    {
        if (isset($this->subs) && !empty($this->subs)) {
            foreach ($this->subs as $controller => $tables) {
                foreach ($tables as $table => $sub) {
                    $field_ext                                                              = (
                        isset($sub->def->relationship[$sub->def->mainTable][self::FREL_EXTERNAL])
                                                                                                ? $sub->def->relationship[$sub->def->mainTable][self::FREL_EXTERNAL]
                                                                                                : null
                                                                                            );
                    $field_main_ext                                                         = (
                        isset($sub->def->relationship[$this->main->def->mainTable][self::FREL_EXTERNAL])
                                                                                                ? $sub->def->relationship[$this->main->def->mainTable][self::FREL_EXTERNAL]
                                                                                                : null
                                                                                            );

                    if (isset($sub->def->struct[$field_ext]) || isset($sub->def->struct[$field_main_ext])) {
                        $this->rev[$table]                                                  = $controller;
                    } else {
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
     */
    private function cmdData(string $command, string $controller = null, string $table = null) : void
    {
        $data                                                                               = $this->getCurrentData($controller, $table);
        if (isset($data->where)) {
            $ormModel                                                                       = $this->getModel($controller);
            $regs                                                                           = $ormModel
                                                                                                ->setStorage($data->def)
                                                                                                ->cmd(
                                                                                                    $data->where,
                                                                                                    $command
                                                                                            );

            $this->result[$data->def->mainTable]                                            = $regs;
        }
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
            $data   = ($this->subs[$controller][(
                $table
                ? $table
                : $this->getModel($controller)->getMainTable()
            )]);
        }

        return $data;
    }

    /**
     * @return OrmResults
     */
    private function getResult() : OrmResults
    {
        return new OrmResults($this->result[$this->main->def->mainTable] ?? null, $this->count, $this->result_keys[$this->main->def->mainTable] ?? null, $this->main->def->key_primary, $this->map_class);
    }

    /**
     * @param array $scopes
     * @return bool
     */
    private function resolveFieldsByScopes(array $scopes) : bool
    {
        $this->services_by_data                                                             = new OrmControllers();
        $this->main                                                                         = new OrmQuery();
        foreach ($scopes as $scope => $fields) {
            $this->resolveFields($scope, $fields);
        }

        if (!isset($this->services_by_data->services)) {
            Error::register("Query is empty", static::ERROR_BUCKET);
        }
        $is_single_service                                                                  = (count($this->services_by_data->services) == 1);
        if (empty($this->main->where) && empty($this->main->select) && empty($this->main->insert) && $is_single_service) {
            $subService                                                                     = key($this->services_by_data->services);
            $ormModel                                                                       = $this->getModel($subService);
            $subTable                                                                       = $ormModel->getMainTable();

            if (isset($this->subs[$subService]) && isset($this->subs[$subService][$subTable])) {
                $this->main                                                                 = $this->subs[$subService][$subTable];
            } else {
                $this->main->def                                                            = $ormModel->getStruct($subTable);
            }
            $this->main->service                                                            = $subService;

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
            $this->main->service                                                            = $this->getName();

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
     */
    private function &getSubs($service = null, $table = null) : OrmQuery
    {
        if (!isset($this->subs[$service][$table])) {
            $this->subs[$service][$table]                                                   = new OrmQuery();
            $this->subs[$service][$table]->def                                              = $this->getModel($service)->getStruct($table);
        }

        return $this->subs[$service][$table];
    }

    /**
     * @param string $scope
     * @param array|null $fields
     */
    private function resolveFields($scope, array $fields = null) : void
    {
        if (!empty($fields)) {
            $mainService                                                                    = $this->getName();
            $mainTable                                                                      = $this->getMainTable();
            if ($scope == self::SCOPE_SELECT || $scope == self::SCOPE_WHERE || $scope == self::SCOPE_SORT) {
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
                        if (Kernel::$Environment::DEBUG) {
                            Debug::dump("Wrong Format: " . $key);
                            exit;
                        }
                        break;
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

                $this->services_by_data->services[$service]                                 = true;
                $this->services_by_data->tables[$service . "." . $table]                    = true;
                $this->services_by_data->scopes[$scope][$service]                           = true;
                if ($scope == self::SCOPE_SELECT || $scope == self::SCOPE_WHERE || $scope == self::SCOPE_SORT) {
                    $this->services_by_data->last                                           = $service;
                    $this->services_by_data->last_table                                     = $table;
                }
                if ($fIndex === null || $fIndex < 0) {
                    $this->main->set(
                        $scope,
                        $parts[abs($fIndex)],
                        (
                            $alias === true && $scope == self::SCOPE_SELECT
                            ? $parts[abs($fIndex)]
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
                            Error::register("Undefined Struct on Table: `" . $table . "` Model: `" . $service . "`", static::ERROR_BUCKET);
                        }
                    } else {
                        Error::register("missing field: `" . $parts[$fIndex] . "` on Table: `" . $table . "` Model: `" . $service . "`", static::ERROR_BUCKET);
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
     * @param string $model
     * @return OrmModel
     */
    private function getModel(string $model = null) : OrmModel
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
            $ref->where                                                                     = array();
        }
        if (!isset($ref->where[$field])) {
            $ref->where[$field]                                                             = (
                count($keys) == 1
                ? $keys[0]
                : $keys
            );
        } elseif (is_array($ref->where[$field])) {
            $ref->where[$field]                                                             = $ref->where[$field] + $keys;
        } else {
            $ref->where[$field]                                                             = array($ref->where[$field]) + $keys;
        }
    }

    /**
     * @param int|null $count
     */
    private function setCount(int $count = null)
    {
        if ($count) {
            $this->count                                                    = $count;
        }
    }

    private function clearResult() : void
    {
        $this->rev                                                          = array();
        $this->rel                                                          = array();
        $this->rel_done                                                     = array();
        $this->main                                                         = null;
        $this->subs                                                         = array();
        $this->result                                                       = array();
        $this->result_keys                                                  = null;
        $this->count                                                        = null;
        $this->services_by_data                                             = null;

        Error::clear(static::ERROR_BUCKET);
    }
}
