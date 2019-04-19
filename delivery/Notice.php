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

use phpformsframework\libs\delivery\notice\Adapter;

/*
Notice::getInstance(array("email", "sms", "push"))
    ->addRecipient("wolfgan@gmail.com")
    ->addRecipient("3369874563")
    ->setShortMessage("ciao mondo")
    ->setLongMessage(
        "title"
        , "array message"
        , "template"
    )

    ->setTitle("ciao mondo")
    ->setMessage(array(
            "content" => $mycontent
            , "blabla" => $blabla
        ), "/mytemplate.html"
    )
    ->send();

*/

class Notice
{
    const NAME_SPACE                                        = 'phpformsframework\\libs\\delivery\\notice\\';

    private static $singleton                               = null;

    /**
     * @var Adapter[]
     */
    protected $adapters                                     = null;

    public static function getInstance($noticeAdapters)
	{
		if (self::$singleton === null) {
            self::$singleton                                = new Notice();
        }

		return self::$singleton->setAdapters($noticeAdapters);
	}

    public function addRecipient($target, $name = null) {
        foreach ($this->adapters as $adapter)               { $adapter->addRecipient($target, $name); }

        return $this;
    }
    public function addAction($name, $url) {
        foreach ($this->adapters as $adapter)               { $adapter->addAction($name, $url); }

        return $this;
    }


    public function sendLongMessage($title, $fields, $template = null) {
        $res                                                = null;
        foreach ($this->adapters as $key => $adapter)       { $res[$key] = $adapter->sendLongMessage($title, $fields, $template); }

        return $res;
    }

    public function send($message) {
        $res                                                = null;
        foreach ($this->adapters as $key => $adapter)       { $res[$key] = $adapter->send($message); }

        return $res;
    }


    private function setAdapters($noticeAdapters) {
        if(is_array($noticeAdapters)) {
            foreach($noticeAdapters AS $adapter => $connection) {
                if(is_numeric($adapter) && strlen($connection)) {
                    $adapter                                = $connection;
                    $connection                             = null;
                }

                $class_name                                 = static::NAME_SPACE . ucfirst($adapter);
                $this->adapters[$adapter]                   = new $class_name($connection);
            }
        } elseif($noticeAdapters) {
            $class_name                                     = static::NAME_SPACE . ucfirst($noticeAdapters);
            $this->adapters[$noticeAdapters]                = new $class_name();
        }

        return $this;
    }
}
