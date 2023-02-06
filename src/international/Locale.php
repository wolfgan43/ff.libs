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
namespace ff\libs\international;

use ff\libs\Configurable;
use ff\libs\Debug;
use ff\libs\Dir;
use ff\libs\dto\ConfigRules;
use ff\libs\Kernel;

/**
 * Class Locale
 * @package ff\libs\international
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
     * @param string|null $lang_code
     * @return bool
     */
    public static function isDefaultLang(string $lang_code = null) : bool
    {
        return empty($lang_code) || $lang_code == self::$default[self::LANG_][self::CODE_] || !self::isAcceptedLanguage($lang_code);
    }

    /**
     * @return string
     */
    public static function getTimeZone() : string
    {
        return Kernel::$Environment::LOCALE_TIME_ZONE;
    }

    /**
     * @return string
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
     * @return string|null
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
     * @return string
     */
    public static function get() : string
    {
        return self::$lang[self::CODE_] . "-" . self::$country[self::CODE_];
    }

    /**
     * @return array
     */
    public static function getAll() : array
    {
        return self::acceptLocale();
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
        if (!empty(Kernel::$Environment::LOCALE_ACCEPTED_LANGS)) {
            self::$accepted_langs                                       = array_intersect(self::$accepted_langs, Kernel::$Environment::LOCALE_ACCEPTED_LANGS);
        }

        $acceptLocale                                                   = self::getLocale($locale) ?? self::acceptLocale(true);
        $lang_tiny_code                                                 = $acceptLocale[self::LANG_];
        $country_tiny_code                                              = $acceptLocale[self::COUNTRY_];

        if (!isset(self::$locale[self::LANG_][$lang_tiny_code])) {
            Debug::set("Lang " . $lang_tiny_code . " accepted but missing configuration in config.xml. Add <" . $lang_tiny_code . " /> in tag <locale>", self::ERROR_BUCKET);

            self::$lang                                                 = self::$default[self::LANG_];
            self::$country                                              = self::$default[self::COUNTRY_];
        } else {
            self::$lang                                                 = self::$locale[self::LANG_][$lang_tiny_code];
            self::$country                                              = self::$locale[self::COUNTRY_][$country_tiny_code] ?? self::$locale[self::COUNTRY_][self::$lang[self::COUNTRY_]];
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
     * @param string $lang
     * @return bool
     */
    public static function isAcceptedLanguage(string $lang)
    {
        return in_array($lang, self::$accepted_langs);
    }

    /**
     * @param string|null $locale
     * @return array|null
     */
    private static function getLocale(string $locale = null) : ?array
    {
        $res                                                        = null;
        if (!empty($locale)) {
            $arrLocale                                              = explode("-", $locale, 2);
            if (isset(self::$locale[self::LANG_][$arrLocale[0]])) {
                $lang                                               = $arrLocale[0];
                $country                                            = (
                isset($arrLocale[1]) && isset(self::$locale[self::COUNTRY_][$arrLocale[1]])
                    ? $arrLocale[1]
                    : null
                );
            }
            if (!empty($lang) && in_array($lang, self::$accepted_langs)) {
                $res                                                = [
                    self::LANG_     => strtolower($lang),
                    self::COUNTRY_  => (
                    !empty($country)
                        ? strtoupper($country)
                        : self::$locale[self::LANG_][$lang][self::COUNTRY_]
                    )
                ];
            }
        }

        return $res;
    }

    /**
     * @param bool $onlyFirst
     * @return array
     */
    private static function acceptLocale(bool $onlyFirst = false) : array
    {
        $locale_accepted                                        = [];
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $locale) {
                $pattern                                            = '/^(?P<primarytag>[a-zA-Z]{2,8})'.
                    '(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
                    '(?P<quantifier>\d\.\d))?$/';

                $splits                                             = [];
                if (preg_match($pattern, trim($locale), $splits)) {
                    $lang                                           = strtolower($splits["primarytag"]);
                    if (!in_array($lang, self::$accepted_langs)) {
                        continue;
                    }

                    $country                                        = (
                    isset($splits["subtag"])
                        ? strtoupper($splits["subtag"])
                        : self::$locale[self::LANG_][$lang][self::COUNTRY_]
                    );

                    $locale_country                                 = (
                    empty($country)
                        ? ""
                        : "-" . $country
                    );

                    $locale_accepted[$lang . $locale_country]        = [
                        self::LANG_     => $lang,
                        self::COUNTRY_  => $country
                    ];

                    if ($onlyFirst) {
                        return $locale_accepted[$lang . $locale_country];
                    }
                }
            }
        }

        if (empty($locale_accepted)) {
            Debug::set((empty($lang) ? "Lang is null" : "Lang not accepted: " . $lang)
                . ". Lang allowed: (" . implode(", ", self::$accepted_langs) . ")"
                . ". Lang will be set to default: " . self::$default[self::LANG_][self::CODE_]
                . ". Check LOCALE_ACCEPTED_LANGS in Config", self::ERROR_BUCKET);

            $locale_default                                     = self::defaultLocale();
            if ($onlyFirst) {
                return $locale_default;
            }
            $locale_accepted[$locale_default[self::LANG_] . "-" . $locale_default[self::COUNTRY_]] = $locale_default;
        }

        return $locale_accepted;
    }

    /**
     * @return array
     */
    private static function defaultLocale() : array
    {
        return [
            self::LANG_     => strtolower(Kernel::$Environment::LOCALE_LANG_CODE),
            self::COUNTRY_  => strtoupper(Kernel::$Environment::LOCALE_COUNTRY_CODE)
        ];
    }
}
