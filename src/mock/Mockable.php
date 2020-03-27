<?php
namespace phpformsframework\libs\mock;

use stdClass;
/**
 * Trait Mockable
 * @package phpformsframework\libs\mock
 */
trait Mockable
{
    protected $mock           = null;

    /**
     * @param string $name
     * @return stdClass
     */
    private function mock(string $name) : stdClass
    {
        return (object) ($this->mock[$name] ?? []);
    }
}