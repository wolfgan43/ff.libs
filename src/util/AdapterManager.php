<?php
namespace phpformsframework\libs\util;

/**
 * Trait AdapterManager
 * @package phpformsframework\libs\util
 */
trait AdapterManager
{
    private $adapters                                       = array();
    private $adapter                                        = null;

    /**
     * @param string $adapterName
     * @param array|null $args
     * @param string $class_name
     * @return mixed
     */
    private static function loadAdapter(string $adapterName, array $args = array(), $class_name = __CLASS__) : object
    {
        $class                                              = str_replace(array('\\drivers\\','\\'), array('\\', '/'), $class_name);
        $className                                          = basename($class);
        $nameSpace                                          = str_replace('/', '\\', dirname($class));
        $classNameAdapter                                   = $nameSpace . '\\adapters\\' . $className . ucfirst($adapterName);

        if (!class_exists($classNameAdapter)) {
            die($class_name . " Adapter not supported: " . $classNameAdapter);
        }

        return new $classNameAdapter(...$args);
    }

    /**
     * @param string $adapterName
     * @param array $args
     * @param string $class_name
     * @return object
     */
    private function setAdapter(string $adapterName, array $args = array(), $class_name = __CLASS__) : object
    {
        $this->adapters[$adapterName]                       = $this->loadAdapter($adapterName, $args, $class_name);
        $this->adapter                                      =& $this->adapters[$adapterName];

        return $this->adapter;
    }
}
