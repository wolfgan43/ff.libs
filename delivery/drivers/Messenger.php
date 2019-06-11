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

use phpformsframework\libs\Debug;
use phpformsframework\libs\Error;
use phpformsframework\libs\Log;
use phpformsframework\libs\Request;
use phpformsframework\libs\security\Validator;

if(!defined("MESSENGER_ADAPTER"))                       define("MESSENGER_ADAPTER", "twilio");

class Messenger {
    const NAME_SPACE                                        = 'phpformsframework\\libs\\delivery\\messenger\\';

    const ADAPTER                                           = MESSENGER_ADAPTER;

    private $to                                             = null;
    private $content                                        = null;
    /**
     * @var MessengerAdapter
     */
    private $adapter                                        = null;
    /**
     * @var Messenger
     */
    private static $singleton                               = null;

    /**
     * @param null|string $messengerAdapter
     * @return Messenger
     */
    public static function getInstance($messengerAdapter = null)
    {
        if(!self::$singleton) {
            self::$singleton = new Messenger($messengerAdapter);
        }

        return self::$singleton;
    }

    public function __construct($smsAdapter = null)
    {
        $this->setAdapter($smsAdapter);
    }

    /**
     * @param array[email => name] $emails
     * @param string[to|cc|bcc] $type
     * @return Messenger
     */
    public function addAddresses($tels) {
        if(is_array($tels)) {
            foreach ($tels as $tel) {
                $this->addAddress($tel);
            }
        }

        return $this;
    }

    public function send($message = null, $to = null) {

        Debug::startWatch();

        if($to)                                             { $this->addAddress($to); }
        if($message)                                        { $this->setMessage($message); }

        if(DEBUG::ACTIVE)                                   { $this->addAddress($this->adapter->debug()); }
        $this->addAddress($this->adapter->bcc());

        $this->adapter->send($this->content, $this->to);

        return $this->getResult(Debug::stopWatch());
    }

    public function setMessage($content) {
        $this->content                                      = $content;

        return $this;
    }

    /**
     * @param float $exTime
     * @return array
     */
    private function getResult($exTime = null)
    {
        if(Error::check("messenger") || Debug::ACTIVE) {
            $dump = array(
                "source" => Debug::stackTrace()
                , "URL" => Request::url()
                , "REFERER" => Request::referer()
                , " content" => $this->content
                , " from" => $this->adapter->from()
                , " error" => Error::raise("messenger")
                , " exTime" => $exTime
            );
            if(Error::check("messenger")) {
                Log::error($dump);
            } else {
                Log::debugging($dump);
            }
        }


        return (Error::check("messenger")
            ? array(
                "status"    => 500
                , "error"   => Error::raise("messenger")
                , "exTime"  => $exTime
            )
            : array(
                "status"    => 0
                , "error"   => ""
                , "exTime"  => $exTime
            )
        );
    }

    /**
     * @param string $tel
     * @param null|string $name
     * @return Messenger
     */
    public function addAddress($tel, $name = null)
    {
        if($tel && Validator::isTel($tel)) {
            $name                                           = ($name
                                                                ? $name
                                                                : $tel
                                                            );

            $this->to[$tel] 			                    = $name;
        }

        return $this;
    }

    /**
     * @param null|string $messengerAdapter
     */
    private function setAdapter($messengerAdapter = null) {
        if(!$this->adapter && !$messengerAdapter)           { $messengerAdapter = static::ADAPTER; }

        $this->adapter                                  = new MessengerAdapter($messengerAdapter);
    }
}