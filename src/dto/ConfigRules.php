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
namespace ff\libs\dto;

use ff\libs\Configurable;

/**
 * Class ConfigRules
 * @package ff\libs\dto
 */
class ConfigRules
{
    private $context            = null;
    private $data               = array();

    /**
     * ConfigRules constructor.
     * @param $context
     */
    public function __construct($context)
    {
        $this->context          = $context;
    }

    /**
     * @param string $bucket
     * @param int $method
     * @return ConfigRules
     */
    public function add(string $bucket, int $method = Configurable::METHOD_MERGE) : ConfigRules
    {
        $this->data[$bucket]    = [
                                    "method"     => $method,
                                    "context"   => $this->context
                                ];
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->data;
    }
}
