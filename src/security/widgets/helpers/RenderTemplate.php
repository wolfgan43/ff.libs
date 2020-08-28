<?php
namespace phpformsframework\libs\security\widgets\helpers;

use hcore\App;
use hcore\security\Hash;
use phpformsframework\libs\gui\View;
use phpformsframework\libs\security\User;

use Exception;
/**
 * Trait RenderTemplate
 * @package hcore\security\widgets\helpers
 */
trait RenderTemplate
{
    public $config = [
        "api"                   => [
            "login"             => "/api/auth/user/login",
            "logout"            => "/api/auth/user/logout",
            "recover"           => "/api/auth/user/recover",
            "change"            => "/api/auth/user/change",
            "registration"      => "/api/auth/user/registration",
            "requestActivation" => "/api/auth/user/request/activation",
            "activate"          => "/api/auth/user/activate"
        ],
        "error"                 => null,
        "redirect"              => "/",
        "tpl_path"              => null,
        "email_path"            => null,
        "logo"                  => "100x50",
        "logo_path"             => null,
        "login_path"            => "/login",
        "logout_path"           => "/logout",
        "registration_path"     => "/registration",
        "activation_path"       => "/activation",
        "recover_account_path"  => "/recover/account",
        "recover_password_path" => "/recover/password",
    ];

    /**
     * @param View $view
     * @param array $config
     */
    private function setLogo(&$view, array &$config)
    {
        if ($config["logo"]) {
            $logo = (
                $config["logo_path"]
                ? $config["logo_path"]
                : "nobrand"
            );

            $view->assign("logo_url", $this->script_path);
            $view->assign("logo_path", $this->mediaUrl($logo, $config["logo"]));
            $view->parse("SezLogo", false);
        }
    }

    /**
     * @param View $view
     * @param array $config
     */
    private function setError(&$view, array &$config)
    {
        if ($config["error"]) {
            $view->assign(
                "error",
                '<div class="alert alert-warning">'
                . $config["error"]
                . '</div>'
            );
        }
    }
    /**
     * @param View $view
     * @param array $config
     */
    private function setHeader(&$view, array &$config)
    {
        if ($config["title"]) {
            $view->assign("title", $this->translate($config["title"]));
            $view->parse("SezTitle", false);
        }
        if ($config["description"]) {
            $view->assign("description", $this->translate($config["description"]));

            $view->parse("SezDescription", false);
        }
    }
    /**
     * @param View $view
     * @param array $config
     */
    private function setDomain(&$view, array &$config)
    {
        if ($config["domain"]) {
            $view->parse("SezDomain", false);
        } else {
            $view->assign("domain_name", App::request()->hostname());
            $view->parse("SezDomainHidden", false);
        }
    }
    /**
     * @param View $view
     * @param array $config
     */
    private function setDefault(&$view, array &$config)
    {
        $view->assign("csrf_token", Hash::csrf());
        if ($config["redirect"]) {
            $view->assign("redirect_url", $this->getUrl($config["redirect"]));
        }
    }

    /**
     * @param $view
     * @throws Exception
     */
    private function displayUser(&$view)
    {
        $user = User::get();
        if ($user) {
            $view->assign("user_name", $user->username);
            $view->assign("user_email", $user->email);
            $view->assign("user_avatar", $user->getAvatar(/*$config["avatar"]*/));
        }
    }
}
