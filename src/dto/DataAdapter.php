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

use phpformsframework\libs\Debug;
use stdClass;

/**
 * Class DataAdapter
 * @package phpformsframework\libs\dto
 */
abstract class DataAdapter
{
    use Exceptionable;

    const CONTENT_TYPE                      = null;



    abstract public function output();

    /**
     * DataAdapter constructor.
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->fill($data);
    }

    /**
     * @return array
     */
    protected function getObjectVars() : array
    {
        return get_object_vars($this);
    }
    /**
     * @return array
     */
    private function getVars() : array
    {
        $vars                               = $this->getObjectVars();
        $this->setDebugger($vars);

        return $vars;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->getVars();
    }
    /**
     * @return stdClass|null
     */
    public function toObject() : ?stdClass
    {
        $vars                               = $this->getObjectVars();
        unset($vars["status"]);
        unset($vars["error"]);
        unset($vars["debug"]);

        return (count($vars)
            ? (object) $vars
            : null
        );
    }
    /**
     * @return string
     */
    public function toJson() : string
    {
        return (string) json_encode($this->getVars());
    }





    /**
     * @param array $values
     * @return $this
     */
    public function fill(array $values) : self
    {
        foreach ($values as $key => $value) {
            $this->$key                     = $value;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clear() : self
    {
        foreach ($this->getVars() as $key => $value) {
            $this->$key                     = $value;
        }

        return $this;
    }
    /**
     * @param array $values
     * @return $this
     */
    public function filter(array $values) : self
    {
        $vars                               = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (isset($values[$key])) {
                unset($this->$key);
            }
        }

        return $this;
    }

    /**
     * @todo da tipizzare
     * @param string $key
     * @param string|array $value
     * @return $this
     */
    public function set(string $key, $value) : self
    {
        $this->$key                         = $value;

        return $this;
    }

    /**
     * @todo da tipizzare
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        return (isset($this->$key)
            ? $this->$key
            : null
        );
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isset(string $key) : bool
    {
        return !empty($this->$key);
    }

    /**
     * @param string $key
     * @return $this
     */
    public function unset(string $key) : self
    {
        unset($this->$key);

        return $this;
    }
}
