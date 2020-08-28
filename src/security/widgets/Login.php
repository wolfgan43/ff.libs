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

namespace phpformsframework\libs\security\widgets;

use phpformsframework\libs\security\widgets\helpers\RenderTemplate;
use phpformsframework\libs\security\User;

use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\gui\View;
use phpformsframework\libs\gui\Widget;

use stdClass;
use Exception;

/**
 * Class Login
 * @package phpformsframework\libs\security\widgets
 */
class Login extends Widget
{
    use RenderTemplate;

    protected $requiredJs           = ["hcore.auth"];

    /**
     * @param stdClass $request
     * @return array
     * @throws Exception
     */
    protected function getConfigDefault(stdClass $request) : array
    {
        return array_replace($this->config, array(
            "title"                 => "Sign In",
            "description"           => "Enter your email address and password to access admin panel.",
            "domain"                => false,
            "domain_name"           => $request->domain ?? $this->request()->hostname(),
            "stay_connect"          => true,
            "referer"               => $this->request()->url(),
            "login_path"            => $this->script_path,
            "social"                => array(
                "facebook"          => array(
                    "enable"        => $this->env("FACEBOOK_APP_ID") && $this->env("FACEBOOK_APP_SECRET"),
                    "path"          => "/api/auth/user/social/facebook",
                    "icon"          => "fas fa-facebook-f",
                    "name"          => "Facebook",
                    "app"           => array(
                        "id"        => $this->env("FACEBOOK_APP_ID"),
                        "secret"    => $this->env("FACEBOOK_APP_SECRET"),
                        "scope"     => $this->env("FACEBOOK_APP_SCOPE")
                    )
                ),
                "gplus"             => array(
                    "enable"        => $this->env("GPLUS_APP_ID") && $this->env("GPLUS_APP_SECRET"),
                    "path"          => "/api/auth/user/social/gplus",
                    "icon"          => "fas fa-google-plus-g",
                    "name"          => "GooglePlus",
                    "app"           => array(
                        "id"        => $this->env("GPLUS_APP_ID"),
                        "secret"    => $this->env("GPLUS_APP_SECRET"),
                        "scope"     => $this->env("GPLUS_APP_SCOPE")
                    )
                ),
                "twitter"           => array(
                    "enable"        => $this->env("TWITTER_APP_ID") && $this->env("TWITTER_APP_SECRET"),
                    "path"          => "/api/auth/user/social/twitter",
                    "icon"          => "fas fa-twitter",
                    "name"          => "Twitter",
                    "app"           => array(
                        "id"        => $this->env("TWITTER_APP_ID"),
                        "secret"    => $this->env("TWITTER_APP_SECRET"),
                        "scope"     => $this->env("TWITTER_APP_SCOPE")
                    )
                ),
                "linkedin"          => array(
                    "enable"        => $this->env("LINKEDIN_APP_ID") && $this->env("LINKEDIN_APP_SECRET"),
                    "path"           => "/api/auth/user/social/linkedin",
                    "icon"          => "fas fa-linkedin-in",
                    "name"          => "Linkedin",
                    "app"           => array(
                        "id"        => $this->env("LINKEDIN_APP_ID"),
                        "secret"    => $this->env("LINKEDIN_APP_SECRET"),
                        "scope"     => $this->env("LINKEDIN_APP_SCOPE"),
                    )
                ),
                "dribbble"          => array(
                    "enable"        => $this->env("DRIBBBLE_APP_ID") && $this->env("DRIBBBLE_APP_SECRET"),
                    "path"          => "/api/auth/user/social/dribbble",
                    "icon"          => "fas fa-dribbble",
                    "name"          => "Dribbble",
                    "app"           => array(
                        "id"        => $this->env("DRIBBBLE_APP_ID"),
                        "secret"    => $this->env("DRIBBBLE_APP_SECRET"),
                        "scope"     => $this->env("DRIBBBLE_APP_SCOPE"),
                    )
                )
            )
        ));
    }

    /**
     * @param array $config
     * @param stdClass $request
     * @param bool $isAjax
     * @throws Exception
     */
    protected function controller(array &$config, stdClass $request, bool $isAjax) : void
    {
        if (User::isLogged()) {
            $this->view(User::widget("logout", array(
                "error"         => $config["error"],
                "logout_path"   => $config["logout_path"],
                "redirect"      => $config["redirect"]
            )));
        } else {
            $this->view("index", $config);
        }
    }

    /**
     * @param array $config
     * @param stdClass $request
     * @return DataResponse|null
     * @throws Exception
     */
    protected function callToAction(array &$config, stdClass $request) : ?DataResponse
    {
        $responseData                           = User::login($request->username, $request->password, $request->permanent);
        if (!$responseData->isError()) {
            if ($this->aclVerify()) {
                $responseData->set("welcome", User::widget("welcome")->toArray());
            } else {
                $this->api($config["api"]["logout"]);
                $responseData->clear();
                $responseData->error(401, "Permission Denied.");
            }
        } elseif ($responseData->isError(409) && $config["activation_path"]) {
            $responseData->set("error_link", $this->getUrl($config["activation_path"]));
        }

        return $responseData;
    }

    /**
     * @param View $view
     * @param array $config
     */
    protected function renderTemplate(&$view, array $config)
    {
        $view->assign("login_url", $this->getUrl($config["login_path"]));

        if ($config["stay_connect"]) {
            $view->parse("SezStayConnect", false);
        }

        if ($config["registration_path"]) {
            $view->assign("registration_url", $this->getUrl($config["registration_path"]));
            $view->parse("SezRegistration", false);
        }

        if ($config["recover_password_path"]) {
            $view->assign("recover_password_url", $this->getUrl($config["recover_password_path"]));
            $view->parse("SezRecoverPassword", false);
        }
        if ($config["recover_account_path"]) {
            $view->assign("recover_account_url", $this->getUrl($config["recover_account_path"]));
            $view->parse("SezRecoverAccount", false);
        }

        $show_social = false;
        if (!empty($config["social"])) {
            foreach ($config["social"] as $social_name => $social_setting) {
                if ($social_setting["enable"]) {
                    $show_social = true;
                    $view->assign("social_class", $social_name);
                    $view->assign("social_dialog_name", $social_setting["title"]);
                    $view->assign("social_url", $this->getUrl($social_setting["path"]));
                    $view->assign("social_icon", $social_setting["icon"]);
                    $view->assign("social_name", $social_setting["name"]);

                    if ($view->isset("SezSocialLogin" . ucfirst($social_name))) {
                        $view->parse("SezSocialLogin" . ucfirst($social_name), false);
                    } else {
                        $view->parse("SezSocialLoginOther", true);
                    }
                }
            }
        }
        if ($show_social) {
            $view->parse("SezSocialLogin", false);
        }

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
        $this->setDomain($view, $config);
    }
}
