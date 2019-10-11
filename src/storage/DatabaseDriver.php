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

    abstract public function insert($query, string $table = null) : bool;
    abstract public function update($query, string $table = null) : bool;
    abstract public function delete($query, string $table = null) : bool;

    abstract public function execute($query) : bool;

    abstract public function query($query) : bool;

    abstract public function cmd($query, string $name = "count");

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
     * @param string|Data $cDataValue
     * @param string $data_type
     * @param bool $enclose_field
     * @param bool $transform_null
     * @return string
     */
    public function toSql($cDataValue, string $data_type = "Text", bool $enclose_field = true, $transform_null = true)
    {
        $value = null;

        if (!$this->link_id) {
            $this->connect();
        }
        if (is_array($cDataValue)) {
            $this->errorHandler("toSql: Wrong parameter, array not managed.");
        } elseif (!is_object($cDataValue)) {
            $value = $this->toSqlEscape($cDataValue);
        } elseif (get_class($cDataValue) == "Data") {
            if ($data_type === null) {
                $data_type = $cDataValue->data_type;
            }
            $value = $this->toSqlEscape($cDataValue->getValue($data_type, $this->locale));
        } elseif (get_class($cDataValue) == "DateTime") {
            switch ($data_type) {
                case "Date":
                    $tmp = new Data($cDataValue, "Date");
                    $value = $this->toSqlEscape($tmp->getValue($data_type, $this->locale));
                    break;

                case "DateTime":
                default:
                    $data_type = "DateTime";
                    $tmp = new Data($cDataValue, "DateTime");
                    $value = $this->toSqlEscape($tmp->getValue($data_type, $this->locale));
            }
        } else {
            $this->errorHandler("toSql: Wrong parameter, unmanaged datatype");
        }

        switch ($data_type) {
            case "Number":
            case "ExtNumber":
                if (!strlen($value)) {
                    if ($transform_null) {
                        return 0;
                    } else {
                        return "null";
                    }
                }
                return $value;

            default:
                if (!strlen($value) && !$transform_null) {
                    return "null";
                }

                if (!strlen($value) && ($data_type == "Date" || $data_type == "DateTime")) {
                    $value = Data::getEmpty($data_type, $this->locale);
                }
                if ($enclose_field) {
                    return "'" . $value . "'";
                } else {
                    return $value;
                }
        }
    }
}
