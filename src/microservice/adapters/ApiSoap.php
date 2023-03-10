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

use ff\libs\App;
use ff\libs\Exception;
use ff\libs\Kernel;
use SoapClient;
use SoapHeader;
use stdClass;
use SoapFault;

/**
 * Class ApiSoap
 * @package ff\libs\microservice\adapters
 */
class ApiSoap extends ApiAdapter
{
    protected const ERROR_RESPONSE_INVALID_FORMAT                       = "Response is not a valid Object";

    private const REQUEST_METHOD                                        = "Soap";

    /**
     * @var SoapClient|null
     */
    private $client                                                     = null;

    private $schema                                                     = [];
    private $dtds                                                       = [];
    private $defaults                                                   = [];
    private $sFunctions                                                 = [];

    protected $header_namespace                                         = null;
    protected $header_name                                              = null;

    protected $uri                                                      = null;

    protected $wsdl                                                     = null;

    protected $encoding                                                 = 'UTF-8';
    protected $wsdl_ttl                                                 = 900;
    protected $version                                                  = SOAP_1_2;

    protected $connection_by_curl                                       = true;
    protected $request_container                                        = null;

    /**
     * @param string $wsdl
     * @return ApiSoap
     */
    public function setWsdl(string $wsdl) : self
    {
        $this->wsdl = $wsdl;

        return $this;
    }

    /**
     * @param string $namespace
     * @param string $root
     * @return self
     */
    public function setHeader(string $namespace, string $root) : self
    {
        $this->header_namespace                                         = $namespace;
        $this->header_name                                              = $root;

        return $this;
    }

    /**
     * @return SoapClient|null
     * @throws Exception
     */
    private function loadClient() : ?SoapClient
    {
        if (!$this->client) {
            $options                                                    = array(
                'location'                                              => $this->endpoint(),
                'uri'                                                   => $this->uri,
                'style'                                                 => SOAP_RPC,
                'use'                                                   => SOAP_ENCODED,
                'soap_version'                                          => $this->version,
                'cache_wsdl'                                            => WSDL_CACHE_NONE,
                'connection_timeout'                                    => $this->timeout,
                'trace'                                                 => true,
                'encoding'                                              => $this->encoding,
                'exceptions'                                            => true
            );

            if ($this->http_auth_username && $this->http_auth_secret) {
                $options                                                = $options + array(
                    'login'                                             => $this->http_auth_username,
                    'password'                                          => $this->http_auth_secret
                );
            }

            try {
                $this->client                                           = (
                    $this->connection_by_curl
                    ? new SoapClientCurl($this->wsdl, $options, $this->getMockResponse())
                    : new SoapClient($this->wsdl, $options)
                );
            } catch (SoapFault $e) {
                throw new Exception($e->faultstring, 501);
            }
        }

        ini_set('soap.wsdl_cache_enabled', WSDL_CACHE_NONE);
        ini_set('soap.wsdl_cache_ttl', $this->wsdl_ttl);
        ini_set('default_socket_timeout', self::REQUEST_TIMEOUT);

        return $this->client;
    }

    /**
     * @return array|string|null
     */
    protected function getMockResponse() : array|string|null
    {

        $mock = parent::getMockResponse();
        return (!empty($mock) && !is_array($mock)
            ? file_get_contents(Kernel::$Environment::DISK_PATH . $mock)
            : null
        );
    }

    /**
     * @return array|null
     */
    public function preflight() : ?array
    {
        self::$preflight[$this->endpoint]                               = null;

        return self::$preflight[$this->endpoint];
    }


    /**
     * @param array $params
     * @param array $headers
     * @return stdClass
     * @throws Exception
     */
    protected function get(array $params = [], array $headers = []) : stdClass
    {
        $response                                                       = new stdClass();
        $this->protocol                                                 = self::PROTOCOL_SECURE;
        $this->request_method                                           = self::REQUEST_METHOD;

        $this->loadClient();
        $this->setSoapHeader($headers);
        try {
            $this->client->__action                                     = $this->action;

            $request                                                    = $this->getRequest($this->action, $params);
            $response                                                   = $this->getResponse($this->action, $request);
        } catch (SoapFault $e) {
            throw new Exception($e->faultstring, 500);
        } finally {
            App::debug([
                "location"                                              => $this->endpoint(),
                "uri"                                                   => $this->uri,
                "header"                                                => [
                                                                            "namespace"     => $this->header_namespace,
                                                                            "name"          => $this->header_name
                                                                        ],
                "xml"                                                   => $this->client->__getLastRequest()

            ], $this->request_method . "::" . $this->action);
        }

        return $response;
    }

