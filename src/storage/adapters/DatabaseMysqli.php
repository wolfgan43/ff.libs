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
namespace phpformsframework\libs\storage\adapters;

use phpformsframework\libs\storage\DatabaseAdapter;
use phpformsframework\libs\storage\drivers\MySqli as sql;

class DatabaseMysqli extends DatabaseAdapter
{
    const PREFIX                                        = "MYSQL_DATABASE_";
    const TYPE                                          = "sql";
    const KEY                                           = "ID";

    protected function getDriver()
    {
        return new sql();
    }


    public function toSql($cDataValue, $data_type = null, $enclose_field = true, $transform_null = null)
    {
        return $this->driver->toSql($cDataValue, $data_type, $enclose_field, $transform_null);
    }

    protected function processRead($query)
    {
        $res                                            = $this->processRawQuery($query);
        if ($res && isset($query["limit"]) && is_array($query["limit"]) && isset($query["limit"]["calc_found_rows"])) {
            $this->driver->query("SELECT FOUNT_ROWS() AS tot_row");
            if ($this->driver->nextRecord()) {
                $res["count"]                           = $this->driver->getField("tot_row", "Number", true);
            }
        }

        return $res;
    }

    protected function processInsert($query)
    {
        $res                                            = null;
        if ($this->driver->insert($query)) {
            $res                                        = array(
                                                            "keys" => array($this->driver->getInsertID(true))
                                                        );
        }

        return $res;
    }

    protected function processUpdate($query)
    {
        $res                                            = null;
        if ($this->driver->update($query)) {
            $res = true;
        }

        return $res;
    }

    protected function processDelete($query)
    {
        $res                                            = null;
        if ($this->driver->delete($query)) {
            $res = true;
        }

        return $res;
    }

    protected function processWrite($query)
    {
        //todo: da valutare se usare REPLACE INTO. Necessario test benckmark
        $res                                            = null;
        $keys                                           = null;
        if ($this->driver->query(array(
            "select"			                        => $query["key"],
            "from" 			                            => $query["from"],
            "where" 			                        => $query["where"]
        ))) {
            $keys                                       = $this->extractKeys($this->driver->getRecordset(), $query["key"]);
        }

        if (is_array($keys)) {
            if ($this->driver->update(array(
                "from"                              => $query["from"],
                "update"                            => $query["update"],
                "where"                             => $query["key"] . " IN(" . $this->driver->toSql(implode(",", $keys), "Text", false) . ")"

            ))) {
                $res                                = array(
                                                        "keys"      => $keys,
                                                        "action"  => "update"
                                                    );
            }
        } elseif ($query["insert"]) {
            if ($this->driver->insert($query)) {
                $res                                    = array(
                                                            "keys"      => array($this->driver->getInsertID(true))
                                                            , "action"  => "insert"
                                                        );
            }
        }

        return $res;
    }

    protected function processCmd($query)
    {
        $res                                            = null;

        $success                                        = $this->driver->cmd($query, $query["action"]);
        if ($success !== null) {
            $res                                        = $success;
        }

        return $res;
    }


