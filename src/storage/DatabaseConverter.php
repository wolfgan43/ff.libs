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
namespace ff\libs\storage;

use ff\libs\storage\dto\OrmDef;
use ff\libs\Exception;
use ff\libs\storage\dto\Schema;
use ff\libs\util\Convert;

/**
 * Class DatabaseConverter
 * @package ff\libs\storage
 */
class DatabaseConverter
{
    /**
     * @var OrmDef
     */
    private $def                        = null;
    private $to                         = [];
    private $in                         = [];
    private $replace                    = null;
    private $prototype                  = [];
    private $fields                     = [];

    /**
     * @param OrmDef $def
     * @param Schema|null $schema
     * @throws Exception
     */
    public function __construct(OrmDef $def, Schema $schema = null)
    {
        $this->def                      = $def;
        if ($schema) {
            $this->replace              = $schema->replace;
            $this->prototype            = $schema->prototype;

            foreach ($schema->onRead as $field => $hook) {
                $this->add($this->to, $field, $this->getStructField($this->prototype[$field] ?? $field), $hook, $schema->properties[$field]);
            }
            foreach ($schema->onWrite as $field => $hook) {
                $this->add($this->in, $field, $this->getStructField($this->prototype[$field] ?? $field), $hook, $schema->properties[$field]);
            }
            foreach ($schema->columns as $field) {
                $type = $schema->dtd[$field];

                if (isset($schema->to[$type])) {
                    $this->add($this->to, $field, $this->getStructField($this->prototype[$field] ?? $field), $schema->to[$type]["callback"] ?? $type, array_replace($schema->to[$type], $schema->properties[$field] ?? []));
                }
                if (isset($schema->in[$type])) {
                    $this->add($this->in, $field, $this->getStructField($this->prototype[$field] ?? $field), $schema->in[$type]["callback"] ?? $type, array_replace($schema->in[$type], $schema->properties[$field] ?? []));
                }
            }
        }
    }

    /**
     * @param array $fields
     * @param bool $sort
     * @return $this
     */
    public function fields(array $fields, bool $sort = false) : self
    {
        $this->fields = (
            empty($this->prototype)
            ? $fields
            : array_intersect($this->prototype, $fields)
        );
        if ($sort) {
            ksort($this->fields);
        }

        return $this;
    }

    /**
     * @param string $field_output
     * @param string|null $field_db
     * @return string
     * @throws Exception
     */
    public function set(string &$field_output, string $field_db = null) : string
    {
        $struct_type                                = $this->getStructField($field_db ?? $field_output);
        $this->cast($struct_type, $field_output, $field_db);

        return $struct_type;
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @param mixed|null $value
     * @return string|array
     */
    public function in(string $name, $value = null)
    {
        if (!empty($value) && isset($this->in[$name])) {
            $value = ($this->in[$name]->callback)($value);
        }

        return $value;
    }

    /**
     * @param array $record
     * @return array
     */
    public function to(array $record) : array
    {
        $res = [];
        foreach ($this->fields as $keyfield => $dbField) {
            $res[$keyfield] = $record[$dbField] ?? null;

            if (isset($this->replace[$keyfield]) && isset($this->replace[$keyfield][$res[$keyfield]])) {
                $res[$keyfield] = $this->replace[$keyfield][$res[$keyfield]];
            }

            if (isset($this->to[$keyfield]) && $res[$keyfield] !== null) {
                $res[$keyfield] = ($this->to[$keyfield]->callback)($res[$keyfield], $this->to[$keyfield]->properties);
            }
        }

        $this->def->hook()->handle("onRead", $res, $record);

        return $res;
    }

    /**
     * @return bool
     */
    public function issetTo() : bool
    {
        return !empty($this->to);
    }

    /**
     * @param string $subject
     * @return array
     */
    private function converterCasts(string &$subject) : array
    {
        $casts                                      = [];
        if (strpos($subject, ":") !== false) {
            $casts                                  = explode(":", $subject);
            $subject                                = array_shift($casts);
        }

        return $casts;
    }

    /**
     * @param string $struct_type
     * @param string $field_output
     * @param string|null $field_db
     * @throws Exception
     */
    private function cast(string &$struct_type, string &$field_output, string $field_db = null) : void
    {
        $casts                                      = array_merge($this->converterCasts($field_output), $this->converterCasts($struct_type));
        if (!empty($casts)) {
            foreach ($casts as $cast) {
                $params                             = [];
                $op                                 = strtolower(substr($cast, 0, 2));
                if (strpos($cast, "(") !== false) {
                    $func = explode("(", $cast, 2);
                    $cast = $func[0];
                    $params = explode(",", rtrim($func[1], ")"));
                }

                if ($op === "to" && !empty($field_output)) {
                    $this->add($this->to, $field_output, $struct_type, substr($cast, 2), $params);
                } elseif ($op === "in" && !empty($field_db ?? $field_output)) {
                    $this->add($this->in, $field_db ?? $field_output, $struct_type, substr($cast, 2), $params);
                } else {
                    throw new Exception($cast . " is not a valid function", 500);
                }
            }
        }
    }

    /**
     * @param array $ref
     * @param string $field
     * @param string $dbType
     * @param string $func
     * @param array|null $params
     * @throws Exception
     */
    private function add(array &$ref, string $field, string $dbType, string $func, array $params = null) : void
    {
        if (!isset($ref[$field])) {
            if (is_callable(Convert::class . "::" . $func)) {
                $callback       = Convert::class . "::" . $func;
            } elseif (is_callable($func)) {
                $callback       = $func;
            } else {
                throw new Exception("Function " . $func . " not implemented for " . __CLASS__, "501");
            }

            $params["dbType"]   = $dbType;
            $ref[$field]        = (object) [
                "callback"      => $callback,
                "properties"    => (object) $params
            ];
        }
    }
    /**
     * @param string $key
     * @return string
     * @throws Exception
     */
    private function getStructField(string $key) : string
    {
        if (!isset($this->def->struct[$key])) {
            throw new Exception("Field: '" . $key . "' not found in struct on table: " . $this->def->table["name"], 500);
        }

        return $this->def->struct[$key];
    }
}
