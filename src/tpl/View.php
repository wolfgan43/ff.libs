<?php
namespace phpformsframework\libs\tpl;

use phpformsframework\libs\Error;
use phpformsframework\libs\Kernel;

/**
 * Class View
 * @package phpformsframework\libs\tpl
 */
class View
{
    const ERROR_BUCKET                              = "view";
    const NAME_SPACE                                = __NAMESPACE__ . '\\adapters\\';

    /**
     * @var ViewAdapter
     */
    private $adapter                                = null;

    /**
     * View constructor.
     * @param string|null $templateAdapter
     */
    public function __construct(string $templateAdapter = null)
    {
        if (!$templateAdapter) {
            $templateAdapter                        = Kernel::$Environment::TEMPLATE_ADAPTER;
        }

        $class_name                                 = static::NAME_SPACE . "View" . ucfirst($templateAdapter);
        if (class_exists($class_name)) {
            $this->adapter                          = new $class_name();
        } else {
            Error::register("Template Adapter not supported: " . $templateAdapter, static::ERROR_BUCKET);
        }
    }

    /**
     * @param string $file_disk_path
     * @return $this
     */
    public function fetch(string $file_disk_path) : View
    {
        $this->adapter->fetch($file_disk_path);

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
}
