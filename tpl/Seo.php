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
namespace phpformsframework\libs\tpl;

use phpformsframework\libs\international\Locale;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\Validator;

if (!defined("ENCODING"))                { define("ENCODING", "uft-8"); }

class Seo {
    private $title                              = null;
    private $description                        = null;
    private $content                            = null;
    private $lang                               = null;
    private $encoding                           = ENCODING;
    private $stopwords                          = null;
    private $unwantedwords                      = array();
    private $stemmer                            = null;
    private $containers                         = array(
        '<title></title>' => "0.1"
        , '<meta name="description" />' => "0.4"
        , '<h1></h1>' => "0.5"
        , '<h2></h2>' => "0.1"
        , '<h3></h3>' => "0.1"
        , '<p></p>' => "0.1"
        , '<img alt="" />' => "0.1"
        , '<a> <a/>' => "0.1"
    );

    public function __construct($content, $title = null, $description = null)
    {
        $this->title                            = $title;
        $this->description                      = $description;

        if(Validator::is($content, "url")) {
            $content                            =  $this->get_content_by_web_page($content);
        }

        $this->lang                             = $this->detectLang($content);
        $this->stopwords                        = $this->loadConf("stopwords/" . $this->lang);
        $this->content                          = $this->html2text($content);
    }

    public function setEncoding($encoding) {
        $this->encoding                         = $encoding;

        return $this;
    }
    public function setLang($tiny_code) {
        $this->lang                             = $tiny_code;

        return $this;
    }
    public function extractKeywords() {
        $text                                   = $this->content;
        $text                                   = $this->strip_punctuation($text);
        $text                                   = $this->strip_symbols($text);
        $text                                   = $this->strip_numbers($text);
        $text                                   = mb_strtolower( $text, $this->encoding );

        mb_regex_encoding( $this->encoding );
        $words                                  = mb_split( ' +', $text );

        foreach ( $words as $key => $word ) {
            $words[$key]                        = $this->Stemmer($word);
        }
        $words = array_diff( $words, $this->stopwords );
        $words = array_diff( $words, $this->unwantedwords );

        $keywordCounts = array_count_values( $words );
        arsort( $keywordCounts, SORT_NUMERIC );
        $uniqueKeywords = array_keys( $keywordCounts );

        foreach ($keywordCounts  as $frequency) {
            $density = $frequency / count ($words) * 100;

            $keys = array_keys ($words, $word); // $word is the word we're currently at in the loop
            $positionSum = array_sum ($keys) + count ($keys);
            $prominence = (count ($words) - (($positionSum - 1) / count ($keys))) * (100 /   count ($words));
            $value = (double) ((1 + $density) * ( $prominence) * (1 + $this->containers[""]));

                                     // 0.x ~ 5        0.x ~ 100
         }



    }

    private function Stemmer($word) {
        if(!$this->stemmer) {
            $lang = Locale::getLang($this->lang);
            $class_name = '\\Wamania\\Snowball\\' . $lang["description"];
            $this->stemmer = new $class_name();
        }

        return ($this->stemmer
            ? $this->stemmer->stem($word)
            : $word
        );
    }

    private function detectLang($raw_text) {
        $matches = array();
        preg_match('@<html.*\slang="([^\s"]+)"@i',
            $raw_text, $matches);
        if(isset($matches[1])) {
            $lang                               = $matches[1];
        } else {
            preg_match('@<meta\s+http-equiv="Content-Language"\s+content="([^\s"]+)"@i',
                $raw_text, $matches);

            $lang                               = (isset($matches[1])
                                                    ? $matches[1]
                                                    : Locale::getLang("tiny_code")
                                                );
        }
        return $lang;
    }
    private function loadConf($what) {
        return Filemanager::getInstance("json")->read(__DIR__ . "/conf/" . $what . ".json");
    }
    private function convertEncoding($raw_text, $encoding) {
        //return iconv( $encoding, $this->encoding, $raw_text );
        return mb_convert_encoding( $raw_text, $this->encoding, $encoding );
    }
    private function detectEncoding($raw_text) {
        /* Get the file's character encoding from a <meta> tag */
        preg_match('@<meta\s+http-equiv="Content-Type"\s+content="([\w/]+)(;\s+charset=([^\s"]+))?@i',
            $raw_text, $matches);
        $encoding                               = $matches[3];
        if (!$encoding)                         { $encoding = mb_detect_encoding($raw_text); }
        if (!$encoding)                         { $encoding = $this->encoding; }

        return $encoding;
    }
    private function html2text($encoded_text) {
        /* Strip HTML tags and invisible text */
        $encoded_text                           = $this->strip_html_tags($encoded_text);
        /* Decode HTML entities */
        return html_entity_decode( $encoded_text, ENT_QUOTES, strtoupper($this->encoding) );
    }
    private function get_content_by_web_page($url) {
        $res                                    = null;
        /* Read an HTML file */
        $raw_text                               = file_get_contents( $url );
        if($raw_text) {
            $encoding                           = $this->detectEncoding($raw_text);
            $res                                = ($encoding != $this->encoding
                                                    ? $this->convertEncoding($raw_text, $this->encoding)
                                                    : $raw_text
                                                );
        }
        return $res;
    }

