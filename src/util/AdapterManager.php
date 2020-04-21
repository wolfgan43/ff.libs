<?php
namespace phpformsframework\libs\util;

use phpformsframework\libs\Error;

/**
 * Trait AdapterManager
 * @package phpformsframework\libs\util
 */
trait AdapterManager
{
    private $adapters   = array();
    private $adapter    = null;

    /**
     * @param string $adapterName
     * @param array|null $args
     * @param string $class_name
     */
    private function setAdapter(string $adapterName, array $args = array(), $class_name = __CLASS__) : void
    {
        $class                                              = str_replace(array('\\drivers\\','\\'), array('\\', '/'), $class_name);
        $className                                          = basename($class);
        $nameSpace                                          = str_replace('/', '\\', dirname($class));
        $classNameAdapter                                   = $nameSpace . '\\adapters\\' . $className . ucfirst($adapterName);
        if (class_exists($classNameAdapter)) {
            $this->adapters[$adapterName]                   = new $classNameAdapter(...$args);
            $this->adapter                                  =& $this->adapters[$adapterName];
        } else {
            Error::register($class_name . " Adapter not supported: " . $classNameAdapter);
        }
    }

    /**
     * @param string|null $adapterName
     * @return object|null
     */
    public function getAdapter(string $adapterName = null) : ?object
    {
        if ($adapterName && !isset($this->adapters[$adapterName])) {
            return null;
        }
        return ($adapterName
            ? $this->adapters[$adapterName]
            : $this->adapter
        );
    }
}
