<?php
namespace phpformsframework\libs\microservice;

use phpformsframework\libs\Kernel;
use phpformsframework\libs\microservice\adapters\ApiAdapter;
use phpformsframework\libs\util\AdapterManager;
use Exception;

/**
 * Class Api
 * @package phpformsframework\libs\microservice
 * @property ApiAdapter $adapter
 */
class Api
{
    use AdapterManager;

    public const ADAPTER_SOAP               = "Soap";
    public const ADAPTER_WSP                = "JsonWsp";

    protected const CLASSNAME              = __CLASS__;

    /**
     * Api constructor.
     * @param $url
     * @param string|null $apiAdapter
     */
    public function __construct($url, string $apiAdapter = null)
    {
        if (!$this->adapter && !$apiAdapter) {
            $apiAdapter                     = Kernel::$Environment::MICROSERVICE_ADAPTER;
        }

        $this->setAdapter($apiAdapter, [$url], static::CLASSNAME);
    }

    /**
     * @param string $method
     * @param array $params
     * @param array $heaers
     * @return object
     * @throws Exception
     */
    public function send(string $method, array $params, array $heaers) : object
    {
        return $this->adapter->send($method, $params, $heaers);
    }
}
