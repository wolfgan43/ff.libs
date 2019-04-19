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
namespace phpformsframework\libs\delivery\mailer;

abstract class Adapter {
    const PREFIX                                            = null;

    private $from                                           = null;
    private $debug                                          = null;
    private $bcc                                            = null;

    abstract public function smtp();

    public function from($key = null) {
        if(!$this->from) {
            $prefix                                         = (defined(self::PREFIX . "_FROM_EMAIL")
                                                                ? self::PREFIX . "_FROM_"
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


    public function bcc() {
        if(!$this->bcc) {
            $prefix                                         = (defined(self::PREFIX . "_BCC_EMAIL")
                                                                ? self::PREFIX . "_BCC_"
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

        return $this->bcc;
    }

    public function debug() {
        if(!$this->debug) {
            $prefix                                         = (defined(self::PREFIX . "_DEBUG_EMAIL")
                                                                ? self::PREFIX . "_DEBUG_"
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

        return $this->debug;
    }
}