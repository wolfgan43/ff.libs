<?php

namespace phpformsframework\libs;

class Locale {

    private static $lang                                    = null;
    private static $country                                 = null;
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

    public static function getLang($key = null) {
        return ($key
            ? self::$lang[$key]
            : self::$lang
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

        define("LANGUAGE_INSET_TINY", self::$lang["tiny_code"]);
        define("LANGUAGE_INSET", self::$lang["code"]);
        define("LANGUAGE_INSET_ID", self::$lang["id"]);
        define("FF_LOCALE", self::$lang["code"]);
        define("FF_LOCALE_ID", self::$lang["id"]);

    }

    public static function load($config) {
        if(is_array($config)) {
            /**
             * Lang
             */
            if(is_array($config["lang"]) && count($config["lang"])) {
                foreach ($config["lang"] AS $code => $lang) {
                    $attr                                               = \Filemanager::getAttr($lang);
                    self::$locale["lang"][$code]                        = $attr;

                    //self::$locale["rev"]["lang"][$attr["tiny_code"]]    = $code;
                    //self::$locale["rev"]["key"][$attr["id"]]            = $code;
                }
            }
            /**
             * Country
             */
            if(is_array($config["country"]) && count($config["country"])) {
                foreach ($config["country"] AS $code => $country) {
                    $attr                                               = \Filemanager::getAttr($country);
                    self::$locale["country"][$code]                     = $attr;

                    //self::$locale["rev"]["country"][$code]              = $attr["lang"];
                }
            }
        }
    }

    private static function acceptLanguage($key = null) {
        static $res                                                     = null;

        if(!$res) {
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
        if(!isset(self::$locale["lang"][$lang_tiny_code]))  { $lang_tiny_code = \Kernel::env("LANG_TINY_CODE"); }

        self::$lang                                         = self::$locale["lang"][$lang_tiny_code];
        self::$lang["tiny_code"]                            = $lang_tiny_code;

        \ffTranslator::setLang(self::$lang["code"]);
    }
    private static function setCountry($country_tiny_code = null) {
        if(!isset(self::$locale["country"][$country_tiny_code])) {
            $country_tiny_code                              = (isset(self::$lang["country"])
                ? self::$lang["country"]
                : \Kernel::env("COUNTRY_TINY_CODE")
            );
        }

        self::$country                                      = self::$locale["country"][$country_tiny_code];
    }
}