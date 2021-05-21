<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\dto\DataTableResponse;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\AdapterManager;
use stdClass;

/**
 * Class View
 * @package phpformsframework\libs\gui
 */
class View
{
    use AdapterManager;

    private const ERROR_BUCKET                      = "view";

    private $controller                             = null;
    private $config                                 = null;

    /**
     * View constructor.
     * @param string $template_disk_path
     * @param string|null $templateAdapter
     * @param Controller|null $controller
     * @param string|null $widget
     * @param stdClass|null $config
     */
    public function __construct(string $template_disk_path, string $templateAdapter = null, Controller $controller = null, string $widget = null, stdClass $config = null)
    {
        $this->config                               = $config;
        $this->controller                           =& $controller;
        $this->setAdapter($templateAdapter ?? Kernel::$Environment::TEMPLATE_ADAPTER, [$widget]);
        $this->adapter->fetch($template_disk_path);
    }

    /**
     * @param string $sectionName
     * @param bool $repeat
     * @return $this
     */
    public function parse(string $sectionName, bool $repeat = false) : View
    {
        $this->adapter->parse($sectionName, $repeat);

        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isset(string $name) : bool
    {
        return $this->adapter->isset($name);
    }

    /**
     * @param array|string|callable $data
     * @param null|string $value
     * @return $this
     */
    public function assign($data, $value = null) : View
    {
        if ($data instanceof DataTableResponse) {
            //@todo da fare
        } elseif ($data instanceof DataResponse) {
            //@todo da fare
        } else {
            $this->adapter->assign($data, $value);
        }
        
        return $this;
    }

    /**
     * @return string
     */
    public function html() : string
    {
        return $this->adapter->display();
    }

    /**
     * @return object
     */
    public function getConfig() : object
    {
        return $this->config ?? new stdClass();
    }
}
