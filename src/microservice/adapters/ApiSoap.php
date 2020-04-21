<?php
namespace phpformsframework\libs\microservice\adapters;

use phpformsframework\libs\Log;
use SoapClient;
use SoapHeader;
use stdClass;
use Exception;

/**
 * Class Soap
 * @package hcore\adapters
 */
class ApiSoap extends ApiAdapter
{
    protected const ERROR_LABEL     = "Api Soap: ";
    /**
     * @var SoapClient|null
     */
    private $client                 = null;

    private $schema                 = [];
    private $dtds                   = [];
    private $defaults               = [];
    private $sFunctions             = [];

    protected $header_namespace     = null;
    protected $header_name          = null;

    protected $body_namespace       = null;

    protected $wsdl                 = null;

    protected $encoding             = 'UTF-8';
    protected $wsdl_ttl             = 900;
    protected $version              = SOAP_1_2;


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
        $this->header_namespace     = $namespace;
        $this->header_name          = $root;

        return $this;
    }

    /**
     * @return SoapClient|null
     * @throws Exception
     */
    private function getClient() : ?SoapClient
    {
        if (!$this->client) {
            $options                    = array(
                'location'              => $this->endpoint,
                'uri'                   => $this->body_namespace,
                'style'                 => SOAP_RPC,
                'use'                   => SOAP_ENCODED,
                'soap_version'          => $this->version,
                'cache_wsdl'            => WSDL_CACHE_NONE,
                'connection_timeout'    => self::REQUEST_TIMEOUT,
                'trace'                 => true,
                'encoding'              => $this->encoding,
                'exceptions'            => true
            );

            if ($this->http_auth_username && $this->http_auth_secret) {
                $options                = $options + array(
                    'login'             => $this->http_auth_username,
                    'password'          => $this->http_auth_secret
                );
            }

            try {
                $this->client = new SoapClient($this->wsdl, $options);
                $this->parseSoapTypes($this->client);
            } catch (Exception $e) {
                throw new Exception(static::ERROR_LABEL . $e->getMessage(), 501);
            }
        }

        ini_set('soap.wsdl_cache_enabled', WSDL_CACHE_NONE);
        ini_set('soap.wsdl_cache_ttl', $this->wsdl_ttl);
        ini_set('default_socket_timeout', self::REQUEST_TIMEOUT);

        return $this->client;
    }
    /**
     * @return array
     */
    public function preflight() : ?array
    {
        self::$preflight[$this->endpoint] = null;

        return self::$preflight[$this->endpoint];
    }
    /**
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @return stdClass
     * @throws Exception
     */
    protected function get(string $method, array $params = null, array $headers = null) : stdClass
    {
        $client                         = $this->getClient();

        $request                        = $this->getRequest($method, $params);
        $requestHeaders                 = $this->getSoapHeader($headers);

        try {
            if (isset($this->sFunctions[$method])) {
                if ($requestHeaders) {
                    $client->__setSoapHeaders($requestHeaders);
                }

                $response               = $this->getResponse($method, $request);
            } else {
                throw new Exception(static::ERROR_LABEL . "Method not found in wsdl", 404);
            }
        } catch (Exception $e) {
            Log::debugging([
                $method => [
                    "header"            => $requestHeaders,
                    "request"           => $request,
                    "response"          => $this->sFunctions[$method]->response,
                    "dtd"               => $this->sFunctions[$method]->dtd
                ]
            ]);

            throw new Exception(static::ERROR_LABEL . $method . " (". $e->getMessage() . ")", 500);
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
                    $this->dtds[$request_name . "." . $param]           = $type; //$this->setDefault($type);
                    $this->defaults[$request_name . "." . $param]       = null; //$this->setDefault($type);

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
            $types = $soap->__getTypes();

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
                $this->dtds[$struct]                        = $this->schema[$type]["dtd"];
                $this->defaults[$struct]                    = $this->schema[$type]["default"];
            } else {
                $this->defaults[$struct]                    = $this->setDefault($type);
            }
        }
    }

    /**
     * @param string $type
     */
    private function parseSoapType(string $type) : void
    {
        $regex                                              = '/^\s(\w*?)\s(\w*?);/mi';
        $regex_struct                                       = '/struct\s(.*?)\s/';

        preg_match($regex_struct, $type, $match_struct);
        $struct = isset($match_struct[1]) ? $match_struct[1] : null;
        preg_match_all($regex, $type, $matches);

        if (isset($matches[1]) && isset($match_struct[1])) {
            $dtd = array_combine($matches[2], $matches[1]);
            foreach ($dtd as $dtd_key => $dtd_value) {
                $this->dtds[$struct . "." . $dtd_key]        = $dtd_value;
                $this->schema[$struct]["dtd"][$dtd_key]     =& $this->dtds[$struct . "." . $dtd_key];
                $this->schema[$struct]["default"][$dtd_key] =& $this->defaults[$struct . "." . $dtd_key];
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
        $schema             = $this->sFunctions[$method]->request ?? null;

        return ($schema
            ? $this->mergeRequest($schema, $params)
            : []
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
            $key = array_keys($schema)[0];
            $res            = [$key => array_replace_recursive($schema[$key], $params)];
        } else {
            $res            = array_replace_recursive($schema, $params);
        }

        return $res;
    }

    /**
     * @param string $method
     * @return array
     * @throws Exception
     */
    protected function getResponseSchema(string $method) : ?array
    {
        $this->getClient();

        $schema             = $this->schema[$this->sFunctions[$method]->response]["default"] ?? null;
        if (isset($schema[$method]) || isset($schema[$method . "Result"])) {
            $schema         = array_pop($schema);
        }

        return $schema;
    }

    /**
     * @param string $method
     * @param array $request
     * @return stdClass
     */
    private function getResponse(string $method, array $request) : stdClass
    {
        $response           = $this->client->__soapCall($method, $request);
        if (!empty($response) && isset($response->{$method . "Result"})) {
            $response       = $response->{$method . "Result"};
        }

        return $response ?? new stdClass();
    }

    /**
     * @param array|null $headers
     * @return stdClass|null
     */
    private function getSoapHeader(array $headers = null) : ?SoapHeader
    {
        $soapHeader         = null;
        if ($this->header_namespace) {
            $soapHeader     = new SoapHeader($this->header_namespace, $this->header_name, $headers, true);
        }
        return $soapHeader;
    }
}
