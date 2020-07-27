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
namespace phpformsframework\libs;

use phpformsframework\libs\dto\Mapping;

/**
 * Class Mappable
 * @package phpformsframework\libs
 */
abstract class Mappable
{
    use Mapping;
    use ClassDetector;

    protected const ERROR_BUCKET            = "mappable";

    /**
     * @param $map |null
     * @param string|null $class_name
     * @todo da tipizzare
     * Mappable constructor.
     */
    public function __construct($map = null, string $class_name = null)
    {
        if (is_array($map)) {
            $this->autoMapping($map);
        } elseif ($map) {
            $this->autoMapping($this->loadMap($map, $this->getPrefix($class_name)));
        }
    }

    /**
     * @param array $maps
     * @param string|null $class_name
     */
    protected function loadMaps(array $maps, string $class_name = null) : void
    {
        $res                                = array();
        foreach ($maps as $name) {
            $res                            = array_replace_recursive($res, $this->loadMap($name, $this->getPrefix($class_name)));
        }

        $this->autoMapping($res);
    }

    /**
     * @param string $name
     * @param string|null $prefix
     * @return array
     */
    private function loadMap(string $name, string $prefix) : array
    {
        $bucket                             = $prefix . "_" . $name;

        Debug::stopWatch("mapping/" . $bucket);
        $map                                = Config::mapping($prefix, $name);
        if (empty($map)) {
            Response::httpCode(500);
            if (Kernel::$Environment::DEBUG) {
                echo "Mapping: " . basename(str_replace("\\", DIRECTORY_SEPARATOR, get_called_class())) . ": " . $bucket . " not found";
            }
            exit;
        }
        Debug::stopWatch("mapping/" . $bucket);

        return $map;
    }

    /**
     * @param string|null $class_name
     * @return string
     */
    private function getPrefix(string $class_name = null) : string
    {
        return strtolower(self::getClassName($class_name));
    }
}
