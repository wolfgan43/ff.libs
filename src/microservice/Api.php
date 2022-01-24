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

    public const ADAPTER_SOAP               = "Soap";
    public const ADAPTER_WSP                = "JsonWsp";

    private $headers                        = [
                                                "Accept" => "application/json"
                                            ];

    /**
     * Api constructor.
     * @param string $url
     * @param array $headers
     * @param string|null $apiAdapter
     */
    public function __construct(string $url, array $headers = [], string $apiAdapter = null)
    {
        $this->headers                      = array_replace($this->headers, $headers);

        $this->setAdapter($apiAdapter ?? Kernel::$Environment::MICROSERVICE_ADAPTER, [$url]);
    }

    /**
     * @param string|null $accessToken
     * @return $this
     */
    public function authorization(string $accessToken = null) : self
    {
        $this->headers['Authorization']     = $accessToken ?? User::accessToken();

        return $this;
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function get(array $params = []) : DataAdapter
    {
        return $this->adapter->send($params, $this->headers, "GET");
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function post(array $params = []) : DataAdapter
    {
        return $this->adapter->send($params, $this->headers, "POST");
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function put(array $params = []) : DataAdapter
    {
        return $this->adapter->send($params, $this->headers, "PUT");
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function patch(array $params = []) : DataAdapter
    {
        return $this->adapter->send($params, $this->headers, "PATCH");
    }

    /**
     * @param array $params
     * @return DataAdapter
     * @throws Exception
     */
    public function delete(array $params = []) : DataAdapter
    {
        return $this->adapter->send($params, $this->headers, "DELETE");
    }
}
