<?php
/**
 * VGallery: CMS based on FormsFramework
 * Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage core
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/gpl-3.0.html
 *  @link https://github.com/wolfgan43/vgallery
 */
namespace phpformsframework\libs;

use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\storage\FilemanagerScan;
use ReflectionClass;
use ReflectionException;
use Exception;

/**
 * Class Autoloader
 * @package phpformsframework\libs
 */
class Autoloader
{
    protected const ERROR_BUCKET                                            = "config";

    private static $classes                                                 = null;

    /**
     * @param array|null $paths
     * @throws Exception
     */
    public static function register(array $paths)
    {
        $cache                                                              = Buffer::cache(static::ERROR_BUCKET);
        self::$classes                                                      = $cache->get("autoloader");
        if (self::$classes) {
            spl_autoload_register(function ($class_name) {
                if (isset(self::$classes[$class_name])) {
                    include self::$classes[$class_name];
                }
            });
        } else {
            $config_dirs                                                    = [];
            foreach ($paths as $i => $path) {
                $abs_path = Constant::DISK_PATH . $path;
                if (is_dir($abs_path)) {
                    $config_dirs[$abs_path]                                 = filemtime($abs_path);
                } else {
                    unset($paths[$i]);
                }
            }
            //$paths                                                          = array_keys($config_dirs);
            $patterns                                                       = array_fill_keys($paths, ["flag" => FilemanagerScan::SCAN_DIR_RECURSIVE]);

            FilemanagerScan::scan($patterns, function ($path) use (&$config_dirs) {
                $config_dirs[$path]                                         = filemtime($path);
            });

            self::spl($paths);

            $classes                                                        = get_declared_classes();
            $patterns                                                       = array_fill_keys($paths, ["flag" => FilemanagerScan::SCAN_FILE_RECURSIVE, "filter" => [Constant::PHP_EXT]]);
            FilemanagerScan::scan($patterns, function ($path) {
                include_once($path);
            });

            $classes                                                        = array_diff(get_declared_classes(), $classes);
            try {
                $lib_namespace                                              = substr(static::class, 0, strrpos(static::class, '\\'));
                foreach ($classes as $class_name) {
                    $class                                                  = new ReflectionClass($class_name);
                    if (strpos($class->getNamespaceName(), $lib_namespace) === false
                        && strpos($class->getNamespaceName(), Constant::NAME_SPACE) === false
                    ) {
                        self::$classes[$class_name]                         = $class->getFileName();
                    }
                }
            } catch (ReflectionException $e) {
                App::throwError($e->getCode(), $e->getMessage());
            }
            $cache->set("autoloader", self::$classes, $config_dirs);
        }
    }

    /**
     * @param string $abs_path
     * @param bool $once
     * @return mixed|null
     */
    public static function loadScript(string $abs_path, bool $once = false)
    {
        Debug::stopWatch("loadscript" . $abs_path);

        $rc                                                                 = null;
        if (Dir::checkDiskPath($abs_path)) {
            $rc                                                             = (
                $once
                ? require_once($abs_path)
                : include($abs_path)
            );
        }

        Debug::stopWatch("loadscript" . $abs_path);

        return $rc;
    }

    /**
     * @param array $paths
     */
    private static function spl(array $paths) : void
    {
        spl_autoload_register(function ($class_name) use ($paths) {
            foreach ($paths as $autoload) {
                if (self::loadScript(Constant::DISK_PATH . $autoload . DIRECTORY_SEPARATOR . self::getClassPath($class_name) . "." . Constant::PHP_EXT)) {
                    break;
                };
            }
        });
    }

    /**
     * @param string $class_name
     * @return string
     */
    private static function getClassPath(string $class_name) : string
    {
        return str_replace(array('\\'), array('/'), $class_name);
    }
}
