<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs;

use ff\libs\cache\Buffer;
use ff\libs\storage\FilemanagerScan;

/**
 * Class Autoloader
 * @package ff\libs
 */
class Autoloader implements Dumpable
{
    protected const ERROR_BUCKET                                            = "config";

    private static $includes                                                = null;
    private static $classes                                                 = null;

    /**
     * @param array $paths
     */
    public static function register(array $paths)
    {
        $cache                                                              = Buffer::cache(static::ERROR_BUCKET);
        if ($autoloader = $cache->get("autoloader")) {
            self::$includes                                                 = $autoloader["includes"];
            self::$classes                                                  = $autoloader["classes"];
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
            $patterns                                                       = array_fill_keys($paths, ["flag" => FilemanagerScan::SCAN_DIR_RECURSIVE]);

            FilemanagerScan::scan($patterns, function ($path) use (&$config_dirs) {
                $config_dirs[$path]                                         = filemtime($path);
            });

            $includes_paths = [];
            FilemanagerScan::scan(array_fill_keys($paths, [
                "flag"      => FilemanagerScan::SCAN_FILE_RECURSIVE,
                "filter"    => [Constant::PHP_EXT],
                "callback"  => function ($fileinfo, $opt) use (&$includes_paths) {
                    $includes_paths[$fileinfo->dirname . DIRECTORY_SEPARATOR . $fileinfo->basename] = $opt->rootname;
                }
            ]));
            self::tokenize($includes_paths, true);

            $cache->set("autoloader", ["includes" => self::$includes, "classes" => self::$classes], $config_dirs);
        }

        spl_autoload_register(function ($class_name) {
            if (isset(self::$includes[$class_name])) {
                include self::$includes[$class_name];
            }
        });
    }

    /**
     * @param array $include_paths
     * @return array
     */
    public static function includes2Classes(array $include_paths) : array
    {
        return self::tokenize(array_fill_keys($include_paths, null));
    }

    /**
     * @param array $include_paths
     * @param bool $store
     * @return array
     */
    private static function tokenize(array $include_paths, bool $store = false) : array
    {
        $classes = [];
        foreach ($include_paths as $include_path => $group) {
            $buffer = '';
            $namespace = '';
            $class = '';

            if ($fp = fopen($include_path, 'r')) {
                $offset = 0;
                while (!feof($fp)) {
                    $buffer .= fread($fp, 512);

                    if (($offset = strpos($buffer, '{', intval($offset))) === false) {
                        continue;
                    }

                    $namespace = '';
                    $class = '';

                    $tokens = @token_get_all($buffer);
                    error_clear_last();

                    for ($i = 0; $i < count($tokens); $i++) {
                        if (!$store && $tokens[$i][0] === T_ABSTRACT) {
                            break;
                        }

                        if ($tokens[$i][0] === T_NAMESPACE) {
                            for ($j = $i + 1; $j < count($tokens); $j++) {
                                if (defined("T_NAME_QUALIFIED") && ($tokens[$j][0] === T_NAME_QUALIFIED || $tokens[$j][0] === T_NAME_FULLY_QUALIFIED)) {
                                    $namespace = $tokens[$j][1];
                                    break;
                                } elseif ($tokens[$j][0] === T_STRING) {
                                    $namespace .= '\\' . $tokens[$j][1];
                                } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                    break;
                                }
                            }
                        }

                        if ($tokens[$i][0] === T_CLASS || $tokens[$i][0] === T_INTERFACE || $tokens[$i][0] === T_TRAIT) {
                            for ($j = $i + 1; $j < count($tokens); $j++) {
                                if ($tokens[$j][0] === T_STRING) {
                                    $class = $tokens[$j][1];
                                    break 3;
                                }
                                if ($tokens[$j] === '{') {
                                    $class = $tokens[$i + 2][1];
                                    break 3;
                                }
                            }
                        }
                    }
                }
                fclose($fp);
            }

            if ($class) {
                $classes[$class] = ltrim($namespace . "\\" . $class, "\\");
                if ($store) {
                    self::$includes[$classes[$class]] = $include_path;
                    self::$classes[$group][$class] = $classes[$class];
                }
            }
        }

        return  $classes;
    }

    /**
     * @param string $name
     * @param string $bucket
     * @return string|null
     */
    public static function getClass(string $name, string $bucket) : ?string
    {
        return self::$classes[$bucket][strtolower($name)] ?? null;
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
                ? @require_once($abs_path)
                : @include($abs_path)
            );
        }

        Debug::stopWatch("loadscript" . $abs_path);

        return ($rc === false || $rc === 1 ? null : $rc);
    }

    public static function dump(): array
    {
        return [
            "includes" => self::$includes,
            "classes" => self::$classes
        ];
    }
}
