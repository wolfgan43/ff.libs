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

use Exception;
use phpformsframework\libs\Error;
use phpformsframework\libs\Kernel;

/**
 * Class MailerAdapter
 * @package phpformsframework\libs\delivery\drivers
 */
class MailerAdapter
{
    const PREFIX                                            = null;

    public $driver                                          = "smtp";
    public $host                                            = null;
    public $username                                        = null;
    public $password                                        = null;
    public $auth                                            = false;
    public $port                                            = 25;
    public $secure                                          = "none";
    public $autoTLS                                         = false;

    public $from_email                                      = null;
    public $from_name                                       = null;
    public $debug_email                                     = null;

    public function __construct()
    {
        try {
            $env                                            = Kernel::$Environment;
            $class_name                                     = $env . '::';
            $prefix                                         = $class_name . (
                defined($class_name . static::PREFIX . "_SMTP_HOST")
                    ? static::PREFIX . "_SMTP_"
                    : "SMTP_"
                );

            $this->host                                     = constant($prefix . "HOST");
            $this->username                                 = constant($prefix . "USER");
            $this->password                                 = constant($prefix . "SECRET");
            $this->auth                                     = constant($prefix . "AUTH");
            $this->port                                     = constant($prefix . "PORT");
            $this->secure                                   = constant($prefix . "SECURE");
            $this->from_email                               = constant($class_name . "FROM_EMAIL");
            $this->from_name                                = constant($class_name . "FROM_NAME");
            if (defined($prefix . "DRIVER")) {
                $this->driver                               = constant($prefix . "DRIVER");
            }
            if (defined($prefix . "AUTOTLS")) {
                $this->autoTLS                              = constant($prefix . "AUTOTLS");
            }
            if (defined($prefix . "DEBUG_EMAIL")) {
                $this->debug_email                          = constant($class_name . "DEBUG_EMAIL");
            }

        } catch (Exception $e) {
            Error::register("Mailer Params Missing: " . $e->getMessage(), Mailer::ERROR_BUCKET);
        }
    }
}
