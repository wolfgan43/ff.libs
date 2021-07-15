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
namespace phpformsframework\libs\international;

use phpformsframework\libs\Configurable;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dir;
use phpformsframework\libs\dto\ConfigRules;
use phpformsframework\libs\Kernel;

/**
 * Class Locale
 * @package phpformsframework\libs\international
 */
class Locale implements Configurable
{
    private const ERROR_BUCKET                                          = "locale";
    private const LANG_                                                 = "lang";
    private const COUNTRY_                                              = "country";
    private const CODE_                                                 = "tiny_code";

    private static $lang                                                = null;
    private static $country                                             = null;
    private static $locale                                              = null;
    private static $default                                             = null;
    private static $accepted_langs                                      = [];

    /**
     * @return bool
     */
    public static function isDefaultLang() : bool
    {
        return self::$lang[self::CODE_] == self::$default[self::LANG_][self::CODE_];
    }

    /**
     * @return string|null
     */
    public static function getTimeZone() : string
    {
        return Kernel::$Environment::LOCALE_TIME_ZONE;
    }

    /**
     * @return string|null
     */
    public static function getTimeZoneLoc() : string
    {
        return Kernel::$Environment::LOCALE_TIME_LOC;
    }

    /**
     * @return string|null
     */
    public static function getCodeLang() : ?string
    {
        return self::$lang[self::CODE_] ?? null;
    }

    /**
     * @return string|null
     */
    public static function getCodeCountry() : ?string
    {
        return self::$country[self::CODE_] ?? null;
    }

    /**
     * @return string
     */
    public static function getCodeLangDefault() : ?string
    {
        return self::$default[self::LANG_][self::CODE_] ?? null;
    }

    /**
     * @return string|null
     */
    public static function getCodeCountryDefault() : ?string
    {
        return self::$default[self::COUNTRY_][self::CODE_] ?? null;
    }

    /**
     * @return array
     */
    public static function getLangs() : array
    {
        return self::$locale[self::LANG_];
    }

    /**
     * @return array
     */
    public static function getCountries() : array
    {
        return self::$locale[self::COUNTRY_];
    }

    /**
     * @return string|null
     */
    public static function get() : string
    {
        return self::$lang[self::CODE_] . "-" . self::$country[self::CODE_];
    }

    /**
     * @return array|null
     */
    public static function getAll() : array
    {
        return self::acceptLocale() ?? [];
    }

