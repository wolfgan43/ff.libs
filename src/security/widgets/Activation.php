<?php
namespace phpformsframework\libs\security\widgets;

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

    protected $requiredJs           = ["hcore.security"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        $view                       = $this->view("index");
        $config                     = $view->getConfig();
        $config->error              = $this->request->error;
        if ($this->request->email) {
            $response = Auth::requestWrite("activation", $this->request->email);

            $view->assign("email_conferma", $this->request->email);
            $view->assign("email_class", "");

            if (isset($response->t)) {
                $view->assign("bearer_code", $response->t);
                $view->parse("SezBearerContainer", false);
            }
        } else {
            $view->assign("email_class", "hide-code-string");
        }
        $view->assign("activation_url", $this->getWebUrl($config["activation_path"]));
        $view->assign("help_mail", $config["help_mail"]);

        $this->setDefault($view, $config);
        $this->setError($view, $config);
        $this->setLogo($view, $config);
        $this->setHeader($view, $config);
    }

    protected function post(): void
    {
        $config                     = $this->getConfig();
        if (!empty($this->request->email) && $this->request->code) {
            //@todo da sostituire con i magic link. non funziona il link nella mail
            // Auth::writeByUrl($request->email, "activation", $request->code);
            $response               = null;
        } elseif ($this->request->code) {
            $response               = $this->api($config->api->activate, ["code" => $this->request->code]);
        } else {
            $response               = $this->api($config->api->requestActivation . $this->path_info, ["identity" => $this->request->identity]);
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
