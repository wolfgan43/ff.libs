<?php
namespace phpformsframework\libs\util;

use phpformsframework\libs\Error;

/**
 * Trait AdapterManager
 * @package phpformsframework\libs\util
 */
trait AdapterManager
{
    private $adapters   = null;
    private $adapter    = null;

    /**
     * @param string $adapterName
     */
    private function setAdapter(string $adapterName) : void
    {
        $className                                          = str_replace(__NAMESPACE__ . '\\', "", __CLASS__);

        $classNameAdapter                                   = __NAMESPACE__ . '\\adapters\\' . $className . ucfirst($adapterName);
        if (class_exists($classNameAdapter)) {
            $this->adapters[$adapterName]                   = new $classNameAdapter();
            $this->adapters                                 =& $this->adapters[$adapterName];
        } else {
            Error::register($className . " Adapter not supported: " . $adapterName);
        }
    }

    /**
     * @param array $adapters
     */
    private function setAdapters(array $adapters) : void
    {
        foreach ($adapters as $adapter) {
            $this->setAdapter($adapter);
        }
    }

    /**
     * @param string|null $adapterName
     * @return object|null
     */
    private function getAdapter(string $adapterName = null) : ?object
    {
        if (!isset($this->adapters[$adapterName])) {
            return null;
        }
        return ($adapterName
            ? $this->adapters[$adapterName]
            : $this->adapter
        );
    }
}
