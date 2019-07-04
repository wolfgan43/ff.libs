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
namespace phpformsframework\libs\delivery\drivers;

use phpformsframework\libs\Request;

abstract class MessengerAdapter
{
    const ERROR_BUCKET                                      = "messenger";
    const PREFIX                                            = null;
    protected $appname                                      = null;

    public $sid                                             = null;
    public $token                                           = null;
    public $from                                            = null;
    public $debug                                           = null;

    abstract public function send($message, $to);

    public function __construct()
    {
        $sms_prefix                                         = (
            defined(static::PREFIX . "_SMS_SID")
                                                                ? static::PREFIX . "_SMS_"
                                                                : "SMS_"
                                                            );

        $this->setProperty("sid", $sms_prefix);
        $this->setProperty("token", $sms_prefix);

        $this->setProperty("from");
        $this->setProperty("debug");
    }

    private function setProperty($name, $prefix = "")
    {
        $const                                              = strtoupper($prefix . $name);

        if (defined($const)) {
            $this->$name = constant($const);
        }
    }

    protected function getAppName()
    {
        return substr((
            $this->appname
            ? $this->appname
            : str_replace(" ", "", ucwords(str_replace(array(".", "-"), " ", Request::hostname())))
        ), 0, 11);
    }
}
