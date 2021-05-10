<?php
namespace phpformsframework\libs\dto;

/**
 * Trait Mapping
 */
trait Mapping
{
    /**
     * @param array $map
     * @param object|null $obj
     */
    protected function autoMapping(array $map, object $obj = null) : void
    {
        if (!$obj) {
            $obj = $this;
        }

        foreach (array_intersect_key($this->removeNull($map), get_object_vars($obj))  as $key => $value) {
            $obj->$key = $value;
        }
    }


    /**
     * @param array $map
     * @param object|null $obj
     */
    protected function autoMappingMagic(array $map, object $obj = null) : void
    {
        if (!$obj) {
            $obj = $this;
        }

        foreach ($this->removeNull($map) as $key => $value) {
            $obj->$key = $value;
        }
    }

    private function removeNull(array $map) : array
    {
        return array_filter($map, function ($var) {
            return $var !== null;
        });
    }
}
