<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Mappable;
use phpformsframework\libs\Request;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\util\TypesConverter;

/**
 * Class ConfigPage
 * @package phpformsframework\libs\dto
 */
class RequestPage extends Mappable
{
    use TypesConverter;
    use Exceptionable { error as private setErrorDefault; }

    private const NAMESPACE_MAP_REQUEST     = '\\dto\\Request';

    public const REQUEST_RAWDATA            = "rawdata";
    public const REQUEST_VALID              = "valid";
    public const REQUEST_UNKNOWN            = "unknown";

    public $path_info                       = null;
    public $script_path                     = null;

    public $log                             = null;
    public $validation                      = true;
    public $nocache                         = false;
    public $https                           = null;
    public $method                          = null;
    public $root_path                       = null;
    public $namespace                       = null;
    public $map                             = null;
    public $acl                             = null;
    public $accept                          = "*/*";

    public $layout                          = null;

    /**
     * @var RequestPageRules $rules
     */
    public $rules                           = null;

    private $path2params                    = array();

    private $headers                        = array();
    private $body                           = null;



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

        $this->method               = (
            $this->method
            ? strtoupper($this->method)
            : Request::method()
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
                $config             = array_replace($pages[$path_info]["config"], $config);
                if ($script_path == DIRECTORY_SEPARATOR) {
                    $script_path    = (
                        isset($config["accept_path_info"])
                        ? $orig_path_info
                        : $path_info
                    );
                }
                $rules->set($pages[$path_info]);
            }
        } while ($path_info != DIRECTORY_SEPARATOR && $path_info = dirname($path_info));

        $this->rules                = $rules;
        $this->script_path          = $script_path;
        $this->path_info            = str_replace($script_path, "", $orig_path_info);

        $this->setPattern($config, $patterns);

        return $config;
    }

    /**
     * @param string $path_info
     * @param array|null $path2params
     * @return string
     */
    private function findEnvByPathInfo(string $path_info, array $path2params = null): string
    {
        $path_info                  = rtrim($path_info, "/");
        if (!$path_info) {
            $path_info              = DIRECTORY_SEPARATOR;
        }

        if ($path2params) {
            foreach ($path2params as $page_path => $params) {
                if (preg_match_all($params["regexp"], $path_info, $matches)) {
                    $this->path2params = array_combine($params["matches"], $matches[1]);

                    $path_info = $page_path;
                    break;
                }
            }
        }

        return $path_info;
    }
    /**
     * @param array $config
     * @param array $patterns
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
     *
     */
    public function isInvalidURL() : bool
    {
        return $this->method == Request::METHOD_GET && !Request::isAjax() && count($this->getRequestUnknown());
    }

    /**
     * @return string
     */
    public function canonicalURL() : string
    {
        $query = http_build_query($this->getRequestValid());
        return Request::protocolHostPathinfo() . ($query ? "?" . $query : "");
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
     * @param string $name
     * @param mixed|null $value
     * @return RequestPage
     */
    public function setHeader(string $name, $value = null) : self
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

    /**
     * @return bool
     */
    public function issetRequest() : bool
    {
        return (!empty($this->body));
    }

    /**
     * @param string $scope
     * @return array
     */
    public function getRequest(string $scope = self::REQUEST_RAWDATA) : array
    {
        return (isset($this->body[$scope])
            ? $this->body[$scope]
            : array()
        );
    }

    /**
     * @param string $namespace
     * @param string $method
     * @return object
     */
    public function mapRequest(string $namespace, string $method) : object
    {
        $mapRequest = $this->map ?? $namespace . self::NAMESPACE_MAP_REQUEST . ucfirst($method);
        if (class_exists($mapRequest)) {
            $obj = new $mapRequest();

            $this->autoMapping($this->getRequest(self::REQUEST_RAWDATA) + $this->getHeaders(), $obj);
        } else {
            $obj = (object) $this->getRequest(self::REQUEST_RAWDATA);
        }
        return $obj;
    }

    /**
     * @return array
     */
    public function getRequestValid() : array
    {
        return (isset($this->body[self::REQUEST_VALID])
            ? $this->body[self::REQUEST_VALID]
            : array()
        );
    }

    /**
     * @return array
     */
    public function getRequestUnknown() : array
    {
        return $this->body[self::REQUEST_UNKNOWN] ?? [];
    }

    /**
     * @param string $scope
     * @param $value
     * @param string|null $name
     * @return RequestPage
     */
    public function setRequest(string $scope, $value, string $name = null) : self
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
    public function setUnknown(array $request) : self
    {
        $this->body[self::REQUEST_UNKNOWN] = (
            isset($this->body[self::REQUEST_VALID])
            ? array_diff_key($request, $this->body[self::REQUEST_VALID])
            : $request
        );
        return $this;
    }

    /**
     * @param array $server
     * @return bool
     */
    public function loadHeaders(array $server) : bool
    {
        return $this->securityHeaderParams($server);
    }

    /**
     * @param array $request
     * @return bool
     */
    public function loadRequest(array $request) : bool
    {
        return $this->securityParams($this->path2params + $request, $this->method);
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
     * @return bool
     */
    private function securityHeaderParams(array $headers) : bool
    {
        $errors                                                                         = null;
        if ($this->isAllowedSize($this->getRequestHeaders(), Request::METHOD_HEAD)) {
            foreach ($this->rules->header as $rule) {
                $header_key                                                             = str_replace("-", "_", $rule["name"]);
                if ($rule["name"] == "Authorization") {
                    $header_name                                                        = "Authorization";
                } else {
                    $header_name                                                        = "HTTP_" . strtoupper($header_key);
                }

                $this->setHeader($header_key);
                if (isset($rule["required"]) && !isset($headers[$header_name])) {
                    $errors[400][]                                                      = $rule["name"] . " is required";
                } elseif (isset($rule["required_ifnot"]) && !isset($headers["HTTP_" . strtoupper($rule["required_ifnot"])]) && !isset($headers[$header_name])) {
                    $errors[400][]                                                      = $rule["name"] . " is required";
                } elseif (isset($headers[$header_name])) {
                    $validator_rule                                                     = (
                        isset($rule["validator"])
                        ? $rule["validator"]
                        : null
                    );
                    $validator_range                                                    = (
                        isset($rule["validator_range"])
                        ? $rule["validator_range"]
                        : null
                    );
                    $validator                                                          = Validator::is($headers[$header_name], $header_key . " (in header)", $validator_rule, $validator_range);
                    if ($validator->isError()) {
                        $errors[$validator->status][]                                   = $validator->error;
                    }

                    $this->setHeader($header_key, $headers[$header_name]);
                }
            }
        } else {
            $errors[413][]                                                              = "Headers Max Size Exceeded";
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
        $errors                                                                         = array();
        $bucket                                                                         = $this->bucketByMethod($method);

        if ($this->isAllowedSize($request, $method) && $this->isAllowedSize($this->getRequestHeaders(), Request::METHOD_HEAD)) {
            if (!empty($this->rules->$bucket)) {
                foreach ($this->rules->$bucket as $rule) {
                    if (isset($rule["required"]) && $rule["required"] === true && !isset($request[$rule["name"]])) {
                        $errors[400][]                                                  = $rule["name"] . " is required";
                    } elseif (isset($rule["required_ifnot"]) && !isset($_SERVER[$rule["required_ifnot"]]) && !isset($request[$rule["name"]])) {
                        $errors[400][]                                                  = $rule["name"] . " is required";
                    } elseif (isset($request[$rule["name"]])) {
                        $validator_rule                                                 = (
                            isset($rule["validator"])
                            ? $rule["validator"]
                            : null
                        );
                        $validator_range                                                = (
                            isset($rule["validator_range"])
                            ? $rule["validator_range"]
                            : null
                        );

                        $errors                                                         = $errors + $this->securityValidation($request[$rule["name"]], $rule["name"], $validator_rule, $validator_range);


                        if (isset($rule["scope"])) {
                            $this->setRequest($rule["scope"], $request[$rule["name"]], $rule["name"]);
                        }
                        if (!isset($rule["hide"]) || $rule["hide"] === false) {
                            $this->setRequest(self::REQUEST_VALID, $request[$rule["name"]], $rule["name"]);
                        } else {
                            unset($request[$rule["name"]]);
                        }
                    } else {
                        $request[$rule["name"]]                                         = $this->getDefault($rule);
                        $this->setRequest(self::REQUEST_VALID, $request[$rule["name"]], $rule["name"]);
                        if (isset($rule["scope"])) {
                            $this->setRequest($rule["scope"], $request[$rule["name"]], $rule["name"]);
                        }
                    }
                }
                $this->setUnknown($request);
                foreach ($this->getRequestUnknown() as $unknown_key => $unknown) {
                    $errors                                                             = $errors + $this->securityValidation($unknown, $unknown_key);
                }
            }

            $this->setRequest(self::REQUEST_RAWDATA, $request);
        } else {
            $errors[413][]                                                              = "Request Max Size Exceeded";
        }

        $this->error($errors);

        return $this->isError();
    }


    /**
     * @return bool
     */
    private function securityFileParams() : bool
    {
        $errors                                                                         = array();
        if (!empty($_FILES)) {
            foreach ($_FILES as $file_name => $file) {
                if (isset($this->rules->body[$file_name]["mime"]) && strpos($this->rules->body[$file_name]["mime"], $file["type"]) === false) {
                    $errors[400][]                                                      = $file_name . " must be type " . $this->rules->body[$file_name]["mime"];
                } else {
                    $validator                                                          = Validator::is($file_name, $file_name, "file");
                    if ($validator->isError()) {
                        $errors[$validator->status][] = $validator->error;
                    }
                }
            }
        }

        $this->error($errors);

        return $this->isError();
    }

    /**
     * @todo da tipizzare
     * @param $value
     * @param string $fakename
     * @param string|null $type
     * @param string|null $range
     * @return array
     */
    private function securityValidation(&$value, string $fakename, string $type = null, string $range = null) : array
    {
        $errors                                                                         = array();

        $validator                                                                      = Validator::is($value, $fakename, $type, $range);
        if ($validator->isError()) {
            $errors[$validator->status][]                                               = $validator->error;
        }


        return $errors;
    }


    /**
     * @todo da tipizzare
     * @param array $rule
     * @return mixed|null
     */
    private function getDefault(array $rule)
    {
        $res = null;
        if (isset($rule["default"])) {
            $res = $rule["default"];
        } elseif (isset($rule["validator"])) {
            $res = Validator::getDefault($rule["validator"]);
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
        $request_size                                                                           = strlen(http_build_query($req, '', ''));
        return $request_size < Validator::getRequestMaxSize($method);
    }

    /**
     * @param string $method
     * @return string
     */
    private function bucketByMethod(string $method) : string
    {
        return ($method == Request::METHOD_GET || $method == Request::METHOD_PUT
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
}
