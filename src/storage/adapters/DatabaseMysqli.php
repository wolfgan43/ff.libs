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
namespace phpformsframework\libs\storage\adapters;

use phpformsframework\libs\storage\DatabaseAdapter;
use phpformsframework\libs\storage\drivers\MySqli as sql;
use phpformsframework\libs\Exception;

/**
 * Class DatabaseMysqli
 * @package phpformsframework\libs\storage\adapters
 */
class DatabaseMysqli extends DatabaseAdapter
{
    private const OPERATOR_COMPARISON_NULL = [
        null            => 'NULL',
        '$eq'           => '`NAME` IS NULL',
        '$ne'           => '`NAME` IS NOT NULL',
        '$set'          => '`NAME` = NULL'
    ];

    private const OPERATOR_COMPARISON   = [
        '$gt'           => '`NAME` > `VALUE`',
        '$gte'          => '`NAME` >= `VALUE`',
        '$lt'           => '`NAME` < `VALUE`',
        '$lte'          => '`NAME` <= `VALUE`',
        '$eq'           => '`NAME` = `VALUE`',
        '$regex'        => '`NAME` LIKE `*VALUE*`',
        '$in'           => '`NAME` IN(`VALUE`)',
        '$nin'          => '`NAME` NOT IN(`VALUE`)',
        '$ne'           => '`NAME` != `VALUE`',
        '$inset'        => 'FIND_IN_SET(`NAME`, `VALUE`)',
        '$inc'          => '`NAME` = `NAME` + 1',
        '$inc-'         => '`NAME` = `NAME` - 1',
        '$addToSet'     => '`NAME` = CONCAT(`NAME`, IF(`NAME` = \'\', \'\', \',\'), `VALUE`)',
        '$set'          => '`NAME` = `VALUE`'
    ];
    private const CONCAT                                = ", ";

    protected const OR                                  = " OR ";
    protected const AND                                 = " AND ";

    protected const PREFIX                              = "MYSQL_DATABASE_";
    protected const TYPE                                = "sql";
    protected const KEY_NAME                            = "ID";
    protected const KEY_IS_INT                          = true;

    /**
     * @return sql
     */
    protected function getDriver() : sql
    {
        return new sql();
    }

    /**
     * @param string $value
     * @return string
     */
    protected function convertFieldSort(string $value): string
    {
        return ($value === "-1" || $value === "DESC" || $value === "desc"
            ? "DESC"
            : "ASC"
        );
    }

    /**
     * @param array $res
     * @param string $name
     * @param string|null $or
     */
    protected function convertFieldWhere(array &$res, string $name, string $or = null) : void
    {
        if ($or) {
            if (isset($res[$or][$name])) {
                $res[$name][] = $res[$or][$name];
            }

            $res[$or][$name] = implode($or, $res[$name]);
            unset($res[$name]);
        } else {
            $res[$name] = implode(self::AND, $res[$name]);
            if (isset($res[self::OR][$name])) {
                $res[$name] = "((" . $res[$name] . ")" . self::OR . "(" . str_replace(self::OR, self::AND, $res[self:: OR][$name]) . "))";
                unset($res[self:: OR][$name]);
            }
        }
    }

    /**
     * @param string $key_primary
     * @return string
     */
    protected function convertKeyPrimary(string $key_primary): string
    {
        return $key_primary;
    }

    /**
     * @param $value
     * @param string $struct_type
     * @param string|null $name
     * @param string|null $op
     * @return string
     * @throws Exception
     * @todo da tipizzare
     */
    protected function fieldOperation($value, string $struct_type, string $name = null, string $op = null) : string
    {
        if ($value === null) {
            $res = $this->fieldOperationNULL($struct_type, $name, $op);
        } else {
            if ($name == $this->key_name) {
                $struct_type = self::FTYPE_PRIMARY;
            }
            if (is_array($value) && in_array(null, $value, true)) {
                $value = array_filter($value, function ($v) {
                    return !is_null($v);
                });
                $res = "(" . $this->replacerNULL($name, '$eq') . " OR " . $this->replacer(
                    $name,
                    $op,
                    $struct_type,
                    $value
                    ) . ")";
            } else {
                $res = $this->replacer(
                    $name,
                    $op,
                    $struct_type,
                    $value
                );
            }
        }
        return $res;
    }

