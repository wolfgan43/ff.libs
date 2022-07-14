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
namespace ff\libs\delivery;

use ff\libs\dto\DataError;

/**
 * Class NoticeAdapter
 * @package ff\libs\delivery
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
    protected $lang                                         = null;

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
     * @param string $title
     * @param string|null $message
     * @return DataError
     */
    abstract public function send(string $title, string $message = null) : DataError;

    /**
     * @param string $title
     * @param array|null $fields
     * @param string|null $template
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

    /**
     * @param string|null $lang_code
     * @return NoticeAdapter
     */
    public function setLang(string $lang_code = null) : self
    {
        $this->lang                                      = $lang_code;

        return $this;
    }
}
