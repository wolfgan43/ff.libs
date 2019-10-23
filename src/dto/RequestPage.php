<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Mappable;
use phpformsframework\libs\Request;

/**
 * Class ConfigPage
 * @package phpformsframework\libs\dto
 * @todo da finire tutto
 */
class RequestPage extends Mappable
{
    public $path_info       = null;

    public $log             = false;
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

    public $headers        = array();
    public $request        = array();

    public function __construct($map)
    {
        parent::__construct($map);

        $this->method = (
            $this->method
            ? strtoupper($this->method)
            : Request::method()
        );
    }
}


