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
namespace ff\libs\storage;

use ff\libs\Constant;
use ff\libs\Dir;
use ff\libs\Exception;
use ff\libs\security\Validator;
use ff\libs\util\AdapterManager;
use stdClass;

/**
 * Class FilemanagerFs
 * @package ff\libs\storage
 */
class FilemanagerFs
{
    use AdapterManager;

    private const ERROR_FILE_FORBIDDEN                                  = ": forbidden";

    /**
     * @param string $file_path
     * @return string
     * @throws Exception
     */
    public static function fileGetContents(string $file_path) : string
    {
        $content = @file_get_contents($file_path);
        if ($content === false) {
            throw new Exception(error_get_last()["message"] ?? ($file_path . self::ERROR_FILE_FORBIDDEN), 403);
        }

        return $content;
    }

    /**
     * @param string $file_path
     * @param bool $toArray
     * @return stdClass|array
     * @throws Exception
     */
    public static function fileGetContentsJson(string $file_path, bool $toArray = false)
    {
        return Validator::jsonDecode(self::fileGetContents($file_path), $toArray);
    }

    /**
     * @param string $filename
     * @param string $data
     * @return false
     */
    public static function filePutContents(string $filename, string $data) : bool
    {
        if (!Dir::checkDiskPath(dirname($filename))) {
            return false;
        }

        return file_put_contents($filename, $data);
    }

    /**
     * @param $upload_dir
     * @param $key
     * @param bool $unique_name
     * @return string|null
     */
    public static function moveUploadedFile($upload_dir, $key, bool $unique_name = false) : ?string
    {
        if(isset($_FILES[$key])) {
            $upload_dir = Constant::UPLOAD_PATH . rtrim($upload_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $upload_disk_path = Constant::DISK_PATH . $upload_dir;
            if (!is_dir($upload_disk_path)) {
                mkdir($upload_disk_path, 0775, true);
            }
            $filename = $_FILES[$key]["name"];
            if ($unique_name) {
                $filename = microtime(true) . "-" . $filename;
            }

            if (@move_uploaded_file($_FILES[$key]["tmp_name"], $upload_disk_path . $filename)) {
                return $upload_dir . $filename;
            }
        }

        return null;
    }

    /**
     * @param string $fileType
     * @return FilemanagerAdapter
     */
    public static function loadFile(string $fileType) : FilemanagerAdapter
    {
        return self::loadAdapter($fileType);
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
     * @param string $path
     * @param string|null $base_path
     * @param $chmod
     * @return bool
     */
    public static function makeDir(string $path, string $base_path = null, $chmod = 0775) : bool
    {
        $res                                                            = true;
        if (!$base_path) {
            $base_path = Constant::DISK_PATH;
        }
        $path                                                           = str_replace($base_path, "", $path);

        if ($path && $path != DIRECTORY_SEPARATOR) {
            if (pathinfo($base_path . $path, PATHINFO_FILENAME) && pathinfo($base_path . $path, PATHINFO_EXTENSION)) {
                $path = dirname($path);
            }

            if (!is_dir($base_path . $path)) {
                if (!@mkdir($base_path . $path, $chmod, true)) {
                    $res                                                    = false;
                    Exception::warning("MakeDir Permission Denied: " . $base_path . $path);
                }
            }
        }
        return $res;
    }

    /**
     * @param string $file_disk_path
     * @return bool
     */
    public static function touch(string $file_disk_path) : bool
    {
        if (!file_exists($file_disk_path)) {
            self::makeDir($file_disk_path);

            return @touch($file_disk_path);
        }

        return true;
    }

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function copy(string $source, string $destination) : bool
    {
        return self::fullCopy($source, $destination);
    }

    /**
     * @param string $filepath
     * @return bool
     */
    public static function delete(string $filepath) : bool
    {
        $result                                         = false;
        if (file_exists($filepath)) {
            $result                                     = @unlink($filepath);
        }
        return $result;
    }

    /**
     * @param string $relative_path
     * @param bool $only_file
     * @return bool
     */
    public static function deleteDir(string $relative_path, bool $only_file = false) : bool
    {
        $res = true;
        $absolute_path = Constant::DISK_PATH . $relative_path;
        if (is_dir($absolute_path)) {
            $handle = opendir($absolute_path);
            if ($handle !== false) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($absolute_path . DIRECTORY_SEPARATOR . $file)) {
                            $res = ($res && self::deleteDir($relative_path . DIRECTORY_SEPARATOR . $file));
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
     * @param string $source
     * @param string $target
     * @param bool $delete_source
     * @param $chmod
     * @return bool
     */
    private static function fullCopy(string $source, string $target, bool $delete_source = false, $chmod = 0775) : bool
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
                $res = ($res && @chmod($target . DIRECTORY_SEPARATOR . $entry, $chmod));
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

            $res = ($res && self::makeDir(dirname($target)));

            $res = ($res && @copy($source, $target));
            $res = ($res && @chmod($target, $chmod));
            if ($delete_source) {
                $res = ($res && @unlink($source));
            }
        }
        return $res ?? false;
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
