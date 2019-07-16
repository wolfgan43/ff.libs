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

abstract class DatabaseDriver
{
    const ERROR_BUCKET              = "database";

    protected static $_dbs 	        = array();

    public $transform_null	        = true;

    protected $locale               = "ISO9075";

    protected $database             = null;
    protected $user                 = null;
    protected $password             = null;
    protected $host                 = null;

    protected $row	                = -1;
    protected $errno                = 0;
    protected $error                = "";

    protected $link_id              = null;
    protected $query_id             = null;
    protected $fields               = null;
    protected $fields_names	        = null;

    protected $num_rows             = null;
    protected $record               = null;
    protected $buffered_insert_id   = null;

    abstract public static function factory();
    abstract public static function free_all();

    abstract protected function id2object($value);
    abstract public function connect($Database = null, $Host = null, $User = null, $Password = null, $force = false);
    abstract public function insert($query, $table = null);
    abstract public function update($query, $table = null);
    abstract public function delete($query, $table = null);
    abstract public function execute($query);
    abstract public function query($query);
    abstract public function cmd($query, $name = "count");
    abstract public function multiQuery($queries);
    abstract public function lookup($tabella, $chiave = null, $valorechiave = null, $defaultvalue = null, $nomecampo = null, $tiporestituito = null, $bReturnPlain = false);
    abstract public function nextRecord($obj = null);
    abstract public function numRows($use_found_rows = false);
    abstract public function getRecordset();
    abstract public function getFieldset();
    abstract public function getInsertID($bReturnPlain = false);

    /**
     * @param string $DataValue
     * @return string
     */
    abstract protected function toSql_escape($DataValue);
    abstract protected function errorHandler($msg);

    /**
     *
     * @param String Nome del campo
     * @param String Tipo di dato inserito
     * @param bool $bReturnPlain
     * @param bool $return_error
     * @return mixed Dato recuperato dal DB
     */
    public function getField($Name, $data_type = "Text", $bReturnPlain = false, $return_error = true)
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
     * @param null|string $data_type
     * @param bool $enclose_field
     * @param null|bool $transform_null
     * @return string
     */
    public function toSql($cDataValue, $data_type = null, $enclose_field = true, $transform_null = null)
    {
        $value = null;

        if (!$this->link_id) {
            $this->connect();
        }
        if (is_array($cDataValue)) {
            $this->errorHandler("toSql: Wrong parameter, array not managed.");
        } elseif (!is_object($cDataValue)) {
            $value = $this->toSql_escape($cDataValue);
        } elseif (get_class($cDataValue) == "Data") {
            if ($data_type === null) {
                $data_type = $cDataValue->data_type;
            }
            $value = $this->toSql_escape($cDataValue->getValue($data_type, $this->locale));
        } elseif (get_class($cDataValue) == "DateTime") {
            switch ($data_type) {
                case "Date":
                    $tmp = new Data($cDataValue, "Date");
                    $value = $this->toSql_escape($tmp->getValue($data_type, $this->locale));
                    break;

                case "DateTime":
                default:
                    $data_type = "DateTime";
                    $tmp = new Data($cDataValue, "DateTime");
                    $value = $this->toSql_escape($tmp->getValue($data_type, $this->locale));
            }
        } else {
            $this->errorHandler("toSql: Wrong parameter, unmanaged datatype");
        }
        if ($transform_null === null) {
            $transform_null = $this->transform_null;
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
