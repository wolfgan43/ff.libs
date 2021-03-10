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
    private static $storage                                             = null;
    private static $rawdata                                             = [];

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
            "storage"   => self::$storage,
            "rawdata"   => self::$rawdata
        ];
    }

    /**
     * @param array $patterns
     * @param callable|null $callback
     * @return array
     */
    public static function scan(array $patterns, callable $callback = null) : array
    {
        Debug::stopWatch("filemanager/scan");

        self::$callback             = $callback;
        self::$patterns[]           = $patterns;

        foreach ($patterns as $pattern => $opt) {
            self::scanRun($pattern, (object) $opt);
        }

        Debug::stopWatch("filemanager/scan");

        return self::$storage ?? self::$rawdata;
    }

    /**
     * @param string $path
     * @param stdClass|null $opt
     */
    private static function scanRun(string $path, stdClass $opt = null) : void
    {
        $pattern            = Constant::DISK_PATH . $path .
            (
                strpos($path, "*") === false
                ? '/*'
                : ''
            );

        $opt->pattern       = $pattern;
        $opt->pattername    = basename($opt->pattern);
        $opt->rootname      = basename(dirname($pattern));

        switch ($opt->flag) {
            case self::SCAN_DIR:
                if (self::$callback) {
                    self::globDirCallback($pattern, $opt);
                } else {
                    self::globDir($pattern, $opt);
                }
                break;
            case self::SCAN_DIR_RECURSIVE:
                self::globDirRecursive($pattern, $opt);
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
     * @param stdClass|null $opt
     */
    private static function globDir(string $pattern, stdClass $opt = null) : void
    {
        if ($opt->type) {
            self::$storage[$opt->type] = glob($pattern, GLOB_ONLYDIR);
        } else {
            self::$rawdata = glob($pattern, GLOB_ONLYDIR);
        }
    }

    /**
     * @param string $pattern
     * @param stdClass|null $opt
     */
    private static function globDirCallback(string $pattern, stdClass $opt = null) : void
    {
        foreach (glob($pattern, GLOB_ONLYDIR) as $file) {
            if (isset($opt->exclude) && in_array(basename($file), $opt->exclude)) {
                continue;
            }

            self::scanAddItem($file, $opt);
        }
    }

    /**
     * @param string $pattern
     * @param stdClass|null $opt
     */
    private static function globDirRecursive(string $pattern, stdClass $opt = null) : void
    {
        foreach (glob($pattern, GLOB_ONLYDIR) as $file) {
            if (isset($opt->exclude) && in_array(basename($file), $opt->exclude)) {
                continue;
            }

            self::scanAddItem($file, $opt);
            self::globDirRecursive($file . '/*', $opt);
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
            if (isset($opt->exclude) && in_array(basename($file), $opt->exclude)) {
                continue;
            }

            if (!empty($opt->dir) || is_file($file)) {
                self::scanAddItem($file, $opt);
            }
        }
    }

    /**
     * @param string $pattern
     * @param stdClass|null $opt
     */
    private static function globRecursive(string $pattern, stdClass $opt = null) : void
    {
        foreach (glob($pattern) as $file) {
            if (isset($opt->exclude) && in_array(basename($file), $opt->exclude)) {
                continue;
            }

            if (is_file($file)) {
                self::scanAddItem($file, $opt);
            } else {
                self::globRecursive($file . '/*', $opt);
            }
        }
    }

    /**
     * @param string $pattern
     * @param stdClass|null $opt
     */
    private static function globFilterRecursive(string $pattern, stdClass $opt = null) : void
    {
        foreach (glob($pattern) as $file) {
            if (isset($opt->exclude) && in_array(basename($file), $opt->exclude)) {
                continue;
            }

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
            self::$rawdata[] = $file;
        } else {
            if (self::scanIsInvalidItem($file_info, $opt)) {
                return;
            }

            if (isset($opt->type)) {
                self::setStorage($file_info, $opt);
            } else {
                if (isset($opt->callback) && is_callable($opt->callback)) {
                    ($opt->callback)($file_info, $opt);
                }
                self::$rawdata[] = $file;
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
            $file_info->rootpathname                    = basename($file_info->rootpath);
            $file_info->rootname                        = basename($file_info->rootpath);
        } else {
            $subdir_count                               = substr_count($opt->pattern, DIRECTORY_SEPARATOR);
            $arrDir                                     = explode(DIRECTORY_SEPARATOR, $file_info->dirname, $subdir_count + 2);

            $file_info->rootpathname                    = $arrDir[$subdir_count + 1] ?? null;
            $file_info->rootpath                        = DIRECTORY_SEPARATOR . $file_info->rootpathname;
            $file_info->rootname                        = $arrDir[$subdir_count] ?? null;
        }

        $file_info->defaultname                         = (
            $file_info->rootpathname
            ? $file_info->rootpathname . DIRECTORY_SEPARATOR
            : null
        ) . $file_info->filename;


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

        if (isset($opt->callback) && is_callable($opt->callback)) {
            ($opt->callback)($file_info, $opt);
        }
    }
}
