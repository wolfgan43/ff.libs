<?php
namespace phpformsframework\libs\gui;

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

    private $config                                 = null;

    /**
     * View constructor.
     * @param stdClass|null $config
     * @param string|null $templateAdapter
     */
    public function __construct(stdClass $config = null, string $templateAdapter = null)
    {
        $this->config                               = $config;
        $this->setAdapter($templateAdapter ?? Kernel::$Environment::TEMPLATE_ADAPTER);
    }

    /**
     * @param string $template_disk_path
     * @return $this
     */
    public function fetch(string $template_disk_path) : View
    {
        $this->adapter->fetch($template_disk_path);

        return $this;
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
        if (is_callable($data)) {
            $data($this);
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
