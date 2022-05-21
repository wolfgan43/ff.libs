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

/**
 * Class FilemanagerAdapter
 * @package ff\libs\storage
 */
abstract class FilemanagerAdapter
{
    const ERROR_BUCKET                                                  = "storage";
    const EXT                                                           = null;
    const SEARCH_IN_KEY                                                 = 1;
    const SEARCH_IN_VALUE                                               = 2;
    const SEARCH_IN_BOTH                                                = 3;
    const SEARCH_DEFAULT                                                = self::SEARCH_IN_KEY;

    private $file_path                                                  = null;
    private $var                                                        = null;

    /**
     * FilemanagerAdapter constructor.
     * @param string|null $file_path
     * @param string|null $var
     */
    public function __construct(string $file_path = null, string $var = null)
    {
        $this->setFilePath($file_path);
        $this->setVar($var);
    }

    /**
     * @param string $file_path
     * @param string|null $var
     * @return array|null
     */
    abstract protected function loadFile(string $file_path, string $var = null) : ?array;

    /**
     * @param array $data
     * @param string|null $var
     * @return string
     */
    abstract protected function output(array $data, string $var = null) : string;

    /**
     * @param null|string $file_path
     * @param null|array $search_keys
     * @param int $search_flag
     * @return array|null
     */
    public function read(string $file_path = null, array $search_keys = null, int $search_flag = self::SEARCH_DEFAULT) : ?array
    {
        $res                                                            = null;

        $params                                                         = $this->getParams($file_path);
        if ($params) {
            $return                                                     = $this->loadFile($params->file_path);
            if ($return) {
                if ($search_keys) {
                    $res                                                = $this->search($return, $search_keys, $search_flag);
                } else {
                    $res                                                = $return;
                }
            }
        }
        return $res;
    }

    /**
     * @param array $data
     * @param null|string $file_path
     * @param null|string $var
     * @return bool
     */
    public function write(array $data, string $file_path = null, string $var = null) : bool
    {
        $params                                                         = $this->setParams($file_path, $var);
        $output                                                         = $this->output($data, $params->var);

        return $this->save($output, $params->file_path);
    }

    /**
     * @param array $data
     * @param null|string $var
     * @param null|string $file_path
     * @return bool
     */
    public function update(array $data, string $var = null, string $file_path = null) : bool
    {
        $res                                                            = (
            is_array($data)
                                                                            ? array_replace($this->read(), $data)
                                                                            : $data
                                                                        );

        return $this->write($res, $file_path, $var);
    }

    /**
     * @param array $search_keys
     * @param int $search_flag
     * @param null|string $file_path
     * @return bool
     */
    public function delete(array $search_keys, int $search_flag = self::SEARCH_DEFAULT, string $file_path = null) : bool
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
    public function save(string $buffer, string $file_path = null, int $expire = null) : bool
    {
        if (!$file_path) {
            $file_path = $this->getFilePath();
        }
        $rc                                                         = $this->isValid($file_path) && $this->makeDir(dirname($file_path));
        if ($rc && FilemanagerFs::fsave($buffer, $file_path) && $expire !== null) {
            $this->touch($expire, $file_path);
        }
        return $rc;
    }

    /**
     * @param string $buffer
     * @param null|string $file_path
     * @param null|int $expires
     * @return bool
     */
    public function saveAppend(string $buffer, string $file_path = null, int $expires = null) : bool
    {
        if (!$file_path) {
            $file_path = $this->getFilePath();
        }
        $rc                                                             = $this->isValid($file_path) && $this->makeDir(dirname($file_path));
        if ($rc && FilemanagerFs::fappend($buffer, $file_path) && $expires !== null) {
            $this->touch($expires, $file_path);
        }

        return $rc;
    }

