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
 * Class Activation
 * @package phpformsframework\libs\security\widgets
 */
class Activation extends Widget
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
                "title"                 => "Activation",
                "description"           => 'Check your mailbox and insert your code',
                "redirect"              => $this->env("AUTH_USER_DASHBOARD"),
                "help_mail"             => "help@mail.it",
                "activation_path"        => $this->script_path
            ));
    }

    /**
     * @param array $config
     * @param stdClass $request
     * @param bool $isAjax
     */
    protected function controller(array &$config, stdClass $request, bool $isAjax) : void
    {
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
        if (!empty($request->email) && $request->code) {
            //@todo da sostituire con i magic link. non funziona il link nella mail
            // Auth::writeByUrl($request->email, "activation", $request->code);
            $response = null;
        } elseif ($request->code) {
            $response = $this->api($config["api"]["activate"], ["code" => $request->code]);
        } else {
            $response = $this->api($config["api"]["requestActivation"] . $this->path_info, ["identity" => $request->identity]);
        }

        return $response;
    }

    /**
     * @param View $view
     * @param array $config
     */
    protected function renderTemplate(&$view, array $config)
    {
        $reqAuth = $this->request()->getModel("auth");
        $config["error"] = $reqAuth->error;
        if ($reqAuth->email) {
            $response = Auth::requestWrite("activation", $reqAuth->email);

            $view->assign("email_conferma", $reqAuth->email);
            $view->assign("email_class", "");

            if (isset($response->t)) {
                $view->assign("bearer_code", $response->t);
                $view->parse("SezBearerContainer", false);
            }
        } else {
            $view->assign("email_class", "hide-code-string");
        }
        $view->assign("activation_url", $this->getUrl($config["activation_path"]));
        $view->assign("help_mail", $config["help_mail"]);

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }
}
