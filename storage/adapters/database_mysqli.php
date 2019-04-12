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

use phpformsframework\libs\Error;

class databaseMysqli extends databaseAdapter {
    const PREFIX                                        = "FF_DATABASE_";
    const TYPE                                          = "sql";
    const KEY                                           = "ID";

    /**
     * @var MySqli
     */
    private $driver                                     = null;

    public  function toSql($cDataValue, $data_type = null, $enclose_field = true, $transform_null = null)
    {
        return $this->driver->toSql($cDataValue, $data_type, $enclose_field, $transform_null);
    }

    protected function processRawQuery($sSQL, $key = null) {
        $res                                            = null;
        $success                                        = $this->driver->query($sSQL);
        if($success) {
            switch ($key) {
                case "recordset":
                    $res                                = $this->driver->getRecordset();
                    break;
                case "fields":
                    $res                                = $this->driver->fields_names;
                    break;
                case "num_rows":
                    $res                                = $this->driver->numRows();
                    break;
                default:
                    $res                                = array(
                                                            "recordset"     => $this->driver->getRecordset()
                                                            , "fields"      => $this->driver->fields_names
                                                            , "num_rows"    => $this->driver->numRows()
                                                        );
            }
        } else {
            $res                                        = $success;
        }

        return $res;
    }

    protected function processRead($query) {
        $sSQL                                           = "SELECT "
                                                            . ($query["limit"]["calc_found_rows"]
                                                                ? " SQL_CALC_FOUND_ROWS "
                                                                : ""
                                                            ) . $query["select"] . "  
                                                            FROM " .  $query["from"] . "
                                                            WHERE " . $query["where"]
                                                            . ($query["sort"]
                                                                ? " ORDER BY " . $query["sort"]
                                                                : ""
                                                            )
                                                            . ($query["limit"]
                                                                ? " LIMIT " . (is_array($query["limit"])
                                                                    ? $query["limit"]["skip"] . ", " . $query["limit"]["limit"]
                                                                    : $query["limit"]
                                                                )
                                                                : ""
                                                            );
        $res                                            = $this->processRawQuery($sSQL);
        if($res) {
            if($query["limit"]["calc_found_rows"]) {
                $this->driver->query("SELECT FOUNT_ROWS() AS tot_row");
                if ($this->driver->nextRecord()) {
                    $res["count"]                       = $this->driver->getField("tot_row", "Number", true);
                }
            }
        } else {
            Error::register("Read - N°: " . $this->driver->errno . " Msg: " . $this->driver->error . " SQL: " . $sSQL, "database");
        }

        return $res;
    }

    protected function processInsert($query)
    {
        $res                                            = null;
        $sSQL                                           = "INSERT INTO " .  $query["from"] . "
                                                            (
                                                                " . $query["insert"]["head"] . "
                                                            ) VALUES (
                                                                " . $query["insert"]["body"] . "
                                                            )";
        if($this->driver->execute($sSQL)) {
            $res                                        = array(
                                                            "keys" => array($this->driver->getInsertID(true))
                                                        );
        } else {
            Error::register("Insert - N°: " . $this->driver->errno . " Msg: " . $this->driver->error . " SQL: " . $sSQL, "database");
        }

        return $res;
    }

    protected function processUpdate($query)
    {
        $res                                            = null;
        $sSQL                                           = "UPDATE " . $query["from"] . " SET 
                                                                " . $query["update"] . "
                                                            WHERE " . $query["where"];


        if($this->driver->execute($sSQL)) {
            $res = true;
        } else {
            Error::register("Update - N°: " . $this->driver->errno . " Msg: " . $this->driver->error . " SQL: " . $sSQL, "database");
        }

        return $res;
    }

    protected function processDelete($query)
    {
        $res                                            = null;

        $sSQL                                           = "DELETE FROM " .  $query["from"] . "  
                                                            WHERE " . $query["where"];
        if($this->driver->execute($sSQL)) {
            $res = true;
        } else {
            Error::register("Delete - N°: " . $this->driver->errno . " Msg: " . $this->driver->error . " SQL: " . $sSQL, "database");
        }

        return $res;
    }

