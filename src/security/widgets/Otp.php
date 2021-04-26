<?php
namespace phpformsframework\libs\security\widgets;

use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;

/**
 * Class Otp
 * @package phpformsframework\libs\security\widgets
 */
class Otp extends Widget
{
    use CommonTemplate;

    private const API_AUTH2_OTP_CREATE = "auth2/otp/create";

    protected $requiredJs           = ["hcore.security"];

    /**
     *
     */
    protected function get(): void
    {
        $view                       = $this->view("index");
        $config                     = $view->getConfig();
        //@todo da finire
        if (0 && $this->request->identifier) {
            $this->api(
                self::API_AUTH2_OTP_CREATE,
                [
                    "uuid" => $this->request->identifier,
                    "sender" => false,
                ]
            );
        }

        $view->assign("help_mail", $config->help_mail ?? "support@" . $_SERVER['HTTP_HOST']);
        $view->assign("otp_url", $this->getWebUrl($this->script_path));

        $this->setError($view);
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