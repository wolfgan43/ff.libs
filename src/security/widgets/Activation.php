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
use ff\libs\security\User;
use ff\libs\security\widgets\helpers\CommonTemplate;
use ff\libs\Exception;
use ff\libs\util\Cookie;

/**
 * Class Activation
 * @package ff\libs\security\widgets
 */
class Activation extends Widget
{
    use CommonTemplate;

    protected const ERROR_VIEW              = "displayError";
    protected const USER_CLASS              = "ff\libs\security\User";
    protected const TOKEN_EXPIRATION        = 60 * 5;

    private const COOKIE_NAME               = "activation";

    protected $requiredJs                   = ["cm"];

    /**
     * @var User
     */
    private $user                           = null;

    public static function setOtpToken(string $token) : void
    {
        Cookie::create(self::COOKIE_NAME, $token, time() + self::TOKEN_EXPIRATION);
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
        $this->render("index");
    }

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        if(!empty($this->request->identifier) && $this->isXhr) {
            $this->post();
            $this->send(["alert" => "Otp code Sent!"]);
        } else {
            $this->render("index");
        }
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        if (!empty($this->request->identifier) && empty($this->request->code)) {
            $response = $this->user::activationRequest($this->request->identifier);
            if ($response->get("token")) {
                $this->setOtpToken($response->get("token"));
                $this->otp();
            } else {
                $this->wait();
            }
        } elseif (!empty($authorization = Cookie::get(self::COOKIE_NAME)) && !empty($this->request->code)) {
            $this->user::activationComplete($authorization, $this->request->code);
            $this->success();
        } else {
            Cookie::destroy(self::COOKIE_NAME);
            $this->error(500, "Service not available");
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

    /**
     * @throws Exception
     */
    protected function otp() : void
    {
        $this->replaceWith(Otp::class, ["resend_code" => "/activation?identifier=" . $this->request->identifier], "get");
    }

    /**
     * @throws Exception
     */
    protected function wait() : void
    {
        $this->render("wait");
    }

    /**
     * @throws Exception
     */
    protected function success() : void
    {
        $this->render("success");
    }

    /**
     * @param string $method
     * @throws Exception
     */
    protected function render(string $method) : void
    {
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
}
