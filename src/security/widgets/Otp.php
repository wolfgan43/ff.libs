<?php
namespace phpformsframework\libs\security\widgets;

use hcore\util\MicroServices;
use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use phpformsframework\libs\util\ServerManager;

/**
 * Class Otp
 * @package phpformsframework\libs\security\widgets
 */
class Otp extends Widget
{
    use CommonTemplate;
    use MicroServices;

    private const API_AUTH2_OTP_CREATE = "auth2/otp/create";

    protected $requiredJs           = ["hcore.security"];

    /**
     *
     */
    protected function get(): void
    {
        $view                       = $this->view("index");
        $config                     = $view->getConfig();
        //$config->error              = $this->request->error;

        if (0 && $this->request->identity) {
            $this->api(
                self::API_AUTH2_OTP_CREATE,
                [
                    "uuid" => $this->request->identity,
                    "sender" => false,
                ]
            );
        }

        $view->assign("help_mail", $config->help_mail ?? "support@" . $_SERVER['HTTP_HOST']);
        $view->assign("otp_url", $this->getWebUrl($this->script_path));

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }

    /**
     *
     */
    protected function post(): void
    {
        // TODO: Implement post() method.
    }

    /**
     *
     */
    protected function put(): void
    {
        // TODO: Implement put() method.
    }

    /**
     *
     */
    protected function delete(): void
    {
        // TODO: Implement delete() method.
    }

    /**
     *
     */
    protected function patch(): void
    {
        // TODO: Implement patch() method.
    }
}