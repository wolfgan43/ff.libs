<?php
namespace phpformsframework\libs\security\widgets;

use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\User;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use Exception;

/**
 * Class Recover
 * @package phpformsframework\libs\security\widgets
 */
class Recover extends Widget
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

        $view->assign("recover_url", $this->getWebUrl($this->script_path . $this->path_info));
        $view->assign("email_class", "hide-code-string");
        $view->assign("recover_conferma_title", "Forgot your password?");

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }

    protected function post(): void
    {
        $config                     = $this->getConfig();
        if ($this->path_info == "/confirm") {
            if (!empty($this->request->code)) {
                $response = $this->api($config->api->change . $this->path_info, ["code" => $this->request->code, "value" => $this->request->value], ["Bearer" => User::request()->getBearerToken()]);
            }
        } else {
            $response = $this->api($config->api->recover . $this->path_info, ["identity" => $this->request->identity]);
            $this->confirm();
        }
    }

    private function confirm()
    {
        $view                       = $this->view("confirm");
        $config                     = $view->getConfig();

        $view->assign("recover_url", $this->getWebUrl($this->script_path . $this->path_info));

        $this->setDefault($view, $config);
        $this->setError($view, $config);
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
