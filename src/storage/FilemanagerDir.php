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
namespace phpformsframework\libs\storage;

use phpformsframework\libs\Constant;
use phpformsframework\libs\Error;

/**
 * Class FilemanagerDir
 * @package phpformsframework\libs\storage
 */
class FilemanagerDir
{
    /**
     * @param string $path
     * @param int $chmod
     * @param string|null $base_path
     * @return bool
     */
    public static function makeDir(string $path, int $chmod = 0775, string $base_path = null) : bool
    {
        $res                                                            = false;
        if (!$base_path) {
            $base_path = Constant::DISK_PATH;
        }
        $path                                                           = str_replace($base_path, "", $path);

        if ($path && $path != DIRECTORY_SEPARATOR) {
            if (!is_dir($base_path . $path)) {
                $path = dirname($path);
            }

            if (!is_dir($base_path . $path)) {
                if (@mkdir($base_path . $path, $chmod, true)) {
                    $res                                                    = true;
                } else {
                    Error::registerWarning("MakeDir Permission Denied: " . $base_path . $path);
                }
            }
        }
        return $res;
    }

    /**
     * @param string $content
     * @param string $file
     * @return bool
     */
    public static function fappend(string $content, string $file) : bool
    {
        return self::fwrite($content, $file, "a");
    }

    /**
     * @param string $content
     * @param string $file
     * @return bool
     */
    public static function fsave(string $content, string $file) : bool
    {
        return self::fwrite($content, $file);
    }

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function move(string $source, string $destination) : bool
    {
        return self::fullCopy($source, $destination, true);
    }

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function copy(string $source, string $destination) : bool
    {
        return self::fullCopy($source, $destination, false);
    }

    /**
     * @param string $filepath
     * @param bool $only_file
     * @return bool
     */
    public static function delete(string $filepath, bool $only_file = false) : bool
    {
        return self::purgeDir($filepath, str_replace(Constant::DISK_PATH, "", $filepath), $only_file);
    }

    /**
     * @param string $source
     * @param string $target
     * @param bool $delete_source
     * @return bool
     */
    private static function fullCopy(string $source, string $target, bool $delete_source = false) : bool
    {
        if ($source && $target && $source != $target && file_exists($source) && is_dir($source)) {
            $disable_rmdir = false;

            $res = self::makeDir($target);

            $d = dir($source);
            while (false !== ($entry = $d->read())) {
                if (strpos($entry, ".") === 0) {
                    continue;
                }

                if ($source . DIRECTORY_SEPARATOR . $entry == $target) {
                    $disable_rmdir = true;
                    continue;
                }
                if (is_dir($source . DIRECTORY_SEPARATOR . $entry)) {
                    self::fullCopy($source . DIRECTORY_SEPARATOR . $entry, $target . DIRECTORY_SEPARATOR . $entry, $delete_source);
                    continue;
                }

                $res = ($res && @copy($source . DIRECTORY_SEPARATOR . $entry, $target . DIRECTORY_SEPARATOR . $entry));
                $res = ($res && @chmod($target . DIRECTORY_SEPARATOR . $entry, 0777));
                if ($delete_source) {
                    $res = ($res && @unlink($source . DIRECTORY_SEPARATOR . $entry));
                }
            }
            $d->close();
            if ($delete_source && !$disable_rmdir) {
                $res = ($res && @rmdir($source));
            }
        } elseif (file_exists($source) && is_file($source)) {
            $res = true;

            $res = ($res && @mkdir(dirname($target), 0777, true));

            $res = ($res && @copy($source, $target));
            $res = ($res && @chmod($target, 0777));
            if ($delete_source) {
                $res = ($res && @unlink($source));
            }
        }
        return $res ?? false;
    }

    /**
     * @param string $absolute_path
     * @param string $relative_path
     * @param bool $only_file
     * @return bool
     */
    private static function purgeDir(string $absolute_path, string $relative_path, bool $only_file = false) : bool
    {
        $res = true;
        if (is_dir($absolute_path)) {
            $handle = opendir($absolute_path);
            if ($handle !== false) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($absolute_path . DIRECTORY_SEPARATOR . $file)) {
                            $res = ($res && self::purgeDir($absolute_path . DIRECTORY_SEPARATOR . $file, $relative_path . DIRECTORY_SEPARATOR . $file));
                        } else {
                            if (is_file($absolute_path . DIRECTORY_SEPARATOR . $file)) {
                                $res = ($res && @unlink($absolute_path . DIRECTORY_SEPARATOR . $file));
                            }
                        }
                    }
                }
                if (!$only_file) {
                    $res = ($res && @rmdir($absolute_path));
                }
            }
        } else {
            if (file_exists($absolute_path) && is_file($absolute_path)) {
                $res = ($res && @unlink($absolute_path));
            }
        }

        return $res;
    }

    /**
     * @param string $data
     * @param string $file
     * @param string $mode
     * @return bool
     */
    private static function fwrite(string $data, string $file, string $mode = "w") : bool
    {
        $success = false;
        if ($data && $file) {
            $handle = @fopen($file, $mode);
            if ($handle !== false) {
                if (flock($handle, LOCK_EX)) {
                    if (@fwrite($handle, $data . "\n") !== false) {
                        $success = true;
                    }
                    flock($handle, LOCK_UN);
                }
                @fclose($handle);
            }
        }
        return $success;
    }
}
