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
use phpformsframework\libs\Error;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Request;
use stdClass;

/**
 * Class Filemanager
 * @package phpformsframework\libs\storage
 */
class Filemanager implements Dumpable
{
    const NAME_SPACE                                                    = __NAMESPACE__ . '\\adapters\\';

    private static $singletons                                          = null;
    private static $storage                                             = null;
    private static $scanExclude                                         = null;
    private static $cache                                               = null;

    /**
     * @var null|callable $callback
     */
    private static $callback                                            = null;
    private static $patterns                                            = null;

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
    public static function getInstance(string $filemanagerAdapter, string $file = null, string $var = null, int $expire = null) : FilemanagerAdapter
    {
        if ($filemanagerAdapter && !isset(self::$singletons[$filemanagerAdapter])) {
            $class_name                                                 = static::NAME_SPACE . "Filemanager" . ucfirst($filemanagerAdapter);
            self::$singletons[$filemanagerAdapter]                      = new $class_name($file, $var, $expire);
        }

        return self::$singletons[$filemanagerAdapter];
    }

    /**
     * @return array
     */
    public static function dump() : array
    {
        return array(
            "patterns"      => self::$patterns,
            "storage"       => self::$storage,
            "contents"      => self::$cache["request"]
        );
    }

    /**
     * @param string $type
     * @return array
     */
    public static function dumpContent(string $type = "remote") : ?array
    {
        return (isset(self::$cache["response"][$type])
            ? self::$cache["response"][$type]
            : null
        );
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

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function move(string $source, string $destination) : bool
    {
        return self::makeDir($destination) && rename($source, $destination);
    }

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
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function xCopy(string $source, string $destination) : bool
    {
        $res                                                            = false;
        $ftp                                                            = self::ftpConnect();

        if ($ftp) {
            $res                                                        = self::ftpCopy($ftp["conn"], $ftp["path"], $source, $destination, Constant::DISK_PATH);
            @ftp_close($ftp["conn"]);
        }

        if (!$res) {
            self::fullCopy(Constant::DISK_PATH . $source, Constant::DISK_PATH . $destination);
        }
        return $res;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function xPurgeDir(string $path) : bool
    {
        $res                                                            = false;

        $ftp                                                            = self::ftpConnect();
        if ($ftp) {
            $res                                                        = self::ftpPurgeDir($ftp["conn"], $ftp["path"], $path, Constant::DISK_PATH);
            @ftp_close($ftp["conn"]);
        }

        if (!$res) {
            $res                                                        = self::purgeDir(Constant::DISK_PATH . $path, $path);
        }

        return $res;
    }

    /**
     * @return array|null
     */
    private static function ftpConnect() : ?array
    {
        $res                                                            = null;
        if (Kernel::$Environment::FTP_USERNAME && Kernel::$Environment::FTP_SECRET) {
            $conn_id = @ftp_connect("localhost");
            if ($conn_id === false) {
                $conn_id = @ftp_connect("127.0.0.1");
            }
            if ($conn_id === false) {
                $conn_id = @ftp_connect(Request::serverAddr());
            }

            if ($conn_id !== false && @ftp_login($conn_id, Constant::FTP_USERNAME, Constant::FTP_USERNAME)) {
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
                        "conn" => $conn_id,
                        "path" => $real_ftp_path
                    );
                } else {
                    @ftp_close($conn_id);
                }
            }
        }

        return $res;
    }

    /**
     * @param resource $conn_id
     * @param string $ftp_disk_path
     * @param string $source
     * @param string $dest
     * @param string|null $local_disk_path
     * @return bool
     */
    private static function ftpCopy($conn_id, string $ftp_disk_path, string $source, string $dest, string $local_disk_path = null) : bool
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

