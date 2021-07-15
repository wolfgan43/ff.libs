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
namespace phpformsframework\libs\delivery\drivers;

use phpformsframework\libs\Debug;
use phpformsframework\libs\dto\DataError;
use phpformsframework\libs\Exception;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Log;
use phpformsframework\libs\security\Validator;
use phpformsframework\libs\util\AdapterManager;

/**
 * Class Messenger
 * @package phpformsframework\libs\delivery\drivers
 * @property MessengerAdapter $adapter
 */
class Messenger
{
    use AdapterManager;

    const ERROR_BUCKET                                      = "messenger";

    private $to                                             = null;
    private $content                                        = null;

    /**
     * @var Messenger
     */
    private static $singleton                               = null;

    /**
     * @param null|string $messengerAdapter
     * @return Messenger
     */
    public static function getInstance(string $messengerAdapter = null) : self
    {
        if (!self::$singleton) {
            self::$singleton = new Messenger($messengerAdapter);
        }

        return self::$singleton;
    }

    /**
     * Messenger constructor.
     * @param string|null $messengerAdapter
     */
    public function __construct(string $messengerAdapter = null)
    {
        $this->setAdapter($messengerAdapter ?? Kernel::$Environment::MESSENGER_ADAPTER);
    }

    /**
     * @param array[email => name] $emails
     * @param string[to|cc|bcc] $type
     * @return Messenger
     */
    public function addAddresses(array $tels) : self
    {
        if (is_array($tels)) {
            foreach ($tels as $tel) {
                $this->addAddress($tel);
            }
        }

        return $this;
    }

    /**
     * @param array|null $connection
     * @return Messenger
     */
    public function setConnection(array $connection = null) : self
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

    /**
     * @param string|null $from
     * @param string|null $label
     * @return Messenger
     */
    public function setFrom(string $from = null, string $label = null) : self
    {
        if ($from) {
            $this->adapter->from = (
                $label
                ? $label . " (" . $from . ")"
                : $from
            );
        }

        return $this;
    }

    /**
     * @param string|null $message
     * @param string|null $to
     * @return DataError
     */
    public function send(string $message = null, string $to = null) : DataError
    {
        Debug::stopWatch("messenger/send");

        if ($to) {
            $this->addAddress($to);
        }
        if ($message) {
            $this->setMessage($message);
        }

        if (Kernel::$Environment::DEBUG && $this->adapter->debug) {
            $this->addAddress($this->adapter->debug);
        }

        $this->adapter->send($this->content, $this->to);

        Debug::stopWatch("messenger/send");

        return $this->getResult();
    }

    /**
     * @param string $content
     * @return Messenger
     */
    public function setMessage(string $content) : self
    {
        $this->content                                      = $content;

        return $this;
    }

    /**
     * @return DataError
     */
    private function getResult() : DataError
    {
        $dataError                                          = new DataError();
        $error                                              = Exception::raise(static::ERROR_BUCKET);
        if ($error || Kernel::$Environment::DEBUG) {
            $dump = array(
                "source"        => Debug::stackTrace(),
                "content"       => $this->content,
                "from"          => $this->adapter->from,
                "error"         => $error,
                "exTime"        => Debug::exTime("messenger/send")
            );
            if ($error) {
                Log::error($dump, static::ERROR_BUCKET);
            } else {
                Log::debugging($dump, static::ERROR_BUCKET);
            }
        }

        if ($error) {
            $dataError->error(502, $error);
        }


        return $dataError;
    }

    /**
     * @param string $tel
     * @param null|string $name
     * @return Messenger
     */
    public function addAddress(string $tel, string $name = null) : self
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


}
