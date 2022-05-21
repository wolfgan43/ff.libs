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
namespace ff\libs\storage\adapters;

use ff\libs\Autoloader;
use ff\libs\Exception;
use ff\libs\storage\FilemanagerAdapter;

/**
 * Class FilemanagerFsPhp
 * @package ff\libs\storage\adapters
 */
class FilemanagerFsPhp extends FilemanagerAdapter
{
    const EXT                                                   = "php";

    /**
     * @param string $file_path
     * @param string|null $var
     * @return array|null
     */
    protected function loadFile(string $file_path, string $var = null) : ?array
    {
        $return                                                 = null;
        $output                                                 = exec("php -l " . addslashes($file_path));
        if (strpos($output, "No syntax errors") === 0) {
            $include                                            = Autoloader::loadScript($file_path);
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
            Exception::warning($output, static::ERROR_BUCKET);
        }

        return $return;
    }

    /**
     * @param array $data
     * @param string|null $var
     * @return string
     */
    protected function output(array $data, string $var = null) : string
    {
        if ($var) {
            $return = '$' . $var . ' = ';
        } else {
            $return = 'return ';
        }

        return "<?php\n" . ' '. $return . var_export($data, true) . ";";
    }
}
