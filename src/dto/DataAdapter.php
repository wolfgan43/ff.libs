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

namespace phpformsframework\libs\dto;

use phpformsframework\libs\Constant;

abstract class DataAdapter
{
    const CONTENT_TYPE                      = null;

    const CONTINUE                          = 100;
    const SWITCHING_PROTOCOLS               = 101;
    const PROCESSING                        = 102;
    const EARLY_HINTS                       = 103;
    const OK                                = 200;
    const CREATED                           = 201;
    const ACCEPTED                          = 202;
    const NON_AUTHORITATIVE_INFORMATION     = 203;
    const NO_CONTENT                        = 204;
    const RESET_CONTENT                     = 205;
    const PARTIAL_CONTENT                   = 206;
    const MULTI_STATUS                      = 207;
    const ALREADY_REPORTED                  = 208;
    const IM_USED                           = 226;
    const MULTIPLE_CHOICES                  = 300;
    const MOVED_PERMANENTLY                 = 301;
    const FFOUND                            = 302;
    const SEE_OTHER                         = 303;
    const NOT_MODIFIED                      = 304;
    const USE_PROXY                         = 305;
    const SWITCH_PROXY                      = 306;
    const TEMPORARY_REDIRECT                = 307;
    const PERMANENT_REDIRECT                = 308;
    const BAD_REQUEST                       = 400;
    const UNAUTHORIZED                      = 401;
    const PAYMENT_REQUIRED                  = 402;
    const FORBIDDEN                         = 403;
    const NOT_FOUND                         = 404;
    const METHOD_NOT_ALLOWED                = 405;
    const NOT_ACCEPTABLE                    = 406;
    const PROXY_AUTHENTICATION_REQUIRED     = 407;
    const REQUEST_TIMEOUT                   = 408;
    const CONFLICT                          = 409;
    const GONE                              = 410;
    const LENGTH_REQUIRED                   = 411;
    const PRECONDITION_FAILED               = 412;
    const PAYLOAD_TOO_LARGE                 = 413;
    const URI_TOO_LONG                      = 414;
    const UNSUPPORTED_MEDIA_TYPE            = 415;
    const RANGE_NOT_SATISFIABLE             = 416;
    const EXPECTATION_FAILED                = 417;
    const IM_A_TEAPOT                       = 418;
    const MISDIRECTED_REQUEST               = 421;
    const UNPROCESSABLE_ENTITY              = 422;
    const LOCKED                            = 423;
    const FAILED_DEPENDENCY                 = 424;
    const TOO_EARLY                         = 425;
    const UPGRADE_REQUIRED                  = 426;
    const PRECONDITION_REQUIRED             = 428;
    const TOO_MANY_REQUESTS                 = 429;
    const REQUEST_HEADER_FIELDS_TOO_LARGE   = 431;
    const UNAVAILABLE_FOR_LEGAL_REASONS     = 451;
    const INTERNAL_SERVER_ERROR             = 500;
    const NOT_IMPLEMENTED                   = 501;
    const BAD_GATEWAY                       = 502;
    const SERVICE_UNAVAILABLE               = 503;
    const GATEWAY_TIMEOUT                   = 504;
    const HTTP_VERSION_NOT_SUPPORTED        = 505;
    const VARIANT_ALSO_NEGOTIATES           = 506;
    const INSUFFICIENT_STORAGE              = 507;
    const LOOP_DETECTED                     = 508;
    const NOT_EXTENDED                      = 510;
    const NETWORK_AUTHENTICATION_REQUIRED   = 511;


    /**
     * @var string
     */
    public $error                           = "";
    /**
     * @var int
     */
    public $status                          = 0;
    /**
     * @var null|mixed
     */
    private $debug                           = null;

    abstract public function output();

    /**
     * @return array
     */
    protected function get_vars()
    {
        $vars                               = get_object_vars($this);
        if (!Constant::DEBUG) {
            unset($vars["debug"]);
        }

        return $vars;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->get_vars();
    }

    /**
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this->get_vars());
    }

    /**
     * @param $status
     * @param null|string $msg
     * @return $this
     */
    public function error($status, $msg = null)
    {
        $this->status                       = $status;
        $this->error                        = (
            $this->error
            ? $this->error . " "
            : ""
        ) . $msg;

        return $this;
    }

    /**
     * @param null|int $code
     * @return bool
     */
    public function isError($code = null)
    {
        return (bool) (
            $code
            ? isset($this->status[$code])
            : $this->status
        );
    }

    /**
     * @param mixed $data
     * @return DataAdapter
     */
    public function debug($data)
    {
        $this->debug                        = ($this->debug ? $this->debug . " " : "") . $data;

        return $this;
    }

    /**
     * @param array $values
     * @return DataAdapter
     */
    public function fill($values)
    {
        foreach ($values as $key => $value) {
            $this->$key                     = $value;
        }

        return $this;
    }

    /**
     * @param array $values
     * @return DataAdapter
     */
    public function filter($values)
    {
        $vars                               = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (isset($values[$key])) {
                unset($this->$key);
            }
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->$key                         = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get($key)
    {
        return (isset($this->$key)
            ? $this->$key
            : null
        );
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isset($key)
    {
        return !empty($this->$key);
    }

    /**
     * @param string $key
     * @return DataAdapter
     */
    public function unset($key)
    {
        unset($this->$key);

        return $this;
    }
}
