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

use phpformsframework\libs\security\widgets\helpers\RenderTemplate;
use phpformsframework\libs\security\User;

use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\gui\View;
use phpformsframework\libs\gui\Widget;

use stdClass;
use Exception;

/**
 * Class Registration
 * @package phpformsframework\libs\security\widgets
 */
class Registration extends Widget
{
    use RenderTemplate;

    protected $requiredJs           = ["hcore.auth"];

    /**
     * @param stdClass $request
     * @return array
     */
    protected function getConfigDefault(stdClass $request) : array
    {
        return array_replace($this->config, array(
            "title"                 => "Registration",
            "description"           => "Registration description",
            "domain"                => false,
            "domain_name"           => $request->domain ?? $this->request()->hostname(),
            "redirect"              => $this->env("AUTH_USER_DASHBOARD"),
            "registration_path"     => $this->script_path,
            "email"                 => true,
            "phone"                 => true,
            "activation"            => true
        ));
    }

    /**
     * @param array $config
     * @param stdClass $request
     * @param bool $isAjax
     */
    protected function controller(array &$config, stdClass $request, bool $isAjax) : void
    {
        //@todo gestione del model e della pagina "registration
        $this->view("index", $config);
    }

    /**
     * @param array $config
     * @param stdClass $request
     * @return DataResponse|null
     * @throws Exception
     */
    protected function callToAction(array &$config, stdClass $request) : ?DataResponse
    {
        $response = $this->api($config["api"]["registration"], (array) $request);
        if (User::isLogged()) {
            $response->set("welcome", User::widget("welcome", array("redirect" => $config["redirect"])));
        } else {
            $this->response()->redirect("/activation");
        }

        return $response;
    }
    /**
     * @param View $view
     * @param array $config
     */
    protected function renderTemplate(&$view, array $config)
    {
        $view->assign("registration_url", $this->getUrl($config["registration_path"]));

        if ($config["email"]) {
            $view->parse("SezEmail", true);
        }

        if ($config["phone"]) {
            $view->parse("SezPhone", false);
        }

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
        $this->setDomain($view, $config);
    }
}
