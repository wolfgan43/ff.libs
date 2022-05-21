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
namespace ff\libs\microservice\adapters;

use ff\libs\microservice\Api;
use ff\libs\util\ServerManager;
use ff\libs\storage\FilemanagerWeb;
use ff\libs\Exception;
use stdClass;

/**
 * Class ApiJsonWsp
 * @package ff\libs\microservice\adapters
 */
class ApiJsonWsp extends ApiAdapter
{
    use ServerManager;

    protected const ERROR_RESPONSE_INVALID_FORMAT   = "Response is not a valid Json";

    protected $user_agent                           = null;
    protected $cookie                               = null;

    /**
     * @param string $method
     * @param array $arguments
     * @return object
     * @throws Exception
     */
    public function __call(string $method, array $arguments) : object
    {
        $this->endpoint                         .= DIRECTORY_SEPARATOR . $method;

        return parent::__call($method, $arguments);
    }

    /**
     * JsonWsp constructor.
     * @param string|null $url
     * @param string|null $username
     * @param string|null $secret
     */
    public function __construct(string $url = null, string $username = null, string $secret = null)
    {
        parent::__construct($url, $username, $secret);

        $this->user_agent                       = $this->userAgent();
    }

    /**
     * @return array|null
     * @throws Exception
     */
    public function preflight() : ?array
    {
        return FilemanagerWeb::fileGetContentsWithHeaders(
            $this->endpoint(),
            [],
            Api::METHOD_HEAD
        );
    }

    /**
     * @param array $params
     * @param array $headers
     * @return stdClass
     * @throws Exception
     */
    protected function get(array $params = [], array $headers = []) : stdClass
    {
        $this->protocol                         = $this->protocol();
        if (!$this->request_method) {
            $this->request_method               = $this->method();
        }

        return FilemanagerWeb::fileGetContentsJson(
            $this->endpoint(),
            $params,
            $this->request_method,
            $this->timeout,
            $this->user_agent,
            $this->cookie,
            $this->http_auth_username,
            $this->http_auth_secret,
            $headers
        );
    }

    /**
     * @param string $method
     * @return array|null
     */
    protected function getResponseSchema(string $method) : ?array
    {
        return null;
    }

    private function method() : string
    {
        $request_method         = $this->requestMethod();
        $discover               = $this->discover($request_method);
        if (isset($discover["Strict-Transport-Security"])) {
            $this->protocol     = self::PROTOCOL_SECURE;
        }

        return $discover["Access-Control-Allow-Methods"] ?? $request_method;
    }
}
