<?php
namespace phpformsframework\libs\util;

/**
 * Trait TypesConverter
 * @package phpformsframework\libs\util
 */
trait TypesConverter
{
    /**
     * @param array $d
     * @return object|null
     */
    private function array2object(array $d) : ?object
    {
        return (is_array($d)
            ? (object) array_map(__FUNCTION__, $d)
            : null
        );
    }

    /**
     * @param string $rule
     * @return string
     */
    private static function regexp(string $rule) : string
    {
        return "#" . (
            strpos($rule, "[") === false && strpos($rule, "(") === false && strpos($rule, '$') === false
                ? str_replace("\*", "(.*)", preg_quote($rule, "#"))
                : $rule
            ) . "#i";
    }
}