    protected function processWrite($query)
    {
        $res                                            = null;
        $keys                                           = null;

        $sSQL                                           = "SELECT " . $query["key"] . " 
                                                            FROM " .  $query["from"] . "
                                                            WHERE " . $query["where"];
        if($this->driver->query($sSQL)) {
            $keys                                       = $this->extractKeys($this->driver->getRecordset(), $query["key"]);
        } else {
            Error::register("Read - N°: " . $this->driver->errno . " Msg: " . $this->driver->error . " SQL: " . $sSQL, "database");
        }

        if(!Error::check("database")) {
            if(is_array($keys)) {
                $sSQL                                   = "UPDATE " .  $query["from"] . " SET 
                                                                " . $query["update"] . "
                                                            WHERE " . $query["key"] . " IN(" . $this->driver->toSql(implode("," , $keys), "Text", false) . ")";
                if($this->driver->execute($sSQL)) {
                    $res                                = array(
                                                            "keys"      => $keys
                                                            , "action"  => "update"
                                                        );
                } else {
                    Error::register("Update - N°: " . $this->driver->errno . " Msg: " . $this->driver->error . " SQL: " . $sSQL, "database");
                }
            }
            elseif($query["insert"])
            {
                $sSQL                                   = "INSERT INTO " .  $query["from"] . "
                                                            (
                                                                " . $query["insert"]["head"] . "
                                                            ) VALUES (
                                                                " . $query["insert"]["body"] . "
                                                            )";
                if($this->driver->execute($sSQL)) {
                    $res                                    = array(
                                                                "keys"      => array($this->driver->getInsertID(true))
                                                                , "action"  => "insert"
                                                            );
                } else {
                    Error::register("Insert - N°: " . $this->driver->errno . " Msg: " . $this->driver->error . " SQL: " . $sSQL, "database");
                }
            }
        }

        return $res;
    }

    protected function processCmd($query)
    {
        $res                                            = null;

        $success                                        = $this->driver->cmd($query, $query["action"]);
        if($success !== null) {
            $res                                        = $success;
        } else {
            Error::register("Sql: unable to execute command" . print_r($query, true), "database");
        }

        return $res;
    }

