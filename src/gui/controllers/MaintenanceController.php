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
namespace ff\libs\gui\controllers;

use ff\libs\gui\Controller;
use ff\libs\Exception;

/**
 * Class MaintenanceController
 * @package ff\libs\gui\controllers
 */
class MaintenanceController extends Controller
{
    protected function get(): void
    {
        $this->assign([self::TPL_VAR_DEFAULT => "site under maintenance"]);
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
