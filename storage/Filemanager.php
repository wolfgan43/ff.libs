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

use phpformsframework\libs\DirStruct;
use phpformsframework\libs\Error;
use phpformsframework\libs\Debug;

if(!defined("FTP_USERNAME"))                                        define("FTP_USERNAME", null);
if(!defined("FTP_PASSWORD"))                                        define("FTP_PASSWORD", null);

abstract class filemanagerAdapter extends DirStruct
{
    const EXT                                                           = null;
    const SEARCH_IN_KEY                                                 = 1;
    const SEARCH_IN_VALUE                                               = 2;
    const SEARCH_IN_BOTH                                                = 3;
    const SEARCH_DEFAULT                                                = self::SEARCH_IN_KEY;

    private $file_path                                                  = null;
    private $var                                                        = null;

    public function __construct($file_path = null, $var = null, $expire = null) {
        if($file_path)                                                  { $this->setFilePath($file_path); }
        if($var)                                                        { $this->setVar($var); }
    }

    /**
     * @param null|string $file_path
     * @param null|string $search_keys
     * @param int $search_flag
     * @return array
     */
    public abstract function read($file_path = null, $search_keys = null, $search_flag = self::SEARCH_DEFAULT);


    /**
     * @param array $data
     * @param null|string $var
     * @param null|string $file_path
     * @return bool
     */
    public abstract function write($data, $var = null, $file_path = null);

    /**
     * @param array $data
     * @param null|string $var
     * @param null|string $file_path
     * @return bool
     */
    public function update($data, $var = null, $file_path = null)
    {
        $res                                                            = (is_array($data)
                                                                            ? array_replace($this->read(), $data)
                                                                            : $data
                                                                        );

        return $this->write($res, $file_path, $var);
    }

    /**
     * @param array|string $search_keys
     * @param int $search_flag
     * @param null|string $file_path
     * @return bool
     */
    public function delete($search_keys, $search_flag = self::SEARCH_DEFAULT, $file_path = null)
    {
        $res                                                            = $this->read($file_path, $search_keys, $search_flag);

        return $this->write($res, $file_path);
    }

    /**
     * @param string $buffer
     * @param null|string $file_path
     * @param null|int $expire
     * @return bool
     */
    public function save($buffer, $file_path = null, $expire = null)
    {
        $rc                                                             = false;
        if(!Error::check("filemanager")) {
            if(!$file_path)                                             { $file_path = $this->getFilePath(); }
            $rc                                                         = $this->makeDir(dirname($file_path));
            if ($rc) {
                if (Filemanager::fsave($buffer, $file_path)) {
                    if ($expire !== null)                              { $this->touch($expire, $file_path); }
                }
            }
        }
        return $rc;
    }

    /**
     * @param string $buffer
     * @param null|string $file_path
     * @param null|int $expires
     * @return bool
     */
    public function saveAppend($buffer, $file_path = null, $expires = null)
    {
        if(!$file_path)                                                 { $file_path = $this->getFilePath(); }
        $rc                                                             = $this->makeDir(dirname($file_path));
        if ($rc) {
            if(Filemanager::fappend($buffer, $file_path)) {
                if($expires !== null)                                   { $this->touch($expires, $file_path); }
            }
        }

        return $rc;
    }

    /**
     * @param string $file_path
     * @param null|string $var
     * @return filemanagerAdapter
     */
    public function fetch($file_path, $var = null) {
        $this->setFilePath($file_path);
        if($var)                                                        { $this->setVar($var); }

        return $this;
    }
    /**
     * @param null|string $path
     * @return bool
     */
    public function makeDir($path = null)
    {
        $rc                                                             = true;
        if(!$path)                                                      { $path = dirname($this->file_path); }
        if(!is_dir($path))                                              { $rc = @mkdir($path, 0777, true); }

        return $rc;
    }




    /**
     * @param int $expires
     * @param null|string $file_path
     * @return bool
     */
    public function touch($expires, $file_path = null)
    {
        if(!$file_path)                                                 { $file_path = $this->getFilePath(); }
        $rc                                                             = @touch($file_path, $expires);

        return $rc;
    }

