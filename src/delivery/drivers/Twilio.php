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
namespace ff\libs\delivery\drivers;

use Exception;
use ff\libs\delivery\NoticeDriver;
use ff\libs\dto\DataError;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * Class MessengerTwilio
 * @package ff\libs\delivery\adapters
 */
class Twilio extends NoticeDriver
{
    public const PREFIX                 = "TWILIO";

    protected $sid                      = null;
    protected $token                    = null;
    protected $from_number              = null;

    /**
     * @param string $message
     * @param string|null $title
     * @return DataError
     * @throws Exception
     */
    public function send(string $message, string $title = null) : DataError
    {
        if (!class_exists(Client::class)) {
            throw new Exception("Twilio class missing", 501);
        }

        $from                                   = $this->from->key ?? $this->from_number;
        $client                                 = new Client($this->sid, $this->token);
        foreach ($this->recipients as $tel => $name) {
            try {
                $number = $client->lookups->v1->phoneNumbers($tel)->fetch(array("countryCode" => $this->lang))->phoneNumber;
                $client->messages->create(
                    $number, // Text this number
                    array(
                        'from' => $from, // From a valid Twilio number
                        'body' => $message
                    )
                );
            } catch (TwilioException $e) {
                throw new Exception($e->getMessage(), 500);
            }
        }

        return new DataError();
    }
}

