<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace phpformsframework\libs\microservice\adapters;

use phpformsframework\libs\App;
use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Debug;
use phpformsframework\libs\dto\DataAdapter;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\dto\DataTableResponse;
use phpformsframework\libs\international\Data;
use phpformsframework\libs\mock\Mockable;
use phpformsframework\libs\Exception;
use stdClass;

/**
 * Class ApiAdapter
 * @package phpformsframework\libs\microservice\adapters
 */
abstract class ApiAdapter
{
    use Mockable;

    private const ERROR_BUCKET                                          = "api";
    private const METHOD_HEAD                                           = "HEAD";

    protected const NAMESPACE_DATARESPONSE                              = App::NAME_SPACE . "dto\\";
    protected const REQUEST_TIMEOUT                                     = 10;
    protected const PROTOCOL                                            = "https://";

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
    protected $request_method                                           = null;
    protected $protocol                                                 = null;
    protected $action                                                   = null;

    protected $headers                                                  = [];
    protected $requests                                                 = [];

    /**
     * @param array|null $params
     * @param array|null $headers
     * @return stdClass
     * @throws Exception
     */
    abstract protected function get(array $params = null, array $headers = null) : stdClass;

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
        $this->action                                                   = $method;

        return $this->send($arguments[0], $arguments[1] ?? null);
    }

    /**
     * JsonWsp constructor.
     * @param string|null $url
     * @param string|null $username
     * @param string|null $secret
     */
    public function __construct(string $url = null, string $username = null, string $secret = null)
    {
        $this->endpoint                                                 = $url;
        $this->http_auth_username                                       = $username;
        $this->http_auth_secret                                         = $secret;
    }

    /**
     * @param string $method
     * @return array|null
     */
    public function discover(string $method) : ?array
    {
        Debug::stopWatch("api/remote/preflight");

        $endpoint                                                       = $this->endpoint();

        if (!isset(self::$preflight)) {
            self::$preflight                                            = Buffer::cache(self::ERROR_BUCKET)->get("preflight");
        }

        if (!isset(self::$preflight[$endpoint])) {
            $cache                                                      = Buffer::cache(self::ERROR_BUCKET);
            self::$preflight[$endpoint]                                 = $this->preflight();

            $cache->set("preflight", self::$preflight);
        }

        $discover                                                       = self::$preflight[$endpoint];
        $discover["exTimePreflight"]                                    = Debug::stopWatch("api/remote/preflight") . (!isset($cache) ? " cached" : null);

        if (empty($discover["headers"]["Access-Control-Allow-Methods"])
            || $discover["headers"]["Access-Control-Allow-Methods"] == "*"
            || strpos($discover["headers"]["Access-Control-Allow-Methods"], $method) !== false
        ) {
            $discover["headers"]["Access-Control-Allow-Methods"] = $method;
        }

        App::debug($discover, self::METHOD_HEAD . self::ERROR_REQUEST_LABEL . $endpoint);

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
     * @return DataTableResponse
     */
    private function dataTableResponse() : DataTableResponse
    {
        $class_name                                                     = static::NAMESPACE_DATARESPONSE . "DataTableResponse";
        return new $class_name();
    }
    /**
     * @param array|null $params
     * @param array|null $headers
     * @return DataAdapter
     * @throws Exception
     */
    public function send(array $params = null, array $headers = null) : DataAdapter
    {
        Debug::stopWatch("api/remote");

        $exception                                                      = null;
        $response                                                       = null;
        $dataResponse                                                   = $this->dataResponse();
        if ($this->endpoint) {
            $headers                                                     = $this->getHeader($headers);
            try {
                $response                                               = $this->getMock() ?? $this->get($params, $headers);
            } catch (\Exception $e) {
                $exception                                              = $e;
            }

            self::debug($this->request_method, $params, $headers, $response->debug ?? null);
            if ($exception) {
                /** Response Invalid Format (nojson or no object) */
                App::debug($exception->getMessage(), $this->request_method . self::ERROR_RESPONSE_LABEL . $this->endpoint());
                throw new Exception($exception->getMessage(), $exception->getCode());
            } elseif (empty((array) $response)) {
                $dataResponse = $this->dataTableResponse();
            } elseif (isset($response->status, $response->error)) {
                if ($response->status >= 400) {
                    /** Request Wrong Params */
                    App::debug($response->error, $this->request_method . self::ERROR_RESPONSE_LABEL . $this->endpoint());
                    throw new Exception($response->error, $response->status);
                } else {
                    App::debug(empty($response->data) ? self::ERROR_RESPONSE_EMPTY : $response->data, $this->request_method . self::ERROR_RESPONSE_LABEL . $this->endpoint());
                }

                if (isset($response->draw, $response->recordsFiltered)) {
                    $dataResponse                   = $this->dataTableResponse();
                    $dataResponse->draw             = $response->draw;
                    $dataResponse->recordsTotal     = $response->recordsTotal;
                    $dataResponse->recordsFiltered  = $response->recordsFiltered;
                    unset($response->draw, $response->recordsTotal, $response->recordsFiltered);
                }

                if (isset($response->data)) {
                    $dataResponse->fillObject($response->data);
                    unset($response->data, $response->error, $response->status, $response->debug);
                    foreach (get_object_vars($response) as $property => $value) {
                        $dataResponse->$property = $value;
                    }
                }
            } elseif (empty($response)) {
                /** Response is empty */
                App::debug(self::ERROR_RESPONSE_EMPTY, $this->request_method . self::ERROR_RESPONSE_LABEL . $this->endpoint());
                throw new Exception(self::ERROR_RESPONSE_EMPTY, 404);
            } else {
                $dataResponse->fillObject($response);
                $dataResponse->outputMode(true);
                App::debug($response, $this->request_method . self::ERROR_RESPONSE_LABEL . $this->endpoint());
            }
        } else {
            $dataResponse->error(500, self::ERROR_RESPONSE_EMPTY);
            App::debug(self::ERROR_ENDPOINT_EMPTY, $this->request_method . self::ERROR_RESPONSE_LABEL);
        }
        return $dataResponse;
    }

    protected function endpoint() : string
    {
        return $this->protocol . $this->endpoint;
    }

    /**
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @param stdClass|null $debug
     */
    private function debug(string $method, array $params = null, array $headers = null, stdClass $debug = null) : void
    {
        App::debug([
            "method"                                                    => $method,
            "header"                                                    => $headers,
            "body"                                                      => $params,
            "isRemote"                                                  => true,
            "isMock"                                                    => $this->mockEnabled,
            "exTimeRequest"                                             => Debug::stopWatch("api/remote"),
            "debug"                                                     => $debug
        ], $method . self::ERROR_REQUEST_LABEL . $this->endpoint());
    }

    /**
     * @return stdClass
     * @throws Exception
     */
    private function getMock() : ?stdClass
    {
        $schema                                                         = null;
        if ($this->mockEnabled) {

            $method                                                     = $this->action ?? parse_url($this->endpoint(), PHP_URL_PATH);

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
     * @throws Exception
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