    protected function loadDriver() {
        $connector                                                                  = $this->getConnector();
        if($connector) {
            $this->driver                                                               = new MySqli();
            if ($this->driver->connect(
                $connector["name"]
                , $connector["host"]
                , $connector["username"]
                , $connector["password"]
            )) {
                $this->key_name                                                         = $connector["key"];
            }
        }
        return (bool) $this->driver;
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
		$struct 																	= $this->struct;
		if(is_array($fields) && count($fields))
		{
            $fields                                                                 = $this->convertKey("_id", $fields);
		    if($action == "where" && is_array($fields['$or'])) {
                $or                                                                 = $this->convertFields($fields['$or'], "where_OR");
                if($or)                                                             { $res['$or'] = $or; }
            }

		    unset($fields['$or']);
			foreach($fields AS $name => $value)
			{
			    if($name == "*") {
			        if($action == "select") {
			            $res                                                        = null;
                        $result["select"]                                           = "*";
			            break;
                    } else {
			            continue;
                    }
                }

			    $name                                                               = str_replace("`", "", $name);
                if(!is_null($value) && !is_array($value)) {
                    $value                                                          = str_replace("`", "", $value);
                }
				if ($name == "key") {
					$name 															= $this->key_name;
				} elseif(0 && strpos($name, "key") === 1) { //todo: esplode se hai un campo tipo pkey chediventa pID. da verificare perche esiste questa condizione
					$name 															= substr($name, 0,1) . $this->key_name;
				} elseif ($action == "select" && strpos($value, ".") > 0) { //todo: da valutare con il ritorno degl array che nn funziona es: read("campo_pippo => ciao.te = ["ciao"]["te"])
					$name 															= substr($value, 0, strpos($value, "."));
					$value 															= true;
				}
				if($action == "select" && !is_array($value)) {
					$arrValue 														= explode(":", $value, 2);
					$value 															= ($arrValue[0] ? $arrValue[0] : true);
				}

				if($action == "sort") {
					$res[$name] 													= "`" . str_replace(".", "`.`", $name) ."` " . ($value === "-1" || $value === "DESC"
																						? "DESC"
																						: "ASC"
																					);
					continue;
				}

				$field 																= $this->normalizeField($name, $value);

                if($field == "special") {
                    if($action == "where" || $action == "where_OR") {
                        foreach($value AS $op => $subvalue) {
                            switch($op) {
                                case '$gt':
                                    $res[$name . '-' . $op] 				        = "`" . $name . "`" . " > " . $this->driver->toSql($subvalue, "Number");
                                    break;
                                case '$gte':
                                    $res[$name . '-' . $op] 				        = "`" . $name . "`" . " >= " . $this->driver->toSql($subvalue, "Number");
                                    break;
                                case '$lt':
                                    $res[$name . '-' . $op] 						= "`" . $name . "`" . " < " . $this->driver->toSql($subvalue, "Number");
                                    break;
                                case '$lte':
                                    $res[$name . '-' . $op] 						= "`" . $name . "`" . " <= " . $this->driver->toSql($subvalue, "Number");
                                    break;
                                case '$eq':
                                    $res[$name . '-' . $op] 						= "`" . $name . "`" . " = " . $this->driver->toSql($subvalue);
                                    break;
                                case '$regex':
                                    $res[$name . '-' . $op] 						= "`" . $name . "`" . " LIKE " . $this->driver->toSql(str_replace(array("(.*)", "(.+)", ".*", ".+", "*", "+"), "%", $subvalue));
                                    break;
                                case '$in':
                                    if(is_array($subvalue))
                                        $res[$name . '-' . $op] 					= "`" . $name . "`" . " IN('" . str_replace(", ", "', '", $this->driver->toSql(implode(", ", $subvalue), "Text", false)) . "')";
                                    else
                                        $res[$name . '-' . $op] 					= "`" . $name . "`" . " IN('" . str_replace(",", "', '", $this->driver->toSql($subvalue, "Text", false)) . "')";
                                    break;
                                case '$nin':
                                    if(is_array($subvalue))
                                        $res[$name . '-' . $op] 					= "`" . $name . "`" . " NOT IN('" . str_replace(", ", "', '", $this->driver->toSql(implode(", ", $subvalue), "Text", false)) . "')";
                                    else
                                        $res[$name . '-' . $op] 					= "`" . $name . "`" . " NOT IN('" . str_replace(",", "', '", $this->driver->toSql($subvalue, "Text", false)) . "')";
                                    break;
                                case '$ne':
                                    $res[$name . '-' . $op] 						= "`" . $name . "`" . " <> " . $this->driver->toSql($subvalue);
                                    break;
                                case '$inset':
                                    $res[$name . '-' . $op] 						= " FIND_IN_SET(" . $this->driver->toSql(str_replace(",", "','", $subvalue)) . ", `" . $name . "`)";
                                    break;
                                default:
                            }
                        }
                    }
                } else {
                    switch($action) {
                        case "select":
                            $res[$name]         									= $field["name"];
                            break;
                        case "insert":
                            $res["head"][$name]         							= $field["name"];
                            if(is_array($field["value"])) {
                                if($this->isAssocArray($field["value"]))														//array assoc to string
                                    $res["body"][$name] 							= "'" . str_replace("'", "\\'", json_encode($field["value"])) . "'";
                                else																				//array seq to string
                                    $res["body"][$name] 							= $this->driver->toSql(implode(",", array_unique($field["value"])));
                            } elseif(is_null($field["value"])) {
                                $res["body"][$name]         						= "NULL";
                            } else {
                                $res["body"][$name]         						= $this->driver->toSql($field["value"]);
                            }
                            break;
                        case "update":
                            if(is_array($field["value"])) {
                                switch($field["op"]) {
                                    case "++":
                                        //skip
                                        break;
                                    case "--":
                                        //skip
                                        break;
                                    case "+":
                                        if($this->isAssocArray($field["value"])) {                                                        //array assoc to string
                                            //skip
                                        } else {																				//array seq to string
                                            $res[$name] 							= "`" . $field["name"] . "` = " . "CONCAT(`"  . $field["name"] . "`, IF(`"  . $field["name"] . "` = '', '', ','), " . $this->driver->toSql(implode(",", array_unique($field["value"]))) . ")";
                                        }
                                        break;
                                    default:
                                        if($this->isAssocArray($field["value"]))														//array assoc to string
                                            $res[$name] 							= "`" . $field["name"] . "` = " . "'" . str_replace("'", "\\'", json_encode($field["value"])) . "'";
                                        else																				//array seq to string
                                            $res[$name] 							= "`" . $field["name"] . "` = " . $this->driver->toSql(implode(",", array_unique($field["value"])));
                                }
                            } else {
                                switch($field["op"]) {
                                    case "++":
                                        $res[$name] = $res[$name] . " + 1";
                                        break;
                                    case "--":
                                        $res[$name] = $res[$name] . " - 1";
                                        break;
                                    case "+":
                                        $res[$name] 								= "`" . $field["name"] . "` = " . "CONCAT(`"  . $field["name"] . "`, IF(`"  . $field["name"] . "` = '', '', ','), " . $this->driver->toSql($field["value"]) . ")";
                                        break;
                                    default:
                                        if(is_null($field["value"])) {
                                            $res[$name]         			        = "`" . $field["name"] . "` = NULL";
                                        } else {
                                            $res[$name]         			        = "`" . $field["name"] . "` = " . $this->driver->toSql($field["value"]);
                                        }
                                }
                            }
                            break;
                        case "where":
                        case "where_OR":
                            if(!is_array($value)) {
                                $value                                              = $field["value"];
                            }
                            if($field["name"] == $this->key_name) {
                                $value 												= $this->convertID($value);
                            }

                            if(is_array($struct[$field["name"]])) {
                                $struct_type 										= "array";
                            } else {
                                $arrStructType 										= explode(":", $struct[$field["name"]], 2);
                                $struct_type 										= $arrStructType[0];
                            }

                            switch ($struct_type) {
                                case "arrayIncremental":                                                                     //array
                                case "arrayOfNumber":                                                                        //array
                                case "array":                                                                                //array
                                    if (is_array($value) && count($value)) {
                                        foreach($value AS $i => $item) {
                                            $res[$name][] 							= ($field["not"] ? "NOT " : "") . "FIND_IN_SET(" . $this->driver->toSql($item) . ", `" . $field["name"] . "`)";
                                        }
                                        $res[$name] 								= "(" . implode(($field["not"] ? " AND " : " OR "), $res[$name]) . ")";
                                    }
                                    break;
                                case "boolean":
                                case "date":
                                case "number":
                                case "primary":
                                case "string":
                                case "char":
                                case "text":
                                default:
                                    if (is_array($value)) {
                                        if(count($value))
                                            $res[$name] 							= "`" . $field["name"] . "` " . ($field["not"] ? "NOT " : "") . "IN(" . $this->valueToFunc($value, $struct_type) . ")";
                                    } elseif(is_null($value)) {
                                        $res[$name] 							    = "`" . $field["name"] . "` " . ($field["not"] ? "not" : "") . " is null";
                                    } elseif(empty($value)) {
                                        $res[$name] 							    = "`" . $field["name"] . "` " . ($field["not"] ? "<>" : "=") . " ''";
                                    } else {
                                        switch($field["op"]) {
                                            case ">":
                                                $op 								= ($field["not"] ? '<' : '>');
                                                break;
                                            case ">=":
                                                $op 								= ($field["not"] ? '<=' : '>=');
                                                break;
                                            case "<":
                                                $op 								= ($field["not"] ? '>' : '<');
                                                break;
                                            case "<=":
                                                $op 								= ($field["not"] ? '>=' : '<=');
                                                break;
                                            default:
                                                $op                                 = ($field["not"] ? "<>" : "=");
                                        }
                                        $res[$name] 							    = "`" . $field["name"] . "` " . $op . " " . $this->valueToFunc($value, $struct_type);
                                    }
                            }
                            break;
                        default:
                    }
                }
			}

			if(is_array($res)) {
				switch ($action) {
					case "select":
                        $result["select"]                                           = "`" . implode("`, `", $res) . "`";
					    if($result["select"] != "*" && !$this->rawdata) {
                            $key_name                                               = $this->getFieldAlias($this->key_name);
                            if($key_name && !$res[$key_name])                       { $result["select"] .= ", `" . $key_name . "`"; }
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
		    switch($action) {
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
    private function convertID($keys) {
		if(is_array($keys))
			$res = array_filter($keys, "is_numeric");
		elseif(!is_numeric($keys))
			$res = null;
		else
            $res = $keys;

		return $res;
	}

    /**
     * @param $value
     * @param $func
     * @return string
     */
    private function valueToFunc($value, $func) {
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
                if(is_array($value)) {
                    $res                                                            = array();
                    foreach($value AS $i => $v) {
                        $res[$i]                                                    = $uFunc . "(" . $this->driver->toSql($value) . ")";
                    }
                    $res                                                            = implode(",", $res);
                } else {
                    $res                                                            = $uFunc . "(" . $this->driver->toSql($value) . ")";
                }

                break;
            case "REPLACE";
            case "CONCAT";
            //todo: da fare altri metodi se servono
                break;
            case "AES256":
                $res = openssl_encrypt ($value, "AES-256-CBC", time()/*$this->getCertificate()*/);
                break;
            default:
                if(is_array($value)) {
                    $res                                                            = "'" . implode("','", array_unique($value)) . "'";
                } else {
                    $res                                                            = $this->driver->toSql($value);
                }
        }

        return $res;
    }
}