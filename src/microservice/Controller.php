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
namespace ff\libs\microservice;

use ff\libs\Kernel;

/**
 * Class Controller
 * @package ff\libs\microservice
 */
class Controller
{
    /**
     * Controller constructor.
     * @param array|null $config
     */
    public function __construct()
    {
        $page                                   = Kernel::$Page;

        $this->method                           = $page->method;
        $this->request                          = $page->mapRequest();
        $this->headers                          = (object) $page->getHeaders();
        $this->authorization                    = $page->getAuthorization();
        $this->script_path                      = $page->script_path;
        $this->path_info                        = $page->path_info;
        $this->isXhr                            = $page->isAjax();
        $this->referer                          = $page->urlReferer();

        if (!empty($page->status)) {
            $this->error($page->status, $page->error, false);
        }
    }

    public function display(string $method = null)
    {
    }

    /**
     * @param int $status
     * @param string|null $msg
     * @param bool $debug
     * @return $this
     */
    protected function error(int $status, string $msg = null, bool $debug = true) : self
    {
        $this->http_status_code     = $status;
        $this->error                = (
            $status < 500 || Kernel::$Environment::DEBUG
            ? $msg
            : self::ERROR_SERVER_NOT_AVAILABLE
        );

        if ($debug && $this->error) {
            $this->debug($this->error, $msg);
        }

        return $this;
    }
}