    /**
     * Remove HTML tags, including invisible text such as style and
     * script code, and embedded objects.  Add line breaks around
     * block-level tags to prevent word joining after tag removal.
     * @param string $text
     */
    private function strip_html_tags( $text )
    {
        $text                                   = preg_replace(
                                                    array(
                                                        // Remove invisible content
                                                        '@<head[^>]*?>.*?</head>@siu',
                                                        '@<style[^>]*?>.*?</style>@siu',
                                                        '@<script[^>]*?.*?</script>@siu',
                                                        '@<object[^>]*?.*?</object>@siu',
                                                        '@<embed[^>]*?.*?</embed>@siu',
                                                        '@<applet[^>]*?.*?</applet>@siu',
                                                        '@<noframes[^>]*?.*?</noframes>@siu',
                                                        '@<noscript[^>]*?.*?</noscript>@siu',
                                                        '@<noembed[^>]*?.*?</noembed>@siu',
                                                        // Add line breaks before and after blocks
                                                        '@</?((address)|(blockquote)|(center)|(del))@iu',
                                                        '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
                                                        '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
                                                        '@</?((table)|(th)|(td)|(caption))@iu',
                                                        '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
                                                        '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
                                                        '@</?((frameset)|(frame)|(iframe))@iu',
                                                    ),
                                                    array(
                                                        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
                                                        "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
                                                        "\n\$0", "\n\$0",
                                                    ),
                                                    $text
                                                );
        return strip_tags( $text );
    }
    /**
     * Strip punctuation from text.
     */
    function strip_punctuation( $text )
    {
        $urlbrackets    = '\[\]\(\)';
        $urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
        $urlspaceafter  = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
        $urlall         = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;

        $specialquotes  = '\'"\*<>';

        $fullstop       = '\x{002E}\x{FE52}\x{FF0E}';
        $comma          = '\x{002C}\x{FE50}\x{FF0C}';
        $arabsep        = '\x{066B}\x{066C}';
        $numseparators  = $fullstop . $comma . $arabsep;

        $numbersign     = '\x{0023}\x{FE5F}\x{FF03}';
        $percent        = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
        $prime          = '\x{2032}\x{2033}\x{2034}\x{2057}';
        $nummodifiers   = $numbersign . $percent . $prime;

        return preg_replace(
            array(
                // Remove separator, control, formatting, surrogate,
                // open/close quotes.
                '/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
                // Remove other punctuation except special cases
                '/\p{Po}(?<![' . $specialquotes .
                $numseparators . $urlall . $nummodifiers . '])/u',
                // Remove non-URL open/close brackets, except URL brackets.
                '/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
                // Remove special quotes, dashes, connectors, number
                // separators, and URL characters followed by a space
                '/[' . $specialquotes . $numseparators . $urlspaceafter .
                '\p{Pd}\p{Pc}]+((?= )|$)/u',
                // Remove special quotes, connectors, and URL characters
                // preceded by a space
                '/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
                // Remove dashes preceded by a space, but not followed by a number
                '/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
                // Remove consecutive spaces
                '/ +/',
            ),
            ' ',
            $text );
    }

