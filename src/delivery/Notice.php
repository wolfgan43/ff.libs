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

use phpformsframework\libs\dto\DataResponse;

/**
 * Class Notice
 * @package phpformsframework\libs\delivery
 */
class Notice
{
    const NAME_SPACE                                        = __NAMESPACE__ . '\\adapters\\';

    private static $singleton                               = null;

    /**
     * @var NoticeAdapter[]
     */
    protected $adapters                                     = null;

    /**
     * @param string $noticeAdapters
     * @return Notice
     */
    public static function getInstance(string $noticeAdapters) : self
    {
        if (self::$singleton === null) {
            self::$singleton                                = new Notice();
        }

        return self::$singleton->setAdapters($noticeAdapters);
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
        $dataResponse                                       = new DataResponse();
        foreach ($this->adapters as $key => $adapter) {
            $result                                         = $adapter->sendLongMessage($title, $fields, $template);
            $dataResponse->set($key, $result->isError());
            if ($result->isError()) {
                $dataResponse->error(502, $result->error);
            }
        }

        return $dataResponse;
    }

    /**
     * @param string $message
     * @return DataResponse
     */
    public function send(string $message) : DataResponse
    {
        $dataResponse                                       = new DataResponse();
        foreach ($this->adapters as $key => $adapter) {
            $result                                         = $adapter->send($message);
            $dataResponse->set($key, $result->isError());
            if ($result->isError()) {
                $dataResponse->error(502, $result->error);
            }
        }

        return $dataResponse;
    }


    private function setAdapters($noticeAdapters)
    {
        $name_space                                         = static::NAME_SPACE . "Notice";
        if (is_array($noticeAdapters)) {
            foreach ($noticeAdapters as $adapter => $connection) {
                if (is_numeric($adapter) && strlen($connection)) {
                    $adapter                                = $connection;
                    $connection                             = null;
                }

                $class_name                                 = $name_space . ucfirst($adapter);
                $this->adapters[$adapter]                   = new $class_name($connection);
            }
        } elseif ($noticeAdapters) {
            $class_name                                     = $name_space . ucfirst($noticeAdapters);
            $this->adapters[$noticeAdapters]                = new $class_name();
        }

        return $this;
    }
}
