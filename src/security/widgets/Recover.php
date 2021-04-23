<?php
namespace phpformsframework\libs\security\widgets;

use hcore\util\MicroServices;
use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use Exception;

/**
 * Class Recover
 * @package phpformsframework\libs\security\widgets
 */
class Recover extends Widget
{
    use CommonTemplate;
    use MicroServices;

    protected $requiredJs           = ["hcore.security", "recover"];
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
            $response = $this->api($config->api->{"change_" . $action}, [$action => $this->request->value], ["Authorization" => $this->authorization . ":" . $this->request->code]);
        } elseif (isset($config->api->{"recover_" . $action})) {
            $response = $this->api($config->api->{"recover_" . $action}, ["identifier" => $this->request->identifier]);

            $response->set("confirm", (
                $response->get("token")
                ? $this->snippet("confirm")
                : $this->snippet("wait")
            ));
        } else {
            throw new Exception("Recover not supported: " . $action, 501);
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
