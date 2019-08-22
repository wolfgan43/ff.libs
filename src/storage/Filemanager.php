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

use phpformsframework\libs\Debug;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Request;

class Filemanager implements Dumpable
{
    const NAME_SPACE                                                    = __NAMESPACE__ . '\\adapters\\';

    private static $singletons                                          = null;
    private static $storage                                             = null;
    private static $scanExclude                                         = null;
    /**
     * @var null|callable $callback
     */
    private static $callback                                            = null;
    private static $patterns                                            = null;

    const FTP_USERNAME                                                  = Constant::FTP_USERNAME;
    const FTP_PASSWORD                                                  = Constant::FTP_PASSWORD;

    const SCAN_DIR                                                      = 1;
    const SCAN_DIR_RECURSIVE                                            = 2;
    const SCAN_FILE                                                     = 4;
    const SCAN_FILE_RECURSIVE                                           = 8;
    const SCAN_ALL                                                      = 16;
    const SCAN_ALL_RECURSIVE                                            = 32;

    /**
     * @param string $filemanagerAdapter
     * @param null|string $file
     * @param null|string $var
     * @param null|integer $expire
     * @return FilemanagerAdapter
     */
    public static function getInstance($filemanagerAdapter, $file = null, $var = null, $expire = null)
    {
        if ($filemanagerAdapter && !isset(self::$singletons[$filemanagerAdapter])) {
            $class_name                                                 = static::NAME_SPACE . "Filemanager" . ucfirst($filemanagerAdapter);
            self::$singletons[$filemanagerAdapter]                      = new $class_name($file, $var, $expire);
        }

        return self::$singletons[$filemanagerAdapter];
    }

    public static function dump()
    {
        return array(
            "patterns"  => self::$patterns
            , "storage" =>  self::$storage
        );
    }

    public static function fappend($content, $file)
    {
        return self::fwrite($content, $file, "a");
    }
    public static function fsave($content, $file)
    {
        return self::fwrite($content, $file);
    }
    private static function fwrite($data, $file, $mode = "w")
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

    public static function makeDir($path, $chmod = 0775, $base_path = null)
    {
        $res                                                            = false;
        if (!$base_path) {
            $base_path = Constant::DISK_PATH;
        }
        $path                                                           = str_replace($base_path, "", $path);

        if ($path && $path != DIRECTORY_SEPARATOR) {
            if (is_file($base_path . $path)) {
                $path = dirname($path);
            }

            if (!is_dir($base_path . $path) && is_writable($base_path . $path) && mkdir($base_path . $path, $chmod, true)) {
                while ($path != DIRECTORY_SEPARATOR) {
                    if (is_dir($base_path . $path)) {
                        chmod($base_path . $path, $chmod);
                    }

                    $path                                               = dirname($path);
                }
                $res                                                    = true;
            }
        }
        return $res;
    }

    public static function xcopy($source, $destination)
    {
        $res                                                            = false;
        $ftp                                                            = self::ftp_xconnect();

        if ($ftp) {
            $res                                                        = self::ftp_copy($ftp["conn"], $ftp["path"], $source, $destination, Constant::DISK_PATH);
            @ftp_close($ftp["conn"]);
        }

        if (!$res) {
            self::full_copy(Constant::DISK_PATH . $source, Constant::DISK_PATH . $destination);
        }
        return $res;
    }

    public static function xpurge_dir($path)
    {
        $res                                                            = false;

        $ftp                                                            = self::ftp_xconnect();
        if ($ftp) {
            $res                                                        = self::ftp_purge_dir($ftp["conn"], $ftp["path"], $path, Constant::DISK_PATH);
            @ftp_close($ftp["conn"]);
        }

        if (!$res) {
            $res                                                        = self::purge_dir(Constant::DISK_PATH . $path, $path);
        }

        return $res;
    }

