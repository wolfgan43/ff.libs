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
use phpformsframework\libs\storage\Filemanager;
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

    private static $classes                                                 = [];

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
                include self::$classes[$class_name];
            });
        } else {
            $config_dirs                                                    = [];
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $config_dirs[$path]                                     = filemtime($path);
                }
            }
            $paths                                                          = array_keys($config_dirs);
            $patterns                                                       = array_fill_keys($paths, ["flag" => Filemanager::SCAN_DIR_RECURSIVE]);
            Filemanager::scan($patterns, function ($path) use (&$config_dirs) {
                $config_dirs[$path]                                         = filemtime($path);
            });

            self::spl($paths);

            $classes                                                        = get_declared_classes();
            $patterns                                                       = array_fill_keys($paths, ["flag" => Filemanager::SCAN_FILE_RECURSIVE, "filter" => [Constant::PHP_EXT]]);
            Filemanager::scan($patterns, function ($path) {
                include_once($path);
            });

            $classes = array_diff(get_declared_classes(), $classes);
            try {
                foreach ($classes as $class_name) {
                    $class                                                      = new ReflectionClass($class_name);
                    self::$classes[$class_name]                                 = $class->getFileName();
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
                if (self::loadScript($autoload . DIRECTORY_SEPARATOR . str_replace(array('\\'), array('/'), $class_name) . "." . Constant::PHP_EXT)) {
                    break;
                };
            }
        });
    }
}
