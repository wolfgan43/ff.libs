<?php
/**
*   VGallery: CMS based on FormsFramework
    Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

 * @package VGallery
 * @subpackage core
 * @author Alessandro Stucchi <wolfgan@gmail.com>
 * @copyright Copyright (c) 2004, Alessandro Stucchi
 * @license http://opensource.org/licenses/gpl-3.0.html
 * @link https://github.com/wolfgan43/vgallery
 */

namespace phpformsframework\libs\storage\database;

use phpformsframework\libs\Error;
use phpformsframework\libs\storage\DatabaseAdapter;
use phpformsframework\libs\storage\drivers\MongoDB AS nosql;


class Mongodb extends DatabaseAdapter {
    const PREFIX                                        = "MONGO_DATABASE_";
    const TYPE                                          = "nosql";
    const KEY                                           = "_id";

    protected function getDriver()
    {
        return new nosql();
    }

    public  function toSql($cDataValue, $data_type = null, $enclose_field = true, $transform_null = null)
    {
        return $this->driver->toSql($cDataValue, $data_type, $enclose_field, $transform_null);
    }



    protected function processRead($query) {
        $res                                            = $this->processRawQuery(array(
                                                            "select" 	    => $query["select"]
                                                            , "from" 	    => $query["from"]
                                                            , "where" 	    => $query["where"]
                                                            , "sort" 	    => $query["sort"]
                                                            , "limit"	    => $query["limit"]
                                                        ));
        if($res) {
            if($query["limit"]["calc_found_rows"]) {
                $res["count"]                           = $this->driver->cmd(array(
                                                            "from" 	        => $query["from"]
                                                            , "where" 	    => $query["where"]
                                                        ), "count");
            }
        } elseif($res !== false) {
            Error::register("MongoDB: Unable to Read: " . print_r($query, true), "database");
        }

        return $res;
    }

    protected function processInsert($query)
    {
        $res                                            = null;
        if($this->driver->insert($query["insert"], $query["from"])) {
            $res                                        = array(
                                                            "keys" => array($this->driver->getInsertID(true))
                                                        );
        } else {
            Error::register("MongoDB: Unable to Insert: " . print_r($query, true), "database");
        }

        return $res;
    }

    protected function processUpdate($query)
    {
        $res                                            = null;

        if($this->driver->update(array(
            "set" 				        => $query["update"]
            , "where" 			        => $query["where"]
        ), $query["from"])) {
            $res                                        = true;
        } else {
            Error::register("MongoDB: Unable to Update: " . print_r($query, true), "database");
        }

        return $res;
    }

    protected function processDelete($query)
    {
        $res                                            = null;
        if($this->driver->delete($query["where"], $query["from"])) {
            $res                                        = true;
        } else {
            Error::register("MongoDB: Unable to Delete: " . print_r($query, true), "database");
        }

        return $res;
    }

    protected function processWrite($query)
    {
        $res                                            = null;
        $keys                                           = null;

        if($this->driver->query(array(
            "select"			        => array($query["key"] => 1)
            , "from" 			        => $query["from"]
            , "where" 			        => $query["where"]
        ))) {
            $keys                                       = $this->extractKeys($this->driver->getRecordset(), $query["key"]);
        } else {
            Error::register("MongoDB: Unable to Read(Write): " . print_r($query, true), "database");
        }

        if(!Error::check("database")) {
            if(is_array($keys)) {
                $update 				    = $this->convertFields(array($query["key"] => $keys), "where");
                $update["set"] 			    = $query["update"];
                $update["from"] 		    = $query["from"];

                if($this->driver->update($update, $update["from"])) {
                    $res                                = array(
                                                            "keys"      => $keys
                                                            , "action"  => "update"
                                                        );
                } else {
                    Error::register("MongoDB: Unable to Update(Write): " . print_r($query, true), "database");
                }
            }
            elseif($query["insert"])
            {
                if($this->driver->insert($query["insert"], $query["from"])) {
                    $res                                = array(
                                                            "keys"      => array($this->driver->getInsertID(true))
                                                            , "action"  => "insert"
                                                        );
                } else {
                    Error::register("MongoDB: Unable to Insert(Write): " . print_r($query, true), "database");
                }
            }
        }

        return $res;
    }

