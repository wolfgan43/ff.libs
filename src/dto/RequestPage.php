<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Mappable;
use phpformsframework\libs\Request;

/**
 * Class ConfigPage
 * @package phpformsframework\libs\dto
 */
class RequestPage extends Mappable
{
    use Exceptionable { error as private setErrorDefault; }

    public const REQUEST_RAWDATA   = "rawdata";
    public const REQUEST_VALID     = "valid";
    public const REQUEST_UNKNOWN   = "unknown";


    public $path_info       = null;

    public $log             = null;
    public $validation      = true;
    public $nocache         = false;
    public $https           = null;
    public $method          = null;
    public $root_path       = null;
    public $namespace       = null;
    public $accept          = "*/*";

    public $layout          = null;

    /**
     * @var RequestPageRules $rules
     */
    public $rules           = null;

    private $headers        = array();
    private $body           = null;


    /**
     * RequestPage constructor.
     * @param string|array $map
     * @param RequestPageRules $rules
     */
    public function __construct($map, RequestPageRules $rules)
    {
        parent::__construct($map);
        $this->rules        = $rules;
        $this->method       = (
            $this->method
            ? strtoupper($this->method)
            : Request::method()
        );
    }

    /**
     * @param array|null $errors
     */
    public function error(array $errors = null) : void
    {
        if (is_array($errors) && count($errors)) {
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
     * @param object $obj|null
     * @param string $scope
     * @return object
     */
    public function mapRequest(object $obj = null, string $scope = self::REQUEST_RAWDATA) : object
    {
        if ($obj) {
            $this->autoMapping($this->getRequest($scope), $obj);
        } else {
            $obj = (object) $this->getRequest($scope);
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
        return (isset($this->body[self::REQUEST_UNKNOWN])
            ? $this->body[self::REQUEST_UNKNOWN]
            : array()
        );
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
}
