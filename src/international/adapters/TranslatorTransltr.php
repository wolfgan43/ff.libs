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

use phpformsframework\libs\Dir;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\international\TranslatorAdapter;

class TranslatorTransltr extends TranslatorAdapter
{
    private $code                                       = null;

    private function __construct($code = null)
    {
        $this->code                                     = $code;
    }

    public function translate($words, $toLang = null, $fromLang = null)
    {
        $fromLang                                       = Translator::getLangDefault($fromLang);
        $toLang                                         = Translator::getLang($toLang);
        if ($fromLang == $toLang) {
            $res                                        = $words;
        } else {
            $res                                        = parent::translate($words, $toLang, $fromLang);
            if (!$res) {
                $transalted = Dir::loadFile("http://www.transltr.org/api/translate?text=" . urlencode($words) . "&to=" . strtolower(substr($toLang, 0, 2)) . "&from=" . strtolower(substr($fromLang, 0, 2)) . ($this->code ? "&key=" . $this->code : ""));
                if ($transalted) {
                    $buffer                             = json_decode($transalted, true);
                    if ($buffer["translationText"]) {
                        $res                            = $this->save($words, $toLang, $fromLang, $buffer["translationText"]);
                    }
                }
            }
        }
        return $res;
    }
}
