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

use phpformsframework\libs\Constant;
use phpformsframework\libs\Debug;
use phpformsframework\libs\delivery\Notice;
use phpformsframework\libs\Error;
use phpformsframework\libs\Log;
use phpformsframework\libs\Request;
use phpformsframework\libs\security\Validator;

class Messenger
{
    const ERROR_BUCKET                                      = "messenger";
    const NAME_SPACE                                        = Notice::NAME_SPACE;

    const ADAPTER                                           = Constant::MESSENGER_ADAPTER;

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
        if (!self::$singleton) {
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
    public function addAddresses($tels)
    {
        if (is_array($tels)) {
            foreach ($tels as $tel) {
                $this->addAddress($tel);
            }
        }

        return $this;
    }
    public function setConnection($connection)
    {
        if (is_array($connection)) {
            foreach ($connection as $key => $value) {
                if (isset($this->adapter->$key)) {
                    $this->adapter->$key = $value;
                }
            }
        }
        return $this;
    }
    public function setFrom($from, $label = null)
    {
        $this->adapter->from = (
            $label
            ? $label . " (" . $from . ")"
            : $from
        );

        return $this;
    }
    public function send($message = null, $to = null)
    {
        Debug::stopWatch("messenger/send");

        if ($to) {
            $this->addAddress($to);
        }
        if ($message) {
            $this->setMessage($message);
        }

        if (Constant::DEBUG) {
            $this->addAddress($this->adapter->debug);
        }

        $this->adapter->send($this->content, $this->to);

        return $this->getResult(Debug::stopWatch("messenger/send"));
    }

    public function setMessage($content)
    {
        $this->content                                      = $content;

        return $this;
    }

    /**
     * @param float $exTime
     * @return array
     */
    private function getResult($exTime = null)
    {
        if (Error::check(static::ERROR_BUCKET) || Constant::DEBUG) {
            $dump = array(
                "source" => Debug::stackTrace()
                , "URL" => Request::url()
                , "REFERER" => Request::referer()
                , " content" => $this->content
                , " from" => $this->adapter->from
                , " error" => Error::raise(static::ERROR_BUCKET)
                , " exTime" => $exTime
            );
            if (Error::check(static::ERROR_BUCKET)) {
                Log::error($dump);
            } else {
                Log::debugging($dump);
            }
        }


        return (Error::check(static::ERROR_BUCKET)
            ? array(
                "status"    => 500
                , "error"   => Error::raise(static::ERROR_BUCKET)
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
        if ($tel && Validator::isTel($tel)) {
            $name                                           = (
                $name
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
    private function setAdapter($messengerAdapter = null)
    {
        if (!$this->adapter && !$messengerAdapter) {
            $messengerAdapter = static::ADAPTER;
        }

        $className                                          = self::NAME_SPACE . "Messenger" . ucfirst($messengerAdapter);

        $this->adapter                                      = new $className();
    }
}
