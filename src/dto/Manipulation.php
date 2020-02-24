<?php
namespace phpformsframework\libs\dto;
/**
 * Trait Manipulation
 * @package phpformsframework\libs\dto
 */
trait Manipulation
{
    /**
     * @todo da tipizzare
     * @param string $name
     * @param $value
     */
    protected function setProperty(string $name, $value)
    {
        if ($value !== null && property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}

