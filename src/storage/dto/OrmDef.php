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
namespace ff\libs\storage\dto;

use ff\libs\Exception;
use ff\libs\Hook;
use ff\libs\storage\Database;

/**
 * Class OrmDef
 * @package ff\libs\storage\dto
 */
class OrmDef
{
    public $mainTable       = null;
    public $table           = array();
    public $struct          = array();
    public $indexes         = array();
    public $relationship    = array();
    public $key_primary     = null;
    public $hook            = null;

    /**
     * OrmDef constructor.
     * @param string $main_table
     */
    public function __construct(string $main_table)
    {
        $this->mainTable   = $main_table;
    }

    /**
     * @throws Exception
     */
    public function setKeyPrimary()
    {
        $this->key_primary  = (string) array_search(Database::FTYPE_PRIMARY, $this->struct);
        if (empty($this->key_primary)) {
            throw new Exception("primary field not set in orm.map: " . $this->table["name"], 500);
        }
    }

    /**
     * @return Hook
     */
    public function hook() : Hook
    {
        return $this->hook ?? new Hook();
    }
}
