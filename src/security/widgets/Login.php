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
namespace phpformsframework\libs\security\widgets;

use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\SecureManager;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use phpformsframework\libs\security\User;
use phpformsframework\libs\Exception;

/**
 * Class Login
 * @package phpformsframework\libs\security\widgets
 */
class Login extends Widget
{
    use CommonTemplate;
    use SecureManager;

    protected $requiredJs           = ["cm"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        if (User::isLogged()) {
            $config                 = $this->getConfig();
            $this->replaceWith(Logout::class, [
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

            $this->setError($view);
            $this->setLogo($view, $config);
            $this->setHeader($view, $config);
        }
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        if (isset($this->request->identifier, $this->request->password)) {
            User::login($this->request->identifier, $this->request->password, $this->request->permanent ?? null);

            $this->welcome();
        } else {
            $this->error(400, "missing identifier or password");
            $this->get();
        }
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

    private function welcome(): void
    {
        $view       = $this->view("welcome");
        $config     = $view->getConfig();
        $this->displayUser($view, $config);
        $this->setLogo($view, $config);
    }
}
