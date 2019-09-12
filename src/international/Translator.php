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
use phpformsframework\libs\Error;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\storage\Orm;

class Translator
{
    const ERROR_BUCKET                                  = "translator";
    const NAME_SPACE                                    = __NAMESPACE__ . '\\adapters\\';
    const ADAPTER                                       = false;

    const REGEXP                                        = '/\{_([\w\:\=\-\|\.\s\?\!\\\'\"\,]+)\}/U';

    const INSERT_EMPTY                                  = true;
    const CACHE_BUCKET                                  = "translations/";


    private static $singletons                          = null;

    private static $cache                               = null;


    public static function getInstance($translatorAdapter = null, $auth = null)
    {
        if (!$translatorAdapter) {
            $translatorAdapter                          = Kernel::$Environment::TRANSLATOR_ADAPTER;
        }
        if (!isset(self::$singletons[$translatorAdapter])) {
            $class_name                                 = static::NAME_SPACE . "Translator" . ucfirst($translatorAdapter);
            if (class_exists($class_name)) {
                self::$singletons[$translatorAdapter]   = new $class_name($auth);
            } else {
                Error::register("Translator Adapter not supported: " . $translatorAdapter, static::ERROR_BUCKET);
            }
        }

        return self::$singletons[$translatorAdapter];
    }

    public static function dump($language = null)
    {
        $lang_code                                      = self::getLang($language);

        return self::$cache[$lang_code];
    }
    public static function clear($language = null)
    {
        $lang_code                                      = self::getLang($language);

        self::$cache[$lang_code]                        = null;
        Mem::getInstance(static::CACHE_BUCKET . $lang_code)->clear();

        return true;
    }
    public static function process($content, $language = null)
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

    public static function checkLang()
    {
        return (Locale::isMultiLang()
            ? self::getLang()
            : "nolang"
        );
    }

    public static function get_word_by_code($code, $language = null)
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

    private static function getCode($code)
    {
        return (Kernel::$Environment::DEBUG
            ? "{" . $code . "}"
            : $code
        );
    }

    private static function getWordByCodeFromDB($code, $language = null)
    {
        $lang                                           = self::getLang($language);
        $i18n                                           = array(
                                                            "code"      => $code
                                                            , "lang"    => $lang
                                                            , "cache"   => false
                                                            , "word"    => $code
                                                        );
        if ($lang) {
            $orm                                        = Orm::getInstance("international");
            $res                                        = $orm->read(array(
                                                            "translation.description"
                                                            , "translation.is_new"
                                                        ), array(
                                                            "lang.code" => $i18n["lang"]
                                                            , "translation.word_code" => substr($i18n["code"], 0, 254)
                                                        ), null, 1);
            if (is_array($res)) {
                $i18n["word"]                           = $res["description"];
                $i18n["cache"]                          = !$res["is_new"];
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
    public static function getLang($lang_code = null)
    {
        return strtolower(
            $lang_code
            ? $lang_code
            : Locale::getLang("code")
        );
    }
    public static function getLangDefault($lang_code = null)
    {
        return strtolower(
            $lang_code
            ? $lang_code
            : Locale::getLangDefault("code")
        );
    }
}
