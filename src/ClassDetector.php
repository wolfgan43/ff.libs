<?php
namespace phpformsframework\libs;

/**
 * Trait ClassDetector
 * @package phpformsframework\libs
 */
trait ClassDetector
{
    /**
     * @param string|null $class_name
     * @return string
     */
    private static function getClassName(string $class_name = null) : string
    {
        static $classes = [];

        if (!$class_name) {
            $class_name         = static::class;
        }

        if (!isset($classes[$class_name])) {
            $classes[$class_name] = strtolower(substr(strrchr($class_name, "\\"), 1));
        }

        return $classes[$class_name];
    }
}