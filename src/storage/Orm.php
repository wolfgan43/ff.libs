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
use phpformsframework\libs\storage\dto\OrmResults;
/**
 * Class Orm
 * @package phpformsframework\libs\storage
 */
class Orm implements Dumpable
{
    private static $singleton                                                               = array();

    /**
     * @param string $ormModel
     * @param string|null $mainTable
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
     * @return array
     */
    public static function exTime() : array
    {
        return OrmModel::$exTime;
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

    /**
     * @param string $name
     * @param string|null $map_class
     * @return Orm
     */
    public static function table(string $name, string $map_class = null) : self
    {
        return new Orm($name, $map_class);
    }

    private $table          = null;
    private $map_class      = null;

    /**
     * Orm constructor.
     * @param string $table
     * @param string $map_class
     */
    public function __construct(string $table, string $map_class = null)
    {
        $this->table        = $table;
        $this->map_class    = $map_class;
    }

    /**
     * @param array|null $select
     * @param array|null $where
     * @param array|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @return OrmResults
     */
    public function read(array $select = null, array $where = null, array $sort = null, int $limit = null, int $offset = null) : OrmResults
    {
        return (new OrmResults(Orm::getInstance($this->table)->read($select, $where, $sort, $limit, $offset)))
            ->setMap($this->map_class);
    }
}