    /**
     * @param null|string $file_path
     * @return bool
     */
    public function isExpired($file_path = null)
    {
        if(!$file_path)                                                 { $file_path = $this->getFilePath(); }
        return (filemtime($file_path) >= filectime($file_path)
            ? false
            : true
        );
    }

    /**
     * @param null|string $file_path
     * @return bool
     */
    public function exist($file_path = null) {
        $file_path                                                      = ($file_path
                                                                            ? $file_path
                                                                            : $this->getFilePath()
                                                                        );

        return strpos(realpath($file_path), $this::$disk_path) === 0;
    }

    /**
     * @return null|string
     */
    public function getFilePath() {
        return $this->file_path;
    }

    /**
     * @param string $file_path
     * @param null|string $ext
     */
    public function setFilePath($file_path, $ext = null) {
        Error::clear("filemanager");
        if(!$ext)                                                       { $ext = $this::EXT; }

        $abs_path                                                       = dirname($file_path) . "/" . basename($file_path, "." . $ext) . "." . $ext;
        if(strpos($file_path, $this::$disk_path) !== 0)                 { $abs_path = $this::$disk_path . $abs_path; }

        if($this->exist($abs_path)) {
            $this->file_path                                            = $abs_path;
        } else {
            Error::register("File not found" . (Debug::ACTIVE ? ": " . $abs_path : ""), "filemanager");
        }
    }

    /**
     * @return null|string
     */
    public function getVar() {
        return $this->var;
    }

    /**
     * @param string $var
     */
    public function setVar($var) {
        $this->var                                                      = $var;
    }

    /**
     * @param array $data
     * @param string $search_keys
     * @param int $search_flag
     * @return array
     */
    protected function search($data, $search_keys, $search_flag = self::SEARCH_DEFAULT) {
        if(!is_array($search_keys))                                     { $search_keys = array($search_keys); }

        foreach($search_keys AS $key) {
            if($search_flag == $this::SEARCH_IN_KEY || $search_flag == $this::SEARCH_IN_BOTH) {
                unset($data[$key]);
            }
            if($search_flag == $this::SEARCH_IN_VALUE || $search_flag == $this::SEARCH_IN_BOTH) {
                $arrToDel                                               = array_flip(array_keys($data, $key));
                $data                                                   = array_diff_key($data, $arrToDel);
            }
        }

        return $data;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function getResult($result)
    {
        return (Error::check("filemanager")
            ? Error::raise("filemanager")
            : $result
        );
    }
}


class Filemanager extends DirStruct
{
    const TYPE                                                          = "filemanager";

    private static $singletons                                          = null;
    private static $storage                                             = null;
    private static $scanExclude                                         = null;

    const FTP_USERNAME                                                  = FTP_USERNAME;
    const FTP_PASSWORD                                                  = FTP_PASSWORD;

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
     * @return filemanagerAdapter
     */
    static public function getInstance($filemanagerAdapter, $file = null, $var = null, $expire = null)
    {
        if($filemanagerAdapter) {
            if (!isset(self::$singletons[$filemanagerAdapter])) {
                $class_name                                             = self::TYPE . ucfirst($filemanagerAdapter);
                self::$singletons[$filemanagerAdapter]                  = new $class_name($file, $var, $expire);
            }
        }

        return self::$singletons[$filemanagerAdapter];
    }


    public static function fappend($content, $file) {
        return self::fwrite($content, $file, "a");
    }
    public static function fsave($content, $file) {
        return self::fwrite($content, $file);

    }
    private static function fwrite($data, $file, $mode = "w") {
        $success = false;
        if($data && $file) {
            $handle = @fopen($file, $mode);
            if ($handle !== false) {
                if (flock($handle, LOCK_EX)) { // exclusive lock will blocking wait until obtained
                    if (@fwrite($handle, $data . "\n") !== FALSE) {
                        $success = true;
                    }
                    flock($handle, LOCK_UN); // unlock
                }
            }
            @fclose($handle);
        }
        return $success;
    }

    public static function xcopy($source, $destination) {
        $res                                                            = false;
        $ftp                                                            = self::ftp_xconnect();

        if($ftp) {
            $res                                                        = self::ftp_copy($ftp["conn"], $ftp["path"], $source, $destination, FF_DISK_PATH);
            @ftp_close($ftp["conn"]);
        }

        if(!$res) {
            self::full_copy(FF_DISK_PATH . $source, FF_DISK_PATH . $destination);
        }
        return $res;
    }

