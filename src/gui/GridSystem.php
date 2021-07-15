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
namespace phpformsframework\libs\gui;

use phpformsframework\libs\Kernel;

/**
 * Class GridSystem
 * @package phpformsframework\libs\gui
 */
class GridSystem
{
    private static $singleton                               = null;
    private static $buttons                                 = null;
    private static $components                              = null;

    /**
     * @param string $bucket
     * @param array $buffer
     * @param int $col
     * @param string $content
     * @param int $count
     */
    public static function colsWrapper(string $bucket, array &$buffer, int $col, string $content, int $count): void
    {
        static $cache = null;

        if (!$cache[$bucket]) {
            $cache[$bucket]         = array(
                "col"               => null
                //, "is_first"        => true
                , "is_wrapped"      => false
                , "count_contents"  => 0
                , "wrapper_count"   => 0
                , "wrapper"         => (
                    strpos($bucket, "row-") === 0
                    ? self::getInstance()->row()
                    : self::getInstance()->form("wrap")
                )
            );
        }

        $cache[$bucket]["count_contents"]++;
        if ($content) {
            if ($col) {
                $cache[$bucket]["col"] = $cache[$bucket]["col"] + $col;
                if ($cache[$bucket]["col"] > 12) {
                    $buffer[] = '</div>';
                    $buffer[] = '<div class="' . $cache[$bucket]["wrapper"] . '">';
                    $cache[$bucket]["is_wrapped"] = true;
                    $cache[$bucket]["wrapper_count"]++;
                    $cache[$bucket]["col"] = 0;
                } elseif (!$cache[$bucket]["wrapper_count"] && $cache[$bucket]["col"] == $col) { //first
                    $buffer[] = '<div class="' . $cache[$bucket]["wrapper"] . '">';
                    $cache[$bucket]["is_wrapped"] = true;
                } elseif ($cache[$bucket]["col"] == 12 && $cache[$bucket]["wrapper_count"] && !$cache[$bucket]["is_wrapped"]) {
                    $buffer[] = '</div>';
                    $buffer[] = '<div class="' . $cache[$bucket]["wrapper"] . '">';
                    $cache[$bucket]["is_wrapped"] = true;
                    $cache[$bucket]["wrapper_count"]++;
                    $cache[$bucket]["col"] = 0;
                } elseif ($cache[$bucket]["col"]/* && $cache[$bucket]["wrapper_count"]*/) {
                    if (!$cache[$bucket]["is_wrapped"]) {
                        $buffer[] = '<div class="' . $cache[$bucket]["wrapper"] . '">';
                        $cache[$bucket]["is_wrapped"] = true;
                    }
                }

                $buffer[] = $content;
                if ($cache[$bucket]["is_wrapped"] && $cache[$bucket]["count_contents"] == $count) {
                    $buffer[] = '</div>';
                    $cache[$bucket]["is_wrapped"] = false;
                    $cache[$bucket]["col"] = 0;
                }
            } else {
                if ($cache[$bucket]["col"] > 0 || $cache[$bucket]["is_wrapped"]) {
                    $buffer[] = '</div>';
                    $cache[$bucket]["is_wrapped"] = false;
                    $cache[$bucket]["wrapper_count"]++;
                    $cache[$bucket]["col"] = 0;
                }
                $buffer[] = $content;
            }
        }
    }

    /**
     * @param array|string $resolution
     * @param bool $rev
     * @return array|null
     * @todo da tipizzare
     */
    public static function calcResolution($resolution, bool $rev = false) : ?array
    {
        $res = null;
        if ($resolution) {
            $resolutions = self::getInstance()->resolutions();

            if (is_array($resolution)) {
                $num = 0;

                foreach ($resolution as $index => $num) {
                    $res[$resolutions[$index]] = ($rev ? 12 - $num : $num);
                }
                if (count($resolutions) > count($res)) {
                    for ($i = count($res) + 1; $i <= count($resolutions); $i++) {
                        $res[$resolutions[$i]] = ($rev ? 12 - $num : $num);
                    }
                }
            } else {
                $res = array_combine($resolutions, array_fill(0, count($resolutions), ($rev ? 12 - $resolution : $resolution)));
            }
        }

        return $res;
    }

    /**
     * @param string $name
     * @return array|null
     */
    public static function &findComponent(string $name) : ?array
    {
        $ref = null;
        $arrName = explode(".", $name);
        if ($arrName[0]) {
            if (self::$components["override"][$arrName[0]] && self::$components[$arrName[0]]) {
                self::$components[$arrName[0]] = array_replace_recursive(self::$components[$arrName[0]], self::$components["override"][$arrName[0]]);
                self::$components["override"][$arrName[0]] = null;
            }

            $ref =& self::$components;
            foreach ($arrName as $item) {
                if (isset($ref[$item])) {
                    $ref =& $ref[$item];
                } else {
                    $ref = null;
                    break;
                }
            }
        }

        return $ref;
    }

    /**
     * @param string $name
     * @param array $data
     */
    public static function setComponent(string $name, array $data) : void
    {
        $ref =& self::findComponent($name);

        $ref = $data;
    }

    /**
     * @param array $data
     * @param string $what
     * @return array|null
     */
    public static function extend(array $data, string $what) : ?array
    {
        if (is_array($data) && $what) {
            if ($what == "buttons") {
                self::extendButtons($data);
                return self::$buttons;
            } else {
                self::extendComponents($data, $what);
                return self::$components[$what];
            }
        }

        return null;
    }

    /**
     * @param array $buttons
     */
    private static function extendButtons(array $buttons) : void
    {
        self::$buttons = array_replace_recursive(self::$buttons, $buttons);
    }

    /**
     * @param array $component
     * @param string $key
     */
    private static function extendComponents(array $component, string $key) : void
    {
        self::$components[$key] = array_replace_recursive((array) self::$components[$key], $component);
    }

    /**
     * @param null|string $frameworkCss
     * @param null|string $fontIcon
     * @return FrameworkCss
     */
    public static function getInstance($frameworkCss = null, $fontIcon = null)
    {
        if (!self::$singleton) {
            self::$singleton                                = new FrameworkCss(
                $frameworkCss ?? Kernel::$Environment::GRIDSYSTEM_ADAPTER,
                $fontIcon ?? Kernel::$Environment::FONTICON_ADAPTER,
                self::$buttons
            );
        }

        return self::$singleton;
    }
}