    protected function processCmd($query)
    {
        $res                                            = null;

        $success                                        = $this->driver->cmd($query, $query["action"]);
        if($success) {
            $res                                        = $success;
        } else {
            Error::register("MongoDB: Unable to Execute Command" . print_r($query, true), "database");
        }

        return $res;
    }


    /**
     * @param $fields
     * @param bool $flag
     * @return array
     */
    public function convertFields($fields, $flag = false)
	{
		$result                                                                     = null;
		$res 																		= array();
		$struct 																	= $this->struct;
		if(is_array($fields) && count($fields))
		{
            $fields                                                                 = $this->convertKey("ID", $fields);

            if($flag == "where" && is_array($fields['$or'])) {
                $or                                                                 = $this->convertFields($fields['$or'], "where_OR");
                if($or)                                                             { $res['$or'] = $or; }
            }

            unset($fields['$or']);
			foreach ($fields AS $name => $value)
			{
                if($name == "*") {
                    if($flag == "select") {
                        $res                                                        = null;
                        $result["select"]                                           = null;
                        break;
                    } else {
                        continue;
                    }
                }

				if ($name == "key") {
					$name 															= $this->key_name;
				} elseif(0 && strpos($name, "key") === 1) {
					$name 															= substr($name, 0,1) . $this->key_name;
				} elseif ($flag == "select" && strpos($value, ".") > 0) {
					$name 															= substr($value, 0, strpos($value, "."));
					$value 															= true;
				}
				if($flag == "select" && !is_array($value)) {
					$arrValue 														= explode(":", $value, 2);
					$value 															= ($arrValue[0] ? $arrValue[0] : true);
				}

				if($flag == "sort") {
					$res["sort"][$name] 												= ($value == "DESC"
																						? -1
																						: 1
																					);
					continue;
				}



				$field 																= $this->normalizeField($name, $value);

				if($field == "special") {
					$res[$flag][$name]                                              = $value;
				} else {
                    switch($flag) {
                        case "select":
                            $res["select"][$field["name"]] 						    = ($field["value"]
                                                                                        ? true
                                                                                        : false
                                                                                    );
                            break;
                        case "insert":
                            $res["insert"][$field["name"]] 							= $field["value"];
                            break;
                        case "update":
                            if($field["type"] == "arrayIncremental"
                                || $field["type"] == "arrayOfNumber"
                                || $field["type"] == "array"
                            ) {
                                switch($field["op"]) {
                                    case "++":
                                        //skip
                                        break;
                                    case "--":
                                        //skip
                                        break;
                                    case "+":
                                        $res["update"]['$addToSet'][$field["name"]] = $field["value"];
                                        break;
                                    default:
                                        $res["update"]['$set'][$field["name"]]      = $field["value"];
                                }
                            } elseif($field["type"] == "number"
                                || $field["type"] == "primary"
                            ) {
                                switch($field["op"]) {
                                    case "++":
                                        $res["update"]['$inc'][$field["name"]]      = 1;
                                        break;
                                    case "--":
                                        $res["update"]['$inc'][$field["name"]]      = -1;
                                        break;
                                    case "+":
                                        //skip
                                        //$res["update"]['$concat'][$field["name"]]   = array('$' . $field["name"], ",", $field["value"]);
                                        break;
                                    default:
                                        $res["update"]['$set'][$field["name"]]      = $field["value"];
                                }
                            } elseif($field["type"] == "string"
                                || $field["type"] == "char"
                                || $field["type"] == "text"
                            ) {
                                switch($field["op"]) {
                                    case "++":
                                        //skip
                                        break;
                                    case "--":
                                        //skip
                                        break;
                                    case "+":
                                        $res["update"]['$concat'][$field["name"]]   = array('$' . $field["name"], ",", $field["value"]);
                                        break;
                                    default:
                                        $res["update"]['$set'][$field["name"]]      = $field["value"];
                                }
                            } else {
                                $res["update"]['$set'][$field["name"]] 				= $field["value"];
                            }

                            break;
                        case "where":
                        case "where_OR":
                            if(!is_array($value))
                                $value 										        = $field["value"];

                            if($field["name"] == $this->key_name)
                                $value 											    = $this->driver->id2object($value);

                            $res["where"][$field["name"]] 						    = $value;

                            if(isset($struct[$field["name"]])) {
                                if(is_array($struct[$field["name"]])) {
                                    $struct_type 									= "array";
                                } else {
                                    $arrStructType 									= explode(":", $struct[$field["name"]], 2);
                                    $struct_type 									= $arrStructType[0];
                                }
                            } else {
                                $struct_type                                        = null;
                            }
                            switch ($struct_type) {
                                case "arrayIncremental":                                                                     //array
                                case "arrayOfNumber":                                                                        //array
                                case "array":                                                                                //array
                                    //search
                                    if (is_array($value) && count($value)) {
                                        if(!$this->isAssocArray($value)) {
                                            $res["where"][$field["name"]] 		    = array(
                                                                                        ($field["not"] ? '$nin' : '$in') => $value
                                                                                    );
                                        }
                                    } elseif(is_array($value) && !count($value)) {
                                        $res["where"][$field["name"]]               = array();
                                    } else {
                                        unset($res["where"][$field["name"]]);
                                    }
                                    break;
                                case "boolean":
                                    if ($field["not"] && $value !== null) {                                                //not
                                        $res["where"][$field["name"]] 			    = array(
                                                                                        '$ne' => $value
                                                                                    );
                                    }
                                    break;
                                case "date":
                                case "number":
                                case "timestamp":
                                case "primary":
                                    if($value !== null) {
                                        if ($field["op"]) {                                                //< > <= >=
                                            switch($field["op"]) {
                                                case ">":
                                                    $op 						    = ($field["not"] ? '$lt' : '$gt');
                                                    break;
                                                case ">=":
                                                    $op 						    = ($field["not"] ? '$lte' : '$gte');
                                                    break;
                                                case "<":
                                                    $op 						    = ($field["not"] ? '$gt' : '$lt');
                                                    break;
                                                case "<=":
                                                    $op 						    = ($field["not"] ? '$gte' : '$lte');
                                                    break;
                                                default:
                                                    $op                             = "";
                                            }
                                            if($op) {
                                                $res["where"][$field["name"]] 	    = array(
                                                                                        "$op" => $value
                                                                                    );
                                            }
                                        } elseif ($field["not"]) {                                                //not
                                            $res["where"][$field["name"]] 		    = array(
                                                                                        '$ne' => $value
                                                                                    );
                                        }
                                    }
                                    if (is_array($value) && count($value)) {
                                        $res["where"][$field["name"]] 			    = array(
                                                                                        ($field["not"] ? '$nin' : '$in') => (($field["name"] == $this->key_name)
                                                                                            ? $value
                                                                                            : array_map('intval', $value)
                                                                                        )
                                                                                    );
                                    }
                                    break;
                                case "string":
                                case "char":
                                case "text":
                                default:
                                    if (is_array($value) && count($value)) {
                                        $res["where"][$field["name"]] 			    = array(
                                                                                        ($field["not"] ? '$nin' : '$in') => (($field["name"] == $this->key_name)
                                                                                            ? $value
                                                                                            : array_map('strval', $value)
                                                                                        )
                                                                                    );
                                    } elseif ($field["not"]) {                                                        //not
                                        if($value) {
                                            $res["where"][$field["name"]] 		    = array(
                                                                                        '$ne' => $value
                                                                                    );
                                        } else {
                                            unset($res["where"][$field["name"]]);
                                        }
                                    }
                                //string
                            }
                            break;
                        default:
                    }
                }
			}

            if(is_array($res)) {
			    $result                                                             = $res;
                switch ($flag) {
                    case "select":
                        if($result["select"] != "*" && !$this->rawdata) {
                            $key_name                                               = $this->getFieldAlias($this->key_name);
                            if ($key_name && !$result["select"][$key_name])         { $result["select"][$key_name] = true; }
                        }
                        break;
                    case "insert":
                        break;
                    case "update":
                        break;
                    case "where":
                    case "where_OR":
                        break;
                    case "sort":
                        break;
                    default:
                }
            }
        } else {
            switch($flag) {
                case "select":
                    $result["select"] = null;
                    break;
                case "where":
                    $result["where"] = true;
                    break;
                case "where_OR":
                    $result = false;
                    break;
                default:
            }
        }

		return $result;
	}


}