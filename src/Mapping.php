<?php
namespace phpformsframework\libs;

/**
 * Trait Mapping
 */
trait Mapping
{
    /**
     * @param array $map
     */
    private function autoMapping(array $map) : void
    {
        $has                    = get_object_vars($this);
        $properties             = array_intersect_key($map, $has);

        foreach ($properties as $key => $value) {
            $this->$key         = $value;
        }
    }
}