    /**
     * @param SoapClient $soap
     * @return array
     */
    private function parseFunctions(SoapClient $soap) : array
    {
        $types                                                          = [];
        $funcs                                                          = array_unique($soap->__getFunctions());
        $regex_request                                                  = '/(\w*)\s+\$(\w*)/mi';
        $regex                                                          = '/^([\w]+)\({0,1}([\w$,\s]*)\){0,1}\s+(\w+)\({0,1}\s*([$\w,\s]*)\){0,1}/i';

        foreach ($funcs as $func) {
            preg_match($regex, $func, $match_funcs);
            if (count($match_funcs) > 4) {
                $function                                               = new stdClass();
                $request_name                                           = $match_funcs[3];

                preg_match_all($regex_request, $match_funcs[4], $match_request);
                $function->request                                      = array_combine($match_request[2], $match_request[1]);
                foreach ($function->request as $param  => &$type) {
                    $this->dtds[$request_name . "." . $param]           = $type;
                    $this->defaults[$request_name . "." . $param]       = null;

                    $function->dtd[$param]                              =& $this->dtds[$request_name . "." . $param];
                    $function->request[$param]                          =& $this->defaults[$request_name . "." . $param];
                }

                if (isset($this->schema[$match_funcs[1]])) {
                    $function->response                                 = $match_funcs[1];
                } elseif ($match_funcs[2]) {
                    $types[]                                            = "struct " . $request_name . "Response" . " { \n" . str_replace(array('$', ','), array('', ";\n"), $match_funcs[2]) . ";\n } ";
                    $function->response                                 = $request_name . "Response";
                }

                $this->sFunctions[$request_name]                        = $function;
            }
        }

        return $types;
    }

    /**
     * @param SoapClient $soap
     */
    private function parseSoapTypes(SoapClient $soap): void
    {
        if (empty($this->schema)) {
            $types                                                      = $soap->__getTypes();

            foreach ($types as $type) {
                $this->parseSoapType($type);
            }

            foreach ($this->parseFunctions($this->client) as $type) {
                $this->parseSoapType($type);
            }

            $this->setDefaults();
        }
    }

    /**
     * Resolve Object Relationship
     */
    private function setDefaults()
    {
        foreach ($this->dtds as $struct => $type) {
            if (isset($this->schema[$type])) {
                $this->dtds[$struct]                                    = $this->schema[$type]["dtd"];
                $this->defaults[$struct]                                = $this->schema[$type]["default"];
            } else {
                $this->defaults[$struct]                                = $this->setDefault($type);
            }
        }
    }

    /**
     * @param string $type
     */
    private function parseSoapType(string $type) : void
    {
        $regex                                                          = '/^\s(\w*?)\s(\w*?);/mi';
        $regex_struct                                                   = '/struct\s(.*?)\s/';

        preg_match($regex_struct, $type, $match_struct);
        $struct                                                         = $match_struct[1] ?? null;
        preg_match_all($regex, $type, $matches);

        if (isset($matches[1]) && isset($match_struct[1])) {
            $dtd = array_combine($matches[2], $matches[1]);
            foreach ($dtd as $dtd_key => $dtd_value) {
                $this->dtds[$struct . "." . $dtd_key]                   = $dtd_value;
                $this->schema[$struct]["dtd"][$dtd_key]                 =& $this->dtds[$struct . "." . $dtd_key];
                $this->schema[$struct]["default"][$dtd_key]             =& $this->defaults[$struct . "." . $dtd_key];
            }
        }
    }



    /**
     * @param string $method
     * @param array $params
     * @return array
     */
    private function getRequest(string $method, array $params) : array
    {
        $schema                                                         = $this->requests[$method] ?? null;

        return $this->getContainerRequest(
            $schema
            ? $this->mergeRequest($schema, $params)
            : $params
        );
    }

    private function getContainerRequest(array $request) : array
    {
        return (
            $this->request_container
            ? [$this->request_container => $request]
            : $request
        );
    }

    /**
     * @param array $schema
     * @param array $params
     * @return array
     */
    private function mergeRequest(array $schema, array $params) : array
    {
        if (count($schema) == 1) {
            $key                                                        = array_keys($schema)[0];
            $res                                                        = [$key => array_replace_recursive($schema[$key], $params)];
        } else {
            $res                                                        = array_replace_recursive($schema, $params);
        }

        return $res;
    }

    /**
     * @param string $method
     * @return array|null
     * @throws Exception
     */
    protected function getResponseSchema(string $method) : ?array
    {
        $this->loadClient();
        $this->parseSoapTypes($this->client);

        $schema                                                         = $this->schema[$this->sFunctions[$method]->response]["default"] ?? null;
        if (isset($schema[$method]) || isset($schema[$method . "Result"])) {
            $schema                                                     = array_pop($schema);
        }

        return $schema;
    }

    /**
     * @param string $method
     * @param array $request
     * @return stdClass
     * @throws SoapFault
     */
    private function getResponse(string $method, array $request) : stdClass
    {
        $response                                                       = (object) @$this->client->__soapCall($method, $request);
        if (!empty($response)) {
            if (isset($response->{$method . "Result"})) {
                $response                                               = $response->{$method . "Result"};
            } elseif (isset($response->{$method})) {
                $response                                               = $response->{$method};
            }
        }

        return $response ?? new stdClass();
    }

    /**
     * @param array $headers
     * @return stdClass|null
     */
    private function getSoapHeader(array $headers = []) : ?SoapHeader
    {
        return ($this->header_namespace
            ? new SoapHeader($this->header_namespace, $this->header_name, $headers ?: null, true)
            : null
        );
    }

    /**
     * @param array $headers
     */
    private function setSoapHeader(array $headers = []) : void
    {
        $requestHeaders                                                 = $this->getSoapHeader($headers);
        if ($requestHeaders) {
            $this->client->__setSoapHeaders($requestHeaders);
        }
    }
}
