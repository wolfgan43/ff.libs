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

use phpformsframework\libs\Configurable;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Env;
use phpformsframework\libs\Kernel;

/**
 * Class Locale
 * @package phpformsframework\libs\international
 */
class Locale implements Configurable
{
    private static $lang                                            = null;
    private static $country                                         = null;
    private static $langDefault                                     = null;
    private static $countryDefault                                  = null;
    private static $locale                                          = null;

    /**
     * @return bool
     */
    public static function isMultiLang() : bool
    {
        return count(Kernel::$Environment::ACCEPTED_LANG) > 1;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getLangDefault(string $key) : ?string
    {
        return (isset(self::$langDefault[$key])
            ? self::$langDefault[$key]
            : null
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    public static function getLang(string $key) : ?string
    {
        return (isset(self::$lang[$key])
            ? self::$lang[$key]
            : null
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    public static function getCountryDefault(string $key) : ?string
    {
        return (isset(self::$countryDefault[$key])
            ? self::$countryDefault[$key]
            : null
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    public static function getCountry(string $key) : ?string
    {
        return (isset(self::$country[$key])
            ? self::$country[$key]
            : null
        );
    }

    /**
     * @param string|null $key
     * @return array
     */
    public static function getLangs(string $key = null) : array
    {
        return ($key
            ? self::$locale["lang"][$key]
            : self::$locale["lang"]
        );
    }

    /**
     * @param string|null $key
     * @return array
     */
    public static function getCountries(string $key = null) : array
    {
        return ($key
            ? self::$locale["country"][$key]
            : self::$locale["country"]
        );
    }

    /**
     * @return string|null
     */
    public static function get() : ?string
    {
        return (self::$lang["tiny_code"] == self::$country["code"]
            ? self::$lang["tiny_code"]
            : self::$lang["tiny_code"] . "-" . strtoupper(self::$country["code"])
        );
    }

    /**
     * @param string $path
     * @return string
     */
    public static function setByPath(string $path) : string
    {
        $arrPathInfo                                                = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR), "2");
        $lang_tiny_code                                             = $arrPathInfo[0];
        if (isset(self::$locale["lang"][$lang_tiny_code])) {
            $path                                                   = DIRECTORY_SEPARATOR . $arrPathInfo[1];
        }
        self::set($lang_tiny_code);

        return $path;
    }

    /**
     * @param string $locale
     */
    public static function set(string $locale) : void
    {
        $locale                                                     = str_replace("_", "-", $locale);
        $arrLocale                                                  = explode("-", $locale, 2);


        if (isset(self::$locale["lang"][$arrLocale[0]])) {
            $lang_tiny_code                                         = $arrLocale[0];
            $country_tiny_code                                      = (
                isset($arrLocale[1]) && isset(self::$locale["lang"][$arrLocale[1]])
                                                                        ? $arrLocale[1]
                                                                        : null
                                                                    );
        } else {
            $acceptLanguage                                         = self::acceptLanguage();

            $lang_tiny_code                                         = $acceptLanguage["lang"];
            $country_tiny_code                                      = $acceptLanguage["country"];
        }

        self::setLang($lang_tiny_code);
        self::setCountry($country_tiny_code);
    }

    /**
     * @access private
     * @param \phpformsframework\libs\dto\ConfigRules $configRules
     * @return \phpformsframework\libs\dto\ConfigRules
     */
    public static function loadConfigRules($configRules)
    {
        return $configRules
            ->add("locale", self::METHOD_REPLACE);
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig(array $config)
    {
        self::$locale                                               = $config["locale"];
        self::$langDefault                                          = $config["langDefault"];
        self::$countryDefault                                       = $config["countryDefault"];
    }

    /**
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema(array $rawdata) : array
    {
        if (!empty($rawdata)) {
            $lang_tiny_code                                         = Env::get("LANG_TINY_CODE");
            $country_tiny_code                                      = Env::get("COUNTRY_TINY_CODE");

            /**
             * Lang
             */
            if (!empty($rawdata["lang"])) {
                foreach ($rawdata["lang"] as $code => $lang) {
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
            if (!empty($rawdata["country"])) {
                foreach ($rawdata["country"] as $code => $country) {
                    $attr                                           = Dir::getXmlAttr($country);
                    self::$locale["country"][$code]                 = $attr;
                    self::$locale["country"][$code]["tiny_code"]    = strtolower($code);
                }

                if (isset(self::$locale["lang"][$lang_tiny_code])) {
                    self::$countryDefault                           = self::$locale["country"][$country_tiny_code];
                }
            }
        }

        return array(
            "locale"            => self::$locale,
            "langDefault"       => self::$langDefault,
            "countryDefault"    => self::$countryDefault
        );
    }

    /**
     * @return array|null
     */
    private static function acceptLanguage() : ?array
    {
        static $res                                                 = null;

        if (!$res && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $locale) {
                $pattern                                            = '/^(?P<primarytag>[a-zA-Z]{2,8})'.
                    '(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
                    '(?P<quantifier>\d\.\d))?$/';

                $splits                                             = array();
                if (preg_match($pattern, $locale, $splits)) {
                    $res                                            = array(
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

        return $res;
    }

    /**
     * @param string|null $lang_tiny_code
     */
    private static function setLang(string $lang_tiny_code = null) : void
    {
        self::$lang                                                 = (
            isset(self::$locale["lang"][$lang_tiny_code])
                                                                        ? self::$locale["lang"][$lang_tiny_code]
                                                                        : self::$langDefault
                                                                    );
    }

    /**
     * @param string|null $country_tiny_code
     */
    private static function setCountry(string $country_tiny_code = null) : void
    {
        if (!isset(self::$locale["country"][$country_tiny_code]) && isset(self::$lang["country"])) {
            $country_tiny_code                                      = self::$lang["country"];
        }

        self::$country                                              = (
            isset(self::$locale["country"][$country_tiny_code])
                                                                        ? self::$locale["country"][$country_tiny_code]
                                                                        : self::$countryDefault
                                                                    );
    }
}