    public static function xpurge_dir($path) {
        $res                                                            = false;

        $ftp                                                            = self::ftp_xconnect();
        if($ftp) {
            $res                                                        = self::ftp_purge_dir($ftp["conn"], $ftp["path"], $path, FF_DISK_PATH);
            @ftp_close($ftp["conn"]);
        }

        if(!$res) {
            $res                                                        = self::purge_dir(FF_DISK_PATH . $path, $path);
        }

        return $res;
    }

    /**
     * FTP
     */
    private static function ftp_xconnect() {
        $res                                                            = null;
        if(self::FTP_USERNAME && self::FTP_PASSWORD) {
            $conn_id = @ftp_connect("localhost");
            if($conn_id === false)
                $conn_id = @ftp_connect("127.0.0.1");
            if($conn_id === false)
                $conn_id = @ftp_connect($_SERVER["SERVER_ADDR"]);

            if($conn_id !== false) {
                // login with username and password
                if(@ftp_login($conn_id, self::FTP_USERNAME, self::FTP_PASSWORD)) {
                    $local_path = self::getDiskPath();
                    $part_path = "";
                    $real_ftp_path = NULL;

                    foreach(explode("/", $local_path) AS $curr_path) {
                        if(strlen($curr_path)) {
                            $ftp_path = str_replace($part_path, "", $local_path);
                            if(@ftp_chdir($conn_id, $ftp_path)) {
                                $real_ftp_path = $ftp_path;
                                break;
                            }

                            $part_path .= "/" . $curr_path;
                        }
                    }
                    if($real_ftp_path === NULL) {
                        if(@ftp_chdir($conn_id, "/")) {
                            $real_ftp_path = "";
                        }
                    }

                    if($real_ftp_path) {
                        $res = array(
                            "conn" => $conn_id
                            , "path" => $real_ftp_path
                        );
                    } else {
                        @ftp_close($conn_id);
                    }
                }
            }
        }

        return $res;
    }

    private static function ftp_copy($conn_id, $ftp_disk_path, $source, $dest, $local_disk_path = null) {
        $absolute_path = dirname($ftp_disk_path . $dest);

        $res = true;
        if (!@ftp_chdir($conn_id, $absolute_path)) {
            $parts = explode('/', trim(ffCommon_dirname($dest), "/"));
            @ftp_chdir($conn_id, $ftp_disk_path);
            foreach($parts as $part) {
                if(!@ftp_chdir($conn_id, $part)) {
                    $res = $res && @ftp_mkdir($conn_id, $part);
                    $res = $res && @ftp_chmod($conn_id, 0755, $part);

                    @ftp_chdir($conn_id, $part);
                }
            }

            if(!$res && $local_disk_path && !is_dir(dirname($local_disk_path . $dest))) {
                $res = @mkdir(dirname($local_disk_path . $dest), 0777, true);
            }
        }

        if($res) {
            if(!is_dir(FF_DISK_UPDIR . "/tmp")) {
                $res = @mkdir(FF_DISK_UPDIR . "/tmp", 0777);
            } elseif(substr(sprintf('%o', fileperms(FF_DISK_UPDIR . "/tmp")), -4) != "0777") {
                $res = @chmod(FF_DISK_UPDIR . "/tmp", 0777);
            }
            if($res) {
                $res = ftp_get($conn_id, FF_DISK_UPDIR . "/tmp/" . basename($dest), $ftp_disk_path . $source, FTP_BINARY);
                if($res) {
                    $res = $res && ftp_put($conn_id, $ftp_disk_path . $dest, FF_DISK_UPDIR . "/tmp/" . basename($dest), FTP_BINARY);

                    $res = $res && @ftp_chmod($conn_id, 0644, $ftp_disk_path . $dest);

                    @unlink(FF_DISK_UPDIR . "/tmp/" . basename($dest));
                }
            }
            if(!$res && $local_disk_path && !is_file($local_disk_path . $dest)) {
                $res = @copy($local_disk_path . $source, $local_disk_path . $dest);
                $res = $res && @chmod($local_disk_path . $dest, 0666);
            }
        }

        return $res;
    }

