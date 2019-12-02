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
namespace phpformsframework\libs\international\adapters;

use phpformsframework\libs\international\Translator;
use phpformsframework\libs\international\TranslatorAdapter;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\storage\Filemanager;

/**
 * Class TranslatorGoogle
 * @package phpformsframework\libs\international\adapters
 */
class TranslatorGoogle extends TranslatorAdapter
{
    /**
     * @param string $words
     * @param string|null $toLang
     * @param string|null $fromLang
     * @return string|null
     */
    public function translate(string $words, string $toLang = null, string $fromLang = null) : ?string
    {
        $fromLang                                       = Translator::getLangDefault($fromLang);
        $toLang                                         = Translator::getLang($toLang);
        if ($fromLang == $toLang) {
            $res                                        = $words;
        } else {
            $res                                        = parent::translate($words, $toLang, $fromLang);
            if (!$res) {
                $transalted                             = Filemanager::fileGetContent("https://translation.googleapis.com/language/translate/v2?q=" . urlencode($words) . "&target=" . substr($toLang, 0, 2) . "&source=" . substr($fromLang, 0, 2) . ($this->code ? "&key=" . $this->code : ""));
                if ($transalted) {
                    $buffer                             = Validator::json2Array($transalted);
                    if (!$buffer["error"] && $buffer["responseData"]["translatedText"]) {
                        $res                            = $this->save($words, $toLang, $fromLang, $buffer["responseData"]["translatedText"]);
                    }
                }
            }
        }
        return $res;
    }
}
