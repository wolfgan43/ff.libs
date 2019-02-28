<?php
namespace phpformsframework\libs;

abstract class Event {
    const EVENT_PRIORITY_HIGH           = 1000;
    const EVENT_PRIORITY_NORMAL         = 100;
    const EVENT_PRIORITY_LOW            = 10;

    private static $events              = null;

    public static function hook($name, $func, $priority = self::EVENT_PRIORITY_NORMAL) {
        if(is_callable($func)) {
            if(self::PROFILING) {
                Debug::dumpCaller("addEvent::" . $name);
            }
            self::$events[$name][$priority + count((array)self::$events[$name])] = $func;
        }
    }
    public static function doHook($name, &$ref, $params = null) {
        $res = null;

        if(is_array(self::$events[$name])) {
            krsort(self::$events[$name], SORT_NUMERIC);
            foreach(self::$events[$name] AS $func) {
                $res[] = $func($ref, $params);
                //$res[] = call_user_func($func, $ref, $params);
            }
        }

        return $res;
    }


}