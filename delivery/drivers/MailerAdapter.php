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

use phpformsframework\libs\Extendible;

class MailerAdapter extends Extendible {
    protected $prefix                                       = null;

    private $from                                           = null;
    private $debug                                          = null;
    private $bcc                                            = null;
    protected $config                                         = null;

    public function smtp() {
        if(!$this->config) {
            $prefix                                         = (defined($this->prefix . "_SMTP_PASSWORD")
                                                                ? $this->prefix . "_SMTP_"
                                                                : "SMTP_"
                                                            );
            $this->config["host"]                           = (defined($prefix . "HOST")
                                                                ? constant($prefix . "HOST")
                                                                : $this->config["host"]
                                                            );
            $this->config["username"]                       = (defined($prefix . "USER")
                                                                ? constant($prefix . "USER")
                                                                : $this->config["username"]
                                                            );
            $this->config["password"]                       = (defined($prefix . "PASSWORD")
                                                                ? constant($prefix . "PASSWORD")
                                                                : $this->config["password"]
                                                            );
            $this->config["auth"]                           = (defined($prefix . "AUTH")
                                                                ? constant($prefix . "AUTH")
                                                                : $this->config["auth"]
                                                            );
            $this->config["port"]                           = (defined($prefix . "PORT")
                                                                ? constant($prefix . "PORT")
                                                                : $this->config["port"]
                                                            );
            $this->config["secure"]                         = (defined($prefix . "SECURE")
                                                                ? constant($prefix . "SECURE")
                                                                : $this->config["secure"]
                                                            );
            $this->config["autoTLS"]                        = (defined($prefix . "AUTOTLS")
                                                                ? constant($prefix . "AUTOTLS")
                                                                : $this->config["autoTLS"]
                                                            );
        }

        return $this->config;
    }

    public function from($key = null) {
        if(!$this->from) {
            $prefix                                         = (defined($this->prefix . "_FROM_EMAIL")
                                                                ? $this->prefix . "_FROM_"
                                                                : "FROM_"
                                                            );

            $this->from["email"]                            = (defined($prefix . "EMAIL")
                                                                ? constant($prefix . "EMAIL")
                                                                : ""
                                                            );
            $this->from["name"]                             = (defined($prefix . "NAME")
                                                                ? constant($prefix . "NAME")
                                                                : $this->from["email"]
                                                            );
        }

        return ($key
            ? $this->from[$key]
            : $this->from
        );
    }


    public function bcc($key = null) {
        if(!$this->bcc) {
            $prefix                                         = (defined($this->prefix . "_BCC_EMAIL")
                                                                ? $this->prefix . "_BCC_"
                                                                : "BCC_"
                                                            );

            $this->bcc["email"]                             = (defined($prefix . "EMAIL")
                                                                ? constant($prefix . "EMAIL")
                                                                : ""
                                                            );
            $this->bcc["name"]                              = (defined($prefix . "NAME")
                                                                ? constant($prefix . "NAME")
                                                                : $this->bcc["email"]
                                                            );
        }

        return ($key
            ? $this->bcc[$key]
            : $this->bcc
        );
    }

    public function debug($key = null) {
        if(!$this->debug) {
            $prefix                                         = (defined($this->prefix . "_DEBUG_EMAIL")
                                                                ? $this->prefix . "_DEBUG_"
                                                                : "DEBUG_"
                                                            );

            $this->debug["email"]                           = (defined($prefix . "EMAIL")
                                                                ? constant($prefix . "EMAIL")
                                                                : ""
                                                            );
            $this->debug["name"]                            = (defined($prefix . "NAME")
                                                                ? constant($prefix . "NAME")
                                                                : $this->debug["email"]
                                                            );
        }

        return ($key
            ? $this->debug[$key]
            : $this->debug
        );
    }
}