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
namespace ff\libs\dto;

use ff\libs\Mappable;
use ff\libs\Request;
use ff\libs\security\Validator;
use ff\libs\security\ValidatorFile;
use ff\libs\util\ServerManager;
use ff\libs\util\TypesConverter;
use stdClass;

/**
 * Class ConfigPage
 * @package ff\libs\dto
 */
class RequestPage extends Mappable
{
    use TypesConverter;
    use Exceptionable { error as private setErrorDefault; }
    use ServerManager;

    private const LAYOUT_EXCEPTION          = "none";
    private const ERROR_IS_REQUIRED         = " is required";

    public const REQUEST_RAWDATA            = "rawdata";
    public const REQUEST_VALID              = "valid";
    public const REQUEST_UNKNOWN            = "unknown";

    public $path_info                       = null;
    public $script_path                     = null;

    public $log                             = null;     //gestito in request
    public $nocache                         = false;    //gestito in kernel
    public $https                           = null;     //gestito in request
    public $method                          = null;     //gestito in request
    public $controller                      = null;     //gestito in request

    public $namespace                       = null;     //gestito in api
    public $map                             = null;     //gestito in self
    public $acl                             = null;     //gestito in secureManager
    public $accept                          = "*/*";    //gestito in request
    public $accept_path_info                = false;    //gestito in self

    public $isXhr                           = null;     //gestito in self (impostato nel costruttore)
    public $layout                          = null;     //gestito in controller
    public $layout_exception                = self::LAYOUT_EXCEPTION;     //gestito in controller
    public $layout_type                     = null;     //gestito in controller
    public $title                           = null;     //gestito in controller
    public $access                          = null;     //gestito in self && api
    public $vpn                             = null;     //gestito in self
    public $csrf                            = null;     //gestito in self
    public $exception                       = true;     //gestito in self

    public $onLoad                          = null;     //gestito in self

    /**
     * @var RequestPageRules $rules
     */
    public $rules                           = null;

    private $path2params                    = [];
    private $file2params                    = [];

    private $authorization                  = null;
    private $headers                        = [];
    private $body                           = [
                                                self::REQUEST_RAWDATA   => [],
                                                self::REQUEST_VALID     => [],
                                                self::REQUEST_UNKNOWN   => []
                                            ];

    private $su                             = [];

    private $rawdata                        = [];
    /**
     * RequestPage constructor.
     * @param string $path_info
     * @param array $pages
     * @param array|null $path2params
     * @param array|null $patterns
     */
    public function __construct(string $path_info, array $pages, array $path2params = null, array $patterns = null)
    {
        parent::__construct($this->setRules($this->findEnvByPathInfo($path_info, $path2params), $pages, $patterns));

        $this->isXhr                = self::isXhr();
        $this->method               = (
            $this->method
            ? strtoupper($this->method)
            : self::requestMethod() ?? Request::METHOD_GET
        );
    }

    /**
     * @param string $path_info
     * @param array $pages
     * @param array|null $patterns
     * @return array
     */
    private function setRules(string $path_info, array $pages, array $patterns = null): array
    {
        $config                     = array();
        $script_path                = DIRECTORY_SEPARATOR;
        $rules                      = new RequestPageRules();
        $orig_path_info             = $path_info;

        do {
            if (isset($pages[$path_info])) {
                if ($script_path == DIRECTORY_SEPARATOR) {
                    $script_path    = $path_info;
                }

                if (empty($config["map"])
                    || (isset($pages[$path_info]["config"]["map"]) && $config["map"] == $pages[$path_info]["config"]["map"])
                ) {
                    $rules->set($pages[$path_info]);
                }
                $config             = array_replace($pages[$path_info]["config"], $config);
            }
        } while ($path_info != DIRECTORY_SEPARATOR && $path_info != "." && $path_info = dirname($path_info));

        $this->rules                = $rules;
        $this->script_path          = $script_path;
        $this->path_info            = $this->path_info ?? preg_replace('#^' . $script_path . '#i', "", $orig_path_info, 1);
        $this->setPattern($config, $patterns);

        if (empty($config["layout"])) {
            $config["layout"] = $config["layout_exception"] ?? self::LAYOUT_EXCEPTION;
        }

        return $config;
    }

