<?php
namespace phpformsframework\libs\security\widgets\helpers;

use hcore\App;
use hcore\security\Hash;
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
            $view->assign("logo_path", $this->media->imageUrl($logo, $config->logo));
            $view->parse("SezLogo", false);
        }
    }

    /**
     * @param View $view
     * @param stdClass $config
     */
    private function setError(View $view, stdClass $config)
    {
        if (!empty($config->error)) {
            $view->assign(
                "error",
                '<div class="alert alert-warning">'
                . $config->error
                . '</div>'
            );
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
     * @param View $view
     * @param stdClass $config
     */
    private function setDomain(View $view, stdClass $config)
    {
        if (!empty($config->domain)) {
            $view->parse("SezDomain", false);
        } else {
            $view->assign("domain_name", $this->request->domain ?? App::request()->hostname());
            $view->parse("SezDomainHidden", false);
        }
    }
    /**
     * @param View $view
     * @param stdClass $config
     */
    private function setDefault(View $view, stdClass $config)
    {
        $view->assign("csrf_token", Hash::csrf());
        if (!empty($config->redirect)) {
            $view->assign("redirect_url", $this->getWebUrl($config->redirect));
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
