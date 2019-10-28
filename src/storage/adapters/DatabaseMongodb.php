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

namespace phpformsframework\libs\storage\adapters;

use phpformsframework\libs\storage\DatabaseAdapter;
use phpformsframework\libs\storage\drivers\MongoDB as nosql;

/**
 * Class DatabaseMongodb
 * @package phpformsframework\libs\storage\adapters
 */
class DatabaseMongodb extends DatabaseAdapter
{
    protected const PREFIX                              = "MONGO_DATABASE_";
    protected const TYPE                                = "nosql";
    protected const KEY_NAME                            = "_id";

    /**
     * @return nosql
     */
    protected function getDriver() : nosql
    {
        return new nosql();
    }

    /**
     * @param $mixed
     * @param string|null $type
     * @param bool $enclose
     * @return string|null
     */
    public function toSql($mixed, string $type = null, bool $enclose = true) : ?string
    {
        return $this->driver->toSql($mixed, $type, $enclose);
    }

    /**
     * @return array
     */
    protected function getConnector() : array
    {
        $connector                                      = parent::getConnector();
        $connector["key"]                               = self::KEY_NAME;

        return $connector;
    }

    /**
     * @param array $query
     * @return array|null
     */
    protected function processRead(array $query) : ?array
    {
        if (!isset($query['sort'])) {
            $query['sort']                              = null;
        }
        if (!isset($query['limit'])) {
            $query['limit']                             = null;
        }

        $res                                            = $this->processRawQuery(array(
                                                            "select" 	    => $query["select"],
                                                            "from" 	        => $query["from"],
                                                            "where" 	    => $query["where"],
                                                            "sort" 	        => $query["sort"],
                                                            "limit"	        => $query["limit"],
                                                            "key_primary"   => $this->key_primary
                                                        ));
        if ($res && $query["limit"]["calc_found_rows"]) {
            $res["count"]                               = $this->driver->cmd(array(
                                                            "from" 	        => $query["from"],
                                                            "where" 	    => $query["where"]
                                                        ), "count");
        }

        return $res;
    }

    /**
     * @param array $query
     * @return array|null
     */
    protected function processInsert(array $query) : ?array
    {
        $res                                            = null;
        if ($this->driver->insert($query["insert"], $query["from"])) {
            $res                                        = array(
                                                            "keys" => array($this->driver->getInsertID())
                                                        );
        }

        return $res;
    }

    /**
     * @param array $query
     * @return array|null
     */
    protected function processUpdate(array $query) : ?array
    {
        $res                                            = null;

        if ($this->driver->update(array(
            "set" 				        => $query["update"],
            "where" 			        => $query["where"]
        ), $query["from"])) {
            $res                                        = array();
        }

        return $res;
    }

    /**
     * @param array $query
     * @return array|null
     */
    protected function processDelete(array $query) : ?array
    {
        $res                                            = null;
        if ($this->driver->delete($query["where"], $query["from"])) {
            $res                                        = array();
        }

        return $res;
    }

    /**
     * @param array $query
     * @return array|null
     */
    protected function processWrite(array $query) : ?array
    {
        $res                                            = null;
        $keys                                           = null;

        if ($this->driver->query(array(
            "select"			                        => array($query["key"] => 1),
            "from" 			                            => $query["from"],
            "where" 			                        => $query["where"]
        ))) {
            $keys                                       = $this->extractKeys($this->driver->getRecordset(), $query["key"]);
        }

        if (is_array($keys)) {
            $update 				                    = $this->convertFields(array($query["key"] => $keys), "where");
            $update["set"] 			                    = $query["update"];
            $update["from"] 		                    = $query["from"];

            if ($this->driver->update($update, $update["from"])) {
                $res                                    = array(
                                                            "keys"          => $keys,
                                                            "action"        => "update"
                                                        );
            }
        } elseif ($query["insert"]) {
            if ($this->driver->insert($query["insert"], $query["from"])) {
                $res                                    = array(
                                                            "keys"          => array($this->driver->getInsertID()),
                                                            "action"        => "insert"
                                                        );
            }
        }

        return $res;
    }

    /**
     * @param array $query
     * @return mixed
     */
    protected function processCmd(array $query)
    {
        $res                                            = null;

        $success                                        = $this->driver->cmd($query, $query["action"]);
        if ($success) {
            $res                                        = $success;
        }

        return $res;
    }


