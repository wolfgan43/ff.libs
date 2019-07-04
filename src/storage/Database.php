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

use phpformsframework\libs\Constant;
use phpformsframework\libs\Error;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dumpable;

class Database implements Dumpable
{
    const ERROR_BUCKET                                                      = "database";
    const NAME_SPACE                                                        = 'phpformsframework\\libs\\storage\\adapters\\';
    const ENABLE_CACHE                                                      = true;
    const ADAPTER                                                           = "mysqli";

    const MAX_RECURSION                                                     = 100;

    private static $singletons                                              = null;
    private static $cache                                                   = null;
    private static $cache_rawdata                                           = array();

    /**
     * @var DatabaseAdapter[]
     */
    private $adapters                                                       = array();
    private $result                                                         = null;

    /**
     * @param array|string $databaseAdapters
     * @param null|array $params
     * @return DatabaseAdapter
     */
    public static function getInstance($databaseAdapters, $params = null)
    {
        $key                                                                = crc32(
            serialize($databaseAdapters)
                                                                                . "-" . $params["table"]["name"]
                                                                                . (
                                                                                    isset($params["rawdata"]) && $params["rawdata"]
                                                                                    ? "-rawdata"
                                                                                    : ""
                                                                                )
                                                                                . (
                                                                                    isset($params["exts"]) && $params["exts"]
                                                                                    ? "-exts"
                                                                                    : ""
                                                                                )
                                                                            );
        if (!isset(self::$singletons[$key])) {
            self::$singletons[$key] = new Database($databaseAdapters, $params);
        }

        return self::$singletons[$key];
    }

    public static function isAssocArray(array $arr)
    {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Database constructor.
     *
     * @example string $databaseAdapters: mysqli OR mongodb OR ecc
     * @example array $databaseAdapters: [mysqli, mongodb]
     * @example arrayAssociative $databaseAdapters: [mysqli : {
            "host"          => null
            , "username"    => null
            , "password"    => null
            , "name"        => null
            , "prefix"		=> null
            , "table"       => null
            , "key"         => null
        }
     *
     *
     * @param array|string $databaseAdapters
     * @param null $params
     */
    public function __construct($databaseAdapters = self::ADAPTER, $params = null)
    {
        if (is_array($databaseAdapters)) {
            foreach ($databaseAdapters as $adapter => $connection) {
                if (is_numeric($adapter) && strlen($connection)) {
                    $adapter                                                = $connection;
                    $connection                                             = null;
                }

                $class_name                                                 = static::NAME_SPACE . "Database" . ucfirst($adapter);
                $this->adapters[$adapter]                                   = new $class_name($connection, $params["table"], $params["struct"], $params["relationship"], $params["indexes"], $params["alias"], (bool) $params["exts"], (bool) $params["rawdata"]);
            }
        } elseif ($databaseAdapters) {
            $class_name                                                     = static::NAME_SPACE . "Database" . ucfirst($databaseAdapters);
            $this->adapters[$databaseAdapters]                              = new $class_name($params["connection"], $params["table"], $params["struct"], $params["relationship"], $params["indexes"], $params["alias"], (bool) $params["exts"], (bool) $params["rawdata"]);
        }
    }

    /**
     * @param string|array $query
     * @param string[recordset|fields|num_rows] $key
     * @return null|bool|array
     */
    public function rawQuery($query, $key = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->rawQuery($query, $key);
        }
        return $this->getResult();
    }

    /**
     * @param string $table_name
     * @param null|array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|array $limit
     * @return bool|array
     */
    public function lookup($table_name, $where = null, $fields = null, $sort = null, $limit = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->lookup($table_name, $where, $fields, $sort, $limit);
        }
        return $this->getResult();
    }