    /**
     * FTP
     */
    private static function ftp_xconnect()
    {
        $res                                                            = null;
        if (self::FTP_USERNAME && self::FTP_PASSWORD) {
            $conn_id = @ftp_connect("localhost");
            if ($conn_id === false) {
                $conn_id = @ftp_connect("127.0.0.1");
            }
            if ($conn_id === false) {
                $conn_id = @ftp_connect(Request::serverAddr());
            }

            if ($conn_id !== false && @ftp_login($conn_id, self::FTP_USERNAME, self::FTP_PASSWORD)) {
                $local_path = Constant::DISK_PATH;
                $part_path = "";
                $real_ftp_path = null;

                foreach (explode("/", $local_path) as $curr_path) {
                    if (strlen($curr_path)) {
                        $ftp_path = str_replace($part_path, "", $local_path);
                        if (@ftp_chdir($conn_id, $ftp_path)) {
                            $real_ftp_path = $ftp_path;
                            break;
                        }

                        $part_path .= "/" . $curr_path;
                    }
                }
                if ($real_ftp_path === null && @ftp_chdir($conn_id, "/")) {
                    $real_ftp_path = "";
                }

                if ($real_ftp_path) {
                    $res = array(
                        "conn" => $conn_id
                        , "path" => $real_ftp_path
                    );
                } else {
                    @ftp_close($conn_id);
                }
            }
        }

        return $res;
    }

    private static function ftp_copy($conn_id, $ftp_disk_path, $source, $dest, $local_disk_path = null)
    {
        $absolute_path = dirname($ftp_disk_path . $dest);

        $res = true;
        if (!@ftp_chdir($conn_id, $absolute_path)) {
            $parts = explode('/', trim(dirname($dest), "/"));
            @ftp_chdir($conn_id, $ftp_disk_path);
            foreach ($parts as $part) {
                if (!@ftp_chdir($conn_id, $part)) {
                    $res = $res && @ftp_mkdir($conn_id, $part);
                    $res = $res && @ftp_chmod($conn_id, 0755, $part);

                    @ftp_chdir($conn_id, $part);
                }
            }

            if (!$res && $local_disk_path && !is_dir(dirname($local_disk_path . $dest))) {
                $res = @mkdir(dirname($local_disk_path . $dest), 0777, true);
            }
        }

        if ($res) {
            if (!is_dir(Constant::UPLOAD_DISK_PATH . "/tmp")) {
                $res = @mkdir(Constant::UPLOAD_DISK_PATH . "/tmp", 0777);
            } elseif (substr(sprintf('%o', fileperms(Constant::UPLOAD_DISK_PATH . "/tmp")), -4) != "0777") {
                $res = @chmod(Constant::UPLOAD_DISK_PATH . "/tmp", 0777);
            }
            if ($res) {
                $res = ftp_get($conn_id, Constant::UPLOAD_DISK_PATH . "/tmp/" . basename($dest), $ftp_disk_path . $source, FTP_BINARY);
                if ($res) {
                    $res = $res && ftp_put($conn_id, $ftp_disk_path . $dest, Constant::UPLOAD_DISK_PATH . "/tmp/" . basename($dest), FTP_BINARY);

                    $res = $res && @ftp_chmod($conn_id, 0644, $ftp_disk_path . $dest);

                    @unlink(Constant::UPLOAD_DISK_PATH . "/tmp/" . basename($dest));
                }
            }
            if (!$res && $local_disk_path && !is_file($local_disk_path . $dest)) {
                $res = @copy($local_disk_path . $source, $local_disk_path . $dest);
                $res = $res && @chmod($local_disk_path . $dest, 0666);
            }
        }

        return $res;
    }

