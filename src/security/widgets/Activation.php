<?php
namespace phpformsframework\libs\security\widgets;

use hcore\util\MicroServices;
use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use Exception;

/**
 * Class Activation
 * @package phpformsframework\libs\security\widgets
 */
class Activation extends Widget
{
    use CommonTemplate;
    use MicroServices;

    protected $requiredJs           = ["hcore.security"];

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
    private function otp() : array
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

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }
}
