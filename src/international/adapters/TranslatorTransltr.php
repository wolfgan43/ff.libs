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
namespace ff\libs\international\adapters;

use ff\libs\international\Translator;
use ff\libs\international\TranslatorAdapter;
use ff\libs\security\Validator;
use ff\libs\storage\FilemanagerWeb;
use ff\libs\Exception;

/**
 * Class TranslatorTransltr
 * @package ff\libs\international\adapters
 */
class TranslatorTransltr extends TranslatorAdapter
{
    /**
     * @param string $words
     * @param string|null $toLang
     * @param string|null $fromLang
     * @return string|null
     * @throws Exception
     */
    public function translate(string $words, string $toLang = null, string $fromLang = null) : ?string
    {
        $fromLang                                       = Translator::getLangDefault($fromLang);
        $toLang                                         = Translator::getLang($toLang);
        if ($fromLang == $toLang) {
            $res                                        = $words;
        } else {
            $res                                        = parent::translate($words, $toLang, $fromLang);
            if (!$res
                && !empty($transalted = FilemanagerWeb::fileGetContentsJson("http://www.transltr.org/api/translate?text=" . urlencode($words) . "&to=" . strtolower(substr($toLang, 0, 2)) . "&from=" . strtolower(substr($fromLang, 0, 2)) . ($this->code ? "&key=" . $this->code : "")))
                && !empty($transalted->translationText)) {
                $res                                    = $this->save($words, $toLang, $fromLang, $transalted->translationText);
            }
        }
        return $res;
    }
}
