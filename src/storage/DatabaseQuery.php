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
 * Class DatabaseQuery
 * @package phpformsframework\libs\storage
 */
class DatabaseQuery
{
    public $action          = null;
    public $key_primary     = null;
    public $options         = array();

    public $from            = null;
    public $select          = null;
    public $sort            = null;
    public $where           = null;
    public $limit           = null;
    public $offset          = null;

    public $update          = null;
    public $insert          = null;

    /**
     * DatabaseQuery constructor.
     * @param string $action
     * @param string $table
     * @param string $key_primary
     * @param array $options
     */
    public function __construct(string $action, string $table, string $key_primary, array $options = array())
    {
        $this->action       = $action;
        $this->from         = $table;
        $this->key_primary  = $key_primary;
        $this->options      = $options;
    }

    /**
     * @return bool
     */
    public function countRecords() : bool
    {
        return $this->limit && $this->offset;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return array_filter(get_object_vars($this));
    }
}
