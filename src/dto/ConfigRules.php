<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Configurable;

/**
 * Class ConfigRules
 * @package phpformsframework\libs\dto
 */
class ConfigRules
{
    private $context            = null;
    private $data               = array();

    /**
     * ConfigRules constructor.
     * @param $context
     */
    public function __construct($context)
    {
        $this->context          = $context;
    }

    /**
     * @param string $bucket
     * @param int $method
     * @return ConfigRules
     */
    public function add(string $bucket, int $method = Configurable::METHOD_MERGE) : ConfigRules
    {
        $this->data[$bucket]    = [
                                    "method"     => $method,
                                    "context"   => $this->context
                                ];
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->data;
    }
}