    /**
     * @param resource $conn_id
     * @param string $ftp_disk_path
     * @param string $relative_path
     * @param string|null $local_disk_path
     * @return bool
     */
    private static function ftpPurgeDir($conn_id, string $ftp_disk_path, string $relative_path, string $local_disk_path = null) : bool
    {
        $absolute_path = $ftp_disk_path . $relative_path;

        $res = true;
        if (@ftp_chdir($conn_id, $absolute_path)) {
            $handle = @ftp_nlist($conn_id, "-la " . $absolute_path);
            if (!empty($handle)) {
                foreach ($handle as $file) {
                    if (basename($file) != "." && basename($file) != "..") {
                        if (strlen($ftp_disk_path)) {
                            $real_file = substr($file, strlen($ftp_disk_path));
                        } else {
                            $real_file = $file;
                        }
                        if (@ftp_chdir($conn_id, $file)) {
                            $res = ($res && self::ftpPurgeDir($conn_id, $ftp_disk_path, $real_file, $local_disk_path));
                            @ftp_rmdir($conn_id, $file);
                            if ($local_disk_path !== null) {
                                @rmdir($local_disk_path . $real_file);
                            }
                        } else {
                            if (!@ftp_delete($conn_id, $file)) {
                                if ($local_disk_path === null) {
                                    $res = false;
                                } else {
                                    $res = @unlink($local_disk_path . $real_file);
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
                    $res = @rmdir($local_disk_path . $relative_path);
                }
            }
        } else {
            if (!@ftp_delete($conn_id, $absolute_path)) {
                if ($local_disk_path === null) {
                    $res = false;
                } else {
                    $res = @unlink($local_disk_path . $relative_path);
                }
            }
        }
        return $res;
    }

    /**
     * @param string $source
     * @param string $target
     * @param bool $delete_source
     */
    private static function fullCopy(string $source, string $target, bool $delete_source = false) : void
    {
        if ($source && $target && $source != Constant::UPLOAD_DISK_PATH && $target != Constant::UPLOAD_DISK_PATH && $source != $target && file_exists($source) && is_dir($source)) {
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
                    self::fullCopy($source . '/' . $entry, $target . '/' . $entry, $delete_source);
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

    /**
     * @param string $absolute_path
     * @param string $relative_path
     * @param bool $exclude_dir
     * @return bool
     */
    private static function purgeDir(string $absolute_path, string $relative_path, bool $exclude_dir = false) : bool
    {
        $res = true;
        if (file_exists($absolute_path) && is_dir($absolute_path)) {
            $handle = opendir($absolute_path);
            if ($handle !== false) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($absolute_path . "/" . $file)) {
                            $res = ($res && self::purgeDir($absolute_path . "/" . $file, $relative_path . "/" . $file));
                        } else {
                            if (is_file($absolute_path . "/" . $file)) {
                                $res = @unlink($absolute_path . "/" . $file);
                            }
                        }
                    }
                }
                if (!$exclude_dir) {
                    $res = @rmdir($absolute_path);
                }
            }
        } else {
            if (file_exists($absolute_path) && is_file($absolute_path)) {
                $res = @unlink($absolute_path);
            }
        }

        return $res;
    }

    /**
     * @param array|null $patterns
     */
    public static function scanExclude(array $patterns = null) : void
    {
        if ($patterns) {
            if ($patterns[0]) {
                $patterns = array_fill_keys($patterns, true);
            }

            self::$scanExclude = array_replace((array)self::$scanExclude, $patterns);
        }
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
            self::scanRun($pattern, $opt);
        }

        Debug::stopWatch("filemanager/scan");

        return self::$storage;
    }

    /**
     * @param string $file
     * @param array|null $opt
     */
    private static function scanAddItem(string $file, array $opt = null) : void
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

    /**
     * @param string $pattern
     * @param array|null $what
     */
    private static function scanRun(string $pattern, array $what = null) : void
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
                        self::globDirCallback($pattern);
                    } else {
                        self::globDir($pattern);
                    }
                    break;
                case Filemanager::SCAN_DIR_RECURSIVE:
                    self::globDirRecursive($pattern);
                    break;
                case Filemanager::SCAN_ALL:
                    self::glob($pattern, ["dir" => true]);
                    break;
                case Filemanager::SCAN_ALL_RECURSIVE:
                    self::globFilterRecursive($pattern, ["dir" => true]);
                    break;
                case Filemanager::SCAN_FILE:
                    self::glob($pattern, $what);
                    break;
                case Filemanager::SCAN_FILE_RECURSIVE:
                    self::globFilterRecursive($pattern, $what);
                    break;
                case null:
                    self::globRecursive($pattern);
                    break;
                default:
                    if (isset($what["filter"])) {
                        $what["filter"] = array_fill_keys($what["filter"], true);
                    }
                    self::globFilterRecursive($pattern, $what);
            }
        }
    }

    /**
     * @param string $pattern
     */
    private static function globDir(string $pattern) : void
    {
        self::$storage["rawdata"] = glob($pattern, GLOB_ONLYDIR);
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
     * @param array|null $opt
     */
    private static function glob(string $pattern, array $opt = null) : void
    {
        $flags = null;
        $limit = null;
        if (is_array($opt["filter"])) {
            $flags = GLOB_BRACE;
            $limit = ".{" . implode(",", $opt["filter"]) . "}";
            unset($opt["filter"]);
        }

        foreach (glob($pattern . $limit, $flags) as $file) {
            if (!empty($opt["dir"]) || is_file($file)) {
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
     * @param array|null $opt
     */
    private static function globFilterRecursive(string $pattern, array $opt = null) : void
    {
        $final_dir = basename(dirname($pattern)); //todo:: da togliere
        if (isset(self::$scanExclude[$final_dir])) {
            return;
        }
        foreach (glob($pattern) as $file) {
            if (is_file($file)) {
                self::scanAddItem($file, $opt);
            } else {
                if (!empty($opt["dir"])) {
                    self::scanAddItem($file);
                }
                self::globFilterRecursive($file . '/*', $opt);
            }
        }
    }

    /**
     * @param array $file_info
     * @param array $opt
     */
    private static function setStorage(array $file_info, array $opt) : void
    {
        $file                                           = $file_info["dirname"] . "/" . $file_info["basename"];
        $type                                           = (
            isset($opt["type"])
            ? $opt["type"]
            : "unknowns"
        );

        $file_info["parentname"]                        = basename($file_info["dirname"]);
        if (isset($opt["rootpath"])) {
            $file_info["rootpath"]                      = realpath($file_info["dirname"] . DIRECTORY_SEPARATOR . $opt["rootpath"]);
            $file_info["rootname"]                      = basename($file_info["rootpath"]);
        }

        if (isset($opt["prototype"])) {
            $key                                        = str_replace(array_keys($file_info), array_values($file_info), $opt["prototype"]);
        } else {
            $key                                        = $file_info["filename"];
        }

        if (isset($opt["groupby"])) {
            $file_info["/"]                             = '"]["';
            $group                                      = str_replace(array_keys($file_info), array_values($file_info), $opt["groupby"]);
            eval('self::$storage[$type]["' . $group . '"][$key]   = $file;');
        } else {
            self::$storage[$type][$key]                 = $file;
        }
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
     * @param string $file
     * @param string|null $default
     * @return string
     */
    public static function getMimeType(string $file, string $default = null) : string
    {
        return Media::getMimeByFilename($file, $default);
    }

    /**
     * @param string $url
     * @param array|null $params
     * @param string $method
     * @param int $timeout
     * @param bool $ssl_verify
     * @param string|null $user_agent
     * @param array|null $cookie
     * @param string|null $username
     * @param string|null $password
     * @param array|null $headers
     * @return string
     */
    public static function fileGetContent(string $url, array $params = null, string $method = Request::METHOD_POST, int $timeout = 10, bool $ssl_verify = false, string $user_agent = null, array $cookie = null, string $username = null, string $password = null, array $headers = null) : string
    {
        $key                                        = self::normalizeUrlAndParams($method, $url, $params);
        $context                                    = self::streamContext($url, $params, $method, $timeout, $ssl_verify, $user_agent, $cookie, $username, $password, $headers);
        $location                                   = self::getUrlLocation($url);

        self::$cache["request"][$key]               = $location;
        self::$cache["response"][$location][$key]   = self::loadFile($url, $context);

        return self::$cache["response"][$location][$key];
    }

    /**
     * @param string $url
     * @param array|null $params
     * @param string $method
     * @param int $timeout
     * @param bool $ssl_verify
     * @param string|null $user_agent
     * @param array|null $cookie
     * @param string|null $username
     * @param string|null $password
     * @param array|null $headers
     * @return stdClass
     */
    public static function fileGetContentJson(string $url, array $params = null, string $method = Request::METHOD_POST, int $timeout = 10, bool $ssl_verify = false, string $user_agent = null, array $cookie = null, string $username = null, string $password = null, array $headers = null) : ?stdClass
    {
        return json_decode(self::fileGetContent($url, $params, $method, $timeout, $ssl_verify, $user_agent, $cookie, $username, $password, $headers));
    }
    /**
     * @param string $url
     * @param array|null $params
     * @param string $method
     * @param int $timeout
     * @param bool $ssl_verify
     * @param string|null $user_agent
     * @param array|null $cookie
     * @param string|null $username
     * @param string|null $password
     * @param array|null $headers
     * @return array|null
     */
    public static function fileGetContentWithHeaders(string $url, array $params = null, string $method = Request::METHOD_POST, int $timeout = 10, bool $ssl_verify = false, string $user_agent = null, array $cookie = null, string $username = null, string $password = null, array $headers = null) : ?array
    {
        $response_headers                           = array();
        $key                                        = self::normalizeUrlAndParams($method, $url, $params);
        $context                                    = self::streamContext($url, $params, $method, $timeout, $ssl_verify, $user_agent, $cookie, $username, $password, $headers);
        $location                                   = self::getUrlLocation($url);

        self::$cache["request"][$key]               = $location;
        self::$cache["response"][$location][$key]   = self::loadFile($url, $context, $response_headers);

        return array(
            "headers" => self::parseResponseHeaders($response_headers),
            "content" => self::$cache["response"][$location][$key]
        );
    }

    /**
     * @param string $method
     * @param string $url
     * @param array|null $params
     * @return string
     */
    private static function normalizeUrlAndParams(string $method, string &$url, array &$params = null) : string
    {
        $params                             = self::getQueryByUrl($url, $params);
        $key                                = $url;
        if (count($params)) {
            $key                            .= "?" . http_build_query($params);
            if ($method != Request::METHOD_POST) {
                $url                        = $key;
                $params                     = null;
            }
        }
        $location                           = (
            strpos($url, "http") === 0
            ? strtoupper($method) . ":"
            : ""
        );
        return $location . $key;
    }

    /**
     * @param string $url
     * @return string
     */
    private static function getUrlLocation(string $url) : string
    {
        return (
            strpos($url, "http") === 0
            ? "remote"
            : "local"
        );
    }

    /**
     * @param array $headers
     * @return array
     */
    private static function parseResponseHeaders(array $headers) : array
    {
        $head                               = array();
        foreach ($headers as $v) {
            $t                              = explode(':', $v, 2);
            if (isset($t[1])) {
                $head[trim($t[0])]          = trim($t[1]);
            } else {
                $head[]                     = $v;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out)) {
                    $head['response_code']  = intval($out[1]);
                }
            }
        }

        return $head;
    }

    /**
     * @param string $path
     * @param resource $context
     * @param array|null $headers
     * @return string
     */
    private static function loadFile(string $path, $context = null, array &$headers = null) : string
    {
        $content                            = file_get_contents($path, false, $context);
        if ($content === false) {
            Error::register("File inaccessible: " . ($path ? $path : "empty"));
        }

        if (isset($http_response_header) && isset($headers)) {
            $headers                        = $http_response_header;
        }


        return $content;
    }

    /**
     * @param string $url
     * @param array|null $params
     * @param string $method
     * @param int $timeout
     * @param bool $ssl_verify
     * @param string|null $user_agent
     * @param array|null $cookie
     * @param string|null $username
     * @param string|null $password
     * @param array|null $headers
     * @return resource
     */
    private static function streamContext(string $url, array $params = null, string $method = Request::METHOD_POST, int $timeout = 60, bool $ssl_verify = false, string $user_agent = null, array $cookie = null, string $username = null, string $password = null, array $headers = null)
    {
        if (!$username) {
            $username                       = Kernel::$Environment::HTTP_AUTH_USERNAME;
        }
        if (!$password) {
            $password                       = Kernel::$Environment::HTTP_AUTH_SECRET;
        }
        if (!$method) {
            $method                         = Request::METHOD_POST;
        }

        $headers                            = (
            $headers
            ? array_combine(array_keys($headers), explode("&", str_replace("=", ": ", http_build_query($headers))))
            : array()
        );

        if ($method == Request::METHOD_POST) {
            $headers[]                      = "Content-type: application/x-www-form-urlencoded";
        }
        if (strpos($url, Request::hostname()) !== false && $username) {
            $headers["Authorization"]       = "Authorization: Basic " . base64_encode($username . ":" . $password);
        }

        if ($cookie) {
            $headers["Cookie"]              = "Cookie: " . http_build_query($cookie, '', '; ');
        }

        $opts = array(
            'ssl'                           => array(
                "verify_peer" 		        => $ssl_verify,
                "verify_peer_name" 	        => $ssl_verify
            ),
            'http'                          => array(
                'method'  			        => $method,
                'timeout'  			        => $timeout,
                'ignore_errors'             => true
            )
        );
        if ($user_agent) {
            $opts['http']['user_agent']     = $user_agent;
        }
        if ($headers) {
            $opts['http']['header']         = implode("\r\n", $headers);
        }

        if ($params && $method == Request::METHOD_POST) {
            $opts["http"]["content"]    = http_build_query($params);
        }


        /** @todo da implementare per gestire il trasgerimento dei file

               define('MULTIPART_BOUNDARY', '--------------------------'.microtime(true));

               $header = 'Content-Type: multipart/form-data; boundary='.MULTIPART_BOUNDARY;
               // equivalent to <input type="file" name="uploaded_file"/>
               define('FORM_FIELD', 'uploaded_file');

               $filename = "/path/to/uploaded/file.zip";
               $file_contents = file_get_contents($filename);

               $content =  "--".MULTIPART_BOUNDARY."\r\n".
                   "Content-Disposition: form-data; name=\"".FORM_FIELD."\"; filename=\"".basename($filename)."\"\r\n".
                   "Content-Type: application/zip\r\n\r\n".
                   $file_contents."\r\n";

               // add some POST fields to the request too: $_POST['foo'] = 'bar'
               $content .= "--".MULTIPART_BOUNDARY."\r\n".
                   "Content-Disposition: form-data; name=\"foo\"\r\n\r\n".
                   "bar\r\n";

               // signal end of request (note the trailing "--")
               $content .= "--".MULTIPART_BOUNDARY."--\r\n";

               $context = stream_context_create(array(
                   'http' => array(
                       'method' => 'POST',
                       'header' => $header,
                       'content' => $content,
                   )
               ));


               file_get_contents('http://url/to/upload/handler', false, $context);
        */

        return stream_context_create($opts);
    }

    /**
     * @param string $url
     * @param array|null $params
     * @return array|null
     */
    public static function getQueryByUrl(string &$url, array $params = null) : array
    {
        $url_params                         = array();
        if (strpos($url, "?") !== false) {
            $query                          = explode("?", $url, 2);
            $url                            = $query[0];
            if (!empty($query[1])) {
                parse_str($query[1], $url_params);
            }
        }
        return array_replace((array) $params, $url_params);
    }
}
