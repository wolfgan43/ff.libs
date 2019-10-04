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
     * @param object $d
     * @return array|null
     */
    private function object2array(object $d) : ?array
    {
        $d = get_object_vars($d);

        return (is_array($d)
            ? array_map(__FUNCTION__, $d)
            : null
        );
    }
}
