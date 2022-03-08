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
use phpformsframework\libs\Exception;
use phpformsframework\libs\util\Cookie;

/**
 * Class Activation
 * @package phpformsframework\libs\security\widgets
 */
class Activation extends Widget
{
    use CommonTemplate;

    protected const ERROR_VIEW              = "displayError";
    protected const USER_CLASS              = "phpformsframework\libs\security\User";
    protected const ACTIVATION_EXPIRATION   = 60 * 5;

    protected $requiredJs                   = ["cm"];

    /**
     * @var User
     */
    private $user                           = null;

    public static function setOtpToken(string $token) : void
    {
        Cookie::create("activation", $token, time() + self::ACTIVATION_EXPIRATION);
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
        Cookie::destroy("activation");

        $this->render("index");
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        $authorization              = Cookie::get("activation");
        if (!empty($authorization)) {
            if (!empty($this->request->code)) {
                $this->user::activationComplete($authorization, $this->request->code);
                $this->success();
            } else {
                $this->otp();
            }
        } elseif (!empty($this->request->identifier)) {
            $response               = $this->user::activationRequest($this->request->identifier);
            if ($response->get("token")) {
                $this->setOtpToken($response->get("token"));
                $this->otp();
            } else {
                $this->wait();
            }
        } else {
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
        $this->replaceWith(Otp::class, null, "get");
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