    private function parser_SpecialFields($name, $values, $action = "AND")
    {
        $res                                            = array();
        foreach ($values as $op => $value) {
            switch ($op) {
                case '$gt':
                    $res[$op] 				            = "`" . $name . "`" . " > " . $this->driver->toSql($value, "Number");
                    break;
                case '$gte':
                    $res[$op] 				            = "`" . $name . "`" . " >= " . $this->driver->toSql($value, "Number");
                    break;
                case '$lt':
                    $res[$op] 						    = "`" . $name . "`" . " < " . $this->driver->toSql($value, "Number");
                    break;
                case '$lte':
                    $res[$op] 						    = "`" . $name . "`" . " <= " . $this->driver->toSql($value, "Number");
                    break;
                case '$eq':
                    $res[$op] 						    = "`" . $name . "`" . " = " . $this->driver->toSql($value);
                    break;
                case '$regex':
                    $res[$op] 						    = "`" . $name . "`" . " LIKE " . $this->driver->toSql(str_replace(array("(.*)", "(.+)", ".*", ".+", "*", "+"), "%", $value));
                    break;
                case '$in':
                    if (is_array($value)) {
                        $res[$op]                       = "`" . $name . "`" . " IN('" . str_replace(", ", "', '", $this->driver->toSql(implode(", ", $value), "Text", false)) . "')";
                    } else {
                        $res[$op]                       = "`" . $name . "`" . " IN('" . str_replace(",", "', '", $this->driver->toSql($value, "Text", false)) . "')";
                    }
                    break;
                case '$nin':
                    if (is_array($value)) {
                        $res[$op] 					    = "`" . $name . "`" . " NOT IN('" . str_replace(", ", "', '", $this->driver->toSql(implode(", ", $value), "Text", false)) . "')";
                    } else {
                        $res[$op] 					    = "`" . $name . "`" . " NOT IN('" . str_replace(",", "', '", $this->driver->toSql($value, "Text", false)) . "')";
                    }
                    break;
                case '$ne':
                    $res[$op] 						    = "`" . $name . "`" . " <> " . $this->driver->toSql($value);
                    break;
                case '$inset':
                    $res[$op] 						    = " FIND_IN_SET(" . $this->driver->toSql(str_replace(",", "','", $value)) . ", `" . $name . "`)";
                    break;
                default:
            }
        }

        return "(" . implode(" " . $action . " ", $res) . ")";
    }

    private function parser_SelectField($field)
    {
        return $field["name"];
    }

    private function parser_InsertField($field)
    {
        $res                                            = null;
        if (is_array($field["value"])) {
            if ($this->isAssocArray($field["value"])) {
                $res                                    = "'" . str_replace("'", "\\'", json_encode($field["value"])) . "'";
            } else {
                $res                                    = $this->driver->toSql(implode(",", array_unique($field["value"])));
            }
        } elseif (is_null($field["value"])) {
            $res         						        = "NULL";
        } elseif (is_bool($field["value"])) {
            $res         						        = (int) $field["value"];
        } else {
            $res         						        = $this->driver->toSql($field["value"]);
        }

        return $res;
    }

    private function parser_UpdateField($field)
    {
        $res                                            = null;
        if (is_array($field["value"])) {
            switch ($field["op"]) {
                case "++":
                    break;
                case "--":
                    break;
                case "+":
                    if ($this->isAssocArray($field["value"])) {
                    } else {
                        $res 							= "`" . $field["name"] . "` = " . "CONCAT(`"  . $field["name"] . "`, IF(`"  . $field["name"] . "` = '', '', ','), " . $this->driver->toSql(implode(",", array_unique($field["value"]))) . ")";
                    }
                    break;
                default:
                    if ($this->isAssocArray($field["value"])) {
                        $res                            = "`" . $field["name"] . "` = " . "'" . str_replace("'", "\\'", json_encode($field["value"])) . "'";
                    } else {
                        $res                            = "`" . $field["name"] . "` = " . $this->driver->toSql(implode(",", array_unique($field["value"])));
                    }
            }
        } else {
            switch ($field["op"]) {
                case "++":
                    $res                                = "`" . $field["name"] . "` = `" . $field["name"] . "` + 1";
                    break;
                case "--":
                    $res                                = "`" . $field["name"] . "` = `" . $field["name"] . "` - 1";
                    break;
                case "+":
                    $res 						        = "`" . $field["name"] . "` = " . "CONCAT(`"  . $field["name"] . "`, IF(`"  . $field["name"] . "` = '', '', ','), " . $this->driver->toSql($field["value"]) . ")";
                    break;
                default:
                    if (is_null($field["value"])) {
                        $res         			        = "`" . $field["name"] . "` = NULL";
                    } elseif (is_bool($field["value"])) {
                        $res         			        = "`" . $field["name"] . "` = " . (int) $field["value"];
                    } else {
                        $res         			        = "`" . $field["name"] . "` = " . $this->driver->toSql($field["value"]);
                    }
            }
        }
        return $res;
    }
    private function parser_DeleteField($field)
    {
        //todo: to implement
        return $field;
    }

