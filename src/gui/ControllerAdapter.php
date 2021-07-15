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
namespace phpformsframework\libs\gui;

use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\Mappable;

/**
 * Class ControllerAdapter
 * @package phpformsframework\libs\gui
 */
abstract class ControllerAdapter extends Mappable
{
    /**
     * Assets Method
     */
    public const ASSET_LOCATION_ASYNC           = "Async";
    public const ASSET_LOCATION_DEFER           = "Defer";
    public const ASSET_LOCATION_HEAD            = "Head";
    public const ASSET_LOCATION_BODY_TOP        = "BodyTop";
    public const ASSET_LOCATION_BODY_BOTTOM     = "BodyBottom";

    public const MEDIA_DEVICE_ALL               = "all";
    public const MEDIA_DEVICE_PRINT             = "print";
    public const MEDIA_DEVICE_SCREEN            = "screen";
    public const MEDIA_DEVICE_SPEECH            = "speech";

    /**
     * @param string $tpl_var
     * @param string|DataHtml|View|Controller $content
     * @return $this
     */
    abstract public function assign(string $tpl_var, $content = null) : self;

}
