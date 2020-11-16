<?php
namespace phpformsframework\libs\security\widgets;

use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\User;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use Exception;

/**
 * Class Registration
 * @package phpformsframework\libs\security\widgets
 */
class Registration extends Widget
{
    use CommonTemplate;

    protected $requiredJs           = ["hcore.auth"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $view                       = $this->view("index");
        $config                     = $view->getConfig();

        $view->assign("registration_url", $this->getWebUrl($config->registration_path));

        if (!empty($config->email)) {
            $view->parse("SezEmail", true);
        }

        if (!empty($config->phone)) {
            $view->parse("SezPhone", false);
        }

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
        $this->setDomain($view, $config);
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        $config                     = $this->getConfig();
        $response                   = $this->api($config->api->registration, (array) $this->request);
        if (User::isLogged()) {
            $response->set("welcome", Welcome::displayJson([
                "redirect" => $config->redirect
            ]));
        } else {
            $this->redirect($this->getWebUrl($config->activation_path));
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
}
