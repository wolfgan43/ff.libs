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

use phpformsframework\libs\Dumpable;

/**
 * Class Orm
 * @package phpformsframework\libs\storage
 */
class Orm implements Dumpable
{
    const ERROR_BUCKET                                                                      = "orm";

    private const RESULT                                                                    = Database::RESULT;
    private const INDEX                                                                     = Database::INDEX;
    private const INDEX_PRIMARY                                                             = Database::INDEX_PRIMARY;
    private const RAWDATA                                                                   = Database::RAWDATA;
    private const COUNT                                                                     = Database::COUNT;

    private static $singleton                                                               = array();

    /**
     * @param string $ormModel
     * @param string $mainTable
     * @return OrmModel
     */
    public static function getInstance(string $ormModel, string $mainTable = null) : OrmModel
    {
        return self::setSingleton($ormModel, $mainTable);
    }

    /**
     * @return array
     */
    public static function dump() : array
    {
        return self::$singleton;
    }

    /**
     * @param string $ormModel
     * @param string|null $mainTable
     * @return OrmModel
     */
    private static function setSingleton(string $ormModel, string $mainTable = null) : OrmModel
    {
        if (!isset(self::$singleton[$ormModel])) {
            self::$singleton[$ormModel]                                        = new OrmModel($ormModel, $mainTable);
        }

        return self::$singleton[$ormModel];
    }

}
