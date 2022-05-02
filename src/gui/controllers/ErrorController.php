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
namespace phpformsframework\libs\gui\controllers;

use phpformsframework\libs\gui\Controller;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Exception;

/**
 * Class Error
 * @package phpformsframework\libs\gui\pages
 */
class ErrorController extends Controller
{
    private $email_support      = null;

    /**
     * @param string $email
     * @return $this
     */
    public function setEmailSupport(string $email) : self
    {
        $this->email_support    = $email;

        return $this;
    }
    /**
     * @return mixed
     * @throws Exception
     */
    protected function get() : void
    {
        $this->addStylesheet("error");

        $error = Translator::getWordByCodeCached($this->error);

        $errorView = $this->view()
            ->assign("title", $error ?? Translator::getWordByCodeCached(Exception::getErrorMessage($this->http_status_code)))
            ->assign("error_code", $this->http_status_code);

        if ($this->email_support) {
            $errorView->assign("email_support", $this->email_support);
            $errorView->parse("SezButtonSupport", false);
        }

        $this
            ->assign(self::TPL_VAR_DEFAULT, $errorView)
            ->debug($error);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function post() : void
    {
        $this->get();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function put() : void
    {
        $this->get();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function delete() : void
    {
        $this->get();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function patch() : void
    {
        $this->get();
    }
}