    /**
     * Strip symbols from text.
     */
    function strip_symbols( $text )
    {
        $plus   = '\+\x{FE62}\x{FF0B}\x{208A}\x{207A}';
        $minus  = '\x{2012}\x{208B}\x{207B}';

        $units  = '\\x{00B0}\x{2103}\x{2109}\\x{23CD}';
        $units .= '\\x{32CC}-\\x{32CE}';
        $units .= '\\x{3300}-\\x{3357}';
        $units .= '\\x{3371}-\\x{33DF}';
        $units .= '\\x{33FF}';

        $ideo   = '\\x{2E80}-\\x{2EF3}';
        $ideo  .= '\\x{2F00}-\\x{2FD5}';
        $ideo  .= '\\x{2FF0}-\\x{2FFB}';
        $ideo  .= '\\x{3037}-\\x{303F}';
        $ideo  .= '\\x{3190}-\\x{319F}';
        $ideo  .= '\\x{31C0}-\\x{31CF}';
        $ideo  .= '\\x{32C0}-\\x{32CB}';
        $ideo  .= '\\x{3358}-\\x{3370}';
        $ideo  .= '\\x{33E0}-\\x{33FE}';
        $ideo  .= '\\x{A490}-\\x{A4C6}';

        return preg_replace(
            array(
                // Remove modifier and private use symbols.
                '/[\p{Sk}\p{Co}]/u',
                // Remove mathematics symbols except + - = ~ and fraction slash
                '/\p{Sm}(?<![' . $plus . $minus . '=~\x{2044}])/u',
                // Remove + - if space before, no number or currency after
                '/((?<= )|^)[' . $plus . $minus . ']+((?![\p{N}\p{Sc}])|$)/u',
                // Remove = if space before
                '/((?<= )|^)=+/u',
                // Remove + - = ~ if space after
                '/[' . $plus . $minus . '=~]+((?= )|$)/u',
                // Remove other symbols except units and ideograph parts
                '/\p{So}(?<![' . $units . $ideo . '])/u',
                // Remove consecutive white space
                '/ +/',
            ),
            ' ',
            $text );
    }

    /**
     * Strip numbers from text.
     */
    function strip_numbers( $text )
    {
        $urlchars      = '\.,:;\'=+\-_\*%@&\/\\\\?!#~\[\]\(\)';
        $notdelim      = '\p{L}\p{M}\p{N}\p{Pc}\p{Pd}' . $urlchars;
        $predelim      = '((?<=[^' . $notdelim . '])|^)';
        $postdelim     = '((?=[^'  . $notdelim . '])|$)';

        $fullstop      = '\x{002E}\x{FE52}\x{FF0E}';
        $comma         = '\x{002C}\x{FE50}\x{FF0C}';
        $arabsep       = '\x{066B}\x{066C}';
        $numseparators = $fullstop . $comma . $arabsep;
        $plus          = '\+\x{FE62}\x{FF0B}\x{208A}\x{207A}';
        $minus         = '\x{2212}\x{208B}\x{207B}\p{Pd}';
        $slash         = '[\/\x{2044}]';
        $colon         = ':\x{FE55}\x{FF1A}\x{2236}';
        $units         = '%\x{FF05}\x{FE64}\x{2030}\x{2031}';
        $units        .= '\x{00B0}\x{2103}\x{2109}\x{23CD}';
        $units        .= '\x{32CC}-\x{32CE}';
        $units        .= '\x{3300}-\x{3357}';
        $units        .= '\x{3371}-\x{33DF}';
        $units        .= '\x{33FF}';
        $percents      = '%\x{FE64}\x{FF05}\x{2030}\x{2031}';
        $ampm          = '([aApP][mM])';

        $digits        = '[\p{N}' . $numseparators . ']+';
        $sign          = '[' . $plus . $minus . ']?';
        $exponent      = '([eE]' . $sign . $digits . ')?';
        $prenum        = $sign . '[\p{Sc}#]?' . $sign;
        $postnum       = '([\p{Sc}' . $units . $percents . ']|' . $ampm . ')?';
        $number        = $prenum . $digits . $exponent . $postnum;
        $fraction      = $number . '(' . $slash . $number . ')?';
        $numpair       = $fraction . '([' . $minus . $colon . $fullstop . ']' .
            $fraction . ')*';

        return preg_replace(
            array(
                // Match delimited numbers
                '/' . $predelim . $numpair . $postdelim . '/u',
                // Match consecutive white space
                '/ +/u',
            ),
            ' ',
            $text );
    }
}

