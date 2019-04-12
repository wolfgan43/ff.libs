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

use phpformsframework\libs\App;
use phpformsframework\libs\Error;

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


abstract class noticeAdapter extends App {
    protected $recipients                                   = array();
    protected $connection                                   = null;
    protected $actions                                      = null;

    public function __construct($connection = null)
    {
        $this->connection                                   = $connection;
    }

    public abstract function checkRecipient($target);
    public abstract function send($message);
    public abstract function sendLongMessage($title, $fields = null, $template = null);

    protected abstract function process();

    public function addAction($name, $url) {
        $this->actions[$url]                                = $name;
    }
    public function addRecipient($target, $name = null) {
        if($this->checkRecipient($target)) {
            $this->recipients[$target]                      = ($name ? $name : $target);
        }
    }
    protected function getResult() {
        return (Error::check("notice")
            ? Error::raise("notice")
            : false
        );
    }
}

class Notice
{
    const TYPE                                              = "notice";

    private static $singleton                               = null;

    /**
     * @var noticeAdapter[]
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

                $class_name                                 = self::TYPE . ucfirst($adapter);
                $this->adapters[$adapter]                   = new $class_name($connection);
            }
        } elseif($noticeAdapters) {
            $class_name                                     = self::TYPE . ucfirst($noticeAdapters);
            $this->adapters[$noticeAdapters]                = new $class_name();
        }

        return $this;
    }
}
