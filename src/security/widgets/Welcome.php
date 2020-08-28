<?php
/**
 * VGallery: CMS based on FormsFramework
 * Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage core
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/gpl-3.0.html
 *  @link https://github.com/wolfgan43/vgallery
 */

namespace phpformsframework\libs\security\widgets;

use phpformsframework\libs\security\widgets\helpers\RenderTemplate;

use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\gui\View;
use phpformsframework\libs\gui\Widget;

use stdClass;
use Exception;

/**
 * Class Welcome
 * @package phpformsframework\libs\security\widgets
 */
class Welcome extends Widget
{
    use RenderTemplate;

    /**
     * @param stdClass $request
     * @return array
     */
    protected function getConfigDefault(stdClass $request) : array
    {
        return array_replace($this->config, array(
            "avatar"                => $this->env("AUTH_AVATAR_MODE")
        ));
    }

    /**
     * @param array $config
     * @param stdClass $request
     * @param bool $isAjax
     */
    protected function controller(array &$config, stdClass $request, bool $isAjax) : void
    {
        $this->view("index", $config);
    }

    /**
     * @param array $config
     * @param stdClass $request
     * @return DataResponse|null
     */
    protected function callToAction(array &$config, stdClass $request): ?DataResponse
    {
        return null;
    }

    /**
     * @param View $view
     * @param array $config
     * @throws Exception
     */
    protected function renderTemplate(&$view, array $config)
    {
        $this->displayUser($view);
        $this->setLogo($view, $config);
    }
}
