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
abstract class DatabaseDriver
{
    const ERROR_BUCKET              = "database";

    protected static $_dbs 	        = array();

    protected $locale               = "ISO9075";

    protected $host                 = null;
    protected $database             = null;
    protected $user                 = null;
    protected $secret               = null;

    protected $row	                = -1;
    protected $errno                = 0;
    protected $error                = "";

    protected $link_id              = null;
    protected $query_id             = null;
    protected $fields               = array();
    protected $fields_names	        = array();

    protected $num_rows             = null;
    protected $record               = null;
    protected $buffered_insert_id   = null;

    abstract public static function factory();
    abstract public static function freeAll();

    abstract protected function id2object($value);

    /**
     * @param string|null $Database
     * @param string|null $Host
     * @param string|null $User
     * @param string|null $Secret
     * @return bool
     */
    abstract public function connect(string $Database = null, string $Host = null, string $User = null, string $Secret = null) : bool;

    /**
     * @param array $query
     * @param string|null $table
     * @return bool
     */
    abstract public function insert(array $query, string $table = null) : bool;

    /**
     * @param array $query
     * @param string|null $table
     * @return bool
     */
    abstract public function update(array $query, string $table = null) : bool;

    /**
     * @param array $query
     * @param string|null $table
     * @return bool
     */
    abstract public function delete(array $query, string $table = null) : bool;

    /**
     * @param array|string $query
     * @return bool
     */
    abstract public function query(array $query) : bool;

    /**
     * @param array $query
     * @param string $name
     * @return mixed
     */
    abstract public function cmd(array $query, string $name = "count");

    /**
     * @param array $queries
     * @return array|null
     */
    abstract public function multiQuery(array $queries) : ?array;
    abstract public function lookup(string $tabella, string $chiave = null, string $valorechiave = null, string $defaultvalue = null, string $nomecampo = null, string $tiporestituito = null, bool $bReturnPlain = false);

    /**
     * @param object|null $obj
     * @return bool
     */
    abstract public function nextRecord(object &$obj = null) : bool;

    /**
     * @return int|null
     */
    abstract public function numRows() : ?int;

    abstract public function getRecordset(object $obj = null);

    /**
     * @return array
     */
    abstract public function getFieldset() : array;

    /**
     * @return string|null
     */
    abstract public function getInsertID() : ?string;

    /**
     * @param string $DataValue
     * @return string
     */
    abstract protected function toSqlEscape(string $DataValue) : string;

    /**
     * @param string $msg
     */
    abstract protected function errorHandler(string $msg) : void;

    /**
     *
     * @param string Nome del campo
     * @param string Tipo di dato inserito
     * @param bool $bReturnPlain
     * @param bool $return_error
     * @return mixed Dato recuperato dal DB
     */
    public function getField(string $Name, string $data_type = "Text", bool $bReturnPlain = false, bool $return_error = true)
    {
        if (!$this->query_id) {
            $this->errorHandler("f() called with no query pending");
            return false;
        }

        if (isset($this->fields[$Name])) {
            $tmp = $this->record[$Name];
        } else {
            if ($return_error) {
                $tmp = "NO_FIELD [" . $Name . "]";
            } else {
                $tmp = null;
            }
        }

        if ($bReturnPlain) {
            if ($data_type == "Number") {
                if (strpos($tmp, ".") === false) {
                    return (int)$tmp;
                } else {
                    return (double)$tmp;
                }
            } else {
                return $tmp;
            }
        } else {
            return new Data($tmp, $data_type, $this->locale);
        }
    }

    /**
     * @todo da tipizzare
     * @param mixed $mixed
     * @param string|null $type
     * @param bool $enclose
     * @return string
     */
    public function toSql($mixed, string $type = null, bool $enclose = true) : string
    {
        if (is_array($mixed)) {
            $res = $this->toSqlArray($mixed, $type, $enclose);
        } elseif (is_object($mixed)) {
            switch (get_class($mixed)) {
                case DateTime::class:
                    $res = $this->toSqlDateTime($mixed, $enclose);
                    break;
                case Data::class:
                    $res = $this->toSqlData($mixed, $enclose);
                    break;
                default:
                    $res = $this->toSqlObject($mixed, $enclose);
            }
        } else {
            $res = $this->toSqlString($mixed, $type, $enclose);
        }

        return $res;
    }

    /**
     * @param Data $Data
     * @param bool $enclose
     * @return string|null
     */
    public function toSqlData(Data $Data, bool $enclose = true) : ?string
    {
        return $this->toSqlString($Data->getValue($Data->data_type, $this->locale), null, $enclose);
    }

    /**
     * @param array $Array
     * @param string|null $type
     * @param bool $enclose
     * @return string|null
     */
    public function toSqlArray(array $Array, string $type = null, bool $enclose = true) : ?string
    {
        switch (strtolower($type)) {
            case DatabaseAdapter::FTYPE_ARRAY_JSON:
                $value = json_encode($Array);
                break;
            case DatabaseAdapter::FTYPE_OBJECT:
                $value = serialize($Array);
                break;
            case DatabaseAdapter::FTYPE_ARRAY:
            case DatabaseAdapter::FTYPE_ARRAY_OF_NUMBER:
            case DatabaseAdapter::FTYPE_ARRAY_INCREMENTAL:
            default:
                $value = implode(",", $Array);
        }

        return $this->toSqlString($value, null, $enclose);
    }

    /**
     * @param object $Object
     * @param bool $enclose
     * @return string|null
     */
    public function toSqlObject(object $Object, bool $enclose = true) : ?string
    {
        return $this->toSqlString(serialize($Object), null, $enclose);
    }

    /**
     * @param DateTime $Object
     * @param bool $enclose
     * @return string|null
     */
    public function toSqlDateTime(DateTime $Object, bool $enclose = true) : ?string
    {
        //@todo to implement
        return $this->toSqlString($Object, null, $enclose);
    }
    /**
     * @param string $value
     * @param string|null $type
     * @param bool $enclose
     * @return string|null
     */
    public function toSqlString(string $value, string $type = null, bool $enclose = true) : ?string
    {
        switch (strtolower($type)) {
            case DatabaseAdapter::FTYPE_BOOLEAN:
                $value = (bool) $value;
                break;
            case DatabaseAdapter::FTYPE_NUMBER:
            case DatabaseAdapter::FTYPE_NUMBER_BIG:
            case DatabaseAdapter::FTYPE_NUMBER_FLOAT:
            case DatabaseAdapter::FTYPE_NUMBER_DECIMAN:
                if (!strlen($value)) {
                    $value = 0;
                }
                break;
            case DatabaseAdapter::FTYPE_DATE:
            case DatabaseAdapter::FTYPE_TIME:
            case DatabaseAdapter::FTYPE_DATE_TIME:
                $value = (
                    strlen($value)
                    ? $this->toSqlEscape((new Data($value, $type))->getValue($type, $this->locale))
                    : Data::getEmpty($type, $this->locale)
                );
            default:
                if ($enclose) {
                    $value = "'" . $value . "'";
                }
        }

        return $value;
    }
}