    private function parser_WhereField($field)
    {
        $res                                            = null;
        $value                                          = $field["value"];

        if ($field["name"] == $this->key_name) {
            $value 										= $this->convertID($value);
        }
        if (isset($this->struct[$field["name"]])) {
            if (is_array($this->struct[$field["name"]])) {
                $struct_type 							= self::FTYPE_ARRAY;
            } else {
                $arrStructType 							= explode(":", $this->struct[$field["name"]], 2);
                $struct_type 							= $arrStructType[0];
            }
        } else {
            $struct_type                                = null;
        }
        switch ($struct_type) {
            case self::FTYPE_ARRAY_INCREMENTAL:
            case self::FTYPE_ARRAY_OF_NUMBER:
            case self::FTYPE_ARRAY:
                if (is_array($value) && count($value)) {
                    foreach ($value as $item) {
                        $res[] 							= "FIND_IN_SET(" . $this->driver->toSql($item) . ", `" . $field["name"] . "`)";
                    }
                    $res 								= "(" . implode(" OR ", $res) . ")";
                }
                break;
            case self::FTYPE_BOOLEAN:
            case self::FTYPE_BOOL:
            case self::FTYPE_DATE:
            case self::FTYPE_NUMBER:
            case self::FTYPE_TIMESTAMP:
            case self::FTYPE_PRIMARY:
            case self::FTYPE_STRING:
            case self::FTYPE_CHAR:
            case self::FTYPE_TEXT:
            default:
                if (is_array($value)) {
                    if (count($value)) {
                        $res                            = "`" . $field["name"] . "` " . "IN(" . $this->valueToFunc($value, $struct_type) . ")";
                    }
                } elseif (is_null($value)) {
                    $res 							    = "`" . $field["name"] . "` " . " is null";
                } elseif (empty($value)) {
                    $res 							    = "`" . $field["name"] . "` = ''";
                } else {
                    $res 							    = "`" . $field["name"] . "` = " . $this->valueToFunc($value, $struct_type);
                }
        }

        return $res;
    }

    /**
     * @param $fields
     * @param bool $action
     * @return mixed
     */
    protected function convertFields($fields, $action)
    {
        $result                                                                     = null;
        $res 																		= array();
        if (is_array($fields) && count($fields)) {
            $fields                                                                 = $this->convertKey("_id", $fields);
            if ($action == "where" && isset($fields['$or']) && is_array($fields['$or'])) {
                $or                                                                 = $this->convertFields($fields['$or'], "where_OR");
                if ($or) {
                    $res['$or']                                                     = $or;
                }
            }
            unset($fields['$or']);

            foreach ($fields as $name => $value) {
                if ($name == "*") {
                    if ($action == "select") {
                        $res                                                        = null;
                        $result["select"]                                           = "*";
                        break;
                    } else {
                        continue;
                    }
                }

                $name                                                               = str_replace("`", "", $name);
                if (!is_null($value) && !is_array($value) && !is_object($value)) {
                    $value                                                          = str_replace("`", "", $value);
                }
                if ($name == "key") {
                    $name 															= $this->key_name;
                } elseif (0 && strpos($name, "key") === 1) { //todo: esplode se hai un campo tipo pkey chediventa pID. da verificare perche esiste questa condizione
                    $name 															= substr($name, 0, 1) . $this->key_name;
                } elseif ($action == "select" && strpos($value, ".") > 0) { //todo: da valutare con il ritorno degl array che nn funziona es: read("campo_pippo => ciao.te = ["ciao"]["te"])
                    $name 															= substr($value, 0, strpos($value, "."));
                    $value 															= true;
                }
                if ($action == "select" && !is_array($value)) {
                    $arrValue 														= explode(":", $value, 2);
                    $value 															= ($arrValue[0] ? $arrValue[0] : true);
                }

                if ($action == "sort") {
                    $res[$name] 													= "`" . str_replace(".", "`.`", $name) ."` " . (
                        $value === "-1" || $value === "DESC" || $value === "desc"
                                                                                        ? "DESC"
                                                                                        : "ASC"
                                                                                    );
                    continue;
                }

                if (isset($value['$or'])) {
                    $parser_action                                                  = "OR";
                    $field 													        = $this->normalizeField($name, $value['$or']);
                    $special                                                        = $value['$or'];
                } elseif (isset($value['$and'])) {
                    $parser_action                                                  = "AND";
                    $field 															= $this->normalizeField($name, $value['$and']);
                    $special                                                        = $value['$and'];
                } else {
                    $parser_action                                                  = "AND";
                    $field 															= $this->normalizeField($name, $value);
                    $special                                                        = $value;
                }


                if ($field == "special") {
                    $res[$name]                                                     = self::parser_SpecialFields($name, $special, $parser_action);
                } else {
                    switch ($action) {
                        case "select":
                            $res[$name]         							        = self::parser_SelectField($field);
                            break;
                        case "insert":
                            $res["head"][$name]         							= $field["name"];
                            $res["body"][$name]         							= self::parser_InsertField($field);
                            break;
                        case "update":
                            $res[$name]         							        = self::parser_UpdateField($field);
                            break;
                        case "delete":
                            $res[$name]         							        = self::parser_DeleteField($field);
                            break;
                        case "where":
                        case "where_OR":
                            if (is_array($value)) {
                                $field["value"]                                     = $value;
                            }
                            $res[$name]         							        = self::parser_WhereField($field);
                            break;
                    }
                }
            }

            if (is_array($res)) {
                switch ($action) {
                    case "select":
                        $result["select"]                                           = "`" . implode("`, `", $res) . "`";
                        if ($result["select"] != "*" && !$this->rawdata) {
                            $key_name                                               = $this->getFieldAlias($this->key_name);
                            if ($key_name && !isset($res[$key_name])) {
                                $result["select"] .= ", `" . $key_name . "`";
                            }
                        }
                        break;
                    case "insert":
                        $result["insert"]["head"] 									= "`" . implode("`, `", $res["head"]) . "`";
                        $result["insert"]["body"] 									= implode(", ", $res["body"]);
                        break;
                    case "update":
                        $result["update"] 											= implode(", ", $res);
                        break;
                    case "where":
                        $result["where"] 											= implode(" AND ", $res);
                        break;
                    case "where_OR":
                        $result 											        = implode(" OR ", $res);
                        break;
                    case "sort":
                        $result["sort"]												= implode(", ", $res);
                        break;
                    default:
                }
            }
        } else {
            switch ($action) {
                case "select":
                    $result["select"] = "*";
                    break;
                case "where":
                    $result["where"] = " 1 ";
                    break;
                case "where_OR":
                    $result = false;
                    break;
                default:
            }
        }

        return $result;
    }

