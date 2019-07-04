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

abstract class TranslatorAdapter
{
    const CACHE_DICTIONARY_BUCKET                       = "dictionary/translations/";

    private static $translation                         = array();

    protected function save($words, $toLang, $fromLang, $words_translated)
    {
        $fromto                                         = strtoupper($fromLang . "|" . $toLang);

        self::$translation[$fromto][$words]             = $words_translated;
        Mem::getInstance(static::CACHE_DICTIONARY_BUCKET . $fromto)->set($words, self::$translation[$fromto][$words]);

        return self::$translation[$fromto][$words];
    }

    public function translate($words, $toLang = null, $fromLang = null)
    {
        $fromto                                         = strtoupper(Translator::getLangDefault($fromLang)  . "|" . Translator::getLang($toLang));

        if (!isset(self::$translation[$fromto][$words])) {
            $cache                                      = Mem::getInstance(static::CACHE_DICTIONARY_BUCKET . $fromto);
            self::$translation[$fromto][$words]         = $cache->get($words);
        }

        return self::$translation[$fromto][$words];
    }
}
