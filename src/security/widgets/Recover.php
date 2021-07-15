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

    protected $requiredJs           = ["cm", "recover"];
    protected $requiredCss          = ["recover"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $this->render($this->request->action ?? null);
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        $action                     = $this->request->action ?? null;
        $config                     = $this->getConfig("recover");

        if (!empty($this->request->code)) {
            if (!isset($config->api->{"change_" . $action})) {
                throw new Exception("Recover not supported", 501);
            }

            $response = $this->api($config->api->{"change_" . $action}, [$action => $this->request->value], ["Authorization" => $this->authorization . ":" . $this->request->code]);
        } else {
            if (!isset($config->api->{"recover_" . $action})) {
                throw new Exception("Recover request not supported", 501);
            }
            $response = $this->api($config->api->{"recover_" . $action}, ["identifier" => $this->request->identifier]);

            $response->set("confirm", (
                $response->get("token")
                ? $this->snippet("confirm")
                : $this->snippet("wait")
            ));
        }

        $this->send($response);
    }

    /**
     * @throws Exception
     */
    protected function confirm() : void
    {
        $action                     = $this->request->action;

        $this->render($action . "_confirm");
    }

    /**
     * @throws Exception
     */
    protected function wait() : void
    {
        $action                     = $this->request->action;

        $this->render($action . "_wait");
    }

    /**
     * @param string|null $method
     * @return array
     * @throws Exception
     */
    private function render(string $method = null) : void
    {
        if (empty($method)) {
            throw new Exception("Recover action is empty", 501);
        }

        $view                       = $this->view($method);
        $config                     = $view->getConfig();

        $view->assign("help_mail", $config->help_mail ?? "support@" . $_SERVER['HTTP_HOST']);
        $view->assign("recover_url", $this->getWebUrl($this->script_path . $this->path_info));

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
}
