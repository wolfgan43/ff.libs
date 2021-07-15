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
namespace phpformsframework\libs\delivery\adapters;

use phpformsframework\libs\delivery\drivers\MessengerAdapter;
use phpformsframework\libs\international\Locale;
use phpformsframework\libs\Exception;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * Class MessengerTwilio
 * @package phpformsframework\libs\delivery\adapters
 */
class MessengerTwilio extends MessengerAdapter
{
    const PREFIX                                            = "TWILIO";

    /**
     * @param string $message
     * @param array|null $to
     * @throws Exception
     */
    public function send(string $message, array $to = null) : void
    {
        if (!class_exists(Client::class)) {
            throw new Exception("Twilio class missing", 501);
        }

        if ($message) {
            if (!empty($to)) {
                try {
                    $client                                 = new Client($this->sid, $this->token);
                    $from                                   = $this->from;
                    if (!$from) {
                        $from = $this->getAppName();
                    }

                    if ($from) {
                        $locale = Locale::getCodeLang();
                        foreach ($to as $tel => $name) {
                            try {
                                $number = (
                                    $locale
                                    ? $client->lookups->v1->phoneNumbers($tel)->fetch(array("countryCode" => $locale))->phoneNumber
                                    : $tel
                                );

                                $client->messages->create(
                                    $number, // Text this number
                                    array(
                                        'from' => $from, // From a valid Twilio number
                                        'body' => $message
                                    )
                                );
                            } catch (TwilioException $e) {
                                Exception::warning($e->getMessage(), static::ERROR_BUCKET);
                            }
                        }
                    } else {
                        throw new Exception(static::PREFIX . " configuration missing. Set constant: " . static::PREFIX. "_SMS_FROM", 500);
                    }
                } catch (ConfigurationException $e) {
                    throw new Exception(static::PREFIX . " configuration missing. Set constant: " . static::PREFIX . "_SMS_SID and " . static::PREFIX . "_SMS_TOKEN", 500);
                }
            } else {
                throw new Exception(static::PREFIX . " recipient is required.", 400);
            }
        } else {
            throw new Exception(static::PREFIX . "  message is required.", 400);
        }
    }
}