    /**
     * @param string $file_path
     * @param null|string $var
     * @return FilemanagerAdapter
     */
    public function fetch(string $file_path, string $var = null) : FilemanagerAdapter
    {
        $this->setFilePath($file_path);
        $this->setVar($var);

        return $this;
    }
    /**
     * @param null|string $path
     * @return bool
     */
    public function makeDir(string $path = null) : bool
    {
        $rc                                                             = true;
        if (!$path) {
            $path                                                       = dirname($this->file_path);
        }
        if (!is_dir($path)) {
            $rc                                                         = @mkdir($path, 0777, true);
        }

        return $rc;
    }




    /**
     * @param int $expires
     * @param null|string $file_path
     * @return bool
     */
    public function touch(int $expires, string $file_path = null) : bool
    {
        if (!$file_path) {
            $file_path                                                  = $this->getFilePath();
        }
        return @touch($file_path, $expires);
    }

    /**
     * @param null|string $file_path
     * @return bool
     */
    public function isExpired(string $file_path = null) : bool
    {
        if (!$file_path) {
            $file_path                                                  = $this->getFilePath();
        }
        return filemtime($file_path) < filectime($file_path);
    }

    /**
     * @param string $file_path
     * @return bool
     */
    private function isValid(string $file_path) : bool
    {
        return (Kernel::$Environment::DEBUG || strpos($file_path, Kernel::$Environment::DISK_PATH) === 0);
    }

    /**
     * @param null|string $file_path
     * @return bool
     */
    public function exist(string $file_path = null) : bool
    {
        $file_path                                                      = (
            $file_path
                                                                            ? $file_path
                                                                            : $this->getFilePath()
                                                                        );

        return $this->isValid(realpath($file_path));
    }

    /**
     * @return null|string
     */
    public function getFilePath() : ?string
    {
        return $this->file_path;
    }

    /**
     * @param string|null $file_path
     */
    public function setFilePath(string $file_path = null) : void
    {
        if ($file_path) {
            $abs_path                                               = $file_path;
            if (!pathinfo($abs_path, PATHINFO_EXTENSION)) {
                $abs_path                                           .= "." . $this::EXT;
            }
            if (strpos($file_path, Kernel::$Environment::DISK_PATH) !== 0) {
                $abs_path                                           = Kernel::$Environment::DISK_PATH . $abs_path;
            }
            $this->file_path                                        = $abs_path;
        }
    }

    /**
     * @return null|string
     */
    public function getVar() : ?string
    {
        return $this->var;
    }

    /**
     * @param string|null $var
     */
    public function setVar(string $var = null) : void
    {
        if ($var) {
            $this->var                                              = $var;
        }
    }

    /**
     * @param string $file_path
     * @param string|null $var
     * @return object
     *
     */
    protected function setParams(string $file_path, string $var = null) : object
    {
        $this->setFilePath($file_path);
        $this->setVar($var);

        return (object) array(
            "file_path"     => $this->getFilePath(),
            "var"           => $this->getVar()
        );
    }

    /**
     * @param string $file_path
     * @return object|null
     */
    protected function getParams(string $file_path) : ?object
    {
        $this->setFilePath($file_path);
        if (!$this->exist($this->getFilePath())) {
            return null;
        }

        return (object) array(
            "file_path"     => $this->getFilePath(),
            "var"           => $this->getVar()
        );
    }

    /**
     * @param array $data
     * @param array $search_keys
     * @param int $search_flag
     * @return array
     */
    protected function search(array $data, array $search_keys, int $search_flag = self::SEARCH_DEFAULT) : array
    {
        foreach ($search_keys as $key) {
            if ($search_flag == $this::SEARCH_IN_KEY || $search_flag == $this::SEARCH_IN_BOTH) {
                unset($data[$key]);
            }
            if ($search_flag == $this::SEARCH_IN_VALUE || $search_flag == $this::SEARCH_IN_BOTH) {
                $arrToDel                                               = array_flip(array_keys($data, $key));
                $data                                                   = array_diff_key($data, $arrToDel);
            }
        }

        return $data;
    }
}
