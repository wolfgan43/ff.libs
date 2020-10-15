<?php
namespace phpformsframework\libs\microservice;

use phpformsframework\libs\Env;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Request;
use phpformsframework\libs\storage\FilemanagerScan;

/**
 * Class Gateway
 * @package phpformsframework\libs\microservice
 */
class Gateway
{
    /**
     * @param array $discover
     * @return array
     */
    public function discover()
    {
        $client_type                    = null;
        $register_scopes                = [];
        $required_scopes                = [];
        $grant_types                    = [];
        $modules                        = [];
        $discover                       = [
            "client_id"                 => Kernel::$Environment::APPNAME,
            "client_secret"             => Kernel::$Environment::APPID,
        ];
        $dirs                           = FilemanagerScan::scan([Kernel::$Environment::VENDOR_LIBS_DIR => ["flag" => FilemanagerScan::SCAN_DIR]]);
        foreach ($dirs["rawdata"] as $module_path) {
            $module_name                = basename($module_path);
            if (!Env::get(strtoupper($module_name) . "_CLIENT_TYPE")) {
                continue;
            }
            $module = [
                "client_type"           => str_replace(" ", "", Env::get(strtoupper($module_name) . "_CLIENT_TYPE")),
                "register_scopes"       => str_replace(" ", "", Env::get(strtoupper($module_name) . "_REGISTER_SCOPES")),
                "required_scopes"       => str_replace(" ", "", Env::get(strtoupper($module_name) . "_REQUIRED_SCOPES")),
                "grant_types"           => str_replace(" ", "", Env::get(strtoupper($module_name) . "_DISCOVER_GRANT_TYPES")),
            ];
            $scopes                     = explode(",", $module["register_scopes"]);
            $register_scopes            = array_replace($register_scopes, array_combine($scopes, $scopes));

            $scopes                     = explode(",", $module["required_scopes"]);
            $required_scopes            = array_replace($required_scopes, array_combine($scopes, $scopes));

            $grants                     = explode(",", $module["grant_types"]);
            $grant_types                = array_replace($grant_types, array_combine($grants, $grants));

            $modules[Env::get(strtoupper($module_name) . "_CLIENT_TYPE")][$module_name] = $module;
        }

        $discover["client_type"]        = "hcore";
        $discover["register_scopes"]    = implode(",", $register_scopes);
        $discover["required_scopes"]    = implode(",", array_diff_key($required_scopes, $register_scopes));
        $discover["grant_types"]        = implode(",", $grant_types);
        $discover["domain"]             = "myapp";
        $discover["secret_uri"]        = Request::protocolHost() . "/api/secreturi";



        return $discover + $modules;
    }
}
