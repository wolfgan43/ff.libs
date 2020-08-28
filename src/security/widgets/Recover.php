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
 * @package VGallery
 * @subpackage core
 * @author Alessandro Stucchi <wolfgan@gmail.com>
 * @copyright Copyright (c) 2004, Alessandro Stucchi
 * @license http://opensource.org/licenses/gpl-3.0.html
 * @link https://github.com/wolfgan43/vgallery
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
 * Class Recover
 * @package phpformsframework\libs\security\widgets
 */
class Recover extends Widget
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
            "title"                 => "Reset Password",
            "description"           => "Enter your email address and we'll send you a code to reset your password",
            "recover_path"           => $this->script_path . $this->path_info
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
        if (!empty($request->code)) {
            $response = $this->api($config["api"]["change"] . $this->path_info, ["code" => $request->code, "value" => $request->value], ["Bearer" => User::request()->getBearerToken()]);
        } else {
            $response = $this->api($config["api"]["recover"] . $this->path_info, ["identity" => $request->identity]);
            if (!$response->isError()) {
                $response->set("recover_confirm", User::widget("recover_confirm"));
            }
        }

        return $response;
    }
    /**
     * @param View $view
     * @param array $config
     */
    protected function renderTemplate(&$view, array $config)
    {
        $view->assign("recover_url", $this->getUrl($config["recover_path"]));
        $view->assign("email_class", "hide-code-string");
        $view->assign("recover_conferma_title", "Forgot your password?");

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }
}
