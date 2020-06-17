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

use phpformsframework\libs\App;
use phpformsframework\libs\cache\Mem;
use phpformsframework\libs\Error;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\AdapterManager;

/**
 * Class Translator
 * @package phpformsframework\libs\international
 */
class Translator
{
    use AdapterManager;

    const ERROR_BUCKET                                  = "translator";
    const ADAPTER                                       = false;

    const REGEXP                                        = '/\{_([\w\:\=\-\|\.\s\?\!\\\'\"\,]+)\}/U';

    const INSERT_EMPTY                                  = true;
    const CACHE_BUCKET                                  = "translations/";


    private static $singletons                          = null;

    private static $cache                               = null;

    /**
     * @param string|null $translatorAdapter
     * @param string|null $auth
     * @return TranslatorAdapter
     */
    public static function getInstance(string $translatorAdapter = null, string $auth = null) : TranslatorAdapter
    {
        if (!$translatorAdapter) {
            $translatorAdapter                          = Kernel::$Environment::TRANSLATOR_ADAPTER;
        }
        if (!isset(self::$singletons[$translatorAdapter])) {
            self::$singletons[$translatorAdapter]       = self::loadAdapter($translatorAdapter, [$auth]);
        }

        return self::$singletons[$translatorAdapter];
    }

    /**
     * @param string|null $language
     * @return array|null
     */
    public static function dump(string $language = null) : ?array
    {
        $lang_code                                      = self::getLang($language);

        return self::$cache[$lang_code];
    }

    /**
     * @param string|null $language
     */
    public static function clear(string $language = null) : void
    {
        $lang_code                                      = self::getLang($language);

        self::$cache[$lang_code]                        = null;
        Mem::getInstance(static::CACHE_BUCKET . $lang_code)->clear();
    }

    /**
     * @param string $content
     * @param string|null $language
     * @return string
     */
    public static function process(string $content, string $language = null) : string
    {
        $matches                                        = array();
        $rc                                             = preg_match_all(self::REGEXP, $content, $matches);

        if ($rc) {
            $replace                                    = null;
            $vars                                       = $matches[1];
            foreach ($vars as $code) {
                $replace["keys"][]                      = "{_" . $code . "}";
                $replace["values"][]                    = self::get_word_by_code($code, $language);
            }

            if ($replace) {
                $content = str_replace($replace["keys"], $replace["values"], $content);
            }
        }

        return $content;
    }

    /**
     * @return string
     */
    public static function checkLang() : string
    {
        return (Locale::isMultiLang()
            ? self::getLang()
            : "nolang"
        );
    }

    /**
     * @param string $code
     * @param string $language Upper Code (es: ENG, ITA, SPA)
     * @return string
     */
    public static function get_word_by_code(string $code, string $language = null) : string
    {
        if (!$code || !Locale::isMultiLang()) {
            return $code;
        }
        $lang_code                                      = self::getLang($language);
        if (array_search($lang_code, Kernel::$Environment::ACCEPTED_LANG) === false) {
            Error::register("Lang not accepted: " . $lang_code . " Lang allowed: " . implode(", ", Kernel::$Environment::ACCEPTED_LANG), static::ERROR_BUCKET);
        }
        if (!isset(self::$cache[$lang_code][$code])) {
            $cache                                      = Mem::getInstance(static::CACHE_BUCKET . $lang_code);
            self::$cache[$lang_code][$code]             = $cache->get($code);
            if (!self::$cache[$lang_code][$code]) {
                self::$cache[$lang_code][$code]         = self::getWordByCodeFromDB($code, $lang_code);

                $cache->set($code, self::$cache[$lang_code][$code]);
            }
        }

        return (self::$cache[$lang_code][$code]["cache"]
            ? self::$cache[$lang_code][$code]["word"]
            : self::getCode(self::$cache[$lang_code][$code]["code"])
        );
    }

    /**
     * @param string $code
     * @return string
     */
    private static function getCode(string $code) : string
    {
        return (Kernel::$Environment::DEBUG
            ? "{" . $code . "}"
            : $code
        );
    }

    /**
     * @param string $code
     * @param string|null $language
     * @return array
     */
    private static function getWordByCodeFromDB(string $code, string $language = null) : array
    {
        $lang                                           = self::getLang($language);
        $i18n                                           = array(
                                                            "code"      => $code
                                                            , "lang"    => $lang
                                                            , "cache"   => false
                                                            , "word"    => $code
                                                        );
        if ($lang) {
            $orm                                        = App::orm("international");
            $res                                        = $orm->readOne(array(
                                                            "translation.description"
                                                            , "translation.is_new"
                                                        ), array(
                                                            "lang.code" => $i18n["lang"]
                                                            , "translation.word_code" => substr($i18n["code"], 0, 254)
                                                        ));
            if ($res) {
                $i18n["word"]                           = $res->description;
                $i18n["cache"]                          = !$res->is_new;
            } elseif (self::INSERT_EMPTY) {
                $orm->insert(array(
                    "lang.code"                         =>  $i18n["lang"]
                    , "translation.word_code"           =>  substr($i18n["code"], 0, 254)
                    , "translation.is_new"              => true
                ));
            }
        }

        return $i18n;
    }

    /**
     * @param string|null $lang_code
     * @return string
     */
    public static function getLang(string $lang_code = null) : string
    {
        return strtolower(
            $lang_code
            ? $lang_code
            : Locale::getLang("code")
        );
    }

    /**
     * @param string|null $lang_code
     * @return string
     */
    public static function getLangDefault(string $lang_code = null) : string
    {
        return strtolower(
            $lang_code
            ? $lang_code
            : Locale::getLangDefault("code")
        );
    }
}
