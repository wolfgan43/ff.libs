<?php
namespace phpformsframework\libs\microservice\adapters;

use phpformsframework\libs\App;
use phpformsframework\libs\international\Data;
use phpformsframework\libs\mock\Mockable;
use stdClass;
use Exception;

/**
 * Interface ApiAdapter
 * @package hcore\adapters
 */
abstract class ApiAdapter
{
    use Mockable;

    protected static $preflight                                           = null;

    protected const ERROR_BUCKET                                        = "webservice";
    protected const ERROR_LABEL                                         = "Api: ";
    protected const NAMESPACE_DATARESPONSE                              = App::NAME_SPACE . "dto\\";
    protected const REQUEST_TIMEOUT                                     = 10;

    protected $endpoint                                                 = null;
    protected $http_auth_username                                       = null;
    protected $http_auth_secret                                         = null;

    protected $headers                                                  = [];

    private $request                                                    = null;
    private $exTimePreflight                                            = null;
    /**
     * @param string $method
     * @param array $arguments
     * @return object
     * @throws Exception
     */
    public function __call(string $method, array $arguments) : object
    {
        $this->request                  = $method;

        return $this->send($method, $arguments[0], $arguments[1] ?? null);
    }

    /**
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @return stdClass
     * @throws Exception
     */
    abstract protected function get(string $method, array $params = null, array $headers = null) : stdClass;

    /**
     * @return array
     */
    abstract protected function preflight() : ?array;

    /**
     * @param string $method
     * @return array
     */
    abstract protected function getResponseSchema(string $method) : ?array;



    /**
     * JsonWsp constructor.
     * @param string|null $url
     * @param string|null $username
     * @param string|null $secret
     */
    public function __construct(string $url = null, string $username = null, string $secret = null)
    {
        $this->endpoint                 = $url      ?? $this->endpoint;
        $this->http_auth_username       = $username ?? $username;
        $this->http_auth_secret         = $secret   ?? $secret;
    }

    /**
     * @return array|null
     * @todo da cachare permanentemente
     */
    public function discover() : ?array
    {
        App::stopWatch("api/remote/preflight");

        $discover                       = $this->preflight();

        $this->exTimePreflight          = App::stopWatch("api/remote/preflight");

        return $discover["headers"] ?? null;
    }

    /**
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @return object
     * @throws Exception
     */
    public function send(string $method, array $params = null, array $headers = null) : object
    {
        App::stopWatch("api/remote");
        /** @var \phpformsframework\libs\dto\DataResponse $DataResponse */
        $class_name                     = static::NAMESPACE_DATARESPONSE . "DataResponse";
        $DataResponse                   = new $class_name();

        $headers                        = $this->getHeader($headers);
        $response                       = (
            $this->mockEnabled
            ? $this->getMock()
            : null
        );

        if (!$response) {
            $response                   = $this->get($method, $params, $headers);
        }

        $DataResponse->fillObject($response);

        App::debug([
            "method"                    => $method,
            "header"                    => $headers,
            "body"                      => $params,
            "isRemote"                  => true,
            "isMock"                    => $this->mockEnabled,
            "exTimePreflight"           => $this->exTimePreflight,
            "exTimeRequest"             => App::stopWatch("api/remote")
        ], $this->endpoint);

        return $DataResponse;
    }

    /**
     * @return stdClass
     * @throws Exception
     */
    private function getMock() : ?stdClass
    {
        $request                        = $this->request ?? parse_url($this->endpoint, PHP_URL_PATH);

        $schema                         = $this->getResponseSchema($request);

        return ($schema
            ? json_decode(json_encode(array_replace_recursive($schema, $this->mock($request))))
            : null
        );
    }

    /**
     * @param string $type
     * @param string|null $default
     * @return mixed
     * @todo da tipizzare
     */
    protected function setDefault(string $type, string $default = null)
    {
        if ($default) {
            return $default;
        }
        switch (strtolower($type)) {
            case "int":
            case "unsignedshort":
                $res = "0";
                break;
            case "boolean":
                $res = false;
                break;
            case "array":
                $res = array();
                break;
            case "datetime":
                $res = Data::getEmpty("datetime");
                break;
            case "date":
                $res = Data::getEmpty("date");
                break;
            case "time":
                $res = Data::getEmpty("time");
                break;
            case "string":
            case "unsignedbyte":
            case "anytype":
            default:
                $res = "";
        }
        return $res;
    }

    /**
     * @param array|null $params
     * @return array
     */
    protected function getHeader(array $params = null) : array
    {
        return array_replace_recursive($this->headers, (array) $params);
    }
}
