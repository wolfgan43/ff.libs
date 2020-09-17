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

use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\AdapterManager;
use Exception;

/**
 * Class Database
 * @package phpformsframework\libs\storage
 * @property DatabaseAdapter[] $adapters
 */
class Database implements Constant
{
    use AdapterManager;

    public const RESULT                                                     = "result";
    public const INDEX                                                      = "index";
    public const INDEX_PRIMARY                                              = "primary";
    public const COUNT                                                      = "count";

    private static $singletons                                              = null;

    private $table                                                          = null;
    private $result                                                         = null;

    /**
     * @param array $databaseAdapters
     * @param null|array $struct
     * @return Database
     */
    public static function getInstance(array $databaseAdapters, array $struct = null) : Database
    {
        $key                                                                = crc32(
            serialize($databaseAdapters + $struct["table"])
                                                                            );
        if (!isset(self::$singletons[$key])) {
            self::$singletons[$key]                                         = new Database($databaseAdapters, $struct);
        }

        return self::$singletons[$key];
    }

    /**
     * @param array $arr
     * @return bool
     */
    public static function isAssocArray(array $arr) : bool
    {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Database constructor.
     *
     * @param array|null $databaseAdapters
     * @param array|null $struct
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
     *
     */
    public function __construct(array $databaseAdapters = null, array $struct = null)
    {
        if (!$databaseAdapters) {
            $databaseAdapters[Kernel::$Environment::DATABASE_ADAPTER]       = null;
        }

        foreach ($databaseAdapters as $adapter => $connection) {
            $this->setAdapter($adapter, array_values($struct));
        }

        $this->table                                                        = (
            isset($struct["table"]["name"])
                                                                                ? $struct["table"]["name"]
                                                                                : null
                                                                            );
    }


    /**
     * @todo da tipizzare
     * @param $query
     * @return array|null
     */
    public function rawQuery($query) : ?array
    {
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->rawQuery($query);
        }
        return $this->getResult();
    }

    /**
     * @param array|null $fields
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $table_name
     * @return array|null
     * @throws Exception
     */
    public function read(array $fields = null, array $where = null, array $sort = null, int $limit = null, int $offset = null, string $table_name = null) : ?array
    {
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->read($fields, $where, $sort, $limit, $offset, $table_name);
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
     * @param array $where
     * @param null|string $table_name
     * @return array|null
     * @throws Exception
     */
    public function update(array $set, array $where, string $table_name = null) : ?array
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
     * @param array $where
     * @param string $action
     * @param null|string $table_name
     * @return array|null
     * @throws Exception
     */
    public function cmd(array $where, string $action, string $table_name = null) : ?array
    {
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->cmd($where, $action, $table_name);
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
