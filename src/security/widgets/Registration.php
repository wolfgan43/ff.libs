<?php
namespace phpformsframework\libs\security\widgets;

use hcore\util\MicroServices;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\User;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use Exception;
use phpformsframework\libs\storage\Model;

/**
 * Class Registration
 * @package phpformsframework\libs\security\widgets
 */
class Registration extends Widget
{
    use CommonTemplate;
    use MicroServices;

    protected $requiredJs           = ["hcore.security"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $view                       = $this->view("index");
        $config                     = $view->getConfig();

        if ($this->request->model && $model = Model::get($this->request->model)) {
            $config->registration_path .= DIRECTORY_SEPARATOR . $this->request->model;
            $propertires = [
                "required",
                "placeholder",
                "min",
                "max",
            ];
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

        $view->assign("registration_url", $this->getWebUrl($config->registration_path));

        $this->setDefault($view, $config);
        $this->setError($view);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        $config                         = $this->getConfig();

        if (!$this->request->password) {
            $response = new DataResponse();
            $response->set("confirm", Activation::toArray([
                "redirect"              => $config->redirect
            ], $this->method));
        } else {
            $response                   = $this->api($config->api->registration, (array)$this->request);
            if (User::isLogged()) {
                $response->set("welcome", Welcome::toArray([
                    "redirect"          => $config->redirect
                ]));
            } elseif ($response->get("activation")) {
                $response->set("confirm", Activation::toArray([
                    "redirect"          => $config->redirect,
                    "response"          => (new DataResponse())->fillObject($response->get("activation"))
                ], $this->method));
            }
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
}
