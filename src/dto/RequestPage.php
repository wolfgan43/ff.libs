<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Mappable;
use phpformsframework\libs\Request;
use phpformsframework\libs\Router;

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
    public $script_path     = null;

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
     * @param string $script_path
     * @param array $pages
     * @param array|null $patterns
     */
    public function __construct(string $script_path, array $pages, array $patterns = null)
    {
        parent::__construct($this->setRules($script_path, $pages, $patterns));

        $this->method       = (
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
        $script_path                = null;
        $rules                      = new RequestPageRules();
        do {
            if (isset($pages[$path_info])) {
                $config     = array_replace($pages[$path_info]["config"], $config);
                if (!$script_path) {
                    $script_path    = $path_info;
                }
                $rules->set($pages[$path_info]);
            }
        } while ($path_info != DIRECTORY_SEPARATOR && $path_info = dirname($path_info));

        $this->rules                = $rules;
        $this->script_path          = $script_path;
        $this->path_info            = str_replace($script_path, "", Request::pathinfo());

        $this->setPattern($config, $patterns);

        return $config;
    }

    /**
     * @param array $config
     * @param array $patterns
     */
    private function setPattern(array &$config, array $patterns = null) : void
    {
        if ($patterns) {
            foreach ($patterns as $pattern => $rule) {
                if (preg_match(Router::regexp($pattern), $this->script_path, $matches)) {
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
     * @param string $path_info
     * @return RequestPage
     */
    public function setPathInfo(string $path_info) : self
    {
        $this->path_info = $path_info;

        return $this;
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