    private static function ftp_purge_dir($conn_id, $ftp_disk_path, $relative_path, $local_disk_path = null)
    {
        $absolute_path = $ftp_disk_path . $relative_path;

        $res = true;
        if (@ftp_chdir($conn_id, $absolute_path)) {
            $handle = @ftp_nlist($conn_id, "-la " . $absolute_path);
            if (is_array($handle) && count($handle)) {
                foreach ($handle as $file) {
                    if (basename($file) != "." && basename($file) != "..") {
                        if (strlen($ftp_disk_path)) {
                            $real_file = substr($file, strlen($ftp_disk_path));
                        } else {
                            $real_file = $file;
                        }
                        if (@ftp_chdir($conn_id, $file)) {
                            $res = ($res && self::ftp_purge_dir($conn_id, $ftp_disk_path, $real_file, $local_disk_path));
                            @ftp_rmdir($conn_id, $file);
                            if ($local_disk_path !== null) {
                                @rmdir($local_disk_path . $real_file);
                            }
                        } else {
                            if (!@ftp_delete($conn_id, $file)) {
                                if ($local_disk_path === null) {
                                    $res = false;
                                } else {
                                    if (!@unlink($local_disk_path . $real_file)) {
                                        $res = false;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!@ftp_rmdir($conn_id, $absolute_path)) {
                if ($local_disk_path === null) {
                    $res = false;
                } else {
                    if (!@rmdir($local_disk_path . $relative_path)) {
                        $res = false;
                    }
                }
            }
        } else {
            if (!@ftp_delete($conn_id, $absolute_path)) {
                if ($local_disk_path === null) {
                    $res = false;
                } else {
                    if (!@unlink($local_disk_path . $relative_path)) {
                        $res = false;
                    }
                }
            }
        }
        return $res;
    }

    private static function full_copy($source, $target, $delete_source = false)
    {
        if (!$source || !$target || $source == Constant::UPLOAD_DISK_PATH || $target == Constant::UPLOAD_DISK_PATH || $source == $target) {
            return null;
        }
        if (file_exists($source) && is_dir($source)) {
            $disable_rmdir = false;

            @mkdir($target, 0777, true);
            $d = dir($source);
            while (false !== ($entry = $d->read())) {
                if (strpos($entry, ".") === 0) {
                    continue;
                }

                if ($source . '/' . $entry == $target) {
                    $disable_rmdir = true;
                    continue;
                }
                if (is_dir($source . '/' . $entry)) {
                    self::full_copy($source . '/' . $entry, $target . '/' . $entry, $delete_source);
                    continue;
                }

                @copy($source . '/' . $entry, $target . '/' . $entry);
                @chmod($target . '/' . $entry, 0777);
                if ($delete_source) {
                    @unlink($source . '/' . $entry);
                }
            }

            $d->close();
            if ($delete_source && !$disable_rmdir) {
                @rmdir($source);
            }
        } elseif (file_exists($source) && is_file($source)) {
            @mkdir(dirname($target), 0777, true);

            @copy($source, $target);
            @chmod($target, 0777);
            if ($delete_source) {
                @unlink($source);
            }
        }
    }

    private static function purge_dir($absolute_path, $relative_path, $exclude_dir = false)
    {
        if (file_exists($absolute_path) && is_dir($absolute_path)) {
            $handle = opendir($absolute_path);
            if ($handle !== false) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($absolute_path . "/" . $file)) {
                            self::purge_dir($absolute_path . "/" . $file, $relative_path . "/" . $file);
                        } else {
                            if (is_file($absolute_path . "/" . $file)) {
                                @unlink($absolute_path . "/" . $file);
                            }
                        }
                    }
                }
                if (!$exclude_dir) {
                    @rmdir($absolute_path);
                }
            }
        } else {
            if (file_exists($absolute_path) && is_file($absolute_path)) {
                @unlink($absolute_path);
            }
        }
    }

    public static function scanExclude($patterns)
    {
        if ($patterns) {
            if ($patterns[0]) {
                $patterns = array_fill_keys($patterns, true);
            }

            self::$scanExclude = array_replace((array)self::$scanExclude, $patterns);
        }
    }
    public static function scan($patterns, $what = null, $callback = null)
    {
        Debug::stopWatch("filemanager/scan");
        if (is_array($patterns) && !$callback) {
            $callback               = $what;
        }
        self::$callback             = (
            $callback && is_callable($callback)
                                        ? $callback
                                        : null
                                    );

        self::$patterns[] = $patterns;

        if (is_array($patterns) && count($patterns)) {
            foreach ($patterns as $pattern => $opt) {
                self::scanRun($pattern, $opt);
            }
        } else {
            self::scanRun($patterns, $what);
        }

        Debug::stopWatch("filemanager/scan");

        return self::$storage;
    }

    private static function scanAddItem($file, $opt = null)
    {
        if (self::$callback) {
            $file_info = pathinfo($file);

            if (isset($opt["filter"]) && (!isset($file_info["extension"]) || !isset($opt["filter"][$file_info["extension"]]))) {
                return;
            }
            if (isset($opt["name"]) && !isset($opt["name"][$file_info["basename"]])) {
                return;
            }
            $callback = self::$callback;
            $callback($file, self::$storage);
        } elseif (!$opt) {
            self::$storage["rawdata"][] = $file;
        } else {
            $file_info = pathinfo($file);
            if (isset($opt["filter"]) && !isset($opt["filter"][$file_info["extension"]])) {
                return;
            }
            if (isset($opt["name"]) && !isset($opt["name"][$file_info["basename"]])) {
                return;
            }

            if (isset($opt["type"])) {
                self::setStorage($file_info, $opt);
            } else {
                self::$storage["rawdata"][] = $file;
            }
        }
    }

    private static function scanRun($pattern, $what = null)
    {
        if ($pattern) {
            $pattern = (
                strpos($pattern, Constant::DISK_PATH) === 0
                    ? ""
                    : Constant::DISK_PATH
                )
                . $pattern
                . (
                    strpos($pattern, "*") === false
                    ? '/*'
                    : ''
                );

            $flag = (
                isset($what["flag"]) && $what["flag"]
                ? $what["flag"]
                : $what
            );


            if (isset($what["filter"]) && $what["filter"] && isset($what["filter"][0])) {
                $what["filter"] = array_combine($what["filter"], $what["filter"]);
            }
            if (isset($what["name"]) && $what["name"] && isset($what["name"][0])) {
                $what["name"] = array_combine($what["name"], $what["name"]);
            }

            switch ($flag) {
                case Filemanager::SCAN_DIR:
                    if (self::$callback) {
                        self::glob_dir_callback($pattern);
                    } else {
                        self::glob_dir($pattern);
                    }
                    break;
                case Filemanager::SCAN_DIR_RECURSIVE:
                    self::rglob_dir($pattern);
                    break;
                case Filemanager::SCAN_ALL:
                    self::glob($pattern, false);
                    break;
                case Filemanager::SCAN_ALL_RECURSIVE:
                    self::rglobfilter($pattern, false);
                    break;
                case Filemanager::SCAN_FILE:
                    self::glob($pattern, $what);
                    break;
                case Filemanager::SCAN_FILE_RECURSIVE:
                    self::rglobfilter($pattern, $what);
                    break;
                case null:
                    self::rglob($pattern);
                    break;
                default:
                    if (isset($what["filter"])) {
                        $what["filter"] = array_fill_keys($what["filter"], true);
                    }
                    self::rglobfilter($pattern, $what);
            }
        }
    }

    private static function glob_dir($pattern)
    {
        self::$storage["rawdata"] = glob($pattern, GLOB_ONLYDIR);
    }
    private static function glob_dir_callback($pattern)
    {
        foreach (glob($pattern, GLOB_ONLYDIR) as $file) {
            self::scanAddItem($file);
        }
    }
    private static function rglob_dir($pattern)
    {
        foreach (glob($pattern, GLOB_ONLYDIR) as $file) {
            self::scanAddItem($file);
            self::rglob_dir($file . '/*');
        }
    }

    private static function glob($pattern, $opt = null)
    {
        $flags = null;
        $limit = null;
        if (is_array($opt["filter"])) {
            $flags = GLOB_BRACE;
            $limit = ".{" . implode(",", $opt["filter"]) . "}";
            unset($opt["filter"]);
        }

        foreach (glob($pattern . $limit, $flags) as $file) {
            if ($opt === false || is_file($file)) {
                self::scanAddItem($file, $opt);
            }
        }
    }
    private static function rglob($pattern)
    {
        foreach (glob($pattern) as $file) {
            if (is_file($file)) {
                self::scanAddItem($file);
            } else {
                self::rglob($file . '/*');
            }
        }
    }

    private static function rglobfilter($pattern, $opt = null)
    {
        $final_dir = basename(dirname($pattern)); //todo:: da togliere
        if (self::$scanExclude[$final_dir]) {
            return;
        }
        foreach (glob($pattern) as $file) {
            if (is_file($file)) {
                self::scanAddItem($file, $opt);
            } else {
                if ($opt === false) {
                    self::scanAddItem($file);
                }
                self::rglobfilter($file . '/*', $opt);
            }
        }
    }

    private static function setStorage($file_info, $opt)
    {
        if (isset($opt["prototype"])) {
            $file_info["parentname"]        = basename($file_info["dirname"]);
            $key                            = str_replace(array_keys($file_info), array_values($file_info), $opt["prototype"]);
        } else {
            $key                            = $file_info["filename"];
        }

        $file = $file_info["dirname"] . "/" . $file_info["basename"];
        if (isset($opt["type"])) {
            self::$storage[$opt["type"]][$key] = $file;
        } else {
            self::$storage["unknowns"][$key] = $file;
        }
    }
}
