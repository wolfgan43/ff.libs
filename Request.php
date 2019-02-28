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

//namespace phpformsframework\libs;

class Request {
    const TYPE                                                                                  = "request";

    private static $request                                                                     = null;
    private static $cache                                                                       = null;
    private static $requestRules                                                                = null;

    public static function get($key = null, $toArray = false) {
        if(!$key) { $toArray = true; }

        $res                                                                                    = self::getRequest($key
                                                                                                    ? $key
                                                                                                    : "rawdata"
                                                                                                );
        return ($toArray
            ? $res
            : (object) $res
        );
    }
    public static function post($key = null) {
        return (object) self::getRequest($key ? $key : "rawdata", "post");
    }
    public static function cookie($key = null) {
        return (object) self::getRequest($key ? $key : "rawdata" , "cookie");
    }
    public static function session($key = null) {
        return (object) self::getRequest($key ? $key : "rawdata" , "session");
    }

    /**
     * @param
     *
     * $requestRules = array(
                        "map"               => ""
                        , "last_update"     => ""
                        , "method"          => ""
                        , "exts"            => ""
                        , "navigation"      => ""
                        , "select"          => ""
                        , "default"         => ""
                        , "order"           => ""
                        , "fields"          => ""
                        , "fields_fixed"    => ""
                     );
     */
    public static function set($rules) {
        self::$requestRules                                                                     = array_replace((array) self::$requestRules, $rules);

        self::$requestRules["last_update"]                                                      = microtime(true);
    }

    public static function isAjax() {
        return ($_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest"
            ? true
            : false
        );
    }

