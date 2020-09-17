<?php
namespace phpformsframework\libs\storage;

use phpformsframework\libs\Constant;
use phpformsframework\libs\Debug;

use phpformsframework\libs\Dumpable;
use stdClass;

/**
 * Class FilemanagerScan
 * @package phpformsframework\libs\storage
 */
class FilemanagerScan implements Dumpable
{
    private const STORAGE_RAWDATA                                       = "rawdata";

    private static $storage                                             = null;
    private static $scanExclude                                         = null;

    /**
     * @var null|callable $callback
     */
    private static $callback                                            = null;
    private static $patterns                                            = null;

    public const SCAN_DIR                                               = 1;
    public const SCAN_DIR_RECURSIVE                                     = 2;
    public const SCAN_FILE                                              = 4;
    public const SCAN_FILE_RECURSIVE                                    = 8;
    public const SCAN_ALL                                               = 16;
    public const SCAN_ALL_RECURSIVE                                     = 32;


    public static function dump(): array
    {
        return [
            "patterns"  => self::$patterns,
            "storage"   => self::$storage
        ];
    }

    /**
     * @param array $patterns
     * @param callable|null $callback
     * @return array|null
     */
    public static function scan(array $patterns, callable $callback = null) : ?array
    {
        Debug::stopWatch("filemanager/scan");

        self::$callback             = $callback;
        self::$patterns[]           = $patterns;

        foreach ($patterns as $pattern => $opt) {
            self::scanRun($pattern, (object) $opt);
        }

        Debug::stopWatch("filemanager/scan");

        return self::$storage;
    }

    /**
     * @param string $path
     * @param stdClass|null $opt
     */
    private static function scanRun(string $path, stdClass $opt = null) : void
    {
        $pattern = Constant::DISK_PATH
            . $path
            . (
                strpos($path, "*") === false
                ? '/*'
                : ''
            );

        switch ($opt->flag) {
            case self::SCAN_DIR:
                if (self::$callback) {
                    self::globDirCallback($pattern);
                } else {
                    self::globDir($pattern);
                }
                break;
            case self::SCAN_DIR_RECURSIVE:
                self::globDirRecursive($pattern);
                break;
            case self::SCAN_ALL:
                $opt->dir = true;
                self::glob($pattern, $opt);
                break;
            case self::SCAN_ALL_RECURSIVE:
                $opt->dir = true;
                self::globFilterRecursive($pattern, $opt);
                break;
            case self::SCAN_FILE:
                self::glob($pattern, $opt);
                break;
            case self::SCAN_FILE_RECURSIVE:
                self::globFilterRecursive($pattern, $opt);
                break;
            case null:
                self::globRecursive($pattern);
                break;
            default:
                die("Scan Type not Implemented for: " . $path);
        }
    }

    /**
     * @param string $pattern
     */
    private static function globDir(string $pattern) : void
    {
        self::$storage[self::STORAGE_RAWDATA] = glob($pattern, GLOB_ONLYDIR);
    }

    /**
     * @param string $pattern
     */
    private static function globDirCallback(string $pattern) : void
    {
        foreach (glob($pattern, GLOB_ONLYDIR) as $file) {
            self::scanAddItem($file);
        }
    }

    /**
     * @param string $pattern
     */
    private static function globDirRecursive(string $pattern) : void
    {
        foreach (glob($pattern, GLOB_ONLYDIR) as $file) {
            self::scanAddItem($file);
            self::globDirRecursive($file . '/*');
        }
    }

    /**
     * @param string $pattern
     * @param stdClass|null $opt
     */
    private static function glob(string $pattern, stdClass $opt = null) : void
    {
        $flags = null;
        $limit = null;
        if (is_array($opt->filter)) {
            $flags = GLOB_BRACE;
            $limit = ".{" . implode(",", $opt->filter) . "}";
            unset($opt->filter);
        }

        foreach (glob($pattern . $limit, $flags) as $file) {
            if (!empty($opt->dir) || is_file($file)) {
                self::scanAddItem($file, $opt);
            }
        }
    }

    /**
     * @param string $pattern
     */
    private static function globRecursive(string $pattern) : void
    {
        foreach (glob($pattern) as $file) {
            if (is_file($file)) {
                self::scanAddItem($file);
            } else {
                self::globRecursive($file . '/*');
            }
        }
    }

    /**
     * @param string $pattern
     * @param stdClass|null $opt
     */
    private static function globFilterRecursive(string $pattern, stdClass $opt = null) : void
    {
        $final_dir = basename(dirname($pattern)); //todo:: da togliere
        if (isset(self::$scanExclude[$final_dir])) {
            return;
        }
        foreach (glob($pattern) as $file) {
            if (is_file($file)) {
                self::scanAddItem($file, $opt);
            } else {
                if (!empty($opt->dir)) {
                    self::scanAddItem($file);
                }
                self::globFilterRecursive($file . '/*', $opt);
            }
        }
    }

    /**
     * @param stdClass $file_info
     * @param stdClass|null $opt
     * @return bool
     */
    private static function scanIsInvalidItem(stdClass $file_info, stdClass $opt = null) : bool
    {
        if (!empty($opt->filter) && !in_array($file_info->extension, $opt->filter)) {
            return true;
        }
        if (!empty($opt->name) && !in_array($file_info->basename, $opt->name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $file
     * @param stdClass|null $opt
     */
    private static function scanAddItem(string $file, stdClass $opt = null) : void
    {
        $file_info = (object) pathinfo($file);
        if (self::$callback) {
            if (self::scanIsInvalidItem($file_info, $opt)) {
                return;
            }

            (self::$callback)($file, self::$storage);
        } elseif (!$opt) {
            self::$storage[self::STORAGE_RAWDATA][] = $file;
        } else {
            if (self::scanIsInvalidItem($file_info, $opt)) {
                return;
            }

            if (isset($opt->type)) {
                self::setStorage($file_info, $opt);
            } else {
                self::$storage[self::STORAGE_RAWDATA][] = $file;
            }
        }
    }

    /**
     * @param stdClass $file_info
     * @param stdClass $opt
     */
    private static function setStorage(stdClass $file_info, stdClass $opt) : void
    {
        $file                                           = $file_info->dirname . DIRECTORY_SEPARATOR . $file_info->basename;
        $type                                           = $opt->type ?? "unknowns";

        $file_info->parentname                          = basename($file_info->dirname);
        if (isset($opt->rootpath)) {
            $file_info->rootpath                        = realpath($file_info->dirname . DIRECTORY_SEPARATOR . $opt->rootpath);
            $file_info->rootname                        = basename($file_info->rootpath);
        }

        if (isset($opt->replace[$file_info->extension])) {
            $file_info->filename                        = str_replace("." . $opt->replace[$file_info->extension], "", $file_info->filename);
            $file_info->extension                       = $opt->replace[$file_info->extension];
        }

        $arrFileInfo                                    = (array) $file_info;
        $key                                            = (
            isset($opt->prototype)
            ? str_replace(array_keys($arrFileInfo), array_values($arrFileInfo), $opt->prototype)
            : $file_info->filename
        );

        if (isset($opt->groupby)) {
            $arrFileInfo["/"]                           = '"]["';
            $group                                      = str_replace(array_keys($arrFileInfo), array_values($arrFileInfo), $opt->groupby);
            eval('self::$storage[$type]["' . $group . '"][$key]   = $file;');
        } else {
            self::$storage[$type][$key]                 = $file;
        }
    }
}
