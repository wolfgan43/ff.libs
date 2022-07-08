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
namespace ff\libs\dto;

use ff\libs\Debug;
use ff\libs\Exception;
use stdClass;

/**
 * Class DataAdapter
 * @package ff\libs\dto
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
    public function toArray() : array
    {
        return $this->getObjectVars(true);
    }
    /**
     * @return stdClass|array|null
     */
    public function toObject() /*: ?stdClass con array sequenziali da errore */
    {
        $vars                               = $this->toArray();
        return (!empty($vars)
            ? (object) $vars
            : null
        );
    }

    /**
     * @param bool $diableDebug
     * @return string
     * @throws Exception
     */
    public function toJson(bool $diableDebug = false) : string
    {
        $vars                               = $this->getVars();
        if (!$diableDebug) {
            $this->setDebugger($vars);
        }

        if (($res = json_encode($vars)) === false) {
            if (isset($vars["debug"])) {
                $vars["debug"]              = "json encode failed";
            }
            if (($res = json_encode($vars)) === false) {
                $res                        = json_encode(["Error" => "json encode failed"]);
            }
        }

        return $res;
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
        foreach ($this->getObjectVars(true) as $key => $value) {
            unset($this->$key);
        }

        return $this;
    }
    /**
     * @param array $values
     * @return $this
     */
    public function filter(array $values) : self
    {
        foreach ($this->getObjectVars(true) as $key => $value) {
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
        return $this->$key ?? null;
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

    /**
     * @return array
     */
    protected function getVars() : array
    {
        return $this->getObjectVars();
    }
    /**
     * @param bool $remove_exception
     * @return array
     */
    private function getObjectVars(bool $remove_exception = false) : array
    {
        $vars                               = get_object_vars($this);
        if ($remove_exception) {
            $this->removeExceptionVars($vars);
        }
        return $vars;
    }

    /**
     * @param array $vars
     */
    private function setDebugger(array &$vars) : void
    {
        if ($debug = Debug::get()) {
            $vars["debug"] = $debug;
        }
    }
}
