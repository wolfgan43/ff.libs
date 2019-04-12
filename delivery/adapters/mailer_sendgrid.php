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
namespace phpformsframework\libs\delivery;

class mailerSendgrid extends mailerAdapter
{
    const PREFIX                                            = "SENDGRID";
    private $config                                         = null;

    public function smtp() {
        if(!$this->config) {
            $prefix                                         = (defined(self::PREFIX . "_SMTP_PASSWORD")
                                                                ? self::PREFIX . "_SMTP_"
                                                                : "SMTP_"
                                                            );

            $this->config["host"]                           = "smtp.sendgrid.net";
            $this->config["name"]                           = "apikey";
            $this->config["password"]                       = (defined($prefix . "PASSWORD")
                                                                ? constant($prefix . "PASSWORD")
                                                                : ""
                                                            );

            $this->config["auth"]                           = true;
            $this->config["port"]                           = "587";
            $this->config["secure"]                         = "tls";
            $this->config["autoTLS"]                        = false;
        }

        return $this->config;
    }
}