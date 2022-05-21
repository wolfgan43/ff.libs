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

use ff\libs\Kernel;
use ff\libs\Constant;
use ff\libs\util\ServerManager;

/**
 * Class FilemanagerFtp
 * @package ff\libs\storage
 */
class FilemanagerFtp
{
    use ServerManager;

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function copy(string $source, string $destination) : bool
    {
        $res                                                            = false;
        $ftp                                                            = self::ftpConnect();

        if ($ftp) {
            $res                                                        = self::ftpCopy($ftp["conn"], $ftp["path"], $source, $destination, Constant::DISK_PATH);
            @ftp_close($ftp["conn"]);
        }

        return $res;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function delete(string $path) : bool
    {
        $res                                                            = false;

        $ftp                                                            = self::ftpConnect();
        if ($ftp) {
            $res                                                        = self::ftpDelete($ftp["conn"], $ftp["path"], $path, Constant::DISK_PATH);
            @ftp_close($ftp["conn"]);
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
                $conn_id = @ftp_connect(self::serverAddr());
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
                    $res = ftp_put($conn_id, $ftp_disk_path . $dest, Constant::UPLOAD_DISK_PATH . "/tmp/" . basename($dest), FTP_BINARY);

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
    private static function ftpDelete($conn_id, string $ftp_disk_path, string $relative_path, string $local_disk_path = null) : bool
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
                            $res = ($res && self::ftpDelete($conn_id, $ftp_disk_path, $real_file, $local_disk_path));
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
}
