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
namespace phpformsframework\libs\mock;

use phpformsframework\libs\Kernel;
use stdClass;

/**
 * Trait Mockable
 * @package phpformsframework\libs\mock
 */
trait Mockable
{
    private $mockEnabled        = false;
    protected $mock             = null;

    /**
     * @param string $name
     * @return array
     */
    protected function mock(string $name) : array
    {
        return $this->mock[$name] ?? [];
    }

    /**
     * @param string $name
     * @param string $param
     * @return stdClass
     */
    protected function mockParam(string $name, string $param) : ?string
    {
        $default                = null;
        if ($this->mockEnabled()) {
            $default            = $this->mock($name)->$param ?? null;
        }

        return $default;
    }

    /**
     * @return bool
     */
    private function mockEnabled() : bool
    {
        return $this->mockEnabled ?? Kernel::$Environment::DEBUG;
    }

    /**
     * @param bool $enable
     * @return Mockable
     */
    public function useMock(bool $enable) : self
    {
        $this->mockEnabled      = $enable;

        return $this;
    }
}
