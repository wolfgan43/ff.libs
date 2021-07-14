<?php
namespace phpformsframework\libs\microservice\adapters;

use phpformsframework\libs\App;
use phpformsframework\libs\dto\DataResponse;
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

    protected const NAMESPACE_DATARESPONSE                              = App::NAME_SPACE . "dto\\";
    protected const REQUEST_TIMEOUT                                     = 10;

    protected const ERROR_RESPONSE_INVALID_FORMAT                       = "Response Invalid Format";

    private const ERROR_RESPONSE_EMPTY                                  = "Response Empty";
    private const ERROR_ENDPOINT_EMPTY                                  = "Endpoint Empty";
    private const ERROR_REQUEST_LABEL                                   = "::request ";
    private const ERROR_RESPONSE_LABEL                                  = "::response ";

    protected static $preflight                                         = null;

    protected $timeout                                                  = self::REQUEST_TIMEOUT;

    protected $endpoint                                                 = null;
    protected $http_auth_username                                       = null;
    protected $http_auth_secret                                         = null;

    protected $headers                                                  = [];
    protected $requests                                                 = [];

    private $action                                                     = null;
    private $exTimePreflight                                            = null;

    /**
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @return stdClass|array|null
     * @throws Exception
     */
    abstract protected function get(string $method, array $params = null, array $headers = null);

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
     * @param string $method
     * @param array $arguments
     * @return object
     * @throws Exception
     */
    public function __call(string $method, array $arguments) : object
    {
        $this->action                                                   = $arguments["action"] ?? $method;

        return $this->send($method, $arguments[0], $arguments[1] ?? null);
    }

    /**
     * JsonWsp constructor.
     * @param string|null $url
     * @param string|null $username
     * @param string|null $secret
     */
    public function __construct(string $url = null, string $username = null, string $secret = null)
    {
        $this->endpoint                                                 = $url      ?? $this->endpoint;
        $this->http_auth_username                                       = $username ?? $this->http_auth_username;
        $this->http_auth_secret                                         = $secret   ?? $this->http_auth_secret;
    }

    /**
     * @return array|null
     * @todo da cachare permanentemente
     */
    public function discover() : ?array
    {
        App::stopWatch("api/remote/preflight");

        $discover                                                       = $this->preflight();

        $this->exTimePreflight                                          = App::stopWatch("api/remote/preflight");

        return $discover["headers"] ?? null;
    }

    /**
     * @return DataResponse
     */
    private function dataResponse() : DataResponse
    {
        $class_name                                                     = static::NAMESPACE_DATARESPONSE . "DataResponse";
        return new $class_name();
    }
    /**
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @return DataResponse
     * @throws Exception
     */
    public function send(string $method, array $params = null, array $headers = null) : DataResponse
    {
        App::stopWatch("api/remote");

        $exception                                                      = null;
        $response                                                       = null;
        $DataResponse                                                   = $this->dataResponse();
        if ($this->endpoint) {
            $headers                                                     = $this->getHeader($headers);
            try {
                $response                                               = $this->getMock() ?? $this->get($method, $params, $headers);
            } catch (Exception $e) {
                $exception                                              = $e;
            }

            self::debug($method, $params, $headers, $response->debug ?? null);

            if ($exception) {
                /** Response Invalid Format (nojson or no object) */
                App::debug($exception->getMessage(), $method . self::ERROR_RESPONSE_LABEL . $this->endpoint);
                throw new Exception($exception->getMessage(), $exception->getCode());
            } elseif (isset($response->data, $response->status, $response->error)) {
                if ($response->status >= 400) {
                    /** Request Wrong Params */
                    App::debug($response->error, $method . self::ERROR_RESPONSE_LABEL . $this->endpoint);
                    throw new Exception($response->error, $response->status);
                } else {
                    App::debug(empty($response->data) ? self::ERROR_RESPONSE_EMPTY : $response->data, $method . self::ERROR_RESPONSE_LABEL . $this->endpoint);
                }

                $DataResponse->fillObject($response->data);
                unset($response->data, $response->error, $response->status, $response->debug);
                foreach (get_object_vars($response) as $property => $value) {
                    $DataResponse->$property                            = $value;
                }
            } elseif (empty($response)) {
                /** Response is empty */
                App::debug(self::ERROR_RESPONSE_EMPTY, $method . self::ERROR_RESPONSE_LABEL . $this->endpoint);
                throw new Exception(self::ERROR_RESPONSE_EMPTY, 404);
            } else {
                $DataResponse->fillObject($response);
                $DataResponse->outputMode(true);
                App::debug($response, $method . self::ERROR_RESPONSE_LABEL . $this->endpoint);

            }
        } else {
            $DataResponse->error(500, self::ERROR_RESPONSE_EMPTY);
            App::debug(self::ERROR_ENDPOINT_EMPTY, $method . self::ERROR_RESPONSE_LABEL);
        }
        return $DataResponse;
    }

    /**
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     */
    private function debug(string $method, array $params = null, array $headers = null, stdClass $debug = null) : void
    {
        App::debug([
            "method"                                                    => $method,
            "header"                                                    => $headers,
            "body"                                                      => $params,
            "isRemote"                                                  => true,
            "isMock"                                                    => $this->mockEnabled,
            "exTimePreflight"                                           => $this->exTimePreflight,
            "exTimeRequest"                                             => App::stopWatch("api/remote"),
            "debug"                                                     => $debug
        ], $method . self::ERROR_REQUEST_LABEL . $this->endpoint);
    }

    /**
     * @return stdClass
     * @throws Exception
     */
    private function getMock() : ?stdClass
    {
        $schema                                                         = null;
        if ($this->mockEnabled) {

            $method                                                     = $this->action ?? parse_url($this->endpoint, PHP_URL_PATH);

            $schema                                                     = $this->getResponseSchema($method);
            if ($schema) {
                $schema                                                 = json_decode(json_encode(array_replace_recursive($schema, $this->mock($method))));
            }
        }

        return $schema;
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
                $res                                                    = "0";
                break;
            case "boolean":
                $res                                                    = false;
                break;
            case "array":
                $res                                                    = array();
                break;
            case "datetime":
                $res                                                    = Data::getEmpty("datetime");
                break;
            case "date":
                $res                                                    = Data::getEmpty("date");
                break;
            case "time":
                $res                                                    = Data::getEmpty("time");
                break;
            case "string":
            case "unsignedbyte":
            case "anytype":
            default:
                $res                                                    = "";
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