    /**
     * @param string $struct_type
     * @param string $name
     * @param string|null $op
     * @return string
     * @throws Exception
     */
    protected function fieldOperationNULL(string $struct_type, string $name, string $op = null) : string
    {
        if (!isset(self::OPERATOR_COMPARISON_NULL[$op])) {
            throw new Exception('Mysql: Null Operation not supported: ' . $op, 500);
        }

        return $this->replacerNULL(
            $name,
            $op
        );
    }

    /**
     * @param string $name
     * @param string $op
     * @param string $struct_type
     * @param string|array|null $value
     * @return string
     * @throws Exception
     * @todo da tipizzare
     */
    private function replacer(string $name, string $op, string $struct_type, $value = null) : string
    {
        return str_replace(
            array(
                "`NAME`",
                "`*VALUE*`",
                "`VALUE`"
            ),
            array(
                "`" . str_replace("`", "", $name) . "`",
                $this->driver->toSql(str_replace(array("(.*)", "(.+)", ".*", ".+", "*", "+"), "%", $value), $struct_type),
                $this->driver->toSql($value, $struct_type)
            ),
            self::OPERATOR_COMPARISON[$op]
        );
    }

    /**
     * @param string $name
     * @param string|null $op
     * @return string
     */
    private function replacerNULL(string $name, string $op = null) : string
    {
        return str_replace(
            array(
                "`NAME`",
            ),
            array(
                "`" . str_replace("`", "", $name) . "`"
            ),
            self::OPERATOR_COMPARISON_NULL[$op]
        );
    }

    /**
     * @param array|null $fields
     * @param bool $skip_control
     * @return string
     * @throws Exception
     */
    protected function querySelect(array $fields = null, bool $skip_control = false) : string
    {
        return "`" . implode("`" . static::CONCAT . "`", array_keys(parent::querySelect($fields, $skip_control))) . "`";
    }

    /**
     * @param array|null $fields
     * @return string
     * @throws Exception
     */
    protected function querySort(array $fields = null) : string
    {
        return str_replace("=", " ", http_build_query(parent::querySort($fields), '', static::CONCAT));
    }

    /**
     * @param array|null $fields
     * @param string|null $delete_logical_field
     * @return string
     * @throws Exception
     */
    protected function queryWhere(array $fields = null, string $delete_logical_field = null) : string
    {
        $res = null;
        $query = parent::queryWhere($fields, $delete_logical_field);
        if (isset($query[static::OR])) {
            $res = "(" . implode(static::OR, $query[static::OR]) . ")";
            unset($query[static::OR]);
            if (!empty($query)) {
                $res .= static::AND;
            }
        }

        $res .= implode(static::AND, $query);

        return $res;
    }

    /**
     * @param array|null $fields
     * @return array
     * @throws Exception
     */
    protected function queryInsert(array $fields = null) : array
    {
        $res = parent::queryInsert($fields);

        return [
            "head" => "`" . implode("`" . static::CONCAT . "`", array_keys($res)) . "`",
            "body" => implode(static::CONCAT, array_values($res))
        ];
    }

    /**
     * @param array|null $fields
     * @return string
     * @throws Exception
     */
    protected function queryUpdate(array $fields = null) : string
    {
        $update = parent::queryUpdate($fields);

        $res = array();
        if (isset($update[self::OP_INC_DEC])) {
            $res[] = implode(static::CONCAT, $update[self::OP_INC_DEC]);
        }
        if (isset($update[self::OP_ADD_TO_SET])) {
            $res[] = implode(static::CONCAT, $update[self::OP_ADD_TO_SET]);
        }
        if (isset($update[self::OP_SET])) {
            $res[] = implode(static::CONCAT, $update[self::OP_SET]);
        }

        return implode(static::CONCAT, $res);
    }
}
