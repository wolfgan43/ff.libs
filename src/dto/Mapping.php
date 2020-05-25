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
    protected function autoMapping(array $map, object &$obj = null) : void
    {
        if (!$obj) {
            $obj                =& $this;
        }

        $has                    = $this->getObjectProperties($obj);
        $properties             = array_intersect_key($map, $has);

        foreach ($properties as $key => $value) {
            $obj->$key         = $value;
        }
    }
}
