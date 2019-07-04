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
namespace phpformsframework\libs\delivery\adapters;

use phpformsframework\libs\delivery\drivers\MessengerAdapter;
use phpformsframework\libs\Error;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class MessengerTwilio extends MessengerAdapter
{
    const PREFIX                                            = "TWILIO";

    public function send($message, $to)
    {
        $res                                                = null;
        if ($message) {
            if (is_array($to) && count($to)) {
                try {
                    $client                                 = new Client($this->sid, $this->token);
                    $from                                   = $this->from;
                    if (!$from) {
                        $from = $this->getAppName();
                    }

                    if ($from) {
                        foreach ($to as $tel => $name) {
                            try {
                                $client->messages->create(
                                    $tel, // Text this number
                                    array(
                                        'from' => $from, // From a valid Twilio number
                                        'body' => $message
                                    )
                                );
                            } catch (TwilioException $e) {
                                Error::register($e->getMessage(), static::ERROR_BUCKET);
                            }
                        }
                    } else {
                        Error::register(static::PREFIX . " configuration missing. Set constant: " . static::PREFIX. "_SMS_FROM", static::ERROR_BUCKET);
                    }
                } catch (ConfigurationException $e) {
                    Error::register(static::PREFIX . " configuration missing. Set constant: " . static::PREFIX . "_SMS_SID and " . static::PREFIX . "_SMS_TOKEN", static::ERROR_BUCKET);
                }
            } else {
                Error::register(static::PREFIX . " recipient is required.", static::ERROR_BUCKET);
            }
        } else {
            Error::register(static::PREFIX . "  message is required.", static::ERROR_BUCKET);
        }
        return $res;
    }
}
