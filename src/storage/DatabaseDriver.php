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

use DateTime;
use phpformsframework\libs\international\Data;

/**
 * Class DatabaseDriver
 * @package phpformsframework\libs\storage
 */
abstract class DatabaseDriver implements Constant
{
    protected static $_dbs 	                = array();

    protected $locale                       = "ISO9075";

    protected $host                         = null;
    protected $database                     = null;
    protected $user                         = null;
    protected $secret                       = null;

    protected $row	                        = -1;
    protected $errno                        = 0;
    protected $error                        = "";

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
     * @return int|null
     */
    abstract public function numRows() : ?int;

    /**
     * @return array|null
     */
    abstract public function getRecordset() : ?array;

    /**
     * @return array
     */
    abstract public function getFieldset() : array;

    /**
     * @param array $keys
     * @return array|null
     */
    abstract public function getUpdatedIDs(array $keys) : ?array;

    /**
     * @return string|null
     */
    abstract public function getInsertID() : ?string;

    /**
     * @param string $DataValue
     * @return string|null
     */
    abstract protected function toSqlEscape(string $DataValue) : ?string;

    /**
     * @param string $msg
     */
    abstract protected function errorHandler(string $msg) : void;

    /**
     * @todo da tipizzare
     * @param string $value
     * @return mixed
     */
    abstract protected function convertID(string $value);

    /**
     * @todo da tipizzare
     * @param mixed $mixed
     * @param string $type
     * @return mixed
     */
    public function toSql($mixed, string $type)
    {
        if (is_array($mixed)) {
            $res = $this->toSqlArray($type, $mixed);
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
        } else {
            $res = $this->toSqlString($type, $mixed);
        }
        return $res;
    }

    /**
     * @param Data $Data
     * @return string|null
     */
    private function toSqlData(Data $Data) : ?string
    {
        return $this->toSqlString(self::FTYPE_STRING, $Data->getValue($Data->data_type, $this->locale));
    }

    /**
     * todo da tipizzare
     * @param array $Array
     * @param string $type
     * @return string|null
     */
    protected function toSqlArray(string $type, array $Array)
    {
        switch ($type) {
            case self::FTYPE_ARRAY_JSON:
                $value = $this->toSqlEscape(json_encode($Array));
                break;
            case self::FTYPE_OBJECT:
                $value = $this->toSqlEscape(serialize($Array));
                break;
            case self::FTYPE_PRIMARY:
                $value = array_map(function ($value) {
                    return $this->convertID($value);
                }, $Array);
                break;
            case self::FTYPE_ARRAY:
            case self::FTYPE_ARRAY_OF_NUMBER:
            case self::FTYPE_ARRAY_INCREMENTAL:
            default:
                $value = array_map(function ($value) {
                    return $this->toSqlEscape($value);
                }, $Array);
        }

        return $value;
    }

    /**
     * @param object $Object
     * @return string|null
     */
    private function toSqlObject(object $Object) : ?string
    {
        return $this->toSqlString(self::FTYPE_STRING, serialize($Object));
    }

    /**
     * @param DateTime $Object
     * @return string|null
     */
    private function toSqlDateTime(DateTime $Object) : ?string
    {
        //@todo to implement
        return $this->toSqlString(self::FTYPE_STRING, $Object->format('Y-M-d H:m:s'));
    }
    /**
     * @todo da tipizzare
     * @param string|null $value
     * @param string $type
     * @return string|null
     */
    protected function toSqlString(string $type, string $value = null)
    {
        if ($value !== null) {
            switch ($type) {
                case self::FTYPE_PRIMARY:
                    $value = $this->convertID($value);
                    break;
                case self::FTYPE_BOOLEAN:
                case self::FTYPE_BOOL:
                    $value = (bool) $value;
                    break;
                case self::FTYPE_NUMBER:
                case self::FTYPE_NUMBER_BIG:
                case self::FTYPE_NUMBER_DECIMAN:
                case self::FTYPE_TIMESTAMP:
                    $value = (
                        strlen($value)
                        ? (int) $this->toSqlEscape($value)
                        : 0
                    );
                    break;
                case self::FTYPE_NUMBER_FLOAT:
                    $value = (
                        strlen($value)
                        ? (float) $this->toSqlEscape($value)
                        : 0
                    );
                    break;
                case self::FTYPE_DATE:
                case self::FTYPE_TIME:
                case self::FTYPE_DATE_TIME:
                    $value = (
                        strlen($value)
                        ? $this->toSqlEscape((new Data($value, $type))->getValue($type, $this->locale))
                        : Data::getEmpty($type, $this->locale)
                    );
                    break;
                default:
                    $value = $this->toSqlEscape($value);
            }
        }

        return $value;
    }
}
