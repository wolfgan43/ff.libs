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
use Exception;

/**
 * Class MessengerAdapter
 * @package ff\libs\delivery\drivers
 */
abstract class NoticeDriver
{
    protected const PREFIX                                  = null;

    protected $channel                                      = "";
    protected $lang                                         = null;
    protected $recipients                                   = [];
    protected $from                                         = null;
    protected $images                                       = [];
    protected $actions                                      = [];

    /**
     * @param string $message
     * @param string|null $title
     * @return DataError
     */
    abstract public function send(string $message, string $title = null) : DataError;

    /**
     * @param string $channel
     * @param array $recipients
     * @param string $lang
     * @param array $from
     * @param array $images
     * @param array $actions
     * @throws Exception
     */
    public function __construct(string $channel, array $recipients, string $lang, array $from, array $images = [], array $actions = [])
    {
        $this->channel                                  = strtoupper($channel);
        $this->lang                                     = $lang;
        $this->recipients                               = $recipients;
        $this->from                                     = (object) $from;
        $this->images                                   = $images;
        $this->actions                                  = $actions;

        $this->setConnection();
    }

    protected function prefix() : string
    {
        return Kernel::$Environment . '::' . (
            !empty(static::PREFIX)
            ? static::PREFIX . "_" . $this->channel ."_"
            : $this->channel ."_"
        );
    }

    /**
     * @throws Exception
     */
    protected function setConnection() : void
    {
        $prefix                                         = $this->prefix();
        foreach (get_object_vars($this) as $property => $value) {
            if ($value !== null) {
                continue;
            }
            $config = $prefix . strtoupper($property);
            if (!defined($config)) {
                throw new Exception($config . " Missing", 500);
            }
            $this->$property = constant($config);
        }
    }
}
