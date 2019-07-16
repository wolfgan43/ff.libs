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

use phpformsframework\libs\Dir;
use phpformsframework\libs\storage\FilemanagerAdapter;

class FilemanagerPhp extends FilemanagerAdapter
{
    const EXT                                                   = "php";

    protected function load_file($file_path, $var = null)
    {
        $return                                                 = null;
        $output                                                 = exec("php -l " . addslashes($file_path));
        if (strpos($output, "No syntax errors") === 0) {
            $include                                            = Dir::autoload($file_path);
            if ($include === 1) {
                if (!$var) {
                    $arrDefVars                                 = get_defined_vars();
                    end($arrDefVars);
                    $var                                        = key($arrDefVars);
                    if ($var == "output") {
                        $var                                    = null;
                    } else {
                        $this->setVar($var);
                    }
                }
                $return                                         = ${$var};
            } else {
                $return                                         = $include;
                if ($var) {
                    $return                                     = $return[$var];
                }
            }
        } else {
            $return                                             = false;
        }

        return $return;
    }

    protected function output($data, $var)
    {
        if ($var) {
            $return = '$' . $var . ' = ';
        } else {
            $return = 'return ';
        }

        return "<?php\n" . ' '. $return . var_export($data, true) . ";";
    }
}
