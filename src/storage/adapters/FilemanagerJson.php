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
use phpformsframework\libs\Error;
use phpformsframework\libs\storage\FilemanagerAdapter;

class FilemanagerJson extends FilemanagerAdapter //todo: da finire
{
    const EXT                                                   = "json";

    public function read($file_path = null, $search_keys = null, $search_flag = self::SEARCH_DEFAULT)
    {
        $res                                                    = array();

        $params                                                 = $this->getParams($file_path);
        if (!$params) {
            return false;
        }

        $json                                                   = file_get_contents($params->file_path);
        if ($json) {
            $return                                             = json_decode($json, true);
            if ($return) {
                if ($search_keys) {
                    $res                                        = $this->search($return, $search_keys, $search_flag);
                } else {
                    $res                                        = $return;
                }
            } elseif ($return === false) {
                Error::register("syntax errors into file" . (Constant::DEBUG ? ": " . $params->file_path : ""), static::ERROR_BUCKET);
            } else {
                $res                                            = null;
            }
            return $this->getResult($res);
        } else {
            Error::register("syntax errors into file" . (Constant::DEBUG ? ": " . $params->file_path : ""), static::ERROR_BUCKET);
        }

        return null;
    }

    /**
     * @param array $data
     * @param null $file_path
     * @param null $var
     * @return bool
     */
    public function write($data, $file_path = null, $var = null)
    {
        $params                                                 = $this->setParams($file_path, $var);

        $root_node                                              = (
            $params->var
                                                                    ? array($params->var => $data)
                                                                    : $data
                                                                );

        return $this->save(json_encode($root_node), $params->file_path);
    }
}
