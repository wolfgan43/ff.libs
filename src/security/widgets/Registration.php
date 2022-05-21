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
use ff\libs\storage\Model;
use ff\libs\Exception;

/**
 * Class Registration
 * @package ff\libs\security\widgets
 */
class Registration extends Widget
{
    use CommonTemplate;

    protected const USER_CLASS      = "ff\libs\security\User";

    protected $requiredJs           = ["cm"];

    /**
     * @var User
     */
    private $user                   = null;

    public function __construct(array $config = null)
    {
        parent::__construct($config);

        $this->user                 = static::USER_CLASS;
    }

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $view                       = $this->view("index");
        $config                     = $view->getConfig();

        if (!empty($this->request->model) && ($model = Model::columns($this->request->model))) {
            foreach ($model as $field_name) {
                $view->assign("field_name", $field_name);
                $view->assign("field_label", $this->translate($field_name));
                $view->assign("field_type", "Text");
                $view->assign("field_class", "form-control");
                $view->assign("field_properties", null);
                $view->parse("SezModel", true);
            }
        } else {
            if (!empty($config->email)) {
                $view->parse("SezEmail", true);
            }

            if (!empty($config->phone)) {
                $view->parse("SezPhone", false);
            }
        }

        $this->setError($view);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        if (!empty($this->request->code)) {
            $this->replaceWith($this->widget("Activation"), null, "post");
        } elseif ($this->request->password && $this->request->password != $this->request->confirm_password) {
            $this->error(400, "Password Don't Match");
        } else {
            $response                   = $this->user::signUp((array)$this->request, $this->request->model);
            if (User::isLogged()) {
                $this->replaceWith($this->widget("Welcome"));
            } elseif ($response->get("activation")) {
                Activation::setOtpToken($response->get("activation")->token);
                $this->replaceWith($this->widget("Activation"), null, "post");
            } else {
                $config = $this->getConfig();
                $this->redirect($this->request->redirect ?? $this->getWebUrl($config->login_path));
            }
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

    protected function widget(string $class_name) : string
    {
        return __NAMESPACE__ . '\\' . $class_name;
    }
}
