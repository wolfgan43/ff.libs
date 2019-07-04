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
use phpformsframework\libs\Dir;
use phpformsframework\libs\Error;
use phpformsframework\libs\storage\FilemanagerAdapter;

class FilemanagerPhp extends FilemanagerAdapter
{
    const EXT                                                   = "php";

    public function read($file_path = null, $search_keys = null, $search_flag = self::SEARCH_DEFAULT)
    {
        $res                                                    = array();

        $params                                                 = $this->getParams($file_path);
        if (!$params) {
            return false;
        }

        $output                                                 = exec("php -l " . addslashes($params->file_path));

        if (strpos($output, "No syntax errors") === 0) {
            $include                                            = Dir::autoload($params->file_path);
            if ($include === 1) {
                if (!$params->var) {
                    $arrDefVars                                 = get_defined_vars();
                    end($arrDefVars);
                    $params->var                                        = key($arrDefVars);
                    if ($params->var == "output") {
                        $params->var = null;
                    } else {
                        $this->setVar($params->var);
                    }
                }
                $return = ${$params->var};
            } else {
                $return = $include;
                if ($params->var) {
                    $return = $return[$params->var];
                }
            }

            if ($return) {
                if ($search_keys) {
                    $res                                        = $this->search($return, $search_keys, $search_flag);
                } else {
                    $res                                        = $return;
                }
            } else {
                $res                                            = null;
            }

            return $this->getResult($res);
        } else {
            Error::register("syntax errors into file" . (Constant::DEBUG ? ": " . $params->file_path : ""), static::ERROR_BUCKET);
        }

        return null;
    }

    public function write($data, $file_path = null, $var = null)
    {
        $params                                                 = $this->setParams($file_path, $var);

        if ($params->var) {
            $return = '$' . $params->var . ' = ';
        } else {
            $return = 'return ';
        }

        return $this->save("<?php\n" . ' '. $return . var_export($data, true) . ";", $params->file_path);
    }
}
