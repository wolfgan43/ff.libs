<?php
namespace phpformsframework\libs;

use phpformsframework\libs\storage\Orm;

class Model implements Configurable, Dumpable
{
    const ERROR_BUCKET                                  = "model";


    private static $models                              = null;
    private static $models_view                         = null;


    public static function dump()
    {
        return array(
            "models"    => self::$models,
            "views"     => self::$models_view
        );
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
                if (is_array($value)) {
                    $value                                      = json_encode($value);
                }

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

    /**
     * @access private
     * @param dto\ConfigRules $configRules
     * @return dto\ConfigRules
     */
    public static function loadConfigRules($configRules)
    {
        return $configRules
            ->add("models")
            ->add("modelsview");
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig($config)
    {
        self::$models                                           = $config["models"];
        self::$models_view                                      = $config["models_view"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema($rawdata)
    {
        if (isset($rawdata["model"])) {
            self::loadModels($rawdata["model"]);
        }
        if (isset($rawdata["view"])) {
            self::loadModelsView($rawdata["view"]);
        }

        return array(
            "models"       => self::$models,
            "models_view"  => self::$models_view,
        );
    }

    private static function loadModels($models)
    {
        if (is_array($models) && count($models)) {
            $schema                                                                 = array();
            foreach ($models as $model) {
                $model_attr                                                         = Dir::getXmlAttr($model);
                if (isset($model_attr["name"]) && isset($model["field"])) {
                    $model_name                                                     = $model_attr["name"];
                    $fields                                                         = $model["field"];

                    foreach ($fields as $field) {
                        $attr                                                       = Dir::getXmlAttr($field);
                        if (!isset($attr["name"])) {
                            continue;
                        }
                        $key                                                        = $attr["name"];
                        if (isset($attr["fake"])) {
                            $schema[$model_name]["fake"][$key]                      = $attr["fake"];
                        }
                        if (isset($attr["db"])) {
                            $schema[$model_name]["read"][$attr["db"]]               = $key;

                            if (isset($attr["source"])) {
                                $schema[$model_name]["insert"][$attr["db"]]         = $attr["source"];
                            }
                        }
                    }
                } else {
                    Error::registerWarning("Model name not set or Fields empty", static::ERROR_BUCKET);
                }
            }

            self::$models                                                           = $schema;
        }
    }

    private static function loadModelsView($views)
    {
        if (is_array($views) && count($views)) {
            $schema                                             = array();
            foreach ($views as $view) {
                $view_attr                                      = Dir::getXmlAttr($view);
                if (isset($view_attr["model"]) && isset($view_attr["name"]) && isset($view["field"])) {
                    $model_name                                     = $view_attr["model"];
                    $view_name                                      = $view_attr["name"];
                    $fields                                         = $view["field"];

                    foreach ($fields as $field) {
                        $attr                                       = Dir::getXmlAttr($field);
                        if (!isset($attr["name"])) {
                            continue;
                        }
                        $schema[$model_name][$view_name][]          = $attr["name"];
                    }
                } else {
                    Error::registerWarning("ModelView Model Name not set or View Name not set or Fields empty", static::ERROR_BUCKET);
                }
            }

            self::$models_view                                  = $schema;
        }
    }
}
