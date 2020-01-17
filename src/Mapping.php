<?php
namespace phpformsframework\libs;

/**
 * Trait Mapping
 */
trait Mapping
{
    /**
     * @param array $map
     * @param object|null $obj
     */
    protected function autoMapping(array $map, object &$obj = null) : void
    {
        if (!$obj) {
            $obj =& $this;
        }
        $has                    = get_object_vars($obj);
        $properties             = array_intersect_key($map, $has);

        foreach ($properties as $key => $value) {
            $obj->$key         = $value;
        }
    }
}
