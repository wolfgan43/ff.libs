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
}
