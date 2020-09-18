<?php
namespace phpformsframework\libs\mock;

use phpformsframework\libs\Kernel;
use stdClass;

/**
 * Trait Mockable
 * @package phpformsframework\libs\mock
 */
trait Mockable
{
    private $mockEnabled        = false;
    protected $mock             = null;

    /**
     * @param string $name
     * @return array
     */
    protected function mock(string $name) : array
    {
        return $this->mock[$name] ?? [];
    }

    /**
     * @param string $name
     * @param string $param
     * @return stdClass
     */
    protected function mockParam(string $name, string $param) : ?string
    {
        $default                = null;
        if ($this->mockEnabled()) {
            $default            = $this->mock($name)->$param ?? null;
        }

        return $default;
    }

    /**
     * @return bool
     */
    private function mockEnabled() : bool
    {
        return $this->mockEnabled ?? Kernel::$Environment::DEBUG;
    }

    /**
     * @param bool $enable
     * @return Mockable
     */
    public function useMock(bool $enable) : self
    {
        $this->mockEnabled      = $enable;

        return $this;
    }
}
