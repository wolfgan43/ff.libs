<?php
namespace phpformsframework\libs\dto;

/**
 * Trait Mapping
 */
trait Mapping
{
    /**
     * @param object $obj
     * @return array
     */
    private function getObjectProperties(object $obj) : array
    {
        static $mapClass            = null;

        $class_name                 = get_class($obj);
        if (!isset($mapClass[$class_name])) {
            $mapClass[$class_name]  = get_object_vars($obj);
        }

        return $mapClass[$class_name];
    }

    /**
     * @param array $map
     * @param object|null $obj
     */
    protected function autoMapping2(array $map, object &$obj = null) : void
    {
        foreach ($this->getProp($map, $obj) as $key => $value) {
            $obj->$key              = $value;
        }
    }

    private function getProp(array $map, object &$obj = null) : array
    {
        if (!$obj) {
            $obj                    = $this;
        }

        $has                        = $this->getObjectProperties($obj);
        return array_intersect_key($map, $has);
    }


    /**
     * @param array $map
     * @param object|null $obj
     */
    protected function autoMapping(array $map, object $obj = null) : void
    {
        if (!$obj) {
            $obj = $this;
        }

        foreach (array_intersect_key($map, get_object_vars($obj))  as $key => $value) {
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

        foreach ($map  as $key => $value) {
            $obj->$key = $value;
        }
    }
}
