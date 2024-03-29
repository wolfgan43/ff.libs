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
namespace ff\libs\microservice;

use ff\libs\Configurable;
use ff\libs\Dir;
use ff\libs\dto\ConfigRules;
use ff\libs\Dumpable;
use ff\libs\Exception;
use ff\libs\Kernel;
use ff\libs\util\Normalize;
use ff\libs\util\ServerManager;
use ff\libs\security\UUID;

/**
 * Class Gateway
 * @package ff\libs\microservice
 */
class Gateway implements Configurable, Dumpable
{
    use ServerManager;

    protected const API_CLIENT_SIGNUP                       = null;

    private const CLIENT_TYPE_PUBLIC                        = "public";
    private const CLIENT_TYPE_CONFIDENTIAL                  = "confidential";

    private static $clients                                 = [];
    private $request                                        = null;

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

    public function __construct(array $request = null)
    {
        $this->request = (object) $request;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function discover()
    {
        $aud                            = [];
        $scopes_register                = [];
        $scopes_client                  = [];
        $scopes_user                    = [];
        $grant_types                    = [];
        $modules                        = [];
        $discover                       = [
            "client-id"                 => Kernel::$Environment::APPNAME,
            "client-secret"             => Kernel::$Environment::APPID,
        ];

        foreach (self::$clients as $name => $client) {
            if (empty($client["type"])) {
                continue;
            }

            $aud[]                      = $name;

            $scopes                     = $client["scopes"]["register"];
            $scopes_register            = array_replace($scopes_register, array_combine($scopes, $scopes));

            $scopes                     = $client["scopes"]["require_client"];
            $scopes_client              = array_replace($scopes_client, array_combine($scopes, $scopes));

            $scopes                     = $client["scopes"]["require_user"];
            $scopes_user                = array_replace($scopes_user, array_combine($scopes, $scopes));

            $grants                     = (empty(array_filter($scopes_user)) ? ["client"] : ["client","password"]);
            $grant_types                = array_replace($grant_types, array_combine($grants, $grants));

            $module = [
                "client-type"                   => $client["type"],
                "scopes-register"               => implode(",", $client["scopes"]["register"]),
                "scopes-client"                 => implode(",", $client["scopes"]["require_client"]),
                "scopes-user"                   => implode(",", $client["scopes"]["require_user"]),
                "grant-types"                   => implode(",", $grants),
            ];

            $modules[$client["type"]][$name]    = $module;
        }

        $discover["aud"]                        = ucwords(implode(", ", $aud));
        $discover["client-type"]                = (empty($scopes_register) ? self::CLIENT_TYPE_PUBLIC : self::CLIENT_TYPE_CONFIDENTIAL);
        $discover["scopes-register"]            = implode(",", $scopes_register);
        $discover["scopes-client"]              = implode(",", $scopes_client);
        $discover["scopes-user"]                = implode(",", $scopes_user);
        $discover["grant-types"]                = implode(",", $grant_types);
        $discover["secret-uri"]                 = $this->protocolHost() . "/api/" . UUID::v5(Kernel::$Environment::APPID, __CLASS__);
        $discover["site-url"]                   = null;
        $discover["redirect-uri"]               = null;
        $discover["privacy-url"]                = null;

        if (static::API_CLIENT_SIGNUP && !empty($this->request->registrar)) {
            $discover["domain"]                 = $this->request->domain                ?? null;
            $discover["scopes-client"]  = $this->request->scopes_client ?? $discover["scopes-client"];
            $discover["scopes-user"]    = $this->request->scopes_user   ?? $discover["scopes-user"];
            $discover["grant-types"]            = (
                empty($discover["scopes-user"])
                ? "client"
                : "client,password"
            );
            $discover = array_replace(
                $discover,
                Api::request(
                    "POST",
                    $this->request->registrar . static::API_CLIENT_SIGNUP,
                    $discover,
                    ["Authorization" => Kernel::$Page->getAuthorization()]
                )->toArray()
            );
        }

        $discover["client-secret"]              = "*****************";
        $discover["secret-uri"]                 = "*****************";

        return $discover + $modules;
    }
}