    /**
     * @param null|array $fields
     * @param null|array $where
     * @param null|array $sort
     * @param null|array $limit
     * @param null|array $table_name
     * @return bool|array
     */
    public function find($fields = null, $where = null, $sort = null, $limit = null, $table_name = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->find($fields, $where, $sort, $limit, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param array $where
     * @param null|array $fields
     * @param null|array $sort
     * @param null|array $limit
     * @param null|string $table_name
     * @return bool|array
     */
    public function read($where, $fields = null, $sort = null, $limit = null, $table_name = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->read($where, $fields, $sort, $limit, $table_name);
        }

        return $this->getResult();
    }

    /**
     * @param array $insert
     * @param null|string $table_name
     * @return bool
     */
    public function insert($insert, $table_name = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->insert($insert, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param array $set
     * @param array $where
     * @param null|string $table_name
     * @return bool
     */
    public function update($set, $where, $table_name = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->update($set, $where, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param array $insert
     * @param array $update
     * @param null|string $table_name
     * @return bool
     */
    public function write($insert, $update, $table_name = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->write($insert, $update, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param array $where
     * @param null|string $table_name
     * @return bool
     */
    public function delete($where, $table_name = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->delete($where, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @param string $action
     * @param array $what
     * @param null|string $table_name
     * @return bool
     */
    public function cmd($action, $what, $table_name = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->cmd($action, $what, $table_name);
        }
        return $this->getResult();
    }

    private function getResult()
    {
        return (Error::check(static::ERROR_BUCKET)
            ? Error::raise(static::ERROR_BUCKET)
            : (
                is_array($this->result) && count($this->result) == 1
                ? array_shift($this->result)
                : $this->result
            )
        );
    }

    private static function getCacheParam($param, $sep = ", ")
    {
        return (is_array($param)
            ? implode($sep, $param)
            : $param
        );
    }

    private static function getCacheKey($query)
    {
        $action                                     = $query["action"];
        $table                                      = $query["from"];

        $where = (
            isset($query["where"])
            ? " WHERE " . self::getCacheParam($query["where"], " AND ")
            : ""
        );

        $select = (
            isset($query["select"])
            ? " SELECT " . self::getCacheParam($query["select"])
            : ""
        );
        $set = (
            isset($query["set"])
            ? " SET " . self::getCacheParam($query["set"])
            : ""
        );
        $insert = (
            isset($query["insert"])
            ? " INSERT " . self::getCacheParam($query["insert"], " VALUES ")
            : ""
        );

        return ucfirst($action) . " => " . $table . " (" . $insert . $set . $select . $where . ")";
    }

    public static function dump()
    {
        return self::$cache_rawdata;
    }

    public static function cache($query)
    {
        $res                                        = null;

        if (self::ENABLE_CACHE) {
            $cache_key                              = Database::getCacheKey($query);
            if (Constant::DEBUG) {
                self::$cache[$cache_key]["count"]   = (
                    isset(self::$cache[$cache_key])
                                                        ? self::$cache[$cache_key]["count"] + 1
                                                        : 1
                                                    );
                if (self::$cache[$cache_key]["count"] > self::MAX_RECURSION) {
                    Debug::dump("Max Recursion: " . print_r($query, true));
                    exit;
                }
            }

            if (isset(self::$cache[$cache_key]["data"])) {
                if (Constant::DEBUG) {
                    Debug::dumpLog("query_duplicate", $query);
                }
                $res                                = self::$cache[$cache_key]["data"];
            }
        }

        return $res;
    }

    public static function setCache($data, $query)
    {
        if (self::ENABLE_CACHE) {
            $cache_key                                                  = Database::getCacheKey($query);
            if (Constant::DEBUG) {
                $from_cache = isset(self::$cache[$cache_key]["data"]) && self::$cache[$cache_key]["data"];
                self::$cache_rawdata[(count(self::$cache_rawdata) + 1) . ". " . $cache_key] = ($from_cache ? true : $query);
            }
            self::$cache[$cache_key]["query"]                           = $query;
            if (isset($data["exts"]) && $data["exts"] === true) {
                self::$cache[$cache_key][serialize($data["exts"])]      = $data;
            } else {
                self::$cache[$cache_key]["data"]                        = $data;
            }
        }
    }
}
