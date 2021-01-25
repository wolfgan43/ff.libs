<?php
namespace phpformsframework\libs\security\widgets;

use hcore\util\MicroServices;
use phpformsframework\libs\Env;
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

    protected const OTP_SENDER      = "AUTH_SENDER_RECOVER";

    protected $requiredJs           = ["hcore.security"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $view                       = $this->view("index");
        $config                     = $view->getConfig();

        $view->assign("recover_url", $this->getWebUrl($this->script_path . $this->path_info));

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }

    /**
     * @throws Exception
     */
    protected function post(): void
    {
        $action                     = basename($this->path_info);
        $config                     = $this->getConfig();
        if (!empty($this->request->code)) {
            $response = $this->api($config->api->{"change_" . $action}, [$action => $this->request->value], ["Authorization" => $this->authorization . ":" . $this->request->code]);
        } else {
            $response = $this->api($config->api->{"recover_" . $action}, ["identifier" => $this->request->identity, "sender" => Env::get(static::OTP_SENDER)]);
            $response->set("confirm", $this->confirm());
        }

        $this->send($response);
    }

    /**
     * @return array
     * @throws Exception
     */
    private function confirm() : array
    {
        $view                       = $this->view("confirm");
        $config                     = $this->getConfig();

        $view->assign("recover_url", $this->getWebUrl($this->script_path . $this->path_info));

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);

        return [
            "html"  => $view->display(),
            "css"   => null,
            "js"    => null
        ];
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
