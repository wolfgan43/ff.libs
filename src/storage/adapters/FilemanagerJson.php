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
namespace phpformsframework\libs\storage\adapters;

use phpformsframework\libs\Constant;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Error;
use phpformsframework\libs\storage\FilemanagerAdapter;

class FilemanagerJson extends FilemanagerAdapter //todo: da finire
{
    const EXT                                                   = "json";

    public function read($file_path = null, $search_keys = null, $search_flag = self::SEARCH_DEFAULT)
    {
        $res                                                    = array();
        if($file_path)                                          { $this->setFilePath($file_path); }
        $file_path                                              = $this->getFilePath();

        $json                                                   = file_get_contents($file_path);
        if($json) {
            $return                                             = json_decode($json, true);
            if($return) {
                if($search_keys) {
                    $res                                        = $this->search($return, $search_keys, $search_flag);
                } else {
                    $res                                        = $return;
                }
            } elseif($return === false) {
                Error::register("syntax errors into file" . (Constant::DEBUG ? ": " . $file_path : ""));
            } else {
                $res                                            = null;
            }
        } else {
            Error::register("syntax errors into file" . (Constant::DEBUG ? ": " . $file_path : ""), "filemanager");
        }

        return $this->getResult($res);
    }

    public function write($data, $file_path = null, $var = null)
    {
        if($file_path)                                          { $this->setFilePath($file_path); }
        if($var)                                                { $this->setVar($var); }

        $file_path                                              = $this->getFilePath();
        $var                                                    = $this->getVar();

        $root_node                                              = ($var
                                                                    ? array($var => $data)
                                                                    : $data
                                                                );

        return $this->save(json_encode($root_node), $file_path);
    }

}