    private static function ftp_purge_dir($conn_id, $ftp_disk_path, $relative_path, $local_disk_path = null) {
        $absolute_path = $ftp_disk_path . $relative_path;

        $res = true;
        if (@ftp_chdir($conn_id, $absolute_path)) {
            $handle = @ftp_nlist($conn_id, "-la " . $absolute_path);
            if (is_array($handle) && count($handle)) {
                foreach($handle AS $file) {
                    if(basename($file) != "." && basename($file) != "..") {
                        if(strlen($ftp_disk_path))
                            $real_file = substr($file, strlen($ftp_disk_path));
                        else
                            $real_file = $file;

                        if (@ftp_chdir($conn_id, $file)) {
                            $res = ($res && self::ftp_purge_dir($conn_id, $ftp_disk_path, $real_file, $local_disk_path));
                            @ftp_rmdir($conn_id, $file);
                            if($local_disk_path !== null)
                                @rmdir($local_disk_path . $real_file);
                        } else {
                            if(!@ftp_delete($conn_id, $file)) {
                                if($local_disk_path === null) {
                                    $res = false;
                                } else {
                                    if(!@unlink($local_disk_path . $real_file)) {
                                        $res = false;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if(!@ftp_rmdir($conn_id, $absolute_path)) {
                if($local_disk_path === null) {
                    $res = false;
                } else {
                    if(!@rmdir($local_disk_path . $relative_path)) {
                        $res = false;
                    }
                }
            }
        } else {
            if(!@ftp_delete($conn_id, $absolute_path)) {
                if($local_disk_path === null) {
                    $res = false;
                } else {
                    if(!@unlink($local_disk_path . $relative_path)) {
                        $res = false;
                    }
                }
            }
        }
        return $res;
    }

    private static function full_copy( $source, $target, $delete_source = false ) {
        if(!$source || !$target || stripslash($source) == FF_DISK_UPDIR || stripslash($target) == FF_DISK_UPDIR || $source == $target)
            return;

        if (file_exists($source) && is_dir( $source ) ) {
            $disable_rmdir = false;

            @mkdir( $target, 0777, true );
            $d = dir( $source );
            while ( FALSE !== ( $entry = $d->read() ) ) {
                if (strpos($entry, ".") === 0 ) {
                    continue;
                }

                if($source . '/' . $entry == $target) {
                    $disable_rmdir = true;
                    continue;
                }
                if ( is_dir( $source . '/' . $entry )) {
                    self::full_copy( $source . '/' . $entry, $target . '/' . $entry, $delete_source );
                    continue;
                }

                @copy( $source . '/' . $entry, $target . '/' . $entry );
                @chmod( $target . '/' . $entry, 0777);
                if($delete_source)
                    @unlink($source . '/' . $entry);
            }

            $d->close();
            if($delete_source && !$disable_rmdir)
                @rmdir($source);
        } elseif(file_exists($source) && is_file($source)) {
            @mkdir( ffcommon_dirname($target), 0777, true );

            @copy( $source, $target );
            @chmod( $target, 0777);
            if($delete_source)
                @unlink($source);
        }
    }
    //Procedura per cancellare i file/cartelle e le correlazioni nel db
    private static function purge_dir($absolute_path, $relative_path, $delete_db = true, $exclude_dir = false) {
        if (file_exists($absolute_path) && is_dir($absolute_path)) {
            $handle = opendir($absolute_path);
            if ($handle !== false) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($absolute_path . "/" . $file)) {
                            self::purge_dir($absolute_path . "/" . $file, $relative_path . "/" . $file);
                        } else {
                            if(is_file($absolute_path . "/" . $file))
                                @unlink($absolute_path . "/" . $file);
                            if($delete_db)
                                self::delete_file_from_db($relative_path, $file);
                        }
                    }
                }
                if(!$exclude_dir)
                    @rmdir ($absolute_path);
                if($delete_db)
                    self::delete_file_from_db(ffCommon_dirname($relative_path), basename($relative_path));
            }
        } else {
            if(file_exists($absolute_path) && is_file($absolute_path))
                @unlink($absolute_path);

            if($delete_db)
                self::delete_file_from_db(ffCommon_dirname($relative_path), basename($relative_path));
        }
    }

    /**
     * DB
     */
    private static function delete_file_from_db($strPath, $strFile, $exclude = null) {
        $db = ffDB_Sql::factory();
        // $db = Database::getInstance("sql");

        if($exclude !== null && strlen($exclude)) {
            $sSQL_addit = " AND files.ID NOT IN (" . $db->toSql($exclude, "Text", false) . ")";
        } else {
            $sSQL_addit = "";
        }
        $sSQL = "SELECT ID from files WHERE parent = " . $db->toSql($strPath, "Text") . " AND name = " . $db->toSql($strFile, "Text") . $sSQL_addit;
        $db->query($sSQL);
        if ($db->nextRecord()) {
            $ID = $db->getField("ID")->getValue();
            $sSQL = "DELETE FROM files_rel_groups WHERE ID_files = " . $db->toSql($ID, "Number");
            $db->query($sSQL);

            $sSQL = "DELETE FROM files_rel_languages WHERE ID_files = " . $db->toSql($ID, "Number");
            $db->query($sSQL);

            $sSQL = "DELETE FROM files_description WHERE ID_files = " . $db->toSql($ID, "Number");
            $db->query($sSQL);

            $sSQL = "DELETE FROM rel_nodes WHERE contest_src = " . $db->toSql("files", "Text") . " AND ID_node_src = " . $db->toSql($ID, "Number");
            $db->query($sSQL);

            $sSQL = "DELETE FROM rel_nodes WHERE contest_dst = " . $db->toSql("files", "Text") . " AND ID_node_dst = " . $db->toSql($ID, "Number");
            $db->query($sSQL);

            $sSQL = "DELETE FROM files WHERE ID = " . $db->toSql($ID, "Number");
            $db->query($sSQL);
        }
    }

/*
    private static function recursiveiterator($pattern, $filter = null) {
        if(is_array($filter)) {
            $limit = '\.(?:' . implode("|", $filter). ')';
        }

        $directory = new RecursiveDirectoryIterator($pattern, RecursiveDirectoryIterator::SKIP_DOTS);
        $flattened = new RecursiveIteratorIterator($directory);

        // Make sure the path does not contain "/.Trash*" folders and ends eith a .php
        $files = new RegexIterator($flattened, '#^(?:[A-Z]:)?(?:/(?!\.Trash)[^/]+)+/[^/]+' . $limit . '$#Di');

        foreach ($files as $file) {
            self::scanAddItem($file->getPathname());
        }
    }*/
/*
    private static function DirectoryIterator($pattern, $recursive = false) {
        foreach (new DirectoryIterator($pattern) as $fileInfo) {
            if($fileInfo->isDot()) { continue; }
            if($fileInfo->isDir()) {
                self::scanAddItem($fileInfo->getPathname());
                if($recursive) {
                    self::DirectoryIterator($fileInfo->getPathname(), true);
                }
            }
        }
    }
    */
/*
    private static function RecursiveDirectoryIterator($pattern) {
        foreach ($iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pattern,
                RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST) as $fileInfo) {

            if($fileInfo->isDir()) {
                self::scanAddItem($fileInfo->getPathname());
            }
        }
    }*/
    /*
    private static function readdir($pattern, $recursive = false) {
        if (($handle = opendir($pattern))) {
            while (($file = readdir($handle)) !== false) {
                if (($file == '.') || ($file == '..')) { continue; }

                if (is_dir($pattern . "/" . $file)) {
                    self::scanAddItem($pattern . "/" . $file);
                    if($recursive) {
                        self::readdir($pattern . "/" . $file, true);
                    }
                }
            }
            closedir($handle);
        }
    }*/
    public static function scanExclude($patterns) {
        if($patterns) {
            if ($patterns[0]) {
                $patterns = array_fill_keys($patterns, true);
            }

            self::$scanExclude = array_replace((array)self::$scanExclude, $patterns);
        }
    }
    public static function scan($patterns, $what = null, $callback = null) {
        if(is_array($patterns) && !$callback) {
            $callback = $what;
        }

        self::$storage["scan"]   = array(
            "rawdata" => array()
            , "callback" => $callback
        );

        if(is_array($patterns) && count($patterns)) {
            foreach($patterns AS $pattern => $opt) {
                self::scanRun($pattern, $opt);
            }
        } else {
            self::scanRun($patterns, $what);
        }

        return self::$storage;
    }

    private static function scanAddItem($file, $opt = null) {
        if(is_callable(self::$storage["scan"]["callback"])) {
            $file_info = pathinfo($file);
            if($opt["filter"] && !$opt["filter"][$file_info["extension"]]) {
                return;
            }
            if($opt["name"] && !$opt["name"][$file_info["basename"]]) {
                return;
            }

            $callback = self::$storage["scan"]["callback"];
            $callback($file, self::$storage);
        } elseif(!$opt) {
            self::$storage["scan"]["rawdata"][] = $file;
        } else {
            $file_info = pathinfo($file);
            if($opt["filter"] && !$opt["filter"][$file_info["extension"]]) {
                return;
            }
            if($opt["name"] && !$opt["name"][$file_info["basename"]]) {
                return;
            }

            if($opt["rules"] && !self::setStorage($file_info, $opt["rules"])) {
                self::$storage["unknowns"][$file_info["basename"]] = $file;
            }
        }
    }

    private static function scanRun($pattern, $what = null) {
        $pattern = (strpos($pattern, self::$disk_path) === 0
                ? ""
                : self::$disk_path
            )
            . $pattern
            . (strpos($pattern, "*") === false
                ? '/*'
                : ''
            );

        $flag = ($what["flag"]
            ? $what["flag"]
            : $what
        );


        if($what["filter"] && isset($what["filter"][0])) {
            $what["filter"] = array_combine($what["filter"], $what["filter"]);
        }
        if($what["name"] && isset($what["name"][0])) {
            $what["name"] = array_combine($what["name"], $what["name"]);
        }

        switch ($flag) {
            case Filemanager::SCAN_DIR:
                if(self::$storage["scan"]["callback"]) {
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
                self::rglobfilter($pattern);
                break;
            case null;
                self::rglob($pattern);
                break;
            default:
                self::rglobfilter($pattern, $what);
        }
    }

    private static function glob_dir($pattern) {
        self::$storage["scan"] = glob($pattern, GLOB_ONLYDIR);
    }
    private static function glob_dir_callback($pattern) {
        foreach(glob($pattern, GLOB_ONLYDIR) AS $file) {
            self::scanAddItem($file);
        }
    }
    private static function rglob_dir($pattern) {
        foreach(glob($pattern, GLOB_ONLYDIR) AS $file) {
            self::scanAddItem($file);
            self::rglob_dir($file . '/*');
        }
    }

    private static function glob($pattern, $opt = null) {
        $flags = null;
        $limit = null;
        if(is_array($opt["filter"])) {
            $flags = GLOB_BRACE;
            $limit = ".{" . implode(",", $opt["filter"]) . "}";
        }

        foreach(glob($pattern . $limit, $flags) AS $file) {
            if($opt === false || is_file($file)) {
                self::scanAddItem($file, $opt["rules"]);
            }
        }
    }
    private static function rglob($pattern) {
        foreach(glob($pattern) AS $file) {
            if(is_file($file)) {
                self::scanAddItem($file);
            } else {
                self::rglob($file . '/*');
            }
        }
    }

    private static function rglobfilter($pattern, $opt = null) {
        $final_dir = basename(dirname($pattern)); //todo:: da togliere
        if (self::$scanExclude[$final_dir]) {
            return;
        }
        foreach(glob($pattern) AS $file) {
            if(is_file($file)) {
                self::scanAddItem($file, $opt);
            } else {
                if($opt === false) {
                    self::scanAddItem($file);
                }
                self::rglobfilter($file . '/*', $opt);
            }
        }
    }

    private static function setStorage($file_info, $rules) {
        if(is_array($rules) && count($rules)) {
            $key = $file_info["filename"];
            $file = $file_info["dirname"] . "/" . $file_info["basename"];

            foreach($rules AS $rule => $params) {
                if(strpos($file, $rule) !== false) {
                    if($params["ext"] && !$params["ext"][$file_info["extension"]]) {
                        continue;
                    }
                    self::$storage[is_array($params)
                                        ? $params["type"]
                                        : $params
                                    ][$key] = $file;
                    return true;
                }
            }
        }
        return false;
    }
}