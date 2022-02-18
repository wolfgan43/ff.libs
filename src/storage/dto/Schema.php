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
namespace phpformsframework\libs\storage\dto;

use phpformsframework\libs\dto\Mapping;

/**
 * Class Schema
 * @package phpformsframework\libs\storage\dto
 */
class Schema
{
    use Mapping;

    /**
    TYPE = [
        "markdown",
        "select",
        "list",
        "check",
        "radio",
        "hex",
        "date",
        "datetime",
        "email",
        "upload",
        "image",
        "month",
        "int",
        "double",
        "currency",
        "password",
        "range",
        "reset",
        "search",
        "tel",
        "string",
        "time",
        "url",
        "week",
        "video",
        "audio",
        "text",
        "readonly",
        "bool",
    ];
     */
    private const DEFAULT_TYPE = "string";

    public $collection      = null;
    public $table           = null;
    public $mapclass        = null;
    public $id              = null;
    public $columns         = null;
    public $dtd             = null;
    public $prototype       = [];
    public $mock            = null;
    public $read            = null;
    public $insert          = null;
    public $properties      = null;
    public $replace         = null;

    /**
     * @var callable[]
     */
    public $onRead          = [];
    /**
     * @var callable[]
     */
    public $onWrite         = [];

    public $to              = [];
    public $in              = [];

    public function __construct(array $schema)
    {
        $this->autoMapping($schema);
    }
}
