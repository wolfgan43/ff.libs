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
namespace phpformsframework\libs\security;

use phpformsframework\libs\Configurable;
use phpformsframework\libs\Dir;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Log;
use phpformsframework\libs\Response;
use phpformsframework\libs\security\widgets\Login;
use phpformsframework\libs\util\Cookie;
use phpformsframework\libs\util\ServerManager;
use phpformsframework\libs\util\TypesConverter;
use phpformsframework\libs\dto\ConfigRules;
use phpformsframework\libs\Exception;

/**
 * Class Buckler
 * @package phpformsframework\libs\security
 */
class Buckler implements Configurable
{
    use TypesConverter;
    use ServerManager;

    //Error messages
    private const SERVER_BUSY                                   = "server busy";
    private const ERROR_BUCKET                                  = "firewall";

    private const ACCESS_SESSION                                = "session";
    private const ACCESS_PUBLIC                                 = "public";

    private static $rules                                       = null;

    /**
     * @access private
     * @param ConfigRules $configRules
     * @return ConfigRules
     */
    public static function loadConfigRules(ConfigRules $configRules) : ConfigRules
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

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    private static function checkLoadAvg()
    {
        if (function_exists("sys_getloadavg")) {
            $load = sys_getloadavg();
            if ($load[0] > 80) {
                Response::httpCode(503);
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
            $path_info = rtrim(explode("?", $_SERVER["REQUEST_URI"])[0], "/");
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
                                    "action"        => $rule["destination"]
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
     * @todo da fare
     * private static function antiFlood()
     * {
     * }
     */

    /**
     * @throws Exception
     */
    public static function onPageLoad(Kernel $app) : void
    {
        //@todo da spostare la logica nel router. Aggiungere maintenance come tipo di access.
        if (!$app::$Page->accept_path_info && $app::$Page->path_info) {
            Response::sendError(404, "Page not Found");
        }

        if ($app::$Page->vpn && $app::$Page->vpn != self::remoteAddr()) {
            Response::sendError(401, "Access denied for ip: " . self::remoteAddr());
        }

        //@todo da fare controllo acl della pagina
        if ($app::$Page->access == self::ACCESS_SESSION && !User::isLogged() /*$aclVerify = !User::alcVerify($app::$Page->acl))*/) {
            if ($app::$Page->script_path === "/assets") {

            } else {
                $login = new Login();

                Response::send($login->displayException(200));
            }
        }

        if ($app::$Page->csrf) {
            if (empty($_SERVER["HTTP_X_CSRF_TOKEN"])) {
                Cookie::create("csrf", Validator::csrf(self::serverAddr() . $app::$Page->path_info));
            } elseif (Validator::csrf(self::serverAddr() . $app::$Page->path_info) !== $_SERVER["HTTP_X_CSRF_TOKEN"]) {
                Response::sendError(403, "CSRF validation failed.");
            }
        } elseif (isset($_COOKIE["csrf"])) {
            Cookie::destroy("csrf");
        }

        if (is_callable($app::$Page->onLoad)) {
            ($app::$Page->onLoad)();
        }
    }

    /**
     * @param string $data
     * @return string
     */
    public static function encodeEntity(string $data) : string
    {
        return htmlspecialchars(urldecode($data));
    }
}
