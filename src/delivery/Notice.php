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
namespace phpformsframework\libs\delivery;

use phpformsframework\libs\Debug;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\util\AdapterManager;

/**
 * Class Notice
 * @package phpformsframework\libs\delivery
 * @property NoticeAdapter[] $adapters
 */
class Notice
{
    use AdapterManager;

    protected const ERROR_BUCKET                            = "delivery";

    private static $singleton                               = null;
    private static $exTime                                  = null;

    /**
     * @return float
     */
    public static function exTime() : ?float
    {
        return self::$exTime;
    }

    /**
     * @param string $noticeAdapter
     * @return Notice
     */
    public static function getInstance(string $noticeAdapter) : self
    {
        if (self::$singleton === null) {
            self::$singleton                                = new Notice($noticeAdapter);
        }
        return self::$singleton;
    }

    /**
     * Messenger constructor.
     * @param string $noticeAdapter
     */
    public function __construct(string $noticeAdapter)
    {
        $this->setAdapter($noticeAdapter);
    }
    /**
     * @param string $target
     * @param string|null $name
     * @return Notice
     */
    public function addRecipient(string $target, string $name = null) : self
    {
        foreach ($this->adapters as $adapter) {
            $adapter->addRecipient($target, $name);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $url
     * @return Notice
     */
    public function addAction(string $name, string $url) : self
    {
        foreach ($this->adapters as $adapter) {
            $adapter->addAction($name, $url);
        }

        return $this;
    }


    /**
     * @param string $title
     * @param array $fields
     * @param null|string $template
     * @return DataResponse
     */
    public function sendLongMessage(string $title, array $fields, string $template = null): DataResponse
    {
        Debug::stopWatch(static::ERROR_BUCKET);

        $dataResponse                                       = new DataResponse();
        foreach ($this->adapters as $key => $adapter) {
            $result                                         = $adapter->sendLongMessage($title, $fields, $template);
            $dataResponse->set($key, $result->isError());
            if ($result->isError()) {
                $dataResponse->error($result->status, $result->error);
            }
        }

        self::$exTime = Debug::stopWatch(static::ERROR_BUCKET);

        return $dataResponse;
    }

    /**
     * @param string $message
     * @return DataResponse
     */
    public function send(string $message) : DataResponse
    {
        Debug::stopWatch(static::ERROR_BUCKET);

        $dataResponse                                       = new DataResponse();
        foreach ($this->adapters as $key => $adapter) {
            $result                                         = $adapter->send($message);
            $dataResponse->set($key, $result->isError());
            if ($result->isError()) {
                $dataResponse->error($result->status, $result->error);
            }
        }

        self::$exTime = Debug::stopWatch(static::ERROR_BUCKET);

        return $dataResponse;
    }



}
