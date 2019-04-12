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

use phpformsframework\libs\Error;
use phpformsframework\libs\Debug;

class filemanagerPhp extends filemanagerAdapter
{
    const EXT                                                   = "php";

    public function read($file_path = null, $search_keys = null, $search_flag = self::SEARCH_DEFAULT) {
        $res                                                    = array();
        if($file_path)                                          { $this->setFilePath($file_path); }
        $file_path                                              = $this->getFilePath();
        $var                                                    = $this->getVar();

        $output                                                 = exec("php -l " . addslashes($file_path));

        if(strpos($output, "No syntax errors") === 0) {
            $include                                            = $this->autoload($file_path);
            if($include === 1) {
                if(!$var) {
                    $arrDefVars                                 = get_defined_vars();
                    end($arrDefVars);
                    $var                                        = key($arrDefVars);
                    if($var == "output")
                        $var                                    = null;
                    else
                        $this->setVar($var);
                }
                $return = ${$var};
            } else {
                $return = $include;
                if($var)
                    $return = $return[$var];
            }

            if($return) {
                if($search_keys) {
                    $res                                        = $this->search($res, $search_keys, $search_flag);
                } else {
                    $res                                        = $return;
                }
            } else {
                $res                                            = null;
            }
        } else {
            Error::register("syntax errors into file" . (Debug::ACTIVE ? ": " . $file_path : ""), "filemanager");
        }

        return $this->getResult($res);
    }

    public function write($data, $file_path = null, $var = null)
    {
        if($file_path)                                          { $this->setFilePath($file_path); }
        if($var)                                                { $this->setVar($var); }

        $file_path                                              = $this->getFilePath();
        $var                                                    = $this->getVar();

        if($var) {
            $return = '$' . $var . ' = ';
        } else {
            $return = 'return ';
        }

        return $this->save("<?php\n" . ' '. $return . var_export($data, true) . ";", $file_path);
    }


}