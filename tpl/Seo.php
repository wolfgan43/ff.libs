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

use phpformsframework\libs\Error;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\storage\Filemanager;
use phpformsframework\libs\Validator;

if (!defined("ENCODING"))                { define("ENCODING", "uft-8"); }

class Seo {
    private $content                            = null;
    private $lang                               = null;
    private $encoding                           = ENCODING;
    private $stopwords                          = null;
    private $unwantedwords                      = array();

    private $meta                               = null;
    private $title                              = null;
    private $description                        = null;
    private $h1                                 = null;
    private $h2                                 = null;
    private $h3                                 = null;
    private $p                                  = null;
    private $strong                             = null; //b
    private $em                                 = null;  //i
    private $alt                                = null;
    private $a                                  = null;

    private $page_rules                         = array(
                                                    "keyword_density"   => array(
                                                        "min"           => "2"
                                                        , "max"         => "5"
                                                    )
                                                    , "page_speed"      => array(
                                                        "first_byte"    => "0.4"
                                                        , "DOM_loaded"  => "4"
                                                    )
                                                );
    private $container_rules                    = array(
                                                    "title"             => array(
                                                        "required"      => true
                                                        , "limit"       => 1
                                                        , "min"         => 10
                                                        , "max"         => 60
                                                        , "truncate"    => 75
                                                        , "result"      => "first"
                                                        , "prototype"   => "Primary Keyword - Secondary Keyword | Brand Name"
                                                        , "score"      => 0.3
                                                    )
                                                    , "description"     => array(
                                                        "required"      => true
                                                        , "limit"       => 1
                                                        , "min"         => 50
                                                        , "truncate"    => 160
                                                        , "result"      => "first"
                                                        , "prototype"   => "Primary Keyword ... Secondary Keyword"
                                                        , "score"      => 0.1
                                                    )
                                                    , "h1"              => array(
                                                        "required"      => true
                                                        , "limit"       => 1
                                                        , "result"      => "first"
                                                        , "prototype"   => "Primary Keyword"
                                                        , "score"      => 0.4
                                                    )
                                                    , "h2"              => array(
                                                        "required"      => false
                                                        , "limit"       => null
                                                        , "result"      => "merge"
                                                        , "prototype"   => "Primary Keyword || Secondary Keyword"
                                                        , "score"      => 0.1
                                                    )
                                                    , "h3"              => array(
                                                        "required"      => false
                                                        , "limit"       => null
                                                        , "result"      => "merge"
                                                        , "prototype"   => "Primary Keyword || Secondary Keyword"
                                                        , "score"      => 0.01
                                                    )
                                                    , "p" => array(
                                                        "required"      => true
                                                        , "limit"       => null
                                                        , "min"         => 300
                                                        , "result"      => "merge"
                                                        , "prototype"   => "Primary Keyword ... Secondary Keyword"
                                                        , "score"      => 0.5
                                                    )
                                                    , "strong" => array(
                                                        "required"      => false
                                                        , "limit"       => null
                                                        , "result"      => "merge"
                                                        , "prototype"   => "Primary Keyword || Secondary Keyword"
                                                        , "score"      => 0.01
                                                    )
                                                    , "em" => array(
                                                        "required"      => false
                                                        , "limit"       => null
                                                        , "result"      => "merge"
                                                        , "prototype"   => "Primary Keyword || Secondary Keyword"
                                                        , "score"      => 0.01
                                                    )
                                                    , "alt" => array(
                                                        "required"      => false
                                                        , "limit"       => null
                                                        , "result"      => "merge"
                                                        , "prototype"   => "Primary Keyword || Secondary Keyword"
                                                        , "score"      => 0.01
                                                    )
                                                    , "a" => array(
                                                        "required"      => false
                                                        , "limit"       => null
                                                        , "result"      => "merge"
                                                        , "prototype"   => "Primary Keyword || Secondary Keyword"
                                                        , "score"      => 0.01
                                                    )
                                                );

    private $error                              = 0;
    private $warning                            = 0;
    private $success                            = 0;

    /**
     * @var \Wamania\Snowball\Stemmer'
     */
    private $stemmer                            = null;