    private function parserWhereField(array $field)
    {
        $res 						                    = $field["value"];

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
                if (is_array($field["value"]) && count($field["value"])) {
                    if (!$this->isAssocArray($field["value"])) {
                        $res 		                    = array('$in' => $field["value"]);
                    }
                } elseif (is_array($field["value"]) && !count($field["value"])) {
                    $res                                = array();
                } else {
                    $res                                = null;
                }
                break;
            case self::FTYPE_BOOLEAN:
            case self::FTYPE_BOOL:
                break;
            case self::FTYPE_NUMBER:
            case self::FTYPE_TIMESTAMP:
                if (is_array($field["value"]) && count($field["value"])) {
                    $res 			                    = array(
                                                            '$in' => (
                                                                ($field["name"] == $this->key_name)
                                                                ? $field["value"]
                                                                : array_map('intval', $field["value"])
                                                            )
                                                        );
                }
                break;
            case self::FTYPE_DATE:
            case self::FTYPE_STRING:
            case self::FTYPE_CHAR:
            case self::FTYPE_TEXT:
            case self::FTYPE_PRIMARY:
            default:
                if (is_array($field["value"]) && count($field["value"])) {
                    $res 			                    = array(
                                                            '$in' => (
                                                                ($field["name"] == $this->key_name)
                                                                ? $field["value"]
                                                                : array_map('strval', $field["value"])
                                                            )
                                                        );
                }
        }

        return $res;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function convertKeyName(string $name): string
    {
        return ($this->struct[$name] == self::FTYPE_PRIMARY && $name != $this->key_name
            ? $this->key_name
            : parent::convertKeyName($name)
        );

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
        if (is_array($fields) && count($fields)) {
            if ($flag == "where" && isset($fields['$or']) && is_array($fields['$or'])) {
                $or                                                                 = $this->convertFields($fields['$or'], "where_OR");
                if ($or) {
                    $res['$or'] = $or;
                }
            }

            unset($fields['$or']);
            foreach ($fields as $name => $value) {
                if ($name == "*") {
                    if ($flag == "select") {
                        $res                                                        = null;
                        $result["select"]                                           = null;
                        break;
                    } else {
                        continue;
                    }
                }

                if ($flag == "select" && strpos($value, ".") > 0) {
                    $name 															= substr($value, 0, strpos($value, "."));
                    $value 															= true;
                }
                if ($flag == "select" && !is_array($value)) {
                    $arrValue 														= explode(":", $value, 2);
                    $value 															= ($arrValue[0] ? $arrValue[0] : true);
                }

                if ($flag == "sort") {
                    $res["sort"][$name] 												= (
                        $value == "DESC"
                                                                                        ? -1
                                                                                        : 1
                                                                                    );
                    continue;
                }
                $parser_action_or                                                   = false;
                if (isset($value['$or'])) {
                    $parser_action_or                                               = true;
                    $field 													        = $this->normalizeField($name, $value['$or']);
                } elseif (isset($value['$and'])) {
                    $field 															= $this->normalizeField($name, $value['$and']);
                } else {
                    $field 															= $this->normalizeField($name, $value);
                }

                if ($field == "special") {
                    $res[$flag][$name]                                              = $value;
                } else {
                    switch ($flag) {
                        case "select":
                            $res["select"][$field["name"]] 						    = !empty($field["value"]);
                            break;
                        case "insert":
                            $res["insert"][$field["name"]] 							= $field["value"];
                            break;
                        case "update":
                            if ($field["type"] == self::FTYPE_ARRAY_INCREMENTAL
                                || $field["type"] == self::FTYPE_ARRAY_OF_NUMBER
                                || $field["type"] == self::FTYPE_ARRAY
                            ) {
                                switch ($field["op"]) {
                                    case "++":
                                        break;
                                    case "--":
                                        break;
                                    case "+":
                                        $res["update"]['$addToSet'][$field["name"]] = $field["value"];
                                        break;
                                    default:
                                        $res["update"]['$set'][$field["name"]]      = $field["value"];
                                }
                            } elseif ($field["type"] == self::FTYPE_NUMBER) {
                                switch ($field["op"]) {
                                    case "++":
                                        $res["update"]['$inc'][$field["name"]]      = 1;
                                        break;
                                    case "--":
                                        $res["update"]['$inc'][$field["name"]]      = -1;
                                        break;
                                    case "+":
                                        break;
                                    default:
                                        $res["update"]['$set'][$field["name"]]      = $field["value"];
                                }
                            } elseif ($field["type"] == self::FTYPE_STRING
                                || $field["type"] == self::FTYPE_CHAR
                                || $field["type"] == self::FTYPE_TEXT
                                || $field["type"] == self::FTYPE_PRIMARY
                            ) {
                                switch ($field["op"]) {
                                    case "++":
                                        break;
                                    case "--":
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
                            if (is_array($value)) {
                                $field["value"]                                     = $value;
                            }

                            if ($parser_action_or) {
                                $res['$or'][][$field["name"]]                       = $this->parserWhereField($field);
                            } else {
                                $res["where"][$field["name"]]                       = $this->parserWhereField($field);

                            }
                            break;
                        case "where_OR":
                            if (is_array($value)) {
                                $field["value"]                                     = $value;
                            }
                            $res[][$field["name"]]                                  = $this->parserWhereField($field);
                            break;
                        default:
                    }
                }
            }

            if (is_array($res)) {
                $result                                                             = $res;
                switch ($flag) {
                    case "select":
                        if ($result["select"] != "*" && !$this->rawdata) {
                            $key_name                                               = $this->getFieldAlias($this->key_name);
                            if ($key_name && !$result["select"][$key_name]) {
                                $result["select"][$key_name] = true;
                            }
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
            switch ($flag) {
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
