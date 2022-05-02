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
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use phpformsframework\libs\Exception;

/**
 * Class Recover
 * @package phpformsframework\libs\security\widgets
 */
class Recover extends Widget
{
    use CommonTemplate;

    protected const ERROR_VIEW              = "displayError";
    protected const USER_CLASS              = "phpformsframework\libs\security\User";
    protected const TOKEN_EXPIRATION        = 60 * 5;

    protected $requiredJs                   = ["cm"];
    protected $requiredCss                  = ["recover"];

    public static function setOtpToken(string $token) : void
    {
        Cookie::create("recover", $token, time() + self::TOKEN_EXPIRATION);
    }

    public function __construct(array $config = null)
    {
        parent::__construct($config);

        $this->user                         = static::USER_CLASS;
    }

    /**
     * @throws Exception
     */
    protected function displayError(): void
    {
        $this->verifyAction();

        $this->render($this->request->action);
    }

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $this->verifyAction();

        if (!empty($this->request->identifier) && $this->isXhr) {
            $this->post();
            $this->send(["alert" => "Otp code Sent!"]);
        } else {
            $this->render($this->request->action);
        }
    }

    /**
     * @throws Exception
     */
    protected function post() : void
    {
        $this->verifyAction();

        $action                     = $this->request->action;

        if (!empty($this->request->identifier) && empty($this->request->code)) {
            $response = $this->user::{$action . "RecoverRequest"}($this->request->identifier);
            if ($response->get("token")) {
                $this->setOtpToken($response->get("token"));
                $this->confirm($action);
            } else {
                $this->wait($action);
            }
        } elseif (!empty($authorization = Cookie::get("recover")) && !empty($this->request->code)) {
            if (isset($this->request->password, $this->request->confirm_password) && $this->request->password === $this->request->confirm_password) {
                $this->user::{$action . "RecoverChange"}($this->request->$action, $authorization, $this->request->code);
                $this->success($action);
            } else {
                $this->error(400, "Password Don't Match");
            }
        } else {
            Cookie::destroy("recover");
            $this->error(500, "Service not available");
        }
    }

    /**
     * @throws Exception
     */
    protected function confirm(string $action) : void
    {
        $this->render($action . "_confirm");

        $this->view->assign("resend_code", "/recover/" . $action . "?identifier=" . $this->request->identifier);
    }

    /**
     * @throws Exception
     */
    protected function wait(string $action) : void
    {
        $this->render($action . "_wait");
    }

    /**
     * @throws Exception
     */
    protected function success(string $action) : void
    {
        $this->render($action . "_success");
    }

    /**
     * @param string|null $method
     * @throws Exception
     */
    private function render(string $method = null) : void
    {
        $this->verifyAction();

        $view                       = $this->view($method);
        $config                     = $view->getConfig();

        if (empty($config->help_mail)) {
            $config->help_mail = "support@" . $_SERVER['HTTP_HOST'];
        }
        $view->assign($config);

        $this->setError($view);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
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

    /**
     * @throws Exception
     */
    private function verifyAction() : void
    {
        if (empty($this->request->action)) {
            throw new Exception("Service not available", 501);
        }
    }
}
