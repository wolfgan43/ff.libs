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

/**
 * Class Otp
 * @package phpformsframework\libs\security\widgets
 */
class Otp extends Widget
{
    use CommonTemplate;

    private const API_AUTH2_OTP_CREATE = "auth2/otp/create";

    protected $requiredJs           = ["cm"];

    /**
     *
     */
    protected function get(): void
    {
        $view                       = $this->view("index");
        $config                     = $view->getConfig();
        //@todo da finire
        if (0 && $this->request->identifier) {
            $this->api(
                self::API_AUTH2_OTP_CREATE,
                [
                    "uuid" => $this->request->identifier,
                    "sender" => false,
                ]
            );
        }

        if (empty($config->help_mail)) {
            $config->help_mail = "support@" . $_SERVER['HTTP_HOST'];
        }
        $view->assign($config);

        $this->setError($view);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }

    /**
     *
     */
    protected function post(): void
    {
        // TODO: Implement post() method.
    }

    /**
     *
     */
    protected function put(): void
    {
        // TODO: Implement put() method.
    }

    /**
     *
     */
    protected function delete(): void
    {
        // TODO: Implement delete() method.
    }

    /**
     *
     */
    protected function patch(): void
    {
        // TODO: Implement patch() method.
    }
}