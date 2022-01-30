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
namespace phpformsframework\libs\microservice;

use phpformsframework\libs\dto\DataAdapter;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\microservice\adapters\ApiAdapter;
use phpformsframework\libs\Request;
use phpformsframework\libs\security\User;
use phpformsframework\libs\util\AdapterManager;
use phpformsframework\libs\Exception;

/**
 * Class Api
 * @package phpformsframework\libs\microservice
 * @property ApiAdapter $adapter
 */
class Api
{
    use AdapterManager;

    public const ADAPTER_SOAP                   = "Soap";
    public const ADAPTER_WSP                    = "JsonWsp";

    public const METHOD_HEAD                    = Request::METHOD_HEAD;
    public const METHOD_GET                     = Request::METHOD_GET;
    public const METHOD_POST                    = Request::METHOD_POST;
    public const METHOD_PUT                     = Request::METHOD_PUT;
    public const METHOD_PATCH                   = Request::METHOD_PATCH;
    public const METHOD_DELETE                  = Request::METHOD_DELETE;

    private const METHOD_DEFAULT                = Request::METHOD_GET;
    private const METHOD_ALLOWED                = [
        Request::METHOD_GET,
        Request::METHOD_POST,
        Request::METHOD_PUT,
        Request::METHOD_PATCH,
        Request::METHOD_DELETE
    ];
    private const ERROR_METHOD_NOT_SUPPORTED    = "Request Method not Supported";

    public $url                                 = null;

    private $method                             = null;
    private $params                             = [];
    private $headers                            = [
        "Accept" => "application/json"
    ];

    public static function isAllowedMethod(string $method) : bool
    {
        if (!in_array($method, Api::METHOD_ALLOWED)) {
            throw new Exception(self::ERROR_METHOD_NOT_SUPPORTED, 501);
        }
        return true;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return DataAdapter
     * @throws Exception
     */
    public static function request(string $method, string $url, array $params = [], array $headers = []) : DataAdapter
    {
        return (new Api($url, $headers))
            ->method($method)
            ->send($params);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return DataAdapter
     * @throws Exception
     */
    public static function requestWithAuth(string $method, string $url, array $params = [], array $headers = []) : DataAdapter
    {
        return (new Api($url, $headers))
            ->method($method)
            ->authorization()
            ->send($params);
    }

    /**
     * Api constructor.
     * @param string $url
     * @param array $headers
     * @param string|null $apiAdapter
     */
    public function __construct(string $url, array $headers = [], string $apiAdapter = null)
    {
        $this->url                          = $url;
        $this->headers                      = array_replace($this->headers, $headers);

        $this->setAdapter($apiAdapter ?? Kernel::$Environment::MICROSERVICE_ADAPTER, [$this->url]);
    }

    /**
     * @param string|null $accessToken
     * @return $this
     */
    public function authorization(string $accessToken = null) : self
    {
        if (($token = $accessToken ?? User::accessToken())) {
            $this->headers['Authorization']     = $token;
        }

        return $this;
    }

    /**
     * @param string $method
     * @return $this
     * @throws Exception
     */
    public function method(string $method) : self
    {
        $method = strtoupper($method);
        if ($this->isAllowedMethod($method)) {
            $this->method = $method;
        }

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function params(array $params) : self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function send(array $params = []) : DataAdapter
    {
        return $this->adapter->send($this->params + $params, $this->headers, $this->method ?? self::METHOD_DEFAULT);
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function get(array $params = []) : DataAdapter
    {
        return $this->method(Request::METHOD_GET)
            ->send($params);
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function post(array $params = []) : DataAdapter
    {
        return $this->method(Request::METHOD_POST)
            ->send($params);
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function put(array $params = []) : DataAdapter
    {
        return $this->method(Request::METHOD_PUT)
            ->send($params);
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function patch(array $params = []) : DataAdapter
    {
        return $this->method(Request::METHOD_PATCH)
            ->send($params);
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function delete(array $params = []) : DataAdapter
    {
        return $this->method(Request::METHOD_DELETE)
            ->send($params);
    }
}
