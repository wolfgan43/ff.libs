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

use phpformsframework\libs\Error;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class Twilio extends Adapter {
    const PREFIX                                            = "TWILIO";
    private $config                                         = null;

    private function connector() {
        if(!$this->config) {
            $prefix                                         = (defined(self::PREFIX . "_SMS_SID")
                                                                ? self::PREFIX . "_SMS_"
                                                                : "SMS_"
                                                            );
            $this->config["sid"]                            = (defined($prefix . "SID")
                                                                ? constant($prefix . "SID")
                                                                : ""
                                                            );
            $this->config["token"]                          = (defined($prefix . "TOKEN")
                                                                ? constant($prefix . "TOKEN")
                                                                : ""
                                                            );
        }

        return $this->config;
    }

    public function send($message, $to) {
        $res                                                = null;
        if($message && strlen($message) < static::LIMIT_MESSAGE) {
            if(is_array($to) && count($to)) {
                $config                                     = $this->connector();

                try {
                    $client                                 = new Client($config["sid"], $config["token"]);
                } catch (ConfigurationException $e) {
                    Error::register(self::PREFIX . " configuration missing. Set constant: " . self::PREFIX . "_SMS_SID and " . self::PREFIX . "_SMS_TOKEN", "messenger");
                }
                if(!Error::check("messenger")) {
                    $from                                   = $this->from("tel");
                    if(!$from)                              { $from = substr(str_replace(" " , "", ucwords(str_replace(array(".", "-"), " ", $_SERVER["HTTP_HOST"]))), 0, 11); }

                    if($from) {
                        foreach ($to as $tel => $name) {
                            try { //todo: da sistemare l'exception di twilio
                                $client->messages->create(
                                    $tel, // Text this number
                                    array(
                                        'from' => $from, // From a valid Twilio number
                                        'body' => $message
                                    )
                                );
                            } catch (TwilioException $e) {
                                Error::register($e->getMessage(), "messenger");
                            }
                        }
                    } else {
                        Error::register(self::PREFIX . " configuration missing. Set constant: " . self::PREFIX . "_SMS_FROM", "messenger");
                    }
                }
            } else {
                Error::register(self::PREFIX . " recipient is required.", "messenger");
            }
        } else {
            Error::register(self::PREFIX . "  message is required. and the length must be < " . static::LIMIT_MESSAGE, "messenger");
        }
        return $res;
    }
}