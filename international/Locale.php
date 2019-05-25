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
use phpformsframework\libs\DirStruct;
use phpformsframework\libs\Config;
use phpformsframework\libs\Env;

class Locale implements Configurable {
    private static $lang                                    = null;
    private static $country                                 = null;
    private static $langDefault                             = null;
    private static $countryDefault                          = null;
    private static $locale                                  = null;


    private static $locale2                                  = array('af-ZA',
                                                                'am-ET',
                                                                'ar-AE',
                                                                'ar-BH',
                                                                'ar-DZ',
                                                                'ar-EG',
                                                                'ar-IQ',
                                                                'ar-JO',
                                                                'ar-KW',
                                                                'ar-LB',
                                                                'ar-LY',
                                                                'ar-MA',
                                                                'arn-CL',
                                                                'ar-OM',
                                                                'ar-QA',
                                                                'ar-SA',
                                                                'ar-SY',
                                                                'ar-TN',
                                                                'ar-YE',
                                                                'as-IN',
                                                                'az-Cyrl-AZ',
                                                                'az-Latn-AZ',
                                                                'ba-RU',
                                                                'be-BY',
                                                                'bg-BG',
                                                                'bn-BD',
                                                                'bn-IN',
                                                                'bo-CN',
                                                                'br-FR',
                                                                'bs-Cyrl-BA',
                                                                'bs-Latn-BA',
                                                                'ca-ES',
                                                                'co-FR',
                                                                'cs-CZ',
                                                                'cy-GB',
                                                                'da-DK',
                                                                'de-AT',
                                                                'de-CH',
                                                                'de-DE',
                                                                'de-LI',
                                                                'de-LU',
                                                                'dsb-DE',
                                                                'dv-MV',
                                                                'el-GR',
                                                                'en-029',
                                                                'en-AU',
                                                                'en-BZ',
                                                                'en-CA',
                                                                'en-GB',
                                                                'en-IE',
                                                                'en-IN',
                                                                'en-JM',
                                                                'en-MY',
                                                                'en-NZ',
                                                                'en-PH',
                                                                'en-SG',
                                                                'en-TT',
                                                                'en-US',
                                                                'en-ZA',
                                                                'en-ZW',
                                                                'es-AR',
                                                                'es-BO',
                                                                'es-CL',
                                                                'es-CO',
                                                                'es-CR',
                                                                'es-DO',
                                                                'es-EC',
                                                                'es-ES',
                                                                'es-GT',
                                                                'es-HN',
                                                                'es-MX',
                                                                'es-NI',
                                                                'es-PA',
                                                                'es-PE',
                                                                'es-PR',
                                                                'es-PY',
                                                                'es-SV',
                                                                'es-US',
                                                                'es-UY',
                                                                'es-VE',
                                                                'et-EE',
                                                                'eu-ES',
                                                                'fa-IR',
                                                                'fi-FI',
                                                                'fil-PH',
                                                                'fo-FO',
                                                                'fr-BE',
                                                                'fr-CA',
                                                                'fr-CH',
                                                                'fr-FR',
                                                                'fr-LU',
                                                                'fr-MC',
                                                                'fy-NL',
                                                                'ga-IE',
                                                                'gd-GB',
                                                                'gl-ES',
                                                                'gsw-FR',
                                                                'gu-IN',
                                                                'ha-Latn-NG',
                                                                'he-IL',
                                                                'hi-IN',
                                                                'hr-BA',
                                                                'hr-HR',
                                                                'hsb-DE',
                                                                'hu-HU',
                                                                'hy-AM',
                                                                'id-ID',
                                                                'ig-NG',
                                                                'ii-CN',
                                                                'is-IS',
                                                                'it-CH',
                                                                'it-IT',
                                                                'iu-Cans-CA',
                                                                'iu-Latn-CA',
                                                                'ja-JP',
                                                                'ka-GE',
                                                                'kk-KZ',
                                                                'kl-GL',
                                                                'km-KH',
                                                                'kn-IN',
                                                                'kok-IN',
                                                                'ko-KR',
                                                                'ky-KG',
                                                                'lb-LU',
                                                                'lo-LA',
                                                                'lt-LT',
                                                                'lv-LV',
                                                                'mi-NZ',
                                                                'mk-MK',
                                                                'ml-IN',
                                                                'mn-MN',
                                                                'mn-Mong-CN',
                                                                'moh-CA',
                                                                'mr-IN',
                                                                'ms-BN',
                                                                'ms-MY',
                                                                'mt-MT',
                                                                'nb-NO',
                                                                'ne-NP',
                                                                'nl-BE',
                                                                'nl-NL',
                                                                'nn-NO',
                                                                'nso-ZA',
                                                                'oc-FR',
                                                                'or-IN',
                                                                'pa-IN',
                                                                'pl-PL',
                                                                'prs-AF',
                                                                'ps-AF',
                                                                'pt-BR',
                                                                'pt-PT',
                                                                'qut-GT',
                                                                'quz-BO',
                                                                'quz-EC',
                                                                'quz-PE',
                                                                'rm-CH',
                                                                'ro-RO',
                                                                'ru-RU',
                                                                'rw-RW',
                                                                'sah-RU',
                                                                'sa-IN',
                                                                'se-FI',
                                                                'se-NO',
                                                                'se-SE',
                                                                'si-LK',
                                                                'sk-SK',
                                                                'sl-SI',
                                                                'sma-NO',
                                                                'sma-SE',
                                                                'smj-NO',
                                                                'smj-SE',
                                                                'smn-FI',
                                                                'sms-FI',
                                                                'sq-AL',
                                                                'sr-Cyrl-BA',
                                                                'sr-Cyrl-CS',
                                                                'sr-Cyrl-ME',
                                                                'sr-Cyrl-RS',
                                                                'sr-Latn-BA',
                                                                'sr-Latn-CS',
                                                                'sr-Latn-ME',
                                                                'sr-Latn-RS',
                                                                'sv-FI',
                                                                'sv-SE',
                                                                'sw-KE',
                                                                'syr-SY',
                                                                'ta-IN',
                                                                'te-IN',
                                                                'tg-Cyrl-TJ',
                                                                'th-TH',
                                                                'tk-TM',
                                                                'tn-ZA',
                                                                'tr-TR',
                                                                'tt-RU',
                                                                'tzm-Latn-DZ',
                                                                'ug-CN',
                                                                'uk-UA',
                                                                'ur-PK',
                                                                'uz-Cyrl-UZ',
                                                                'uz-Latn-UZ',
                                                                'vi-VN',
                                                                'wo-SN',
                                                                'xh-ZA',
                                                                'yo-NG',
                                                                'zh-CN',
                                                                'zh-HK',
                                                                'zh-MO',
                                                                'zh-SG',
                                                                'zh-TW',
                                                                'zu-ZA'
                                                            );
    public static function getLangDefault($key = null) {
        return ($key
            ? self::$langDefault[$key]
            : self::$langDefault
        );
    }
    public static function getLang($key = null) {
        return ($key
            ? self::$lang[$key]
            : self::$lang
        );
    }
    public static function getLangs($key = null) {
        return ($key
            ? self::$locale["lang"][$key]
            : self::$locale["lang"]
        );
    }
    public static function getCountryDefault($key = null) {
        return ($key
            ? self::$countryDefault[$key]
            : self::$countryDefault
        );
    }
    public static function getCountry($key = null) {
        return ($key
            ? self::$country[$key]
            : self::$country
        );
    }
    public static function get() {
        return (self::$lang["tiny_code"] == self::$country["code"]
            ? self::$lang["tiny_code"]
            : self::$lang["tiny_code"] . "-" . strtoupper(self::$country["code"])
        );
    }


