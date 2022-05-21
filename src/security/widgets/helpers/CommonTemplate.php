<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs\security\widgets\helpers;

use ff\libs\gui\View;
use ff\libs\security\User;
use ff\libs\Exception;
use stdClass;

/**
 * Trait CommonTemplate
 * @package ff\libs\security\widgets\helpers
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    private function displayUser(View $view)
    {
        $user = User::get();

        $view->assign("user_name", $user->display_name);
        $view->assign("user_email", $user->email);
        $view->assign("user_avatar", $user->getAvatar());
    }
}
