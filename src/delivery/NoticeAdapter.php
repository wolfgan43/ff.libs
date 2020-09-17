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

use phpformsframework\libs\dto\DataError;

/**
 * Class NoticeAdapter
 * @package phpformsframework\libs\delivery
 */
abstract class NoticeAdapter
{
    const ERROR_BUCKET                                      = "delivery";

    protected $recipients                                   = array();
    protected $connection_service                           = null;
    protected $actions                                      = array();

    protected $connection                                   = null;
    protected $fromKey                                      = null;
    protected $fromLabel                                    = null;

    /**
     * NoticeAdapter constructor.
     * @param string|null $connection_service
     */
    public function __construct(string $connection_service = null)
    {
        $this->connection_service                           = $connection_service;
    }

    /**
     * @param string $target
     * @return bool
     */
    abstract public function checkRecipient(string $target) : bool;

    /**
     * @param string $message
     * @return DataError
     */
    abstract public function send(string $message) : DataError;

    /**
     * @param string $title
     * @param null|array $fields
     * @param null|string $template
     * @return DataError
     */
    abstract public function sendLongMessage(string $title, array $fields = null, string $template = null) : DataError;

    /**
     * @return DataError
     */
    abstract protected function process() : DataError;

    /**
     * @param string $key
     * @param string|null $label
     * @return NoticeAdapter
     */
    public function setFrom(string $key, string $label = null) : self
    {
        $this->fromKey                                      = $key;
        $this->fromLabel                                    = $label;

        return $this;
    }

    /**
     * @param array|null $connection
     * @return NoticeAdapter
     */
    public function setConnection(array $connection = null) : self
    {
        $this->connection                                   = $connection;

        return $this;
    }

    /**
     * @param string $name
     * @param string $url
     */
    public function addAction(string $name, string $url) : void
    {
        $this->actions[$url]                                = $name;
    }

    /**
     * @param string $target
     * @param string|null $name
     */
    public function addRecipient(string $target, string $name = null) : void
    {
        if ($this->checkRecipient($target)) {
            $this->recipients[$target]                      = ($name ? $name : $target);
        }
    }
}
