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

class ffTranslator_translated extends ffTranslator
{
    private $code                                       = null;

    private function __construct($code = null)
    {
        $this->code                                     = $code;
    }

    public function translate($words, $toLang = ffTranslator::LANG, $fromLang = ffTranslator::LANG_DEFAULT)
    {
        $fromLang                                       = strtolower($fromLang);
        $toLang                                         = strtolower($toLang);
        if($fromLang == $toLang) {
            $res                                        = $words;
        } else {
            $res                                        = parent::translate($words, $toLang, $fromLang);
            if(!$res) {
                $transalted                             = file_get_contents("http://api.mymemory.translated.net/get?q=" . urlencode($words) . "&langpair=" . $fromLang . "|" . $toLang . ($this->code ? "&key=" . $this->code : ""));
                if($transalted) {
                    $buffer                             = json_decode($transalted, true);
                    if ($buffer["responseStatus"] == 200 && $buffer["responseData"]["translatedText"])
                        $res                            = $this->save($words, $toLang, $fromLang, $buffer["responseData"]["translatedText"]);
                }
            }
        }

        return $res;
    }

}

