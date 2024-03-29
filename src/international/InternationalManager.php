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

use ff\libs\Exception;

/**
 * Trait InternationalManager
 * @package ff\libs\international
 */
trait InternationalManager
{
    /**
     * @param string|null $locale
     * @param bool $verifyAcceptedLangs
     * @return array
     */
    protected function locale(string $locale = null, bool $verifyAcceptedLangs = false) : array
    {
        return Locale::get($locale, $verifyAcceptedLangs);
    }

    /**
     * @param string|null $locale
     * @param bool $verifyAcceptedLangs
     * @return string
     */
    protected function lang(string $locale = null, bool $verifyAcceptedLangs = false) : string
    {
        return explode("-", Locale::get($locale, $verifyAcceptedLangs)[0])[0];
    }

    /**
     * @param string $code
     * @param string|null $default
     * @param null|string $language
     * @return string|null
     * @throws Exception
     */
    protected function translate(string $code, string $default = null, string $language = null) : ?string
    {
        $translate = Translator::getWordByCode($code, $language);
        return $translate == $code
            ? $default ?? $code
            : $translate;
    }
}
