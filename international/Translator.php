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
use phpformsframework\libs\Debug;

if(!defined("FF_PREFIX"))                       define("FF_PREFIX", "ff_");
if(!defined("FF_TRANSLATOR_ADAPTER"))           define("FF_TRANSLATOR_ADAPTER", false);

abstract class translatorAdapter {
    const DICTIONARY_PREFIX                             = "dictionary/translations/";

    private static $translation                         = array();

    protected function save($words, $toLang, $fromLang, $words_translated) {
        $fromto                                         = strtoupper($fromLang . "|" . $toLang);

        self::$translation[$fromto][$words]             = $words_translated;
        Mem::getInstance()->set($words, self::$translation[$fromto][$words], self::DICTIONARY_PREFIX . $fromto);

        return self::$translation[$fromto][$words];
    }

    public function translate($words, $toLang = null, $fromLang = null) {
        $fromto                                         = strtoupper(Translator::getLangDefault($fromLang)  . "|" . Translator::getLang($toLang));

        if(!isset(self::$translation[$fromto][$words])) {
            $cache                                      = Mem::getInstance();
            self::$translation[$fromto][$words]         = $cache->get($words, self::DICTIONARY_PREFIX . $fromto);
        }

        return self::$translation[$fromto][$words];
    }
}

class Translator
{
    const ADAPTER                                       = false;

    const REGEXP                                        = "/\{_([\w\[\]\:\=\-\|\.]+)\}/U";

    const DB_TABLE_LANG                                 = FF_PREFIX . "languages";

    const DB_TABLE_INTERNATIONAL                        = FF_PREFIX . "international";

    const INSERT_EMPTY                                  = true;
    const BUCKET_PREFIX                                 = "ffcms/translations/";


    private static $singletons                          = null;

    private static $cache                               = null;


    public static function getInstance($eType = self::ADAPTER, $auth = null)
    {
        if($eType) {
            if (!isset(self::$singletons[$eType])) {
                $classname = "translator" . ucfirst($eType);
                self::$singletons[$eType] = new $classname($auth);
            }
        } else {
            self::$singletons[$eType] = false;
        }

        return self::$singletons[$eType];
    }

    public static function dump($language = null) {
        $lang_code                                       = self::getLang($language);

        return self::$cache[$lang_code];
    }
    public static function clear($language = null) {
        $lang_code                                       = self::getLang($language);

        self::$cache[$lang_code]                         = null;
        Mem::getInstance()->clear(self::BUCKET_PREFIX . $lang_code);

        return true;
    }
    public static function process($content, $language = null) {
        $matches                                        = array();
        $rc                                             = preg_match_all (self::REGEXP, $content, $matches);
        if ($rc) {
            $replace                                    = null;
            $vars                                       = $matches[1];
            foreach ($vars as $code) {
                $replace["keys"][]                      = "{_" . $code . "}";
                $replace["values"][]                    = self::get_word_by_code($code, $language);
            }

            if($replace)                                { $content = str_replace($replace["keys"], $replace["values"], $content); }
        }

        return $content;
    }
    public static function get_word_by_code($code, $language = null) {
        if(!$code)                                      { return null; }
        $lang_code                                      = self::getLang($language);

        if(!self::$cache[$lang_code][$code]) {
            $cache                                      = Mem::getInstance();
            self::$cache[$lang_code][$code]              = $cache->get($code, self::BUCKET_PREFIX . $lang_code);
            if(!self::$cache[$lang_code][$code]) {
                self::$cache[$lang_code][$code]          = self::getWordByCodeFromDB($code, $lang_code);

                $cache->set($code, self::$cache[$lang_code][$code], self::BUCKET_PREFIX . $lang_code);
            }
        }

        return (self::$cache[$lang_code][$code]["cache"]
            ? self::$cache[$lang_code][$code]["word"]
            : (Debug::ACTIVE
                ? "{" . self::$cache[$lang_code][$code]["word"] . "}"
                : self::$cache[$lang_code][$code]["word"]
            )
        );
    }

    private static function getWordByCodeFromDB($code, $language = null) {
        $db                                             = new ffDB_Sql();
        $i18n                                           = array(
                                                            "code"      => $code
                                                            , "lang"    => self::getLang($language)
                                                            , "cache"   => false
                                                            , "word"    => $code
                                                        );
        $sSQL                                           = "SELECT
                                                                " . self::DB_TABLE_INTERNATIONAL . ".*
                                                            FROM
                                                                " . self::DB_TABLE_INTERNATIONAL . "
                                                                INNER JOIN " . self::DB_TABLE_LANG . " ON
                                                                    " . self::DB_TABLE_INTERNATIONAL . ".`ID_lang` = " . self::DB_TABLE_LANG . ".ID
                                                            WHERE
                                                                " . self::DB_TABLE_LANG . ".`code` = " . $db->toSql($i18n["lang"]) . "
                                                                AND " . self::DB_TABLE_INTERNATIONAL . ".`word_code` =" . $db->toSql(substr($i18n["code"], 0, 254));
        $db->query($sSQL);
        if($db->nextRecord()) {
            if(!$db->record["is_new"]) {
                $i18n["word"]                           = $db->getField("description", "Text", true);
                $i18n["cache"]                          = true;
            }
        } elseif(self::INSERT_EMPTY) {
            $sSQL                   = "INSERT INTO " . self::DB_TABLE_INTERNATIONAL . "
                                    (
                                        `ID`
                                        , `ID_lang`
                                        , `word_code`
                                        , `is_new`
                                    )
                                    VALUES
                                    (
                                        null
                                        , IFNULL(
                                            (SELECT " . self::DB_TABLE_LANG . ".`ID` 
                                                FROM " . self::DB_TABLE_LANG . " 
                                                WHERE " . self::DB_TABLE_LANG . ".`code` = " . $db->toSql($i18n["lang"]) . " 
                                                LIMIT 1
                                            )
                                            , 0
                                        )
                                        , " . $db->toSql(substr($i18n["code"], 0, 254)) . "
                                        , " . $db->toSql("1", "Number") . "
                                    )";
            $db->execute($sSQL);
        }

        return $i18n;
    }
    public static function getLang($lang_code = null) {
        return strtolower($lang_code
            ? $lang_code
            : Locale::getLang("code")
        );
    }
    public static function getLangDefault($lang_code = null) {
        return strtolower($lang_code
            ? $lang_code
            : Locale::getLangDefault("code")
        );
    }
}
