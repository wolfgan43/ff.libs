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
use phpformsframework\libs\security\User;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use phpformsframework\libs\util\ServerManager;
use phpformsframework\libs\Exception;

/**
 * Class Logout
 * @package phpformsframework\libs\security\widgets
 */
class Logout extends Widget
{
    use CommonTemplate;
    use ServerManager;

    protected $requiredJs           = ["cm"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        if (User::isLogged()) {
            if (empty($this->request->redirect) && ($referer = $this->referer(PHP_URL_PATH))) {
                $this->redirect($this->script_path . "?redirect=" . urlencode($referer));
            }
            $view = $this->view();
            $config = $view->getConfig();

            $this->displayUser($view, $config);
            $view->assign("logout_url", $this->getWebUrl($config->logout_path));

            $this->setError($view);
            $this->setLogo($view, $config);
        } else {
            $config = $this->getConfig();
            $this->redirect($this->request->redirect ?? $this->getWebUrl($config->login_path));
        }
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        if (User::isLogged()) {
            User::logout();

            $view = $this->view("thankyou");
            $config = $view->getConfig();
            $this->setLogo($view, $config);

            $view->assign("login_path", $config->login_path);
            $this->addJavascriptEmbed('
                setTimeout(function() {
                    window.location.href = "' . ($this->request->redirect ?? DIRECTORY_SEPARATOR) . '";
                }, 1000);
            ');
        } else {
            $config = $this->getConfig();
            $this->redirect($this->request->redirect ?? $this->getWebUrl($config->login_path));
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