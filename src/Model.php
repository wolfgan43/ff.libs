<?php
namespace phpformsframework\libs;

use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\storage\Orm;

class Model implements Configurable, Dumpable
{
    const ERROR_BUCKET                                  = "model";
    const SCHEMA_MODELS                                 = "models";
    const SCHEMA_MODELSVIEW                             = "modelsview";

    private static $models                              = null;
    private static $models_view                         = null;


    public static function dump()
    {
        return array(
            "models"    => self::$models,
            "views"     => self::$models_view
        );
    }

    public static function loadSchema()
    {
        Debug::stopWatch("load/models");
        $cache                                          = Mem::getInstance("config");
        $res                                            = $cache->get("models");
        if (!$res) {
            self::loadModels();
            self::loadModelsView();

            $cache->set("models", array(
                "models"       => self::$models,
                "models_view"  => self::$models_view,
            ));
        } else {
            self::$models                               = $res["models"];
            self::$models_view                          = $res["models_view"];
        }
        Debug::stopWatch("load/models");
    }

    private static function get($scope, $name)
    {
        if (isset(self::$models[$name][$scope])) {
            return self::$models[$name][$scope];
        } else {
            Error::register("Model not found: " . $name . " for scope " . $scope, static::ERROR_BUCKET);
        }

        return null;
    }

    public static function readWithFake($name, $where = null, $sort = null, $limit = null)
    {
        return self::orm($name, $where, $sort, $limit, true);
    }
    public static function read($name, $where = null, $sort = null, $limit = null)
    {
        return self::orm("read", $name, $where, $sort, $limit);
    }

    private static function orm($action, $name, $where = null, $sort = null, $limit = null, $fake = false)
    {
        $model = Orm::getInstance("anagraph")->$action(self::get("read", $name), $where, $sort, $limit);

        return ($fake && !$model
            ? self::fake($name)
            : $model
        );
    }
    public static function assignByRequest($name)
    {
        return self::fillByRequest(self::get("insert", $name));
    }

    public static function fake($name)
    {
        return self::get("fake", $name);
    }

    /**
     * @param array $model
     * @return array
     */
    private static function fillByRequest($model)
    {
        $request                                                = Request::rawdata();
        if (is_array($request) && count($request)) {
            $request_key                                        = array();
            $request_value                                      = array();
            foreach ($request as $key => $value) {
                $request_key[]                                  = '$' . $key . "#";
                $request_key[]                                  = '$' . $key . " ";
                $request_value[]                                = $value . "#";
                $request_value[]                                = $value . " ";
            }

            $prototype                                          = str_replace(
                $request_key,
                $request_value,
                implode("#", $model) . "#"
            );
            $prototype = preg_replace('/\$[a-zA-Z]+/', "", $prototype);


            $model                                              = array_combine(
                array_keys($model),
                explode("#", substr($prototype, 0, -1))
            );
        }

        return array_filter($model);
    }


    private static function loadModels()
    {
        Debug::stopWatch("load/models");

        $config                                                 = Config::rawData(static::SCHEMA_MODELS, true);
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            foreach ($config as $model_name => $model) {
                foreach ($model["field"] as $field) {
                    $attr                                       = Dir::getXmlAttr($field);
                    if (!isset($attr["name"])) {
                        continue;
                    }
                    $key                                        = $attr["name"];
                    if (isset($attr["fake"])) {
                        $schema[$model_name]["fake"][$key]      = $attr["fake"];
                    }
                    if (isset($attr["db"])) {
                        $schema[$model_name]["read"][$attr["db"]] = $key;

                        if (isset($attr["source"])) {
                            $schema[$model_name]["insert"][$attr["db"]]   = $attr["source"];
                        }
                    }

                }
            }

            self::$models                                       = $schema;
        }

        Debug::stopWatch("load/models");
    }

    private static function loadModelsView()
    {
        Debug::stopWatch("load/modelsview");

        $config                                                 = Config::rawData(static::SCHEMA_MODELSVIEW, true, "view");
        if (is_array($config) && count($config)) {
            $schema                                             = array();
            foreach ($config as $view) {
                $view_attr                                      = Dir::getXmlAttr($view);
                $model_name                                     = $view_attr["model"];
                $view_name                                      = $view_attr["name"];
                foreach ($view["field"] as $field) {
                    $attr                                       = Dir::getXmlAttr($field);
                    if (!isset($attr["name"])) {
                        continue;
                    }
                    $schema[$model_name][$view_name][]          = $attr["name"];
                }
            }

            self::$models_view                                  = $schema;
        }

        Debug::stopWatch("load/modelsview");
    }
}
