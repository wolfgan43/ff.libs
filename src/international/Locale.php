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
namespace phpformsframework\libs\international;

use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\Configurable;
use phpformsframework\libs\Config;
use phpformsframework\libs\Constant;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Env;

class Locale implements Configurable
{
    const ACCEPTED_LANG                                     = Constant::ACCEPTED_LANG;

    private static $lang                                    = null;
    private static $country                                 = null;
    private static $langDefault                             = null;
    private static $countryDefault                          = null;
    private static $locale                                  = null;

    public static function isMultiLang()
    {
        return count(static::ACCEPTED_LANG) > 1;
    }

    public static function getLangDefault($key = null)
    {
        return ($key
            ? self::$langDefault[$key]
            : self::$langDefault
        );
    }
    public static function getLang($key = null)
    {
        return ($key
            ? self::$lang[$key]
            : self::$lang
        );
    }
    public static function getLangs($key = null)
    {
        return ($key
            ? self::$locale["lang"][$key]
            : self::$locale["lang"]
        );
    }
    public static function getCountryDefault($key = null)
    {
        return ($key
            ? self::$countryDefault[$key]
            : self::$countryDefault
        );
    }
    public static function getCountry($key = null)
    {
        return ($key
            ? self::$country[$key]
            : self::$country
        );
    }
    public static function get()
    {
        return (self::$lang["tiny_code"] == self::$country["code"]
            ? self::$lang["tiny_code"]
            : self::$lang["tiny_code"] . "-" . strtoupper(self::$country["code"])
        );
    }


    public static function setByPath($path)
    {
        $arrPathInfo                                        = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR), "2");
        $lang_tiny_code                                     = $arrPathInfo[0];
        if (isset(self::$locale["lang"][$lang_tiny_code])) {
            $path                                           = DIRECTORY_SEPARATOR . $arrPathInfo[1];
        }
        self::set($lang_tiny_code);

        return $path;
    }

    public static function set($locale)
    {
        $locale                                             = str_replace("_", "-", $locale);
        $arrLocale                                          = explode("-", $locale, 2);


        if (isset(self::$locale["lang"][$arrLocale[0]])) {
            $lang_tiny_code                                 = $arrLocale[0];
            $country_tiny_code                              = (
                isset($arrLocale[1]) && isset(self::$locale["lang"][$arrLocale[1]])
                                                                ? $arrLocale[1]
                                                                : null
                                                            );
        } else {
            $acceptLanguage                                 = self::acceptLanguage();

            $lang_tiny_code                                 = $acceptLanguage["lang"];
            $country_tiny_code                              = $acceptLanguage["country"];
        }

        self::setLang($lang_tiny_code);
        self::setCountry($country_tiny_code);
    }

    public static function loadSchema()
    {
        Debug::stopWatch("locale/load");

        $cache                                                          = Mem::getInstance("locale");
        $res                                                            = $cache->get("rawdata");

        if (!$res) {
            $config                                                     = Config::rawData(Config::SCHEMA_LOCALE, true);

            if (is_array($config)) {
                $lang_tiny_code                                         = Env::get("LANG_TINY_CODE");
                $country_tiny_code                                      = Env::get("COUNTRY_TINY_CODE");

                /**
                 * Lang
                 */
                if (is_array($config["lang"]) && count($config["lang"])) {
                    foreach ($config["lang"] as $code => $lang) {
                        $attr                                           = Dir::getXmlAttr($lang);
                        self::$locale["lang"][$code]                    = $attr;
                        self::$locale["lang"][$code]["tiny_code"]       = $code;
                    }

                    if (isset(self::$locale["lang"][$lang_tiny_code])) {
                        self::$langDefault                              = self::$locale["lang"][$lang_tiny_code];
                    }
                }

                /**
                 * Country
                 */
                if (is_array($config["country"]) && count($config["country"])) {
                    foreach ($config["country"] as $code => $country) {
                        $attr                                           = Dir::getXmlAttr($country);
                        self::$locale["country"][$code]                 = $attr;
                        self::$locale["country"][$code]["tiny_code"]    = strtolower($code);
                    }

                    if (isset(self::$locale["lang"][$lang_tiny_code])) {
                        self::$countryDefault                           = self::$locale["country"][$country_tiny_code];
                    }
                }
            }

            $cache->set("rawdata", array(
                "locale"            => self::$locale,
                "langDefault"       => self::$langDefault,
                "countryDefault"    => self::$countryDefault,

            ));
        } else {
            self::$locale                                                   = $res["locale"];
            self::$langDefault                                              = $res["langDefault"];
            self::$countryDefault                                           = $res["countryDefault"];
        }

        Debug::stopWatch("locale/load");
    }

    private static function acceptLanguage($key = null)
    {
        static $res                                                     = null;

        if (!$res && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $locale) {
                $pattern                                                = '/^(?P<primarytag>[a-zA-Z]{2,8})'.
                    '(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
                    '(?P<quantifier>\d\.\d))?$/';

                $splits                                                 = array();
                if (preg_match($pattern, $locale, $splits)) {
                    $res                                                = array(
                                                                            "lang"      => strtolower($splits["primarytag"])
                                                                            , "country" => (
                                                                                isset($splits["subtag"])
                                                                                            ? strtoupper($splits["subtag"])
                                                                                            : null
                                                                                        )
                                                                        );
                }
            }
        }

        return ($key
            ? $res[$key]
            : $res
        );
    }

    private static function setLang($lang_tiny_code = null)
    {
        self::$lang                                         = (
            isset(self::$locale["lang"][$lang_tiny_code])
                                                                ? self::$locale["lang"][$lang_tiny_code]
                                                                : self::$langDefault
                                                            );
    }
    private static function setCountry($country_tiny_code = null)
    {
        if (!isset(self::$locale["country"][$country_tiny_code]) && isset(self::$lang["country"])) {
            $country_tiny_code                              = self::$lang["country"];
        }

        self::$country                                      = (
            isset(self::$locale["country"][$country_tiny_code])
                                                                ? self::$locale["country"][$country_tiny_code]
                                                                : self::$countryDefault
                                                            );
    }
}