    private static function getReq($method = null) {
        $req                                                                                    = array();
        switch(strtolower($method)) {
            case "post":
            case "patch":
            case "delete":
                $req                                                                            = $_POST;
                break;
            case "get":
                $req                                                                            = $_GET;
                break;
            case "cookie":
                $req                                                                            = $_COOKIE;
                break;
            case "session":
                $req                                                                            = $_SESSION;
                break;
            default:
                $req                                                                            = $_REQUEST;

        }

        return $req;
    }
    private static function getRequest($key = null, $method = null) {
        static $last_update                                                                     = 0;

        $count_max                                                                              = 1000;
        $count_default                                                                          = 200;
        if(!$method)                                                                            { $method = self::$requestRules["method"]; }

        if(!self::$request) {
            self::$request                                                                      = array(
                "rawdata"   => array()
                , "unknown" => array()
            );

            $request                                                                            = self::getReq($method);
            if(is_array($request) && count($request)) {
                self::$request["rawdata"]                                                       = $request;
                foreach($request AS $req_key => $req_value) {
                    $rkf                                                                        = str_replace("?", "", $req_key);
                    switch($rkf) {
                        case "_ffq_":
                        case "__nocache__":
                        case "__debug__":
                        case "__query__":
                            unset(self::$request["rawdata"][$req_key]);
                            break;
                        case "gclid": //params di google adwords e adsense
                        case "utm_source":
                        case "utm_medium":
                        case "utm_campaign":
                        case "utm_term":
                        case "utm_content":
                            self::$request["gparams"][$rkf]                                     = $req_value;
                            unset(self::$request["rawdata"][$req_key]);
                            break;
                        case "t":
                            self::$request["auth"][$rkf]                                        = $req_value;
                            unset(self::$request["rawdata"][$req_key]);
                            break;
                        case "q":
                            self::$request["search"]                                            = $req_value;
                            unset(self::$request["rawdata"][$req_key]);
                            break;
                        case "page":
                            if(is_numeric($req_value) && $req_value > 0) {
                                self::$request["navigation"]["page"]                            = $req_value;
                                //if($req_value > 1)
                                //    self::$request["query"]["page"]                           = "page=" . urlencode(self::$request["navigation"]["page"]);
                            }
                            break;
                        case "count":
                            if(is_numeric($req_value) && $req_value > 0) {
                                self::$request["navigation"]["count"]                           = $req_value;

                                //self::$request["query"]["count"]                              = "count=" . urlencode(self::$request["navigation"]["rec_per_page"]);
                            }
                            break;
                        case "sort":
                            self::$request["sort"]                                              = $req_value;

                            //self::$request["query"]["sort"]                                   = "sort=" . urlencode(self::$request["sort"]["name"]);
                            break;
                        case "dir":
                            self::$request["dir"]                                               = $req_value;

                            //self::$request["query"]["dir"]                                    = "dir=" . urlencode(self::$request["sort"]["dir"]);
                            break;
                        case "ret_url":
                            self::$request["redirect"][$rkf]                                    = $req_value;
                            unset(self::$request["rawdata"][$req_key]);
                            break;
                        case "lang":
                        case "error":
                            break;
                        default:
                            if($req_key != $rkf) {
                                self::$request["invalid"][$req_key]                             = $req_key . "=" . urlencode($req_value);
                                unset(self::$request["rawdata"][$req_key]);
                            } elseif(isset(self::$requestRules["map"][$rkf])) {
                                if(self::$requestRules["map"][$rkf] !== true) {
                                    self::$request[self::$requestRules["map"][$rkf]][$rkf]      = $req_value;
                                }
                                //unset(self::$requestRules["map"]);
                                //$res["get"]["search"]["available_terms"][$rkf] = $req_value;
                                //$res["get"]["query"][$rkf] = $rkf . "=" . urlencode($res["get"]["search"]["available_terms"][$rkf]);
                            } elseif(self::$requestRules["exts"][$rkf]) {
                                eval('self::$request' . self::$requestRules["exts"][$rkf] . ' = ' . $req_value . ";");
                            } elseif(is_numeric($rkf) && !$req_value) {
                                self::$request["invalid"][$rkf]                                 = $rkf . "=" . urlencode($req_value);
                                unset(self::$request["rawdata"][$req_key]);
                            } elseif(!preg_match('/[^a-z\-0-9_\+]/i', $rkf)) {
                                if(is_array($req_value)) {
                                    self::$request["unknown"]                                   = array_replace((array) self::$request["unknown"], $req_value);
                                }   else {
                                    self::$request["unknown"][$rkf]                             = $req_value;
                                }
                                /*if(is_array($req_value)) {
                                    self::$request["search"]["terms"]                           = array_replace((array) self::$request["search"]["terms"], $req_value);
                                } else {
                                    self::$request["search"]["terms"][$rkf]                     = $req_value;
                                }*/
                                // self::$request["invalid"][$rkf]                               = $rkf . "=" . urlencode($req_value);
                            } else {
                                self::$request["invalid"][$rkf]                                 = $rkf . "=" . urlencode($req_value);
                            }
                    }
                }
            }

            if (self::$request["navigation"]
                && self::$request["navigation"]["count"] > $count_max)                          { self::$request["navigation"]["count"] = $count_max; }

            self::$request["dir"]                                                               = (self::$request["dir"] === "-1" || self::$request["dir"] === "DESC"
                ? "-1"
                : "1"
            );
        }

        if($last_update < self::$requestRules["last_update"]) {
            $res                                                                                = self::$request;

            /*$class_name                                                                         = get_called_class();
            if(defined($class_name . '::REQUEST')) {
                $valid                                                                          = array_intersect_key($res["unknown"], array_fill_keys($class_name::REQUEST, true));
                $res[$class_name::TYPE]                                                         = array_replace((array) $res[$class_name::TYPE], $valid);
                $res["unknown"]                                                                 = array_diff_key($res["unknown"], $valid);
            }*/

            if(!$res["navigation"]["count"]) {
                $res["navigation"]["count"]                                                     = (self::$requestRules["navigation"]["count"]
                    ? self::$requestRules["navigation"]["count"]
                    : $count_default
                );
            }
            if(!$res["navigation"]["page"]) {
                $res["navigation"]["page"]                                                      = (self::$requestRules["navigation"]["page"]
                    ? self::$requestRules["navigation"]["page"]
                    : "1"
                );
            }

            //Mapping Request by Rules
            if(is_array(self::$requestRules["map"]) && count(self::$requestRules["map"])) {
                foreach(self::$requestRules["map"] AS $rule_key => $rule_value) {
                    if(!is_array($rule_value))                                                  $rule_value = array($rule_value);

                    foreach($rule_value AS $rule_type) {
                        $rKey                                                                   = (is_numeric($rule_key)
                            ? $rule_type
                            : $rule_key
                        );

                        if($res["unknown"][$rKey]) {
                            $res[$rule_type][$rKey]                                             = $res["unknown"][$rKey];
                            unset( $res["unknown"][$rKey]);
                        }
                    }
                }
            }

            if($key == "query") {
                //Creation query
                $res["query"]["select"]                                                         = (array)self::$requestRules["select"];

                if(!count($res["unknown"]))                                                     { $res["unknown"] = array_combine((array) self::$requestRules["default"], (array) self::$requestRules["default"]); }
                foreach($res["unknown"] AS $unknown_key => $unknown_value) {
                    if(self::$requestRules["fields"][$unknown_key])                             { $res["query"]["select"][self::$requestRules["fields"][$unknown_key]] = $unknown_value; }
                }

                if(is_array(self::$requestRules["fields_fixed"]) && count(self::$requestRules["fields_fixed"])) {
                    foreach(self::$requestRules["fields_fixed"] AS &$field_value) {
                        $field_value                                                            = str_replace(array_keys(self::$requestRules["fields"]), array_values(self::$requestRules["fields"]));
                    }
                    $res["query"]["select"]                                                     = array_replace($res["query"]["select"], self::$requestRules["fields_fixed"]);
                }
                //da togliere reqallowed


                //where calc
                $res["query"]["where"]                                                          = (array)self::$requestRules["where"];
                if (is_array($res["search"])) {
                    foreach ($res["search"] AS $search_key => $search_value) {
                        if (self::$requestRules["fields"][$search_key] && !$res["query"]["where"][self::$requestRules["fields"][$search_key]])
                            $res["query"]["where"][self::$requestRules["fields"][$search_key]]  = $search_value;
                    }
                } elseif ($res["search"]) {
                    foreach (self::$requestRules["fields"] AS $field_key => $field_value) {
                        $res["query"]["where"]['$or'][$field_value]                             = $res["search"];
                        //$res["query"]["where"]['$or'][] = array($field_key => $res["search"]);
                    }
                }
                if(!count($res["query"]["where"]))                                              $res["query"]["where"] = true;

                //order calc
                if (is_array($res["sort"])) {
                    foreach ($res["sort"] AS $sort_key => $sort_value) {
                        if (self::$requestRules["fields"][$sort_key] && !$res["query"]["order"][self::$requestRules["fields"][$sort_key]])
                            $res["query"]["order"][self::$requestRules["fields"][$sort_key]]    = ($sort_value === "-1" || $sort_value === "DESC"
                                ? "-1"
                                : ($sort_value === "1" || $sort_value === "ASC"
                                    ? "1"
                                    : $res["dir"]
                                )
                            );
                    }
                }
                $res["query"]["order"]                                                          = array_replace((array)self::$requestRules["order"], (array)$res["query"]["order"]);
                if(!count( $res["query"]["order"]))                                             { $res["query"]["order"] = null; }

                //limit calc
                if ($res["navigation"]["page"] > 1 && $res["navigation"]["count"]) {
                    $res["query"]["limit"]["skip"]                                              = ($res["navigation"]["page"] - 1) * $res["navigation"]["count"];
                    $res["query"]["limit"]["limit"]                                             = $res["navigation"]["count"];
                } elseif($res["navigation"]["count"]) {
                    $res["query"]["limit"]                                                      = $res["navigation"]["count"];
                } else {
                    $res["query"]["limit"]                                                      = null;
                }
            }


            $res["valid"]                                                                       = array_diff_key($res["rawdata"], (array) $res["unknown"]);

            self::$cache["request"]                                                             = $res;
            $last_update                                                                        = microtime(true);
        }

//        $last_update                                                                            = microtime(true);

        return ($key
            ? self::$cache["request"][$key]
            : self::$cache["request"]
        );
    }

}