    /**
     * @param $keys
     * @return array|int|null|string
     */
    private function convertID($keys)
    {
        if (is_array($keys)) {
            $res = array_filter($keys, "is_numeric");
        } elseif (!is_numeric($keys)) {
            $res = null;
        } else {
            $res = $keys;
        }
        return $res;
    }

    /**
     * @param $value
     * @param $func
     * @return string
     */
    private function valueToFunc($value, $func)
    {
        $res                                                                        = null;
        $uFunc                                                                      = strtoupper($func);
        switch ($uFunc) {
            case "ASCII":
            case "CHAR_LENGTH":
            case "CHARACTER_LENGTH":
            case "LCASE":
            case "LENGTH":
            case "LOWER":
            case "LTRIM":
            case "REVERSE":
            case "RTRIM":
            case "TRIM":
            case "UCASE":
            case "UPPER":
            case "ENCRYPT":
            case "MD5":
            case "OLD_PASSWORD":
            case "PASSWORD":
                if (is_array($value)) {
                    $res                                                            = array();
                    foreach ($value as $i => $v) {
                        $res[$i]                                                    = $uFunc . "(" . $this->driver->toSql($v) . ")";
                    }
                    $res                                                            = implode(",", $res);
                } else {
                    $res                                                            = $uFunc . "(" . $this->driver->toSql($value) . ")";
                }

                break;
            case "REPLACE":
            case "CONCAT":
            //todo: da fare altri metodi se servono
                break;
            case "AES256":
                $res = openssl_encrypt($value, "AES-256-CBC", time()/*$this->getCertificate()*/);
                break;
            default:
                if (is_array($value)) {
                    $res                                                            = "'" . implode("','", array_unique($value)) . "'";
                } else {
                    $res                                                            = $this->driver->toSql($value);
                }
        }

        return $res;
    }
}