    /**
     * @param string $path_info
     * @param array|null $path2params
     * @return string
     */
    private function findEnvByPathInfo(string $path_info, array $path2params = null): string
    {
        $path_info                      = rtrim($path_info, DIRECTORY_SEPARATOR) ?: DIRECTORY_SEPARATOR;
        if ($path2params) {
            foreach ($path2params as $page_path => $params) {
                if (preg_match($params["regexp"], $path_info, $matches)) {
                    $this->path_info    = substr($path_info, strlen($matches[0]));
                    unset($matches[0]);
                    $i = 0;
                    foreach ($matches as $value) {
                        $this->path2params[$params["matches"][$i]] = $value;
                        $i++;
                    }
                    if ($page_path != DIRECTORY_SEPARATOR) {
                        $path_info      = $page_path;
                    }
                    break;
                }
            }
        }

        return $path_info ?: DIRECTORY_SEPARATOR;
    }

    /**
     * @param array $config
     * @param array|null $patterns
     */
    private function setPattern(array &$config, array $patterns = null) : void
    {
        if ($patterns) {
            foreach ($patterns as $pattern => $rule) {
                if (preg_match($this->regexp($pattern), $this->script_path, $matches)) {
                    $config = (
                        !$this->path_info
                        ? array_replace($rule, $config)
                        : array_replace($config, $rule)
                    );
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function suRequired(): bool
    {
        return !empty($this->su);
    }

    /**
     * @param array|null $errors
     */
    public function error(array $errors = null) : void
    {
        if (!empty($errors)) {
            asort($errors);
            $status = key($errors);

            $this->setErrorDefault($status, implode(", ", $errors[$status]));
        }
    }

    /**
     * @return string|null
     */
    public function getAuthorization() : ?string
    {
        return $this->authorization;
    }

    /**
     * @param string $name
     * @param mixed|null $value
     * @return RequestPage
     */
    private function setHeader(string $name, $value = null) : self
    {
        $this->headers[$name]         = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getRawData() : array
    {
        return $this->rawdata;
    }

    /**
     * @return array
     */
    public function getRequest() : array
    {
        return $this->body[self::REQUEST_RAWDATA];
    }

    /**
     * @return object
     */
    public function mapRequest() : object
    {
        $mapRequest = $this->map ?? stdClass::class;

        if (is_subclass_of($mapRequest, Dto::class)) {
            //@todo da utilizzare il namespace per differenziare i progetti estesi. al momento il true non è corretto
            $obj = new $mapRequest($this, true);
        } elseif ($mapRequest == stdClass::class || !class_exists($mapRequest)) {
            //@todo da sistemare una volta propagata l'extend del dto
            //$obj = new Dto($this, true);
            $obj = (object)$this->getRequest();
        } else {
            $obj = new $mapRequest();
            $this->autoMapping($this->getRequest(), $obj);
        }
        return $obj;
    }

    /**
     * @return array
     */
    public function getRequestValid() : array
    {
        return $this->body[self::REQUEST_VALID];
    }

    /**
     * @return array
     */
    public function getRequestUnknown() : array
    {
        return $this->body[self::REQUEST_UNKNOWN];
    }

    /**
     * @param string $scope
     * @param $value
     * @param string|null $name
     * @return RequestPage
     */
    private function setRequest(string $scope, $value, string $name = null) : self
    {
        if ($name) {
            $this->body[$scope][$name]  = $value;
        } else {
            $this->body[$scope]         = $value;
        }

        return $this;
    }

    /**
     * @param array $request
     * @return RequestPage
     */
    private function setUnknown(array $request) : self
    {
        $this->body[self::REQUEST_UNKNOWN] = (
            isset($this->body[self::REQUEST_VALID])
            ? array_diff_key($request, $this->body[self::REQUEST_VALID])
            : $request
        );
        return $this;
    }

    /**
     * @param string|null $authorization
     * @return void
     * @todo da mettere il base64
     */
    public function loadAuthorization(string $authorization = null) : void
    {
        $this->authorization = urldecode($authorization);
    }

    /**
     * @param array|null $server
     * @param bool $isCli
     * @return bool
     */
    public function loadHeaders(array $server = null, bool $isCli = false) : bool
    {
        return ($server && $this->securityHeaderParams($server, $isCli)
        );
    }

    /**
     * @param array $request
     * @return bool
     */
    public function loadRequest(array $request) : bool
    {
        $this->rawdata = $request;

        return $this->securityParams($this->path2params + $this->file2params + $request, $this->method);
    }
    /**
     * @return bool
     */
    public function loadRequestFile() : bool
    {
        return $this->securityFileParams();
    }

    /**
     * @param array $headers
     * @param bool $isCli
     * @return bool
     */
    private function securityHeaderParams(array $headers, bool $isCli = false) : bool
    {
        $errors                                                                             = array();
        if ($this->isAllowedSize($this->getRequestHeaders(), Request::METHOD_HEAD)) {
            if (!empty($this->rules->header)) {
                foreach ($this->rules->header as $rule) {
                    $rule                                                                   = (object) $rule; //@todo necessario per design pattern
                    $header_key                                                             = $rule->name;
                    if ($isCli) {
                        $header_name                                                        = $header_key;
                    } else {
                        $header_name                                                        = "HTTP_" . strtoupper($header_key);
                    }

                    $this->setHeader($header_key);
                    if (isset($rule->required) && $rule->required === true && (!isset($headers[$header_name]) || $headers[$header_name] === "")) {
                        $errors[400][]                                                      = $rule->name . self::ERROR_IS_REQUIRED;
                    } elseif (isset($rule->required_ifnot) && !isset($headers["HTTP_" . strtoupper($rule->required_ifnot)]) && !isset($headers[$header_name])) {
                        $errors[400][]                                                      = $rule->name . self::ERROR_IS_REQUIRED;
                    } elseif (isset($headers[$header_name])) {
                        $this->securityValidation(
                            $errors,
                            $headers[$header_name],
                            $header_key . " (in header)",
                            $rule->validator        ?? null,
                            $rule->validator_range ?? $rule->validator_mime ?? null
                        );
                        $this->setHeader($header_key, $headers[$header_name]);
                    }
                }
            }
        } else {
            $errors[413][]                                                                  = "Headers Max Size Exceeded";
        }

        $this->error($errors);

        return $this->isError();
    }

    /**
     * @param array $request
     * @param string $method
     * @return bool
     */
    private function securityParams(array $request, string $method) : bool
    {
        $errors                                                                             = array();
        $bucket                                                                             = $this->bucketByMethod($method);

        if ($this->isAllowedSize($request, $method) && $this->isAllowedSize($this->getRequestHeaders(), Request::METHOD_HEAD)) {
            $rawdata                                                                        = $this->normalizeRawData($request);

            if (!empty($this->rules->$bucket)) {
                foreach ($this->rules->$bucket as $key => $rule) {
                    $rule                                                                   = (object) $rule; //@todo necessario per design pattern
                    if (isset($rule->required) && $rule->required === true && (!isset($request[$key]) || $request[$key] === "")) {
                        $errors[400][]                                                      = $key . self::ERROR_IS_REQUIRED;
                    } elseif (isset($rule->required_ifnot) && !isset($_SERVER[$rule->required_ifnot]) && !isset($request[$key])) {
                        $errors[400][]                                                      = $key . self::ERROR_IS_REQUIRED;
                    } elseif (isset($request[$key])) {
                        $this->securityValidation(
                            $errors,
                            $request[$key],
                            $rule->name,
                            $rule->validator        ?? null,
                            $rule->validator_range ?? $rule->validator_mime ?? null
                        );

                        if (!isset($rule->hide) || $rule->hide === false) {
                            $this->setRequest(self::REQUEST_VALID, $request[$key], $rule->name);
                        } else {
                            unset($request[$key]);
                        }
                    } else {
                        $request[$key]                                                      = $this->getDefault($rule);
                        $this->setRequest(self::REQUEST_VALID, $request[$key], $rule->name);
                    }

                    if (!empty($rule->isOwner) && isset($this->path2params[$key])) {
                        $this->su[$key]                                                   = true;
                    }
                }

                $this->setUnknown($rawdata);
                foreach ($this->getRequestUnknown() as $unknown_key => $unknown) {
                    $this->securityValidation($errors, $unknown, $unknown_key);
                }
                $this->setRequest(self::REQUEST_RAWDATA, $this->body[self::REQUEST_VALID] + $this->body[self::REQUEST_UNKNOWN]);
            } else {
                foreach ($rawdata as $rawdata_key => $rawdata_value) {
                    $this->securityValidation($errors, $rawdata_value, $rawdata_key);
                }
                $this->setRequest(self::REQUEST_RAWDATA, $rawdata);
            }
        } else {
            $errors[413][]                                                                  = $method . " Max Size Exceeded";
        }

        $this->error($errors);

        return $this->isError();
    }

    /**
     * @param array $request
     * @return array
     */
    private function normalizeRawData(array $request) : array
    {
        $rawdata                                                                            = [];
        foreach ($request as $key => $value) {
            $rawdata[str_replace("-", "_", $key)]                              = $value;
        }

        return $rawdata;
    }

    /**
     * @return bool
     */
    private function securityFileParams() : bool
    {
        if (!empty($_FILES)) {
            $errors                                                                         = array();
            foreach ($_FILES as $file_name => $file) {
                $this->file2params[$file_name]                                              = $file["name"];

                $rule                                                                       = (object) ($this->rules->body[$file_name] ?? null); //@todo necessario per design pattern

                if ($error = ValidatorFile::check($file_name, $rule->validator_mime ?? null)) {
                    $errors[400][]                                       = $error;
                }
            }

            $this->error($errors);
        }



        return $this->isError();
    }

    /**
     * @param array $errors
     * @param $value
     * @param string $context
     * @param string|null $type
     * @param string|null $range
     * @return array
     * @todo da tipizzare
     */
    private function securityValidation(array &$errors, &$value, string $context, string $type = null, string $range = null) : array
    {
        $validator                                                                          = Validator::is($value, $context, $type, $range);
        if ($validator->isError()) {
            $errors[$validator->status][]                                                   = $validator->error;
        }


        return $errors;
    }


    /**
     * @todo da tipizzare
     * @param stdClass $rule
     * @return mixed|null
     */
    private function getDefault(stdClass $rule)
    {
        $res = null;
        if (isset($rule->default)) {
            $res = $rule->default;
        } elseif (isset($rule->validator)) {
            $res = Validator::getDefault($rule->validator);
        }
        return $res;
    }

    /**
     * @param array $req
     * @param string $method
     * @return bool
     */
    private function isAllowedSize(array $req, string $method) : bool
    {
        $request_size                                                                       = strlen(http_build_query($req, '', ''));
        return $request_size < Validator::getRequestMaxSize($method);
    }

    /**
     * @param string $method
     * @return string
     */
    private function bucketByMethod(string $method) : string
    {
        return ($method == Request::METHOD_GET || $method == Request::METHOD_PUT || $method == Request::METHOD_PATCH || $method == Request::METHOD_DELETE
            ? "query"
            : "body"
        );
    }

    /**
     * @return array
     */
    private function getRequestHeaders() : array
    {
        if (function_exists("getallheaders")) {
            $headers = getallheaders();
        } else {
            $headers = array();
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) <> 'HTTP_') {
                    continue;
                }
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * @return string
     */
    private function pageAccept() : string
    {
        if ($this->isCli()) {
            return "php/cli";
        }

        return ($this->isXhr() && $this->accept == "*/*"
            ? "application/json"
            : $this->accept
        );
    }
    /**
     * @return string
     */
    public function accept() : string
    {
        $accept = $this->rawAccept();
        return ($accept
            ? explode(",", $accept)[0]
            : $this->pageAccept()
        );
    }

    /**
     * @return bool
     */
    public function isAjax() : bool
    {
        return $this->isXhr || $this->accept() == "application/json";
    }

    /**
     * @return string
     */
    public function urlReferer() : string
    {
        return $this->referer() ?? dirname($this->script_path);
    }
}
