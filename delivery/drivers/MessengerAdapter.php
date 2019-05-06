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
namespace phpformsframework\libs\delivery\messenger;

abstract class Adapter {
    const LIMIT_MESSAGE                                     = 1600;

    const PREFIX                                            = null;

    private $from                                           = null;
    private $debug                                          = null;
    private $bcc                                            = null;

    abstract public function send($message, $to);

    public function from($key = null) {
        if(!$this->from) {
            $prefix                                         = (defined(static::PREFIX . "_FROM_TEL")
                                                                ? static::PREFIX . "_FROM_"
                                                                : "FROM_"
                                                            );

            $this->from["tel"]                              = (defined($prefix . "TEL")
                                                                ? constant($prefix . "TEL")
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
            $prefix                                         = (defined(static::PREFIX . "_BCC_TEL")
                                                                ? static::PREFIX . "_BCC_"
                                                                : "BCC_"
                                                            );

            $this->bcc["tel"]                               = (defined($prefix . "TEL")
                                                                ? constant($prefix . "TEL")
                                                                : ""
                                                            );
            $this->bcc["name"]                              = (defined($prefix . "NAME")
                                                                ? constant($prefix . "NAME")
                                                                : $this->bcc["tel"]
                                                            );
        }

        return ($key
            ? $this->bcc[$key]
            : $this->bcc
        );
    }

    public function debug($key = null) {
        if(!$this->debug) {
            $prefix                                         = (defined(static::PREFIX . "_DEBUG_TEL")
                                                                ? static::PREFIX . "_DEBUG_"
                                                                : "DEBUG_"
                                                            );

            $this->debug["tel"]                             = (defined($prefix . "TEL")
                                                                ? constant($prefix . "TEL")
                                                                : ""
                                                            );
            $this->debug["name"]                            = (defined($prefix . "NAME")
                                                                ? constant($prefix . "NAME")
                                                                : $this->debug["tel"]
                                                            );
        }

        return ($key
            ? $this->debug[$key]
            : $this->debug
        );
    }
}