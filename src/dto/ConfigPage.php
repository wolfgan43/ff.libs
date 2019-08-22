<?php
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Constant;
use phpformsframework\libs\Request;
use phpformsframework\libs\Router;

/**
 * Class ConfigPage
 * @package phpformsframework\libs\dto
 * @todo da finire tutto
 */
class ConfigPage
{
    public $path_info       = null;

    private $log            = false;
    private $strip_path     = null;
    private $validate_url   = true;
    private $nocache        = false;


    public function __construct($path_info, $config)
    {
        $page  = null;
        return $page;
    }
}