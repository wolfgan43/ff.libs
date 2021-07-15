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
namespace phpformsframework\libs\microservice;

use phpformsframework\libs\Configurable;
use phpformsframework\libs\Dir;
use phpformsframework\libs\dto\ConfigRules;
use phpformsframework\libs\Dumpable;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\Normalize;
use phpformsframework\libs\util\ServerManager;

/**
 * Class Gateway
 * @package phpformsframework\libs\microservice
 */
class Gateway implements Configurable, Dumpable
{
    use ServerManager;

    private static $clients                                 = [];
    /**
     * @access private
     * @param ConfigRules $configRules
     * @return ConfigRules
     */
    public static function loadConfigRules(ConfigRules $configRules) : ConfigRules
    {
        return $configRules
            ->add("client", self::METHOD_APPEND);
    }

    /**
     * @access private
     * @param array $config
     */
    public static function loadConfig(array $config)
    {
        self::$clients                                      = $config["gateway"];
    }

    /**
     * @access private
     * @param array $rawdata
     * @return array
     */
    public static function loadSchema(array $rawdata) : array
    {
        foreach ($rawdata as $client) {
            $attr                                           = (object) Dir::getXmlAttr($client);
            if (!isset($attr->name)) {
                continue;
            }
            self::$clients[$attr->name]["type"]             = $attr->type;
            if (isset($client["scopes"])) {
                foreach (Dir::getXmlAttr($client["scopes"]) as $name => $scope) {
                    self::$clients[$attr->name]["scopes"][$name] = Normalize::string2array($scope);
                }
            }
        }

        return array(
            "gateway"     => self::$clients
        );
    }

    /**
     * @return array
     */
    public static function dump() : array
    {
        return array(
            "gateway"    => self::$clients
        );
    }

    /**
     * @return array
     */
    public function discover()
    {
        $client_type                    = null;
        $aud                            = [];
        $scopes_register                = [];
        $scopes_require_client          = [];
        $scopes_require_user            = [];
        $grant_types                    = [];
        $modules                        = [];
        $discover                       = [
            "client_id"                 => Kernel::$Environment::APPNAME,
            "client_secret"             => Kernel::$Environment::APPID,
        ];

        foreach (self::$clients as $name => $client) {
            if (empty($client["type"])) {
                continue;
            }

            $aud[]                      = $name;

            $scopes                     = $client["scopes"]["register"];
            $scopes_register            = array_replace($scopes_register, array_combine($scopes, $scopes));

            $scopes                     = $client["scopes"]["require_client"];
            $scopes_require_client      = array_replace($scopes_require_client, array_combine($scopes, $scopes));

            $scopes                     = $client["scopes"]["require_user"];
            $scopes_require_user        = array_replace($scopes_require_user, array_combine($scopes, $scopes));

            $grants                     = explode(",", "client,password");
            $grant_types                = array_replace($grant_types, array_combine($grants, $grants));


            $module = [
                "client_type"                   => $client["type"],
                "scopes_register"               => implode(",", $client["scopes"]["register"]),
                "scopes_require_client"         => implode(",", $client["scopes"]["require_client"]),
                "scopes_require_user"           => implode(",", $client["scopes"]["require_user"]),
                "grant_types"                   => "client,password",
            ];

            $modules[$client["type"]][$name]    = $module;
        }

        $discover["aud"]                        = ucwords(implode(", ", $aud));
        $discover["client_type"]                = Kernel::$Environment::APPNAME;
        $discover["scopes_register"]            = implode(",", $scopes_register);
        $discover["scopes_require_client"]      = implode(",", $scopes_require_client);
        $discover["scopes_require_user"]        = implode(",", $scopes_require_user);
        $discover["grant_types"]                = implode(",", $grant_types);
        $discover["secret_uri"]                 = $this->protocolHost() . "/api/secreturi";
        $discover["site_url"]                   = null;
        $discover["redirect_uri"]               = null;
        $discover["privacy_url"]                = null;

        return $discover + $modules;
    }
}
