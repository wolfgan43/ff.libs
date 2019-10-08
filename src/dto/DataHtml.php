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

namespace phpformsframework\libs\dto;

class DataHtml extends DataAdapter
{
    const CONTENT_TYPE = "text/html";

    /**
     * @param string $key
     * @return string|null
     */
    public function getJs(string $key): ?string
    {
        return (isset($this->js[$key])
            ? $this->js[$key]
            : null
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getCss(string $key): ?string
    {
        return (isset($this->css[$key])
            ? $this->css[$key]
            : null
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getImage(string $key): ?string
    {
        return (isset($this->images[$key])
            ? $this->images[$key]
            : null
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getFont(string $key): ?string
    {
        return (isset($this->fonts[$key])
            ? $this->fonts[$key]
            : null
        );
    }



    /**
     * @return null|string
     */
    public function output()
    {
        return $this->html;
    }
}
