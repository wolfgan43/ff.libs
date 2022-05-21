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

use phpformsframework\libs\international\Data;
use phpformsframework\libs\Exception;
use DateTime;
use phpformsframework\libs\Kernel;

/**
 * Class DatabaseDriver
 * @package phpformsframework\libs\storage
 */
abstract class DatabaseDriver implements Constant
{
    protected static $links 	            = [];

    public const ENGINE                     = null;

    protected const MAX_NUMROWS             = DatabaseAdapter::MAX_NUMROWS;
    protected const PREFIX                  = null;
    protected const SEP                     = ",";

    protected $locale                       = "ISO9075";

    protected $host                         = null;
    protected $database                     = null;
    protected $user                         = null;
    protected $secret                       = null;

    protected $row	                        = -1;
    protected $errno                        = 0;
    protected $error                        = "";

    protected $dbKey                        = null;
    protected $link_id                      = null;
    protected $query_id                     = null;
    protected $fields                       = array();
    protected $fields_names	                = array();

    protected $num_rows                     = null;
    protected $record                       = null;
    protected $buffered_insert_id           = null;

    abstract public static function freeAll() : void;

    /**
     * @param string|null $Database
     * @param string|null $Host
     * @param string|null $User
     * @param string|null $Secret
     * @return bool
     */
    abstract public function connect(string $Database = null, string $Host = null, string $User = null, string $Secret = null) : bool;

    /**
     * @param DatabaseQuery $query
     * @return bool
     */
    abstract public function read(DatabaseQuery $query) : bool;

    /**
     * @param DatabaseQuery $query
     * @return bool
     */
    abstract public function insert(DatabaseQuery $query) : bool;

    /**
     * @param DatabaseQuery $query
     * @return bool
     */
    abstract public function update(DatabaseQuery $query) : bool;

    /**
     * @param DatabaseQuery $query
     * @return bool
     */
    abstract public function delete(DatabaseQuery $query) : bool;

    /**
     * @param DatabaseQuery $query
     * @param string|null $action
     * @return array|null
     */
    abstract public function cmd(DatabaseQuery $query, string $action = self::CMD_COUNT) : ?array;

    /**
     * @param string|DatabaseQuery $query
     * @return bool
     */
    abstract public function query($query) : bool;

    /**
     * @param array $queries
     * @return array|null
     */
    abstract public function multiQuery(array $queries) : ?array;

    /**
     * @param object|null $obj
     * @return bool
     */
    abstract public function nextRecord(object &$obj = null) : bool;

    /**
     * @return int
     */
    abstract public function numRows() : int;

    /**
     * @return array|null
     */
    abstract public function getRecord() : ?array;

    /**
     * @return array|null
     */
    abstract public function getRecordset() : ?array;

    /**
     * @return array
     */
    abstract public function getFieldset() : array;

    /**
     * @param array|null $keys
     * @return array
     */
    abstract public function getUpdatedIDs(array $keys = null) : array;

    /**
     * @param array|null $keys
     * @return array
     */
    abstract public function getDeletedIDs(array $keys = null) : array;

    /**
     * @return string|null
     */
    abstract public function getInsertID() : ?string;

    /**
     * @param bool|float|int|string|array $DataValue
     * @return bool|float|int|string|array
     * @todo da tipizzare
     */
    abstract protected function toSqlEscape($DataValue);

    /**
     * @param string $msg
     */
    abstract protected function errorHandler(string $msg) : void;

    /**
     * @param string|null $value
     * @return mixed
     * @todo da tipizzare
     */
    abstract protected function convertID(string $value = null);

    public function __construct(string $prefix = null)
    {
        $bucket                                 = Kernel::$Environment . '::' . (
            $prefix && defined(Kernel::$Environment . '::' . $prefix . "NAME")
                ? $prefix
                : static::PREFIX
        );

        if (($host = Constant($bucket . "HOST")) && ($name = Constant($bucket . "NAME"))) {
            $this->connect(
                $name,
                $host,
                Constant($bucket . "USER"),
                Constant($bucket . "SECRET")
            );
        } else {
            die("Missing Database Connection Params: " . $bucket . "HOST, " . $bucket . "NAME, [...]");
        }
    }

    /**
     * @return string|null
     */
    public function dbKey() : ?string
    {
        return crc32($this->dbKey);
    }

