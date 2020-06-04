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

namespace phpformsframework\libs\storage;

/**
 * Interface Constant
 * @package phpformsframework\libs\storage
 */
interface Constant
{
    public const ERROR_BUCKET                                               = "database";

    public const FTYPE_ARRAY                                                = "array";
    public const FTYPE_ARRAY_INCREMENTAL                                    = "arrayIncremental";
    public const FTYPE_ARRAY_OF_NUMBER                                      = "arrayOfNumber";
    public const FTYPE_BOOLEAN                                              = "boolean";
    public const FTYPE_BOOL                                                 = "bool";
    public const FTYPE_DATE                                                 = "date";
    public const FTYPE_NUMBER                                               = "number";
    public const FTYPE_TIMESTAMP                                            = "timestamp";
    public const FTYPE_PRIMARY                                              = "primary";
    public const FTYPE_STRING                                               = "string";
    public const FTYPE_CHAR                                                 = "char";
    public const FTYPE_TEXT                                                 = "text";

    public const FTYPE_ARRAY_JSON                                           = "json";
    public const FTYPE_NUMBER_BIG                                           = "bigint";
    public const FTYPE_NUMBER_FLOAT                                         = "float";

    public const FTYPE_NUMBER_DECIMAN                                       = "currency";

    public const FTYPE_BLOB                                                 = "blob";

    public const FTYPE_TIME                                                 = "time";
    public const FTYPE_DATE_TIME                                            = "datetime";

    public const FTYPE_OBJECT                                               = "object";


    public const ACTION_READ                                                = "read";
    public const ACTION_DELETE                                              = "delete";
    public const ACTION_INSERT                                              = "insert";
    public const ACTION_UPDATE                                              = "update";
    public const ACTION_CMD                                                 = "cmd";
    public const ACTION_WRITE                                               = "write";

    public const CMD_COUNT                                                  = "count";
    public const CMD_PROCESS_LIST                                           = "processlist";

}