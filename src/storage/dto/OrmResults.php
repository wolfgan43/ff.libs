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

namespace phpformsframework\libs\storage\dto;

/**
 * Class OrmResult
 * @package phpformsframework\libs\storage\dto
 */
class OrmResults
{
    private $map_class  = null;
    private $recordset  = array();
    private $count      = null;

    /**
     * OrmResult constructor.
     * @param array|null $recordset
     * @param int|null $count
     */
    public function __construct(array $recordset = null, int $count = null)
    {
        $this->recordset    = (array) $recordset;
        $this->count        = $count;
    }

    /**
     * @param string $map_class
     * @return OrmResults
     */
    public function setMap(string $map_class = null) : self
    {
        $this->map_class    = $map_class;
        return $this;
    }
    /**
     * @return int|null
     */
    public function count() : ?int
    {
        return $this->count;
    }

    /**
     * @return object|null
     */
    public function first() : ?object
    {
        return $this->seek(0);
    }

    /**
     * @return array|null
     */
    public function toArray() : ?array
    {
        return $this->recordset;
    }

    /**
     * @param int $offset
     * @return null
     */
    public function seek(int $offset)
    {
        return (isset($this->recordset[$offset])
            ? $this->getRecord($this->recordset[$offset])
            : null
        );
    }

    /**
     * @param array $record
     * @return object
     */
    private function getRecord(array $record) : object
    {
        return ($this->map_class
            ? new $this->map_class($record)
            : json_decode(json_encode($record))
        );
    }
}
