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

use phpformsframework\libs\Kernel;
use phpformsframework\libs\microservice\adapters\ApiAdapter;
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

    protected const CLASSNAME              = __CLASS__;

    /**
     * Api constructor.
     * @param $url
     * @param string|null $apiAdapter
     */
    public function __construct($url, string $apiAdapter = null)
    {
        $this->setAdapter($apiAdapter ?? Kernel::$Environment::MICROSERVICE_ADAPTER, [$url], static::CLASSNAME);
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