    public static function setByPath($path) {
        $arrPathInfo                                        = explode("/", trim($path, "/"), "2");
        $lang_tiny_code                                     = $arrPathInfo[0];
        if(isset(self::$locale["lang"][$lang_tiny_code])) {
            $path                                           = "/" . $arrPathInfo[1];
        }
        self::set($lang_tiny_code);

        return $path;
    }

    public static function set($locale) {
        $locale                                             = str_replace("_", "-", $locale);
        $arrLocale                                          = explode("-", $locale, 2);


        if(isset(self::$locale["lang"][$arrLocale[0]])) {
            $lang_tiny_code                                 = $arrLocale[0];
            $country_tiny_code                              = (isset($arrLocale[1]) && isset(self::$locale["lang"][$arrLocale[1]])
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

        //todo: trovare alternativa (tipo Cms::lang) per semplificare la programmazione
//        self::$locale["lang"]["current"]                    = self::$lang;
//        self::$locale["country"]["current"]                 = self::$locale["country"][$country];
//        self::$locale["country"]["current"]["code"]         = $country;

        /*define("LANGUAGE_INSET_TINY", self::$lang["tiny_code"]);
        define("LANGUAGE_INSET", self::$lang["code"]);
        define("LANGUAGE_INSET_ID", self::$lang["id"]);
        define("FF_LOCALE", self::$lang["code"]);
        define("FF_LOCALE_ID", self::$lang["id"]);*/

    }

    public static function loadSchema() {
        $config                                                         = Config::rawData("locale", true);
        if(is_array($config)) {
            $lang_tiny_code                                             = Env::get("LANG_TINY_CODE");
            $country_tiny_code                                          = Env::get("COUNTRY_TINY_CODE");

            /**
             * Lang
             */
            if(is_array($config["lang"]) && count($config["lang"])) {
                foreach ($config["lang"] AS $code => $lang) {
                    $attr                                               = DirStruct::getXmlAttr($lang);
                    self::$locale["lang"][$code]                        = $attr;
                    self::$locale["lang"][$code]["tiny_code"]           = $code;
                }

                if(isset(self::$locale["lang"][$lang_tiny_code])) {
                    self::$langDefault                                  = self::$locale["lang"][$lang_tiny_code];
                }
            }

            /**
             * Country
             */
            if(is_array($config["country"]) && count($config["country"])) {
                foreach ($config["country"] AS $code => $country) {
                    $attr                                               = DirStruct::getXmlAttr($country);
                    self::$locale["country"][$code]                     = $attr;
                    self::$locale["country"][$code]["tiny_code"]        = strtolower($code);
                }

                if(isset(self::$locale["lang"][$lang_tiny_code])) {
                    self::$countryDefault                               = self::$locale["country"][$country_tiny_code];
                }
            }
        }
    }

    private static function acceptLanguage($key = null) {
        static $res                                                     = null;

        if(!$res && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $locale) {
                $pattern                                                = '/^(?P<primarytag>[a-zA-Z]{2,8})'.
                    '(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
                    '(?P<quantifier>\d\.\d))?$/';

                $splits                                                 = array();
                if (preg_match($pattern, $locale, $splits)) {
                    $res                                                = array(
                                                                            "lang"      => strtolower($splits["primarytag"])
                                                                            , "country" => (isset($splits["subtag"])
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

    private static function setLang($lang_tiny_code = null) {
        self::$lang                                         = (isset(self::$locale["lang"][$lang_tiny_code])
                                                                ? self::$locale["lang"][$lang_tiny_code]
                                                                : self::$langDefault
                                                            );
    }
    private static function setCountry($country_tiny_code = null) {
        if(!isset(self::$locale["country"][$country_tiny_code]) && isset(self::$lang["country"])) {
            $country_tiny_code                              = self::$lang["country"];
        }

        self::$country                                      = (isset(self::$locale["country"][$country_tiny_code])
                                                                ? self::$locale["country"][$country_tiny_code]
                                                                : self::$countryDefault
                                                            );

    }
}