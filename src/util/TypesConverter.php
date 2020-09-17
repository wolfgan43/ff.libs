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
}
