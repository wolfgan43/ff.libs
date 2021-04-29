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

    protected $requiredJs           = ["cm"];

    /**
     * @throws Exception
     */
    protected function get(): void
    {
        if (User::isLogged()) {
            $view = $this->view();
            $config = $view->getConfig();

            $this->displayUser($view, $config);
            $view->assign("logout_url", $this->getWebUrl($config->logout_path));

            $this->setError($view);
            $this->setLogo($view, $config);
        } else {
            $config = $this->getConfig();
            $this->redirect($this->getWebUrl($config->login_path));
        }
    }

    protected function post(): void
    {
        User::logout();

        $view = $this->view("thankyou");
        $config = $view->getConfig();

        $view->assign("login_path", $config->login_path);
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
