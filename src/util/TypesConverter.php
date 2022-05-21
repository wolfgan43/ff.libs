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
namespace ff\libs\util;

/**
 * Trait TypesConverter
 * @package ff\libs\util
 */
trait TypesConverter
{
    /**
     * @param string $rule
     * @return string
     */
    private static function regexp(string $rule) : string
    {
        return "#" . (
            strpos($rule, "[") === false && strpos($rule, "(") === false && strpos($rule, '$') === false
                ? str_replace("\*", "(.*)", preg_quote($rule, "#"))
                : $rule
            ) . "#i";
    }

    /**
     * @param array|null $array $array
     * @return string|null
     */
    private static function checkSumArray(array $array = null) : ?string
    {
        return ($array
            ? crc32(json_encode($array))
            : null
        );
    }
    
    /**
     * @param array $params
     * @param array|null $request
     * @return array
     */
    private static function mergeRequest(array $params, array $request = null) : array
    {
        $request_key            = array();
        $request_value          = array();
        foreach ($request as $key => $value) {
            if (is_array($value)) {
                $value          = json_encode($value);
            }
            $request_key[]      = '$' . $key . "#";
            $request_key[]      = '$' . $key . " ";
            $request_value[]    = $value . "#";
            $request_value[]    = $value . " ";
        }
        $prototype              = str_replace(
            $request_key,
            $request_value,
            implode("#", $params) . "#"
        );
        $prototype              = preg_replace('/\$[a-zA-Z_]+/', "", $prototype);

        return array_combine(
            array_keys($params),
            explode("#", substr($prototype, 0, -1))
        );
    }
}
