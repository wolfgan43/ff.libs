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
 * Class OrmQuery
 * @package phpformsframework\libs\storage\dto
 */
class OrmQuery
{
    private const OR            = '$or';
    public $service             = null;
    public $where               = null;
    public $select              = null;
    public $sort                = null;
    public $insert              = null;
    public $set                 = null;

    public $runned              = false;
    /**
     * @var OrmDef
     */
    public $def                 = null;
    public $table               = null;

    /**
     * @param string|null $controller
     * @return string|null
     */
    public function getController(string $controller = null) : ?string
    {
        return ($this->service
            ? $this->service
            : $controller
        );
    }

    /**
     * @todo da tipizzare
     * @param string $key
     * @param string $name
     * @param string $value
     * @param bool $is_or
     */
    public function set(string $key, string $name, $value, bool $is_or = false) : void
    {
        if ($is_or) {
            $this->setOr($key, $name, $value);
        } else {
            $this->$key[$name] = $value;
        }
    }

    /**
     * @todo da tipizzare
     * @param string $key
     * @param string $name
     * @param mixed $value
     */
    private function setOr(string $key, string $name, $value)
    {
        $this->$key[self::OR][$name] = $value;

    }

    /**
     * @param bool $save
     * @return array
     */
    public function getAllFields(bool $save = false) : array
    {
        $diff = array_keys($this->def->struct);
        $res = array_combine($diff, $diff);

        if ($save) {
            $this->select = $res;
        }

        return $res;
    }

    /**
     * @param bool $fill_select
     * @return array
     */
    public function select(bool $fill_select = false) : array
    {
        return ($fill_select && empty($this->select)
            ? self::getAllFields()
            : $this->select
        );
    }

    /**
     * @param bool $skip
     */
    public function setControl(bool $skip = false) : void
    {
        $this->def->table["skip_control"] = $skip;
    }

    /**
     * @return bool
     */
    public function uniqueIndex() : bool
    {
        return !empty($this->def->indexes) && array_search("unique", $this->def->indexes) !== false;
    }
}
