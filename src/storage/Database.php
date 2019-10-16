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
use phpformsframework\libs\Error;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Kernel;

/**
 * Class Database
 * @package phpformsframework\libs\storage
 */
class Database implements Dumpable
{
    const ERROR_BUCKET                                                      = "database";
    const NAME_SPACE                                                        = __NAMESPACE__ . '\\adapters\\';

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
     * @example string $databaseAdapters: mysqli OR mongodb OR ecc
     * @example array $databaseAdapters: [mysqli, mongodb]
     * @example arrayAssociative $databaseAdapters: [mysqli : {
            "host"          => null
            , "username"    => null
            , "secret"      => null
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
    public function __construct($databaseAdapters = null, $params = null)
    {
        if (!$databaseAdapters) {
            $databaseAdapters                                               = Kernel::$Environment::DATABASE_ADAPTER;
        }

        if (is_array($databaseAdapters)) {
            foreach ($databaseAdapters as $adapter => $connection) {
                if (is_numeric($adapter) && strlen($connection)) {
                    $adapter                                                = $connection;
                    $connection                                             = null;
                }

                $class_name                                                 = static::NAME_SPACE . "Database" . ucfirst($adapter);
                if (class_exists($class_name)) {
                    $this->adapters[$adapter]                               = new $class_name($connection, $params["table"], $params["struct"], $params["relationship"], $params["indexes"], $params["alias"], (bool) $params["exts"], (bool) $params["rawdata"]);
                } else {
                    Error::register("Database Adapter not supported: " . $adapter, static::ERROR_BUCKET);
                }
            }
        } elseif ($databaseAdapters) {
            $class_name                                                     = static::NAME_SPACE . "Database" . ucfirst($databaseAdapters);
            if (class_exists($class_name)) {
                $this->adapters[$databaseAdapters]                          = new $class_name($params["connection"], $params["table"], $params["struct"], $params["relationship"], $params["indexes"], $params["alias"], (bool) $params["exts"], (bool) $params["rawdata"]);
            } else {
                Error::register("Database Adapter not supported: " . $databaseAdapters, static::ERROR_BUCKET);
            }
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
    public function read(array $where, array $fields = null, array $sort = null, array $limit = null, string $table_name = null)
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
    public function insert(array $insert, string $table_name = null)
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
    public function update(array $set, array $where, string $table_name = null)
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
    public function write(array $insert, array $update, string $table_name = null)
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
    public function delete(array $where, string $table_name = null)
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
    public function cmd(string $action, array $what, string $table_name = null)
    {
        Error::clear(static::ERROR_BUCKET);

        foreach ($this->adapters as $adapter_name => $adapter) {
            $this->result[$adapter_name]                                    = $adapter->cmd($action, $what, $table_name);
        }
        return $this->getResult();
    }

    /**
     * @todo da tipizzare
     * @return array|bool
     */
    private function getResult()
    {
        if (Error::check(static::ERROR_BUCKET)) {
            $res = Error::raise(static::ERROR_BUCKET);
        } else {
            $res = (
                is_array($this->result) && count($this->result) == 1
                ? array_shift($this->result)
                : $this->result
            );
        }

        return $res;
    }

    /**
     * @param mixed $param
     * @return string
     */
    private static function getCacheParam($param) : string
    {
        return json_encode($param);
    }

    /**
     * @param array $query
     * @return string
     */
    private static function getCacheKey(array $query) : string
    {
        $action                                                             = $query["action"];
        $table                                                              = $query["from"];

        $where = (
            isset($query["where"])
            ? " WHERE " . self::getCacheParam($query["where"])
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
            ? " INSERT " . self::getCacheParam($query["insert"])
            : ""
        );

        return ucfirst($action) . " => " . $table . " (" . $insert . $set . $select . $where . ")";
    }

    /**
     * @return array
     */
    public static function dump() : array
    {
        return self::$cache_rawdata;
    }

    /**
     * @param array $query
     * @return array|null
     */
    public static function cache(array $query) : ?array
    {
        $res                                                                = null;
        if (Kernel::$Environment::CACHE_DATABASE_ADAPTER) {
            $cache_key                                                      = Database::getCacheKey($query);
            if (Kernel::$Environment::DEBUG) {
                self::$cache[$cache_key]["count"]                           = (
                    isset(self::$cache[$cache_key])
                    ? self::$cache[$cache_key]["count"] + 1
                    : 1
                );

                if (self::$cache[$cache_key]["count"] > Env::get("DATABASE_MAX_RECURSION")) {
                    Debug::dump("Max Recursion ("  . Env::get("DATABASE_MAX_RECURSION") . ") : " . print_r($query, true));
                    exit;
                }
            }

            if (isset(self::$cache[$cache_key]["data"])) {
                if (Kernel::$Environment::DEBUG) {
                    Debug::dumpLog("query_duplicate", $query);
                }
                $res                                                        = self::$cache[$cache_key]["data"];
            }
        }

        return $res;
    }

    /**
     * @todo da tipizzare
     * @param array|bool $data
     * @param array $query
     */
    public static function setCache($data, array $query) : void
    {
        if (Kernel::$Environment::CACHE_DATABASE_ADAPTER) {
            $cache_key                                                      = Database::getCacheKey($query);
            if (Kernel::$Environment::DEBUG) {
                $from_cache                                                 = isset(self::$cache[$cache_key]["data"]) && self::$cache[$cache_key]["data"];
                self::$cache_rawdata[(count(self::$cache_rawdata) + 1) . ". " . $cache_key] = ($from_cache ? $from_cache : $query);
            }
            self::$cache[$cache_key]["query"]                               = $query;
            if (isset($data["exts"]) && $data["exts"] === true) {
                self::$cache[$cache_key][serialize($data["exts"])]          = $data;
            } else {
                self::$cache[$cache_key]["data"]                            = $data;
            }
        }
    }
}
