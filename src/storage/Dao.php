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

/**
 * Class Dao
 * @package ff\libs\dao
 */
class Dao extends OrmItem
{

    /**
     * @param array|null $where
     * @param array|null $fill
     * @return array|null
     */
    protected function onLoad(array &$where = null, array &$fill = null): ?array
    {
        return null;
    }

    /**
     * @param Model $db
     */
    protected function onCreate($db): void
    {
    }

    /**
     * @param Model $db
     * @param string $recordKey
     */
    protected function onChange($db, string $recordKey): void
    {
    }

    /**
     * @param Model $db
     * @param string|null $recordKey
     */
    protected function onApply($db, string $recordKey = null): void
    {
    }

    /**
     * @param Model $db
     */
    protected function onInsert($db): void
    {
    }

    /**
     * @param Model $db
     * @param string $recordKey
     */
    protected function onDelete($db, string $recordKey): void
    {
    }

    /**
     * @param Model $db
     * @param string $recordKey
     */
    protected function onReadAfter($db, string $recordKey): void
    {
        // TODO: Implement onReadAfter() method.
    }

    /**
     * @param array $record
     * @param array $db_record
     */
    protected function onSearch(array &$record, array $db_record): void
    {
        // TODO: Implement onSearch() method.
    }

    /**
     * @param array $record
     * @param array $db_record
     */
    protected function onRead(array &$record, array $db_record): void
    {
        // TODO: Implement onRead() method.
    }

    /**
     * @param array $storedData
     * @param string $recordKey
     */
    protected function onUpdate(array $storedData, string $recordKey): void
    {
        // TODO: Implement onUpdate() method.
    }
}