    public function __construct($html = null)
    {
        if($html) {
            $this->setContainers($html);
        }
    }
    public function loadHtmlByUrl($url) {
        $html = $this->get_content_by_web_page($url);

        return $this->setContainers($html);
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
        $keywords                               = array();
        $sentence                               = $this->keywordSentence($this->content, $this->encoding, $this->stopwords, $this->unwantedwords);
        $uniqueKeywords                         = array_keys( $sentence );

        foreach ($sentence  as $word => $frequency) {
            $density                            = $this->keywordDensity($frequency, $sentence);
            $prominence                         = $this->keywordProminence($word, $sentence);
            $containers                         = $this->keywordContainers($word);
            //        %  0.x ~ 5  %  0.x ~ 100      index
            $keywords[$word]                    = array_sum($containers) * $prominence / 100;
        }
        return $keywords;
    }

    private function setContainers($html) {
        $this->lang                             = $this->detectLang($html);
        $this->stopwords                        = $this->loadConf("stopwords/" . $this->lang);
        $this->meta                             = get_meta_tags($html);
        $this->content                          = $this->html2text($html);
        $this->title                            = $this->title($html);
        $this->description                      = $this->description($html);
        $this->h1                               = $this->h1($html);
        $this->h2                               = $this->h2($html);
        $this->h3                               = $this->h3($html);
        $this->p                                = $this->p($html);
        $this->strong                           = $this->strong($html);
        $this->em                               = $this->em($html);
        $this->alt                              = $this->alt($html);
        $this->a                                = $this->a($html);

        return $this;
    }

    private function keywordContainers($word) {
        $containers                             = array();
        foreach ($this->container_rules as $container_name => $container_rule) {
            $pos                                = strpos($this->$container_name, $word);
            if($pos !== false) {
                $containers[$container_name]    = $container_rule["score"] - ($container_rule["score"] * 100 * $pos / strlen($this->$container_name));
            }
        }

        return $containers;
    }

    private function keywordSentence($text, $encoding = null, $stopwords = null, $unwantedwords = null) {
        $keywordCounts                          = array();
        if($text) {
            $text                               = $this->strip_punctuation($text);
            $text                               = $this->strip_symbols($text);
            $text                               = $this->strip_numbers($text);

            $text                               = mb_strtolower( $text/*,  strtoupper($encoding)*/ );

            mb_regex_encoding( $encoding );
            $words                              = mb_split( ' +', trim($text) );

            foreach ( $words as $key => $word ) {
                $words[$key]                    = $this->Stemmer($word);
            }

            if($stopwords) {
                $words                          = array_diff($words, $stopwords);
            }
            if($unwantedwords) {
                $words                          = array_diff($words, $unwantedwords);
            }
            $keywordCounts                      = array_count_values( $words );
            arsort( $keywordCounts, SORT_NUMERIC );
        }

        return $keywordCounts;
    }
    private function keywordDensity($frequency, $words) {
        return $frequency / count ($words) * 100;
    }

    private function keywordProminence($word, $words) {
        $keys                               = array_keys ($words, $word); // $word is the word we're currently at in the loop
        $positionSum                        = array_sum ($keys) + count ($keys);

        return (count ($words) - (($positionSum - 1) / count ($keys))) * (100 /   count ($words));
    }

    private function Stemmer($word) {
        if(!$this->stemmer) {
            $lang = Locale::getLangs($this->lang);
            $class_name = '\\Wamania\\Snowball\\' . $lang["description"];
            $this->stemmer = new $class_name();
        }

        if($this->stemmer) {
            try {
                $word = $this->stemmer->stem($word);
            } catch (\Exception $exception) {
                Error::register($exception->getMessage(), "seo");
            }
        }
        return $word;
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
        preg_match("/<body[^>]*>(.*?)<\/body>/is", $encoded_text, $matches);
        $body                                   = (isset($matches[1])
                                                    ? $matches[1]
                                                    : $encoded_text
                                                );
        $text                                   = $this->strip_html_tags($body);
        /* Decode HTML entities */
        return html_entity_decode( $text, ENT_QUOTES, strtoupper($this->encoding) );
    }
    private function getTagbyRegExp($regexp, $encoded_text) {
        $res                                    = array();
        $matches                                = array();

        preg_match_all($regexp, $encoded_text, $matches);
        if(is_array($matches) && count($matches) && isset($matches[1])) {
            $res = $matches[1];
        }

        return $res;
    }
    private function getRule($what, $key) {
        return (isset($this->container_rules[$what]) && isset($this->container_rules[$what][$key])
            ? $this->container_rules[$what][$key]
            : false
        );
    }

