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
namespace ff\libs\security\widgets;

use ff\libs\gui\Widget;
use ff\libs\security\widgets\helpers\CommonTemplate;
use ff\libs\security\User;
use ff\libs\Exception;

/**
 * Class Login
 * @package ff\libs\security\widgets
 */
class Login extends Widget
{
    use CommonTemplate;

    protected const USER_CLASS      = "ff\libs\security\User";

    protected $requiredJs           = ["cm"];

    /**
     * @var User
     */
    private $user                   = null;

    public function __construct(array $config = null)
    {
        parent::__construct($config);

        $this->user                     = static::USER_CLASS;
    }

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        if ($this->user::isLogged()) {
            $config = $this->getConfig();
            $this->redirect($this->request->redirect ?? $this->getWebUrl($config->logout_path));
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
        if ($this->user::isLogged()) {
            $config = $this->getConfig();
            $this->redirect($this->request->redirect ?? $this->getWebUrl($config->logout_path));
        } elseif (isset($this->request->identifier, $this->request->password)) {
            $response = $this->user::login($this->request->identifier, $this->request->password, $this->request->permanent ?? null);
            if (!$response->isError()) {
                $this->replaceWith(Welcome::class);
            } elseif ($response->isError(409) && !empty($config->activation_path)) {
                $response->set("error_link", $this->getWebUrl($config->activation_path));
            }
        } else {
            $this->error(400, "missing identifier or password");
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
}
