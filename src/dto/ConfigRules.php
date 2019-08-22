<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Config;

class ConfigRules
{
    private $context            = null;
    private $data               = array();


    public function __construct($context)
    {
        $this->context          = $context;
    }

    public function add($bucket, $method = Config::RAWDATA_XML_MERGE_RECOURSIVE)
    {
        $this->data[$bucket]    = [
                                    "method"     => $method,
                                    "context"   => $this->context
                                ];
        return $this;
    }

    public function toArray()
    {
        return $this->data;
    }
}
