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

use ff\libs\cache\Buffer;
use ff\libs\Debug;
use ff\libs\Dumpable;
use ff\libs\Kernel;
use ff\libs\Response;
use ff\libs\storage\DatabaseManager;
use ff\libs\storage\FilemanagerFs;
use ff\libs\util\AdapterManager;
use ff\libs\Exception;

/**
 * Class Translator
 * @package ff\libs\international
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
     * @param string|null $lang_code
     */
    public static function clear(string $lang_code = null) : void
    {
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
     * @param string|null $lang_code
     * @return string
     */
    public static function infoLangCode(string $lang_code = null) : string
    {
        return self::getLang($lang_code);
    }

    /**
     * @param string|null $lang_code
     * @return string|null
     */
    public static function infoCacheFile(string $lang_code = null) : ?string
    {
        if (($cache_file = Buffer::cache(static::CACHE_BUCKET)->getInfo(self::getLang($lang_code))) && FilemanagerFs::touch($cache_file)) {
            return $cache_file;
        }
        return null;
    }

    /**
     * @param string|null $code
     * @param string|null $lang_code (es: en, it, es)
     * @return string|null
     * @throws Exception
     */
    public static function getWordByCode(string $code = null, string $lang_code = null) : ?string
    {
        if (!$code || Locale::isDefaultLang($lang_code)) {
            return $code;
        }
        Debug::stopWatch("translator/" . $code);
        if (empty(self::$cache[$lang_code]) || !array_key_exists($code, self::$cache[$lang_code])) {
            self::$cache[$lang_code][$code]             = self::getWordByCodeFromDB($code, $lang_code);
            if (!isset(self::$cache_updated[$lang_code])) {
                self::$cache_updated[$lang_code]        =& self::$cache[$lang_code];
            }
        }

        Debug::stopWatch("translator/" . $code);

        return self::$cache[$lang_code][$code] ?? $code;
    }

    /**
     * @param string|null $code
     * @param string|null $lang_code (es: en, it, es)
     * @return string|null
     */
    public static function getWordByCodeCached(string $code = null, string $lang_code = null) : ?string
    {
        if (!$code || Locale::isDefaultLang($lang_code)) {
            return $code;
        }

        return self::$cache[$lang_code][$code] ?? $code;
    }

    /**
     * @param string $code
     * @param string $lang_code
     * @return string|null
     * @throws Exception
     */
    private static function getWordByCodeFromDB(string $code, string $lang_code) : ?string
    {
        $i18n                                           = null;
        $orm                                            = self::orm("international");
        $res                                            = $orm->readOne([
                                                                "translations.text"
                                                            ], [
                                                                "translations.lang" => $lang_code,
                                                                "translations.code" => substr($code, 0, 254)
                                                            ]);

        if ($res) {
            $i18n                                       = $res->text;
        } elseif (Kernel::$Environment::DEBUG) {
            $orm->insert([
                "translations.lang"                     => $lang_code,
                "translations.code"                     => substr($code, 0, 254),
                "translations.created_at"               => time()
            ]);
        }

        return $i18n;
    }

    /**
     * @param string|null $lang_code
     * @return string
     */
    public static function getLang(string $lang_code = null) : string
    {
        return strtolower($lang_code ?? Locale::getCodeLang() ?? "");
    }

    /**
     * @param string|null $lang_code
     * @return string
     */
    public static function getLangDefault(string $lang_code = null) : string
    {
        return strtolower($lang_code ?? Locale::getCodeLangDefault() ?? "");
    }
}
