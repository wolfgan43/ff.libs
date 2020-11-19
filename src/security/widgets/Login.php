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

use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\SecureManager;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use phpformsframework\libs\security\User;
use Exception;

/**
 * Class Login
 * @package phpformsframework\libs\security\widgets
 */
class Login extends Widget
{
    use CommonTemplate;
    use SecureManager;

    protected $requiredJs           = ["hcore.auth"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        if (User::isLogged()) {
            $config                 = $this->getConfig();
            $this->load(Logout::class, [
                "error"             => $config->error,
                "redirect"          => $config->redirect
            ]);
        } else {
            $view                   = $this->view("index");
            $config                 = $view->getConfig();

            $view->assign("login_url", $this->getWebUrl($this->script_path));

            if (!empty($config->stay_connect)) {
                $view->parse("SezStayConnect", false);
            }

            if (!empty($config->registration_path)) {
                $view->assign("registration_url", $this->getWebUrl($config->registration_path));
                $view->parse("SezRegistration", false);
            }

            if (!empty($config->recover_password_path)) {
                $view->assign("recover_password_url", $this->getWebUrl($config->recover_password_path));
                $view->parse("SezRecoverPassword", false);
            }
            if (!empty($config->recover_account_path)) {
                $view->assign("recover_account_url", $this->getWebUrl($config->recover_account_path));
                $view->parse("SezRecoverAccount", false);
            }

            $show_social = false;
            if (!empty($config->social)) {
                foreach ($config->social as $social_name => $social_setting) {
                    if ($social_setting->enable) {
                        $show_social = true;
                        $view->assign("social_class", $social_name);
                        $view->assign("social_dialog_name", $social_setting->title);
                        $view->assign("social_url", $this->getWebUrl($social_setting->path));
                        $view->assign("social_icon", $social_setting->icon);
                        $view->assign("social_name", $social_setting->name);

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

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        $config                                 = $this->getConfig();
        $responseData                           = User::login($this->request->username, $this->request->password, $this->request->permanent ?? null);
        if (!$responseData->isError()) {
            if ($this->aclVerify()) {
                $responseData->set("welcome", Welcome::toJson());
            } else {
                $this->api($config->api->logout);
                $responseData->clear();
                $responseData->error(401, "Permission Denied.");
            }
        } elseif ($responseData->isError(409) && !empty($config->activation_path)) {
            $responseData->set("error_link", $this->getWebUrl($config->activation_path));
        }
        $this->send($responseData);
    }

    protected function put(): void
    {
        // TODO: Implement put() method.
    }

    protected function delete(): void
    {
        // TODO: Implement delete() method.
    }

    protected function patch(): void
    {
        // TODO: Implement patch() method.
    }
}
