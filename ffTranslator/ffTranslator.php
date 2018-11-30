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

if(!defined("FF_PREFIX"))                       define("FF_PREFIX", "ff_");
if(!defined("FF_LOCALE"))                       define("FF_LOCALE", "ITA");
if(!defined("LANGUAGE_DEFAULT"))                define("LANGUAGE_DEFAULT", "ITA");
if(!defined("DEBUG_MODE"))                      define("DEBUG_MODE", false);
if(!defined("FF_TRANSLATOR_ADAPTER"))           define("FF_TRANSLATOR_ADAPTER", false);

class ffTranslator
{
    const ADAPTER                                       = false;

    const REGEXP                                        = "/\{_([\w\[\]\:\=\-\|\.]+)\}/U";

    const DB_TABLE_LANG                                 = FF_PREFIX . "languages";
    const DB_TABLE_INTERNATIONAL                        = FF_PREFIX . "international";
    const LANG                                          = FF_LOCALE;
    const LANG_DEFAULT                                  = LANGUAGE_DEFAULT;

    const DEBUG                                         = DEBUG_MODE;
    const INSERT_EMPTY                                  = true;

    const BUCKET_PREFIX                                 = "ffcms/translations/";
    const DICTIONARY_PREFIX                             = "dictionary/translations/";

    private static $singletons                          = null;

    private static $cache                               = null;
    private static $translation                         = array();

    /**
     *
     * @param type $eType
     */
    static public function getInstance($eType = ffTranslator::ADAPTER, $auth = null)
    {
        if($eType) {
            if (!isset(self::$singletons[$eType])) {
                require_once("adapters/" . $eType . "." . FF_PHP_EXT);
                $classname = "ffTranslator_" . $eType;
                self::$singletons[$eType] = new $classname($auth);
            }
        } else {
            self::$singletons[$eType] = false;
        }

        return self::$singletons[$eType];
    }

    public static function dump($language = self::LANG) {
        $language                                       = strtolower($language);

        return self::$cache[$language];
    }
    public static function clear($language = self::LANG) {
        $language                                       = strtolower($language);

        self::$cache[$language]                         = null;
        ffCache::getInstance()->clear(self::BUCKET_PREFIX . $language);

        return true;
    }
    public static function process($content, $language = ffTranslator::LANG) {
        $matches                                        = array();
        $rc                                             = preg_match_all (ffTranslator::REGEXP, $content, $matches);
        if ($rc) {
            $language                                   = strtoupper($language);
            $vars                                       = $matches[1];
            foreach ($vars as $code) {
                $replace["keys"][]                      = "{_" . $code . "}";
                $replace["values"][]                    = self::get_word_by_code($code, $language);
            }

            if($replace)                                $content = str_replace($replace["keys"], $replace["values"], $content);
        }

        return $content;
    }
    public static function get_word_by_code($code, $language = self::LANG) {
        if(!$code)                                      { return null; }
        $language                                       = strtolower($language);

        if(!self::$cache[$language][$code]) {
            $cache                                      = ffCache::getInstance();
            self::$cache[$language][$code]              = $cache->get($code, self::BUCKET_PREFIX . $language);
            if(!self::$cache[$language][$code]) {
                self::$cache[$language][$code]          = self::getWordByCodeFromDB($code, $language);

                $cache->set($code, self::$cache[$language][$code], self::BUCKET_PREFIX . $language);
            }
        }

        return (self::$cache[$language][$code]["cache"]
            ? self::$cache[$language][$code]["word"]
            : (self::DEBUG
                ? "{" . self::$cache[$language][$code]["word"] . "}"
                : self::$cache[$language][$code]["word"]
            )
        );
    }

    private static function getWordByCodeFromDB($code, $language = self::LANG) {
        $db                                             = new ffDB_Sql();
        $i18n                                           = array(
                                                            "code"      => $code
                                                            , "lang"    => $language
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
                                                                AND " . self::DB_TABLE_INTERNATIONAL . ".`word_code` =" . $db->toSql($i18n["code"]);
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
                                        , " . $db->toSql($i18n["code"]) . "
                                        , " . $db->toSql("1", "Number") . "
                                    )";
            $db->execute($sSQL);
        }

        return $i18n;
    }


    private function __construct()
    {
    }

    protected function save($words, $toLang, $fromLang, $words_translated) {
        $fromto                                                 = strtoupper($fromLang . "|" . $toLang);

        ffTranslator::$translation[$fromto][$words]             = $words_translated;
        ffCache::getInstance()->set($words, ffTranslator::$translation[$fromto][$words], self::DICTIONARY_PREFIX . $fromto);

        return ffTranslator::$translation[$fromto][$words];
    }

    public function translate($words, $toLang = ffTranslator::LANG, $fromLang = ffTranslator::LANG_DEFAULT) {
        $fromto                                                 = strtoupper($fromLang . "|" . $toLang);

        if(!isset(ffTranslator::$translation[$fromto][$words])) {
            $cache                                              = ffCache::getInstance();
            ffTranslator::$translation[$fromto][$words]         = $cache->get($words, self::DICTIONARY_PREFIX . $fromto);
        }

        return ffTranslator::$translation[$fromto][$words];
    }
}
