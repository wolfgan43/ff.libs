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

use phpformsframework\libs\storage\dto\OrmDef;
use phpformsframework\libs\storage\dto\Schema;
use phpformsframework\libs\util\AdapterManager;
use phpformsframework\libs\util\TypesConverter;
use phpformsframework\libs\Exception;

/**
 * Class Database
 * @package phpformsframework\libs\storage
 * @property DatabaseAdapter[] $adapters
 */
class Database implements Constant
{
    use AdapterManager;
    use TypesConverter;

    public const RESULT                                                     = "result";
    public const INDEX                                                      = "index";
    public const INDEX_PRIMARY                                              = "primary";
    public const COUNT                                                      = "count";

    private static $singletons                                              = null;

    private $result                                                         = null;

    /**
     * @param array $databaseAdapters
     * @param OrmDef $def
     * @param Schema|null $schema
     * @return Database
     */
    public static function getInstance(array $databaseAdapters, OrmDef $def, Schema $schema = null) : Database
    {
        $key                                                                = self::checkSumArray($databaseAdapters + $def->table);

        return self::$singletons[$key] ?? (self::$singletons[$key] = new Database($databaseAdapters, $def, $schema));
    }

    /**
     * Database constructor.
     *
     * @param array $databaseAdapters
     * @param OrmDef $def
     * @param Schema|null $schema
     * @example string $databaseAdapters: mysqli OR mongodb OR ecc
     * @example array $databaseAdapters: [mysqli, mongodb]
     * @example arrayAssociative $databaseAdapters: [mysqli : {
     * "host"          => null
     * , "username"    => null
     * , "secret"      => null
     * , "name"        => null
     * , "prefix"      => null
     * , "table"       => null
     * , "key"         => null
     * }
     */
    public function __construct(array $databaseAdapters, OrmDef $def, Schema $schema = null)
    {
        foreach ($databaseAdapters as $adapter => $connection) {
            $this->setAdapter($adapter, [$connection, $def, $schema]);
        }
    }

    /**
     * @param array|null $fields
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $calc_found_rows
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function read(array $fields = null, array $where = null, array $sort = null, int $limit = null, int $offset = null, bool $calc_found_rows = false, string $table_name = null) : ?array
    {
        foreach ($this->adapters as $adapter_name => $adapter) {
            if (!empty($this->result[$adapter_name] = $adapter->read($fields, $where, $sort, $limit, $offset, $calc_found_rows, $table_name))) {
                break;
            }
        }

        return $this->getResult();
    }

    /**
     * @param array $insert
     * @param null|string $table_name
     * @return array|null
     * @throws Exception
     */
    public function insert(array $insert, string $table_name = null) : ?array
    {
        //@todo da alterare la cache in funzione dei dati inseriti
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->insert($insert, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param array $set
     * @param array|null $where
     * @param null|string $table_name
     * @return array|null
     * @throws Exception
     */
    public function update(array $set, array $where = null, string $table_name = null) : ?array
    {
        //@todo da alterare la cache in funzione dei dati inseriti
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->update($set, $where, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param array $insert
     * @param array $set
     * @param array $where
     * @param null|string $table_name
     * @return array|null
     * @throws Exception
     */
    public function write(array $insert, array $set, array $where, string $table_name = null) : ?array
    {
        //@todo da alterare la cache in funzione dei dati inseriti
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->write($insert, $set, $where, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param array $where
     * @param null|string $table_name
     * @return array|null
     * @throws Exception
     */
    public function delete(array $where, string $table_name = null) : ?array
    {
        //@todo da alterare la cache in funzione dei dati inseriti
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->delete($where, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param string $action
     * @param array|null $where
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function cmd(string $action, array $where = null, string $table_name = null) : ?array
    {
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->cmd($action, $where, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @return array|null
     */
    private function getResult() : ?array
    {
        return array_shift($this->result);
    }
}
