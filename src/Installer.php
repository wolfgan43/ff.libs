<?php
namespace phpformsframework\libs;

use Exception;
use phpformsframework\libs\dto\ConfigRules;
use ReflectionClass;

Class Installer
{
    private static function loadRules()
    {
        $classLoader = require(Constant::LIBS_DISK_PATH . '/autoload.php');
        $namespace = rtrim(Kernel::$Environment::NAME_SPACE, '\\');
        foreach ($classLoader->getClassMap() as $file) {
            if (strpos($file, __DIR__) === 0
                || strpos($file, Constant::LIBS_DISK_PATH . DIRECTORY_SEPARATOR . $namespace) === 0
            ) {
                $content = file_get_contents($file);
                if (strpos($content, "Configurable") !== false
                    || strpos($content, " Configurable") !== false
                ) {
                }
            }
        }

        die();
    }

    private static function loadConfigurable()
    {
        $classes                                                = get_declared_classes();
        foreach ($classes as $class_name) {
            try {
                $reflect                                        = new ReflectionClass($class_name);
                if ($reflect->implementsInterface(__NAMESPACE__ . '\\Dumpable')) {
                    $parent                                     = $reflect->getParentClass();
                    if (!$parent || !isset(self::$class_dumpable[strtolower(basename(str_replace('\\', '/', $parent->getName())))])) {
                        self::$class_dumpable[strtolower(basename(str_replace('\\', '/', $class_name)))]  = $class_name;
                    }
                }

                if ($reflect->implementsInterface(__NAMESPACE__ . '\\Configurable')) {
                    $parent                                     = $reflect->getParentClass();

                    if (!$parent || !isset(self::$class_configurable[strtolower(basename(str_replace('\\', '/', $parent->getName())))])) {
                        $class_basename                         = strtolower(basename(str_replace('\\', '/', $class_name)));
                        self::$class_configurable[$class_basename] = $class_name;
                        /**
                         * @var Configurable $class_name
                         */
                        $configRules                            = new ConfigRules($class_basename);
                        self::addRules($class_name::loadConfigRules($configRules));
                    }
                }
            } catch (Exception $exception) {
                Error::register($exception->getMessage(), static::ERROR_BUCKET);
            }
        }
    }
}