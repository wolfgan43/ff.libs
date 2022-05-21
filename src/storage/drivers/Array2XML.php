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
namespace ff\libs\storage\drivers;

use DOMDocument;
use DOMNode;
use UnexpectedValueException;
use ff\libs\Exception;
use ff\libs\security\Validator;
use function simplexml_load_string;
use function libxml_use_internal_errors;

/**
 * Class Array2XML
 * @package ff\libs\storage\drivers
 */
class Array2XML
{
    private const VERSION       = "1.0";
    private const ENCODING      = "UTF-8";

    private static $xml         = null;

    /**
     * Initialize the root XML node [optional]
     * @param string $version
     * @param string $encoding
     * @param bool $format_output
     */
    public static function createDOM(string $version = self::VERSION, string $encoding = self::ENCODING, bool $format_output = true) : void
    {
        self::$xml = new DomDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
    }

    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DomDocument
     * @throws Exception
     */
    public static function &createXML(string $node_name, array $arr = array()) : DomDocument
    {
        try {
            $xml = self::getXMLRoot();
            $xml->appendChild(self::convert($node_name, $arr));
        } catch (UnexpectedValueException $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $xml;
    }

    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DOMNode
     * @throws UnexpectedValueException
     */
    private static function &convert(string $node_name, array $arr = array()) : DOMNode
    {
        $xml = self::getXMLRoot();
        $node = $xml->createElement($node_name);

        if (is_array($arr)) {
            if (isset($arr['@attributes'])) {
                foreach ($arr['@attributes'] as $key => $value) {
                    if (!self::isValidTagName($key)) {
                        throw new UnexpectedValueException('[Array2XML] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr['@attributes']); //remove the key from the array once done.
            }

            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if (isset($arr['@value'])) {
                $node->appendChild($xml->createTextNode(self::bool2str($arr['@value'])));
                unset($arr['@value']);    //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            } elseif (isset($arr['@cdata'])) {
                $node->appendChild($xml->createCDATASection(self::bool2str($arr['@cdata'])));
                unset($arr['@cdata']);    //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            }
        }

        //create subnodes using recursion
        if (is_array($arr)) {
            // recurse to get the node for that key
            foreach ($arr as $key=>$value) {
                if (!self::isValidTagName($key)) {
                    throw new UnexpectedValueException('[Array2XML] Illegal character in tag name. tag: '.$key.' in node: '.$node_name);
                }
                if (is_array($value) && is_numeric(key($value))) {
                    // if the new array is numeric index, means it is array of nodes of the same kind
                    // it should follow the parent key name
                    foreach ($value as $v) {
                        $node->appendChild(self::convert($key, $v));
                    }
                } else {
                    // ONLY ONE NODE OF ITS KIND
                    $node->appendChild(self::convert($key, $value));
                }
                unset($arr[$key]); //remove the key from the array once done.
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if (!is_array($arr)) {
            $node->appendChild($xml->createTextNode(self::bool2str($arr)));
        }

        return $node;
    }

    /**
     * Get the root XML node, if there isn't one, create it.
     * @return DomDocument
     */
    private static function getXMLRoot() : DOMDocument
    {
        if (empty(self::$xml)) {
            self::createDOM();
        }
        return self::$xml;
    }

    /**
     * Get string representation of boolean value
     * @todo da tipizzare
     * @param mixed $v
     * @return string
     */
    private static function bool2str($v) : string
    {
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        return $v === false ? 'false' : $v;
    }

    /**
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     * @param string $tag
     * @return bool
     */
    private static function isValidTagName(string $tag) : bool
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }

    /**
     * Convert xml string into array.
     * @param string $xmlstring
     * @return array|null
     */
    public static function xml2Array(string $xmlstring) : ?array
    {
        libxml_use_internal_errors(true);

        $xmlstring = preg_replace("/\s+</", "<", $xmlstring);
        $xmlstring = preg_replace("/<!--.*?-->/ms", "", $xmlstring);
        $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
        if (!$xml || (is_array($xml) &&  array_key_exists("0", $xml))) {
            return null;
        }

        $json = json_encode($xml);
        $json_normalized = str_replace(
            array('"true"', '"false"', '"null"', '[{}]'),
            array('true', 'false', 'null', '[]'),
            $json
        );

        return Validator::jsonDecode($json_normalized);
    }
}