    /**
     * @param string|int|float|array|object|DateTime|Data $mixed
     * @param string $type
     * @param bool $castResult
     * @return mixed
     * @throws Exception
     * @todo da tipizzare
     */
    public function toSql($mixed, string $type = self::FTYPE_STRING, bool $castResult = false)
    {
        if ($type == self::FTYPE_PRIMARY) {
            $res = (
                is_array($mixed)
                ? implode(static::SEP, array_map(function ($value) {
                    return $this->convertID($value);
                }, $mixed))
                : $this->convertID($mixed)
            );
        } elseif (is_array($mixed)) {
            $res = $this->toSqlArray($type, $mixed, $castResult);
        } elseif (is_object($mixed)) {
            switch (get_class($mixed)) {
                case DateTime::class:
                    $res = $this->toSqlDateTime($mixed);
                    break;
                case Data::class:
                    $res = $this->toSqlData($mixed);
                    break;
                default:
                    $res = $this->toSqlObject($mixed);
            }
        } elseif ($castResult) {
            $res = $this->toSqlString($this->toSqlStringCast($type, $mixed));
        } else {
            $res = $this->toSqlString($mixed);
        }

        return $res;
    }

    /**
     * @param Data $Data
     * @return string|null
     * @throws Exception
     */
    private function toSqlData(Data $Data) : ?string
    {
        return $this->toSqlString($Data->getValue($Data->data_type, $this->locale));
    }

    /**
     * todo da tipizzare
     * @param array $Array
     * @param string $type
     * @return string|array
     */
    protected function toSqlArray(string $type, array $Array, bool $castResult = false)
    {
        switch ($type) {
            case self::FTYPE_ARRAY_JSON:
                $value = $this->toSqlEscape(json_encode($Array));
                break;
            case self::FTYPE_OBJECT:
                $value = $this->toSqlEscape(serialize($Array));
                break;
            case self::FTYPE_ARRAY:
            case self::FTYPE_ARRAY_OF_NUMBER:
            case self::FTYPE_ARRAY_INCREMENTAL:
            default:
                if ($this->toSqlArrayMulti($Array)) {
                    $this->errorHandler("Multidimensional Array not managed: " . json_encode($Array));
                }
                $value = $this->toSqlEscape($Array);
        }
        return $value;
    }

    /**
     * @param array $Array
     * @return bool
     */
    private function toSqlArrayMulti(array $Array) : bool
    {
        foreach ($Array as $v) {
            if (is_array($v)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param object $Object
     * @return string|null
     * @throws Exception
     */
    private function toSqlObject(object $Object) : ?string
    {
        return $this->toSqlString(serialize($Object));
    }

    /**
     * @param DateTime $Object
     * @return string|null
     * @throws Exception
     */
    private function toSqlDateTime(DateTime $Object) : ?string
    {
        //@todo to implement
        return $this->toSqlString($Object->format('Y-M-d H:m:s'));
    }

    /**
     * @param bool|float|int|string $value
     * @return bool|float|int|string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function toSqlString($value)
    {
        return $this->toSqlEscape($value);
    }

    /**
     * @param string $type
     * @param bool|float|int|string $value
     * @return bool|float|int|string
     * @throws Exception
     * @todo da tipizzare
     * @todo i cast int e fload danno problemi con numeri enormi.
     */
    protected function toSqlStringCast(string $type, $value)
    {
        switch ($type) {
            case self::FTYPE_BOOLEAN:
            case self::FTYPE_BOOL:
                $res = (bool) $value;
                break;
            case self::FTYPE_NUMBER:
            case self::FTYPE_NUMBER_BIG:
            case self::FTYPE_NUMBER_DECIMAN:
            case self::FTYPE_TIMESTAMP:
            case self::FTYPE_TIMESTAMP_MICRO:
                $res = (
                    strlen($value)
                    ? (int) $value
                    : 0
                );
                break;
            case self::FTYPE_NUMBER_FLOAT:
            case self::FTYPE_NUMBER_DOUBLE:
                $res = (
                    strlen($value)
                    ? (float) $value
                    : 0
                );
                break;
            case self::FTYPE_DATE:
            case self::FTYPE_TIME:
            case self::FTYPE_DATE_TIME:
                $res = (
                    strlen($value)
                    ? (new Data($value, $type))->getValue($type, $this->locale)
                    : Data::getEmpty($type, $this->locale)
                );
                break;
            default:
                $res = $value;
        }

        return $res;
    }
}
