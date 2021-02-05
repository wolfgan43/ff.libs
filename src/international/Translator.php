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

use Exception;
use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Response;
use phpformsframework\libs\storage\DatabaseManager;
use phpformsframework\libs\storage\FilemanagerFs;
use phpformsframework\libs\util\AdapterManager;

/**
 * Class Translator
 * @package phpformsframework\libs\international
 */
class Translator implements Dumpable
{
    use AdapterManager;
    use DatabaseManager;

    const ERROR_BUCKET                                  = "translator";

    protected const REGEXP                              = '/\{_([\w\:\=\-\|\.\s\?\!\\\'\"\,]+)\}/U';

    const CACHE_BUCKET                                  = "translations";


    private static $singletons                          = null;

    private static $cache                               = [];
    private static $cache_updated                       = [];

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
     * @return array
     */
    public static function dump() : array
    {
        return self::$cache;
    }

    /**
     * @param string $lang_tiny_code
     */
    public static function loadLib(string $lang_tiny_code) : void
    {
        self::$cache[$lang_tiny_code]                   = Buffer::cache(static::CACHE_BUCKET)->get($lang_tiny_code) ?? [];

        Response::onBeforeSend(Translator::class . "::saveLib");
    }

    public static function saveLib() : void
    {
        if (!empty(self::$cache_updated)) {
            $cache = Buffer::cache(static::CACHE_BUCKET);
            foreach (self::$cache_updated as $lang_tiny_code => $translations) {
                $cache->set($lang_tiny_code, $translations);
            }
        }
    }

    /**
     * @param string|null $language
     */
    public static function clear(string $language = null) : void
    {
        $lang_code                                      = self::getLang($language);

        self::$cache[$lang_code]                        = null;
        Buffer::cache(static::CACHE_BUCKET)->clear();
    }

    /**
     * @param string $content
     * @param string|null $language
     * @return string
     * @throws Exception
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
                $replace["values"][]                    = self::getWordByCode($code, $language);
            }

            if ($replace) {
                $content                                = str_replace($replace["keys"], $replace["values"], $content);
            }
        }

        return $content;
    }

    /**
     * @return string
     */
    public static function infoLangCode() : string
    {
        return self::getLang();
    }

    /**
     * @return string
     */
    public static function infoCacheFile() : string
    {
        $cache = Buffer::cache(static::CACHE_BUCKET)->getInfo(self::getLang());
        FilemanagerFs::touch($cache);
        return $cache;
    }

    /**
     * @param string|null $code
     * @param string|null $language Upper Code (es: ENG, ITA, SPA)
     * @return string
     * @throws Exception
     */
    public static function getWordByCode(string $code = null, string $language = null) : ?string
    {
        if (!$code || Locale::isDefaultLang()) {
            return $code;
        }
        Debug::stopWatch("translator/" . $code);

        $lang_tiny_code                                 = self::getLang($language);
        if (self::$cache && !array_key_exists($code, self::$cache[$lang_tiny_code])) {
            self::$cache[$lang_tiny_code][$code]        = self::getWordByCodeFromDB($code, $lang_tiny_code);
            if (!isset(self::$cache_updated[$lang_tiny_code])) {
                self::$cache_updated[$lang_tiny_code]   =& self::$cache[$lang_tiny_code];
            }
        }

        Debug::stopWatch("translator/" . $code);

        return self::$cache[$lang_tiny_code][$code] ?? $code;
    }

    /**
     * @param string $code
     * @param string|null $language
     * @return string|null
     * @throws Exception
     */
    private static function getWordByCodeFromDB(string $code, string $language = null) : ?string
    {
        $i18n                                           = null;
        $lang                                           = self::getLang($language);
        if ($lang) {
            $orm                                        = self::orm("international");
            $res                                        = $orm->readOne([
                                                            "translations.text"
                                                        ], [
                                                            "translations.lang" => $lang
                                                            , "translations.code" => substr($code, 0, 254)
                                                        ]);

            if ($res) {
                $i18n                                   = $res->text;
            } elseif (Kernel::$Environment::DEBUG) {
                $orm->insert([
                    "translations.lang"                 => $lang,
                    "translations.code"                 => substr($code, 0, 254),
                    "translations.created_at"           => time()
                ]);
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
        return strtolower($lang_code ?? Locale::getCodeLang());
    }

    /**
     * @param string|null $lang_code
     * @return string
     */
    public static function getLangDefault(string $lang_code = null) : string
    {
        return strtolower($lang_code ?? Locale::getCodeLangDefault());
    }
}
