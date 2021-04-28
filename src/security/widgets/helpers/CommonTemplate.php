<?php
namespace phpformsframework\libs\security\widgets\helpers;

use phpformsframework\libs\gui\View;
use phpformsframework\libs\security\User;

use Exception;
use stdClass;

/**
 * Trait RenderTemplate
 * @package hcore\security\widgets\helpers
 */
trait CommonTemplate
{
    /**
     * @param View $view
     * @param stdClass $config
     * @throws Exception
     */
    private function setLogo(View $view, stdClass $config)
    {
        if (!empty($config->logo)) {
            $logo = $config->logo_path ?? "nobrand";

            $view->assign("logo_url", $this->script_path);
            $view->assign("logo_path", $this->getImageUrl($logo, $config->logo));
            $view->parse("SezLogo", false);
        }
    }

    /**
     * @param View $view
     */
    private function setError(View $view)
    {
        if (!empty($this->error)) {
            $view->assign("error", $this->error);
            $view->parse("SezError", false);
        }
    }
    /**
     * @param View $view
     * @param stdClass $config
     */
    private function setHeader(View $view, stdClass $config)
    {
        if (!empty($config->title)) {
            $view->assign("title", $this->translate($config->title));
            $view->parse("SezTitle", false);
        }
        if (!empty($config->description)) {
            $view->assign("description", $this->translate($config->description));

            $view->parse("SezDescription", false);
        }
    }

    /**
     * @param $view
     * @param stdClass $config
     * @throws Exception
     */
    private function displayUser(View $view, stdClass $config)
    {
        $user = User::get();
        if ($user) {
            $view->assign("user_name", $user->username);
            $view->assign("user_email", $user->email);
            $view->assign("user_avatar", $user->getAvatar($config->avatar));
        }
    }
}
