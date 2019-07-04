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

abstract class NoticeAdapter extends App
{
    const ERROR_BUCKET                                      = "delivery";

    protected $recipients                                   = array();
    protected $connection_service                           = null;
    protected $actions                                      = array();

    protected $connection                                   = null;
    protected $fromKey                                      = null;
    protected $fromLabel                                    = null;

    public function __construct($connection_service = null)
    {
        $this->connection_service                           = $connection_service;
    }

    abstract public function checkRecipient($target);
    abstract public function send($message);
    abstract public function sendLongMessage($title, $fields = null, $template = null);

    abstract protected function process();

    public function setFrom($key, $label = null)
    {
        $this->fromKey                                      = $key;
        $this->fromLabel                                    = $label;

        return $this;
    }

    public function setConnection($connection)
    {
        $this->connection                                   = $connection;

        return $this;
    }

    public function addAction($name, $url)
    {
        $this->actions[$url]                                = $name;
    }
    public function addRecipient($target, $name = null)
    {
        if ($this->checkRecipient($target)) {
            $this->recipients[$target]                      = ($name ? $name : $target);
        }
    }
    protected function getResult()
    {
        return (Error::check(static::ERROR_BUCKET)
            ? Error::raise(static::ERROR_BUCKET)
            : false
        );
    }
}
