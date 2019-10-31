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
     */
    private function setAdapter(string $adapterName, array $args = array()) : void
    {
        $class                                              = str_replace('\\', '/', __CLASS__);
        $className                                          = basename($class);
        $nameSpace                                          = str_replace('/', '\\', dirname($class));
        $classNameAdapter                                   = $nameSpace . '\\adapters\\' . $className . ucfirst($adapterName);
        if (class_exists($classNameAdapter)) {
            $this->adapters[$adapterName]                   = new $classNameAdapter(...$args);
            $this->adapter                                  =& $this->adapters[$adapterName];
        } else {
            Error::register(__CLASS__ . " Adapter not supported: " . $classNameAdapter);
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