    /**
     * @param string $path
     * @return string
     */
    public static function setByPath(string $path) : string
    {
        $arrPathInfo                                                    = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR), "2");
        $lang_tiny_code                                                 = $arrPathInfo[0];
        if (isset(self::$locale[self::LANG_][$lang_tiny_code])) {
            $path                                                       = DIRECTORY_SEPARATOR . $arrPathInfo[1];
            self::set($lang_tiny_code);
        } else {
            self::set();
        }
        return $path;
    }

    /**
     * @param string|null $locale
     */
    public static function set(string $locale = null) : void
    {
        $lang                                                           = null;
        $country                                                        = null;
        if ($locale) {
            $locale                                                     = str_replace("_", "-", $locale);
            $arrLocale                                                  = explode("-", $locale, 2);
            if (isset(self::$locale[self::LANG_][$arrLocale[0]])) {
                $lang                                                   = $arrLocale[0];
                $country                                                = (
                    isset($arrLocale[1]) && isset(self::$locale[self::COUNTRY_][$arrLocale[1]])
                    ? $arrLocale[1]
                    : null
                );
            }
        }

        if (!empty(Kernel::$Environment::LOCALE_ACCEPTED_LANGS)) {
            self::$accepted_langs                                       = array_intersect(self::$accepted_langs, Kernel::$Environment::LOCALE_ACCEPTED_LANGS);
        }

        $acceptLanguage                                                 = self::acceptLanguage($lang, $country);
        $lang_tiny_code                                                 = $acceptLanguage[self::LANG_];
        $country_tiny_code                                              = $acceptLanguage[self::COUNTRY_];

        if ($lang_tiny_code != self::$default[self::LANG_][self::CODE_] && array_search($lang_tiny_code, self::$accepted_langs) === false) {
            Debug::set("Lang not accepted: " . $lang_tiny_code . " Lang allowed: " . implode(", ", self::$accepted_langs) . ". Lang will be set to default: " . self::$default[self::LANG_][self::CODE_], self::ERROR_BUCKET);

            self::$lang                                                 = self::$default[self::LANG_];
            self::$country                                              = self::$default[self::COUNTRY_];
        } else {
            self::$lang                                                 = self::$locale[self::LANG_][$lang_tiny_code];
            self::$country                                              = (
                isset(self::$locale[self::COUNTRY_][$country_tiny_code])
                ? self::$locale[self::COUNTRY_][$country_tiny_code]
                : self::$locale[self::COUNTRY_][self::$lang[self::COUNTRY_]]
            );
        }

        if (!self::isDefaultLang()) {
            Translator::loadLib(self::$lang[self::CODE_]);
        }
    }

    /**
     * @access private
     * @param ConfigRules $configRules
     * @return ConfigRules
     */
    public static function loadConfigRules(ConfigRules $configRules) : ConfigRules
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
        self::$locale                                                   = $config["locale"];
        self::$default                                                  = $config["default"];
        self::$accepted_langs                                           = $config["accepted_langs"];
    }

    /**
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema(array $rawdata) : array
    {
        if (!empty($rawdata)) {
            $lang_tiny_code                                             = strtolower(Kernel::$Environment::LOCALE_LANG_CODE);
            $country_tiny_code                                          = strtoupper(Kernel::$Environment::LOCALE_COUNTRY_CODE);

            /**
             * Country
             */
            if (!empty($rawdata[self::COUNTRY_])) {
                foreach ($rawdata[self::COUNTRY_] as $code => $country) {
                    $code                                               = strtoupper($code);

                    $attr                                               = Dir::getXmlAttr($country);
                    self::$locale[self::COUNTRY_][$code]                = $attr;
                    self::$locale[self::COUNTRY_][$code][self::CODE_]   = $code;
                    self::$locale[self::COUNTRY_][$code][self::LANG_]   = (
                        isset($attr[self::LANG_])
                        ? strtolower($attr[self::LANG_])
                        : null
                    );
                }

                if (isset(self::$locale[self::COUNTRY_][$country_tiny_code])) {
                    self::$default[self::COUNTRY_]                      = self::$locale[self::COUNTRY_][$country_tiny_code];
                }
            }

            /**
             * Lang
             */
            if (!empty($rawdata[self::LANG_])) {
                foreach ($rawdata[self::LANG_] as $code => $lang) {
                    $code                                               = strtolower($code);

                    $attr                                               = Dir::getXmlAttr($lang);
                    self::$locale[self::LANG_][$code]                   = $attr;
                    self::$locale[self::LANG_][$code][self::CODE_]      = $code;
                    self::$locale[self::LANG_][$code][self::COUNTRY_]   = (
                        isset($attr[self::COUNTRY_])
                        ? strtoupper($attr[self::COUNTRY_])
                        : null
                    );
                }

                if (isset(self::$locale[self::LANG_][$lang_tiny_code])) {
                    self::$default[self::LANG_]                     = self::$locale[self::LANG_][$lang_tiny_code];

                    if (!isset(self::$locale[self::COUNTRY_][$country_tiny_code])) {
                        self::$default[self::COUNTRY_]              = self::$locale[self::COUNTRY_][self::$default[self::LANG_][self::COUNTRY_]];
                    }
                }
            }

            self::$accepted_langs                                   = array_keys(self::$locale[self::LANG_]);
        }

        return array(
            "locale"            => self::$locale,
            "default"           => self::$default,
            "accepted_langs"    => self::$accepted_langs
        );
    }

    /**
     * @param string|null $lang
     * @param string|null $country
     * @return array|null
     */
    private static function acceptLanguage(string $lang = null, string $country = null) : ?array
    {
        $res                                                        = null;
        if ($lang && in_array($lang, self::$accepted_langs)) {
            $res                                                    = [
                                                                        self::LANG_     => strtolower($lang),
                                                                        self::COUNTRY_  => (
                                                                            $country
                                                                            ? strtoupper($country)
                                                                            : self::$lang[$lang][self::COUNTRY_]
                                                                        )
                                                                    ];
        } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $res                                                    = self::acceptLocale(true);
        }

        return $res ?? [
            self::LANG_     => strtolower(Kernel::$Environment::LOCALE_LANG_CODE),
            self::COUNTRY_  => strtoupper(Kernel::$Environment::LOCALE_COUNTRY_CODE)
        ];
    }

    /**
     * @param bool $onlyFirst
     * @return array|null
     */
    private static function acceptLocale(bool $onlyFirst = false) : ?array
    {
        $locale_accepted                                        = null;
        foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? self::get()) as $locale) {
            $pattern                                            = '/^(?P<primarytag>[a-zA-Z]{2,8})'.
                '(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
                '(?P<quantifier>\d\.\d))?$/';

            $splits                                             = [];
            if (preg_match($pattern, $locale, $splits)) {
                $lang                                           = strtolower($splits["primarytag"]);
                if (!in_array($lang, self::$accepted_langs)) {
                    continue;
                }

                $country                                        = (
                    isset($splits["subtag"])
                    ? strtoupper($splits["subtag"])
                    : self::$lang[$lang][self::COUNTRY_]
                );


                $locale_accepted[$lang . "-" . $country]        = [
                                                                    self::LANG_     => $lang,
                                                                    self::COUNTRY_  => $country
                                                                ];


                if ($onlyFirst) {
                    return $locale_accepted[$lang . "-" . $country];
                }
            }
        }

        return $locale_accepted;
    }
}