    private function checkRule($values, $rule) {
        $res                                    = null;
        $error                                  = 0;
        $warning                                = 0;
        $count                                  = count($values);
        if($count) {
            $limit                              = $this->getRule($rule, "limit");
            if ($limit && $count > $limit) {
                $error++;
                Error::register("<" . $rule . "> must be one!", "seo");
            }
            $length                             = strlen($values[0]);
            $min                                = $this->getRule($rule, "min");
            if($min && $length < $min) {
                $warning++;
                Error::register("<" . $rule . "> min length must be " . $min . ". Now is " . $length, "seo");
            }
            $max                                = $this->getRule($rule, "max");
            if($max && $length > $max) {
                $warning++;
                Error::register("<" . $rule . "> max length must be " . $max . ". Now is " . $length, "seo");
            }

            $truncate                           = $this->getRule($rule, "truncate");
            if($truncate && $length > $truncate) {
                $error++;
                Error::register("<" . $rule . "> is too long. The max is " . $truncate . ". Now is " . $length, "seo");
            }
        } elseif($this->getRule($rule, "required")) {
            $error++;
            Error::register("<" . $rule . "> not found!", "seo");
        } else {
            $warning++;
            Error::register("<" . $rule . "> not found! (optional)", "seo");
        }

        if(!$error && !$warning)                { $this->success++; }
        if($error)                              { $this->error++; }
        if($warning)                            { $this->warning++; }
    }
    private function getResult($values, $rule, $check = true) {
        if($check)                              { $this->checkRule($values, $rule); }
        if($this->getRule($rule, "limit")) {
            $res                                = (isset($values[0])
                                                    ? $values[0]
                                                    : ""
                                                );
        } else {
            $res                                = implode(" " , $values);
        }

        return $this->strip_html_tags($res);
    }
    private function title($encoded_text) {
        $regexp                                 = '/<title[^>]*>(.*?)<\/title>/is';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "title");
    }
    private function description($encoded_text) {
        $regexp                                 = '@<meta\s+name="description"\s+content="([^\"]+)"@i';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "description");
    }
    private function h1($encoded_text) {
        $regexp                                 = '/<h1[^>]*>(.*?)<\/h1>/is';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "h1");
    }
    private function h2($encoded_text) {
        $regexp                                 = '/<h2[^>]*>(.*?)<\/h2>/is';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "h2");
    }
    private function h3($encoded_text) {
        $regexp                                 = '/<h3[^>]*>(.*?)<\/h3>/is';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "h3");
    }
    private function p($encoded_text) {
        $regexp                                 = '/<p[^>]*>(.*?)<\/p>/is';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "p");
    }
    private function strong($encoded_text) {
        $regexp                                 = '/<strong[^>]*>(.*?)<\/strong>/is';
        $res                                    = array_merge($this->getTagbyRegExp($regexp, $encoded_text), $this->b($encoded_text));
        return $this->getResult($res, "strong");
    }
    private function b($encoded_text) {
        $regexp                                 = '/<b[^>]*>(.*?)<\/b>/is';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "strong");
    }

    private function em($encoded_text) {
        $regexp                                 = '/<em[^>]*>(.*?)<\/em>/is';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "em");
    }
    private function alt($encoded_text) {
        $regexp                                 = '@alt="([^"]+)"@';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "alt");
    }
    private function a($encoded_text) {
        $regexp                                 = '/<a[^>]*>(.*?)<\/a>/is';
        $res                                    = $this->getTagbyRegExp($regexp, $encoded_text);
        return $this->getResult($res, "a");
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
     * @return string
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
        $text                                   = str_replace("<", " <", $text);
        return strip_tags( $text );
    }
    /**
     * Strip punctuation from text.
     * @param string $text
     * @return string
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
     * @param string $text
     * @return string
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
     * @param string $text
     * @return string
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

