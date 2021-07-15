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
namespace phpformsframework\libs\dto;

use phpformsframework\libs\Kernel;

/**
 * Trait Exceptionable
 */
trait Exceptionable
{
    /**
     * @var string
     */
    public $error                           = "";
    /**
     * @var int
     */
    public $status                          = 0;

    /**
     * @param int|null $code
     * @return bool
     */
    public function isError(int $code = null) : bool
    {
        return (
            $code
            ? $this->status == $code
            : $this->status >= 400
        );
    }

    /**
     * @param string|null $msg
     */
    private function setError(string $msg = null) : void
    {
        if (Kernel::$Environment::DEBUG || $this->status < 500) {
            $this->error                        = (
                $this->error
                    ? $this->error . " "
                    : ""
                ) . $msg;
        } else {
            $this->error = "Internal Server Error";
        }
    }
    /**
     * @param int $status
     * @param string|null $msg
     * @return $this
     */
    public function error(int $status, string $msg = null) : self
    {
        $this->status                       = $status;
        $this->setError($msg);

        return $this;
    }

    /**
     * @return string
     */
    public function toLog() : ?string
    {
        return $this->error;
    }

    /**
     * @param array $vars
     */
    private function removeExceptionVars(array &$vars)
    {
        unset($vars["status"], $vars["error"], $vars["debug"]);
    }
}
