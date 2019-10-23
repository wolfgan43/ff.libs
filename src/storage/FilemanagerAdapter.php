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
use phpformsframework\libs\Kernel;

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

    public function __construct($file_path = null, $var = null)
    {
        $this->setFilePath($file_path);
        $this->setVar($var);
    }

    /**
     * @todo da tipizzare
     * @param string $file_path
     * @param string|null $var
     * @return mixed
     */
    abstract protected function loadFile(string $file_path, string $var = null);
    abstract protected function output($data, $var);

    /**
     * @param null|string $file_path
     * @param null|string $search_keys
     * @param int $search_flag
     * @return bool|null|array
     */
    public function read($file_path = null, $search_keys = null, $search_flag = self::SEARCH_DEFAULT)
    {
        $res                                                            = null;

        $params                                                         = $this->getParams($file_path);
        if (!$params) {
            return false;
        }

        $return                                                         = $this->loadFile($params->file_path);
        if ($return) {
            if ($search_keys) {
                $res                                                    = $this->search($return, $search_keys, $search_flag);
            } else {
                $res                                                    = $return;
            }
        } elseif ($return === false) {
            Error::registerWarning("syntax errors into file" . (Kernel::$Environment::DEBUG ? ": " . $params->file_path : ""), static::ERROR_BUCKET);
            return false;
        }

        return $this->getResult($res);
    }

    /**
     * @param array $data
     * @param null|string $file_path
     * @param null|string $var
     * @return bool
     */
    public function write($data, $file_path = null, $var = null)
    {
        $params                                                 = $this->setParams($file_path, $var);
        $output                                                 = $this->output($data, $params->var);

        return $this->save($output, $params->file_path);
    }

    /**
     * @param array $data
     * @param null|string $var
     * @param null|string $file_path
     * @return bool
     */
    public function update($data, $var = null, $file_path = null)
    {
        $res                                                            = (
            is_array($data)
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
        if (!Error::check(static::ERROR_BUCKET)) {
            if (!$file_path) {
                $file_path = $this->getFilePath();
            }
            $rc                                                         = $this->isValid($file_path) && $this->makeDir(dirname($file_path));
            if ($rc && Filemanager::fsave($buffer, $file_path) && $expire !== null) {
                $this->touch($expire, $file_path);
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
        if (!$file_path) {
            $file_path = $this->getFilePath();
        }
        $rc                                                             = $this->isValid($file_path) && $this->makeDir(dirname($file_path));
        if ($rc && Filemanager::fappend($buffer, $file_path) && $expires !== null) {
            $this->touch($expires, $file_path);
        }

        return $rc;
    }

    /**
     * @param string $file_path
     * @param null|string $var
     * @return FilemanagerAdapter
     */
    public function fetch($file_path, $var = null)
    {
        $this->setFilePath($file_path);
        $this->setVar($var);

        return $this;
    }
    /**
     * @param null|string $path
     * @return bool
     */
    public function makeDir($path = null)
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
    public function touch($expires, $file_path = null)
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
    public function isExpired($file_path = null)
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
    private function isValid($file_path)
    {
        return (Kernel::$Environment::DEBUG || strpos($file_path, Constant::DISK_PATH) === 0);
    }

    /**
     * @param null|string $file_path
     * @return bool
     */
    public function exist($file_path = null)
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
    public function getFilePath()
    {
        return $this->file_path;
    }

    /**
     * @param string $file_path
     */
    public function setFilePath($file_path)
    {
        if ($file_path) {
            Error::clear(static::ERROR_BUCKET);

            $abs_path                                               = $file_path;
            if (!pathinfo($abs_path, PATHINFO_EXTENSION)) {
                $abs_path                                           .= "." . $this::EXT;
            }
            if (strpos($file_path, Constant::DISK_PATH) !== 0) {
                $abs_path                                           = Constant::DISK_PATH . $abs_path;
            }
            $this->file_path                                        = $abs_path;
        }
    }

    /**
     * @return null|string
     */
    public function getVar()
    {
        return $this->var;
    }

    /**
     * @param string $var
     */
    public function setVar($var)
    {
        if ($var) {
            $this->var                                              = $var;
        }
    }
    protected function setParams($file_path, $var)
    {
        $this->setFilePath($file_path);
        $this->setVar($var);

        return (object) array(
            "file_path"     => $this->getFilePath(),
            "var"           => $this->getVar()
        );
    }
    protected function getParams($file_path)
    {
        $this->setFilePath($file_path);
        if (!$this->exist($this->getFilePath())) {
            return false;
        }

        return (object) array(
            "file_path"     => $this->getFilePath(),
            "var"           => $this->getVar()
        );
    }

    /**
     * @param array $data
     * @param string $search_keys
     * @param int $search_flag
     * @return array
     */
    protected function search($data, $search_keys, $search_flag = self::SEARCH_DEFAULT)
    {
        if (!is_array($search_keys)) {
            $search_keys = array($search_keys);
        }

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

    /**
     * @param array $result
     * @return array
     */
    protected function getResult($result)
    {
        return $result;
    }
}
