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
namespace phpformsframework\libs\dto;

use phpformsframework\libs\util\TypesConverter;

/**
 * Class Dto
 * @package phpformsframework\libs\dto
 */
class Dto
{
    use Mapping;
    use TypesConverter;

    private static $page = null;

    public function __construct(RequestPage $page, bool $fill_undefined_properties = false)
    {
        self::$page =& $page;

        if ($fill_undefined_properties) {
            $this->autoMappingMagic(self::$page->getRequest());
        } else {
            $this->autoMapping(self::$page->getRequest());
        }
    }

    /**
     * @param bool $force_array_or_object
     * @return array
     */
    public function getRawData(bool $force_array_or_object = false) : array
    {
        $rawdata = self::$page->getRawData();
        return ($force_array_or_object && !isset($rawdata[0])
            ? [$rawdata]
            : $rawdata
        );
    }

    /**
     * @return string
     */
    public function pathInfo() : string
    {
        return self::$page->script_path;
    }

    /**
     * @param array $params
     * @param array|null $request
     * @return array
     */
    public function fill(array $params, array $request = null) : array
    {
        if (!empty($params) && !empty($request = ($request ?? $this->getRawData()))) {
            $params = $this->mergeRequest($params, $request);
        }
        return $params;
    }
}