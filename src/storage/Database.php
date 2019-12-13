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

use phpformsframework\libs\Env;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\AdapterManager;

/**
 * Class Database
 * @package phpformsframework\libs\storage
 * @property DatabaseAdapter[] $adapters
 */
class Database implements Dumpable
{
    use AdapterManager;

    const ERROR_BUCKET                                                      = "database";
    const NAME_SPACE                                                        = __NAMESPACE__ . '\\adapters\\';

    public const RESULT                                                     = "result";
    public const INDEX                                                      = "index";
    public const INDEX_PRIMARY                                              = "primary";
    public const RAWDATA                                                    = "rawdata";
    public const COUNT                                                      = "count";

    private static $singletons                                              = null;
    private static $cache                                                   = null;
    private static $cache_rawdata                                           = array();

    private $table                                                          = null;
    private $result                                                         = null;
    private $cache_key                                                      = null;

    /**
     * @param array $databaseAdapters
     * @param null|array $struct
     * @param bool $rawdata
     * @return Database
     */
    public static function getInstance(array $databaseAdapters, array $struct = null, bool $rawdata = false) : Database
    {
        $key                                                                = crc32(
            serialize($databaseAdapters)
                                                                                . "-" . $struct["table"]["name"]
                                                                                . (
                                                                                    $rawdata
                                                                                    ? "-rawdata"
                                                                                    : ""
                                                                                )
                                                                            );
        if (!isset(self::$singletons[$key])) {
            self::$singletons[$key]                                         = new Database($databaseAdapters, $struct, $rawdata);
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

     * @example array $databaseAdapters: [mysqli, mongodb]
     * @example arrayAssociative $databaseAdapters: [mysqli : {
     * "host"          => null
     * , "username"    => null
     * , "secret"      => null
     * , "name"        => null
     * , "prefix"        => null
     * , "table"       => null
     * , "key"         => null
     * }
     *
     * @example string $databaseAdapters: mysqli OR mongodb OR ecc
     * @param array|string $databaseAdapters
     * @param array|null $struct
     * @param bool $rawdata
     */
    public function __construct(array $databaseAdapters = null, array $struct = null, bool $rawdata = false)
    {
        if (!$databaseAdapters) {
            $databaseAdapters[Kernel::$Environment::DATABASE_ADAPTER]       = null;
        }

        foreach ($databaseAdapters as $adapter => $connection) {
            //@todo da sistemare meglio l'array params
            $this->setAdapter($adapter, array_values($struct + array($rawdata)));
        }

        $this->table                                                        = (
            isset($struct["table"]["name"])
                                                                                ? $struct["table"]["name"]
                                                                                : null
                                                                            );
    }

    /**
     * @param array $query
     * @param string[recordset|fields|num_rows] $key
     * @return array|null
     */
    public function rawQuery(array $query, string $key = null) : ?array
    {
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->rawQuery($query, $key);
        }
        return $this->getResult();
    }

    /**
     * @param array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|int $limit
     * @param int|null $offset
     * @param null|string $table_name
     * @return array|null
     */
    public function read(array $where, array $fields = null, array $sort = null, int $limit = null, int $offset = null, string $table_name = null) : ?array
    {
        if (!$this::cacheRead($where, $fields, $sort, $limit, $offset, $table_name)) {
            foreach ($this->adapters as $adapter_name => $adapter) {
                $this->result[$adapter_name]                                    = $this->cacheSave($adapter->read($where, $fields, $sort, $limit, $offset, $table_name));
            }
        }

        return self::$cache[$this->cache_key]["data"]; //$this->getResult();
    }

    /**
     * @param array $insert
     * @param null|string $table_name
     * @return array|null
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
     * @param array $update
     * @param null|string $table_name
     * @return array|null
     */
    public function write(array $insert, array $update, string $table_name = null) : ?array
    {
        //@todo da alterare la cache in funzione dei dati inseriti
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->write($insert, $update, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param array $where
     * @param null|string $table_name
     * @return array|null
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
     * @param array $what
     * @param null|string $table_name
     * @return array|null
     */
    public function cmd(string $action, array $what, string $table_name = null) : ?array
    {
        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->cmd($action, $what, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @return array|null
     */
    private function getResult() : ?array
    {
        return (
            is_array($this->result) && count($this->result) == 1
            ? array_shift($this->result)
            : $this->result
        );
    }



    /**
     * @return array
     */
    public static function dump() : array
    {
        return self::$cache_rawdata;
    }




    /**
     * @param array|null $data
     * @return array|null
     */
    private function cacheSave(array $data = null) : ?array
    {
        if (Kernel::$Environment::CACHE_DATABASE_ADAPTER) {
            self::$cache[$this->cache_key]["data"]                                      = $data;
        }

        return $data;
    }

    /**
     * @param array $where
     * @param array|null $fields
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $table_name
     * @return bool
     */
    private function cacheRead(array $where, array $fields = null, array $sort = null, int $limit = null, int $offset = null, string $table_name = null) : bool
    {
        $this->cacheReadKey($this->cacheTable($table_name), $where, $fields, $sort, $limit, $offset);

        return isset(self::$cache[$this->cache_key]["data"]);
    }


    /**
     * @param string|null $table_name
     * @return string
     */
    private function cacheTable(string $table_name = null) : string
    {
        return ($table_name
            ? $table_name
            : $this->table
        );
    }

    /**
     * @param string $table
     * @param array $where
     * @param array|null $fields
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     */
    private function cacheReadKey(string $table, array $where, array $fields = null, array $sort = null, int $limit = null, int $offset = null) : void
    {
        $this->cache_key                                                            = $this->cacheParamArray("SELECT", $fields, "*") .
                                                                                        " FROM " . $table .
                                                                                        $this->cacheParamArray(" WHERE", $where) .
                                                                                        $this->cacheParamArray(" ORDER BY", $sort) .
                                                                                        $this->cacheParamString(" LIMIT", $limit) .
                                                                                        $this->cacheParamString(" OFFSET", $offset);
        $label                                                                      = (count(self::$cache_rawdata) + 1) . ". " . " Read => " . $table . " (" . $this->cache_key . ")";
        $value                                                                      = "From Cache";
        if (!isset(self::$cache[$this->cache_key])) {
            self::$cache[$this->cache_key]["query"]                                 = array(
                "select"    => $fields,
                "from"      => $table,
                "where"     => $where,
                "sort"      => $sort,
                "limit"     => $limit,
                "offset"    => $offset
            );
            $value                                                                  = "From Database";
        } elseif (Kernel::$Environment::DEBUG) {
            Debug::dumpLog(static::ERROR_BUCKET . "_duplicate", $this->cache_key);
        }
        self::$cache_rawdata[$label]                                                = $value;

        self::$cache[$this->cache_key]["count"]                                     =+ 1;
        if (self::$cache[$this->cache_key]["count"] > Env::get("DATABASE_MAX_RECURSION")) {
            Debug::dump("Max Recursion ("  . Env::get("DATABASE_MAX_RECURSION") . ") : " . self::$cache[$this->cache_key]["query"]);
            exit;
        }
    }

    /**
     * @param string $prefix
     * @param string $param
     * @param string|null $default
     * @return string|null
     */
    private function cacheParamString(string $prefix, string $param = null, string $default = null) : ?string
    {
        if (!$param) {
            $param = $default;
        }

        return ($param
            ? $prefix . " " . $param
            : null
        );
    }

    /**
     * @param string $prefix
     * @param array|null $param
     * @param string|null $default
     * @return string|null
     */
    private function cacheParamArray(string $prefix, array $param = null, string $default = null) : ?string
    {
        if ($param !== null) {
            if ($default) {
                $param = array_keys($param);
            }
            $param = $this->cacheArray2String($param);
            if ($prefix == " ORDER BY") {
                $param = str_replace(
                    array(
                        '= -1',
                        '= 1',
                        '= DESC',
                        '= ASC',
                        '= desc',
                        '= asc'
                    ),
                    array(
                        "DESC",
                        "ASC",
                        "DESC",
                        "ASC",
                        "DESC",
                        "ASC"
                    ),
                    $param
                );
            }
        }

        return $this->cacheParamString($prefix, $param, $default);
    }

    /**
     * @param array $param
     * @return string
     */
    private function cacheArray2String(array $param) : string
    {
        return str_replace(
            array(
                ':{"$gt":',
                ':{"$gte":',
                ':{"$lt":',
                ':{"$lte":',
                ':{"$eq":',
                ':{"$regex":',
                ':{"$in":',
                ':{"$nin":',
                ':{"$ne":',
                ':{"$inset":',
                ':[',']}',
                ':',
                '{', '}', '[', ']', '"', '"'),
            array(
                " > ",
                " >= ",
                " < ",
                " <= ",
                " = ",
                " REGEXP ",
                " IN(",
                " NOT IN(",
                " <> ",
                " FIND_IN_SET(",
                " IN(", ')',
                " = ",
                ""),
            (string)json_encode($param)
        );
    }
}
