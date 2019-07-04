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

use phpformsframework\libs\Mappable;

class MailerAdapter extends Mappable
{
    protected $prefix                                       = null;

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

    public function __construct($map_name)
    {
        parent::__construct($map_name);


        $smtp_prefix                                        = (
            defined($this->prefix . "_SMTP_PASSWORD")
            ? $this->prefix . "_SMTP_"
            : "SMTP_"
        );

        $this->setProperty("host", $smtp_prefix);
        $this->setProperty("username", $smtp_prefix);
        $this->setProperty("password", $smtp_prefix);
        $this->setProperty("auth", $smtp_prefix);
        $this->setProperty("port", $smtp_prefix);
        $this->setProperty("secure", $smtp_prefix);
        $this->setProperty("autoTLS", $smtp_prefix);

        $this->setProperty("from_email");
        $this->setProperty("from_name");
        $this->setProperty("debug_email");
    }

    private function setProperty($name, $prefix = "")
    {
        $const                                          = strtoupper($prefix . $name);

        if (defined($const)) {
            $this->$name = constant($const);
        }
    }
}
