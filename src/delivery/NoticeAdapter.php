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
use ff\libs\Kernel;

/**
 * Class NoticeAdapter
 * @package ff\libs\delivery
 */
abstract class NoticeAdapter
{
    const ERROR_BUCKET                                      = "delivery";

    public const CHANNEL                                    = null;

    protected $recipients                                   = [];
    protected $data                                         = [];
    protected $from                                         = [];

    protected $lang                                         = null;


    /**
     * @param string $target
     * @return bool
     */
    abstract public function checkRecipient(string $target) : bool;

    /**
     * @param array $from
     * @return NoticeAdapter
     */
    public function setFrom(array $from) : self
    {
        $this->from                                         = array_replace(
            ["key" => null, "label" => null],
            $from
        );

        return $this;
    }


    /**
     * @param array $data
     */
    public function setData(array $data) : void
    {
        $this->data                                         = array_replace($this->data, $data);
    }

    /**
     * @param string $name
     * @param string $url
     * @return void
     */
    public function addAction(string $name, string $url) : void
    {
        $this->data["actions"][$name]                       = $url;
    }

    /**
     * @param string $path
     * @param string $name
     */
    public function addImage(string $path, string $name) : void
    {
        $this->data["images"][$path]                        = $name;
    }

    /**
     * @param string $target
     * @param string|null $name
     */
    public function addRecipient(string $target, string $name = null) : void
    {
        if ($this->checkRecipient($target)) {
            $this->recipients[$target]                      = ($name ?: $target);
        }
    }

    /**
     * @param string|null $lang_code
     * @return NoticeAdapter
     */
    public function setLang(string $lang_code = null) : self
    {
        $this->lang                                         = $lang_code;

        return $this;
    }

    /**
     * @param string $message
     * @param string|null $title
     * @return DataError
     */
    public function send(string $message, string $title = null) : DataError
    {
        return $this->getDriver()
            ->send($message, $title);
    }

    /**
     * @return NoticeDriver
     */
    private function getDriver() : NoticeDriver
    {
        $class_name = constant(Kernel::$Environment . "::NOTICE_" . strtoupper(static::CHANNEL) . "_DRIVER");
        $class = __NAMESPACE__ . "\\drivers\\" . $class_name;

        return new $class(static::CHANNEL, $this->recipients, $this->lang, $this->from, $this->data);
    }
}
