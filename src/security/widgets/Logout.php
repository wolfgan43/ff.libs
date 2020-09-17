<?php
namespace phpformsframework\libs\security\widgets;

use phpformsframework\libs\gui\Widget;
use phpformsframework\libs\security\User;
use phpformsframework\libs\security\widgets\helpers\CommonTemplate;
use Exception;

/**
 * Class Logout
 * @package phpformsframework\libs\security\widgets
 */
class Logout extends Widget
{
    use CommonTemplate;

    protected $requiredJs           = ["hcore.auth"];

    /**
     * @throws Exception
     */
    public function get(): void
    {
        if (User::isLogged()) {
            $view = $this->view("index");
            $config = $view->getConfig();

            $this->displayUser($view, $config);
            $view->assign("logout_url", $this->getWebUrl($config->logout_path));

            $this->setDefault($view, $config);
            $this->setError($view, $config);
            $this->setLogo($view, $config);
        } else {
            $config = $this->getConfig();
            $this->redirect($this->getWebUrl($config->login_path));
        }
    }

    public function post(): void
    {
        User::logout();
    }

    public function put(): void
    {
        // TODO: Implement put() method.
    }

    public function delete(): void
    {
        // TODO: Implement delete() method.
    }

    public function patch(): void
    {
        // TODO: Implement patch() method.
    }
}
