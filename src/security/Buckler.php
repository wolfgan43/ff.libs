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
namespace phpformsframework\libs\security;

use phpformsframework\libs\Configurable;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Log;
use phpformsframework\libs\Request;
use phpformsframework\libs\Response;

/**
 * Class Buckler
 * @package phpformsframework\libs\security
 */
class Buckler implements Configurable
{
    //Error messages
    private const SERVER_BUSY                                   = "server busy";
    private const ERROR_BUCKET                                  = "firewall";
    private static $rules                                       = null;


    /**
     * @access private
     * @param \phpformsframework\libs\dto\ConfigRules $configRules
     * @return \phpformsframework\libs\dto\ConfigRules
     */
    public static function loadConfigRules($configRules)
    {
        return $configRules
            ->add("badpath");
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig(array $config)
    {
        self::$rules                                            = $config["rules"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema(array  $rawdata) : array
    {
        if (!empty($rawdata["rule"])) {
            $schema                                             = array();
            foreach ($rawdata["rule"] as $badpath) {
                $attr                                           = Dir::getXmlAttr($badpath);
                $key                                            = $attr["source"];
                unset($attr["source"]);
                $schema[$key]                                   = $attr;
            }

            self::$rules                                        = $schema;
        }

        return array(
            "rules"     => self::$rules
        );
    }
    public static function antiVulnerabilityScanner()
    {
        self::checkLoadAvg();
        self::checkAllowedPath();
    }

    public static function antiDenialOfService()
    {

    }

    public static function antiManInTheMiddle()
    {

    }
    public static function antiPhishing()
    {

    }
    public static function antiDriveByAttack()
    {

    }
    public static function antiPasswordAttack()
    {

    }
    public static function antiSqlInjection()
    {

    }
    public static function antiCrossSiteScripting()
    {

    }
    public static function antiEavesdroppingAttack()
    {

    }
    public static function antiBirthdayAttack()
    {

    }
    public static function antiMalwareAttack()
    {

    }

    private static function checkLoadAvg()
    {
        if (function_exists("sys_getloadavg")) {
            $load = sys_getloadavg();
            if ($load[0] > 80) {
                Response::sendError(503);
                Log::emergency(self::SERVER_BUSY);
                exit;
            }
        }
    }

    /**
     * @return string
     */
    private static function pathInfo() : string
    {
        $path_info = null;
        if (isset($_SERVER["REQUEST_URI"])) {
            $path_info =  rtrim(rtrim(isset($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"]
                ? rtrim($_SERVER["REQUEST_URI"], $_SERVER["QUERY_STRING"])
                : $_SERVER["REQUEST_URI"], "?"), "/");
        }

        return $path_info;
    }

    /**
     *
     */
    private static function checkAllowedPath()
    {
        $path_info                                              = self::pathInfo();
        if ($path_info) {
            $matches                                            = array();

            if (!empty(self::$rules)) {
                foreach (self::$rules as $source => $rule) {
                    $src                                        = self::regexp($source);
                    if (preg_match($src, $path_info, $matches)
                        && (is_numeric($rule["destination"]) || ctype_digit($rule["destination"]))
                    ) {
                        Response::httpCode($rule["destination"]);

                        if (isset($rule["log"])) {
                            Log::warning(
                                array(
                                    "rule"          => $source,
                                    "action"        => $rule["destination"],
                                    "url"           => Request::url(),
                                    "referer"       => Request::referer()
                                ),
                                static::ERROR_BUCKET
                            );
                        }
                        exit;
                    }
                }
            }
        }
    }

    /**
     * @param string $rule
     * @return string
     */
    private static function regexp(string $rule) : string
    {
        return "#" . (
            strpos($rule, "[") === false && strpos($rule, "(") === false && strpos($rule, '$') === false
                ? str_replace("\*", "(.*)", preg_quote($rule, "#"))
                : $rule
            ) . "#i";
    }

    /**
     * @todo da fare
     * private static function antiFlood()
     * {
     * }
     */
}
