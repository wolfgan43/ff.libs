<?php
namespace phpformsframework\libs\dto;

trait Manipulation
{
    protected function setProperty(string $name, $value)
    {
        if ($value !== null && property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}

