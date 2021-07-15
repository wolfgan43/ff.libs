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
 * Class Activation
 * @package phpformsframework\libs\security\widgets
 */
class Activation extends Widget
{
    use CommonTemplate;

    protected $requiredJs           = ["cm"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $this->render("index");
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        $config                     = $this->getConfig();

        if ($this->request->code) {
            $response               = $this->api($config->api->activate, null, ["Authorization" => $this->authorization . ":" . $this->request->code]);
            $response->set("confirm", $this->snippet("success"));
        } else {
            $response               = $config->response ?? $this->api($config->api->requestActivation . $this->path_info, ["identifier" => $this->request->identifier]);
            $response->set("confirm", (
                $response->get("token")
                ? $this->otp()
                : $this->snippet("wait")
            ));
        }

        $this->send($response);
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
     * @return array
     * @throws Exception
     */
    protected function otp() : array
    {
        return Otp::toArray([], "get");
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
    private function render(string $method) : void
    {
        $view                       = $this->view($method);
        $config                     = $view->getConfig();

        $view->assign("help_mail", $config->help_mail ?? "support@" . $_SERVER['HTTP_HOST']);
        $view->assign("activation_url", $this->getWebUrl($this->script_path . $this->path_info));

        $this->setError($view);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }
}
