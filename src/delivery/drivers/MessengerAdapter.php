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
namespace phpformsframework\libs\delivery\drivers;

use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\ServerManager;
use phpformsframework\libs\Exception;

/**
 * Class MessengerAdapter
 * @package phpformsframework\libs\delivery\drivers
 */
abstract class MessengerAdapter
{
    use ServerManager;

    const ERROR_BUCKET                                      = "messenger";
    const PREFIX                                            = null;
    protected $appname                                      = null;

    public $sid                                             = null;
    public $token                                           = null;
    public $from                                            = null;
    public $debug                                           = null;

    /**
     * @param string $message
     * @param array $to
     */
    abstract public function send(string $message, array $to) : void;

    /**
     * MessengerAdapter constructor.
     * @throws Exception
     */
    public function __construct()
    {

        try {
            $env                                            = Kernel::$Environment;
            $prefix                                         = $env . '::' . (
                defined($env . '::' . static::PREFIX . "_SMS_SID")
                    ? static::PREFIX . "_SMS_"
                    : "SMS_"
                );

            $this->sid                                      = constant($prefix . "SID");
            $this->token                                    = constant($prefix . "TOKEN");
            $this->from                                     = constant($prefix . "FROM");
            $this->debug                                    = constant($prefix . "DEBUG");
        } catch (\Exception $e) {
            throw new Exception("SMS Params Missing: " . $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return string
     */
    protected function getAppName() : string
    {
        return substr((
            $this->appname
            ? $this->appname
            : str_replace(" ", "", ucwords(str_replace(array(".", "-"), " ", $this->hostname())))
        ), 0, 11);
    }
}
