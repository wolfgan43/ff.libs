<?php
namespace phpformsframework\libs\util;

/**
 * Trait TypesConverter
 * @package phpformsframework\libs\util
 */
trait TypesConverter
{
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

    /**
     * @param array|null $array $array
     * @return string
     */
    private static function checkSumArray(array $array = null) : ?string
    {
        return ($array
            ? crc32(json_encode($array))
            : null
        );
    }
    
    /**
     * @param array $array
     * @return bool
     */
    public static function isAssocArray(array $array) : bool
    {
        if (array() === $array) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
