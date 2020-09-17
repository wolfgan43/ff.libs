<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\Dir;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\AdapterManager;
use stdClass;

/**
 * Class View
 * @package phpformsframework\libs\gui
 * @property ViewAdapter adapter
 */
class View
{
    use AdapterManager;

    const ERROR_BUCKET                              = "view";

    private $config                                 = null;

    /**
     * View constructor.
     * @param array|null $config
     * @param string|null $templateAdapter
     */
    public function __construct(array $config = null, string $templateAdapter = null)
    {
        if ($config) {
            $this->config                           = json_decode(json_encode($config));
        }
        $this->setAdapter($templateAdapter ?? Kernel::$Environment::TEMPLATE_ADAPTER);
    }
    /**
     * @return string
     */
    private function getViewDiskPath() : string
    {
        return Dir::findViewPath();
    }

    /**
     * @param string $file_path
     * @param bool $is_relative
     * @return $this
     */
    public function fetch(string $file_path, bool $is_relative = false) : View
    {
        $this->adapter->fetch(
            $is_relative
            ? $this->getViewDiskPath() . $file_path
            : $file_path
        );

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
    public function display() : string
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
