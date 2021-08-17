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
namespace phpformsframework\libs\storage;

use phpformsframework\libs\storage\dto\OrmDef;
use phpformsframework\libs\Exception;
use phpformsframework\libs\storage\dto\Schema;
use phpformsframework\libs\util\Convert;

/**
 * Class DatabaseConverter
 * @package phpformsframework\libs\storage
 */
class DatabaseConverter
{
    /**
     * @var OrmDef
     */
    private $def                        = null;

    private $to                         = [];
    private $in                         = [];

    /**
     * @param OrmDef $def
     * @param Schema|null $schema
     * @throws Exception
     */
    public function __construct(OrmDef $def, Schema $schema = null)
    {
        $this->def                      = $def;
        if ($schema) {
            foreach ($schema->onRead as $field => $hook) {
                $this->add($this->to, $field, $hook, $schema->properties[$field]);
            }
            foreach ($schema->onWrite as $field => $hook) {
                $this->add($this->in, $field, $hook, $schema->properties[$field]);
            }
            foreach ($schema->columns as $field) {
                $type = $schema->dtd[$field];

                if (isset($schema->to[$type])) {
                    $this->add($this->to, $field, $schema->to[$type]["callback"] ?? $type, array_replace($schema->to[$type], $schema->properties[$field]));
                }
                if (isset($schema->in[$type])) {
                    $this->add($this->in, $field, $schema->in[$type]["callback"] ?? $type, array_replace($schema->in[$type], $schema->properties[$field]));
                }
            }
        }
    }

    /**
     * @param string $field_output
     * @param string|null $field_db
     * @return string
     * @throws Exception
     */
    public function set(string &$field_output, string $field_db = null) : string
    {
        $casts                                      = $this->converterCasts($field_output);
        if (!$field_db) {
            $field_db                               = $field_output;
        }

        $this->converterCallback($casts, $field_db, $field_output);

        return $this->converterStruct($field_db, $field_output);
    }

    /**
     * @todo da tipizzare
     * @param string $name
     * @param mixed|null $value
     * @return string
     */
    public function in(string $name, $value = null): string
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
        foreach ($this->to as $field => $params) {
            $record[$field] = ($params->callback)($record[$field], $params->properties);
        }

        return $record;
    }

    /**
     * @return bool
     */
    public function issetTo() : bool
    {
        return !empty($this->to);
    }



    /*********************+
     * SET
     */

    /**
     * @param string $subject
     * @return array|null
     */
    private function converterCasts(string &$subject) : ?array
    {
        $casts                                      = null;
        if (strpos($subject, ":") !== false) {
            $casts                                  = explode(":", $subject);
            $subject                                = array_shift($casts);
        }

        return $casts;
    }

    /**
     * @param string $field_db
     * @param string|null $field_output
     * @return string
     * @throws Exception
     */
    private function converterStruct(string $field_db, string $field_output = null) : string
    {
        $struct_type                                = $this->getStructField($field_db);
        $casts                                      = $this->converterCasts($struct_type);

        $this->converterCallback($casts, $field_db, $field_output);

        return $struct_type;
    }

    /**
     * @param array|null $casts
     * @param string|null $field_db
     * @param string|null $field_output
     * @throws Exception
     */
    private function converterCallback(array $casts = null, string $field_db = null, string $field_output  = null) : void
    {
        if ($casts) {
            foreach ($casts as $cast) {
                $params                                 = [];
                $op                                     = strtolower(substr($cast, 0, 2));
                if (strpos($cast, "(") !== false) {
                    $func = explode("(", $cast, 2);
                    $cast = $func[0];
                    $params = explode(",", rtrim($func[1], ")"));
                }

                if ($op === "to" && $field_output) {
                    $this->add($this->to, $field_output, substr($cast, 2), $params);
                } elseif ($op === "in" && $field_db) {
                    $this->add($this->in, $field_db, substr($cast, 2), $params);
                } else {
                    throw new Exception($cast . " is not a valid function", 500);
                }
            }
        }
    }

    /**
     * @param array $ref
     * @param string $field
     * @param string $func
     * @param array|null $params
     * @throws Exception
     */
    private function add(array &$ref, string $field, string $func, array $params = null) : void
    {
        if (!isset($ref[$field])) {
            if (is_callable($func)) {
                $callback       = $func;
            } elseif (is_callable(Convert::class . "::" . $func)) {
                $callback       = Convert::class . "::" . $func;
            } else {
                throw new Exception("Function " . $func . " not implemented for " . __CLASS__, "501");
            }

            $params["dbType"]   = $this->def->struct[$field];
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
