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
namespace ff\libs\security;

use ff\libs\storage\Model;
use ff\libs\storage\OrmItem;
use ff\libs\Exception;
use ff\libs\util\Convert;

/**
 * Class DataUser
 * @package ff\libs\dto
 */
class UserData extends OrmItem
{
    protected const COLLECTION      = "access";
    protected const TABLE           = "users";
    protected const JOINS           = [];
    protected const REQUIRED        = [
        "uuid", "username", "email"
    ];
    protected const VALIDATOR       = [
        "uuid"                      => "uuid",
        "anagraph"                  => "uuid",
        "username"                  => "string",
        "email"                     => "email",
        "tel"                       => "number"
    ];
    protected $dbConversion         = [
        "slug"              => "username:toSlug"
    ];

    private const AVATAR_DEFAULT    = "noavatar";
    private const AVATAR_WIDTH      = 100;
    private const AVATAR_HEIGHT     = 100;

    public $uuid                    = null;
    public $username                = null;
    public $domain                  = null;
    public $acl                     = null;
    public $role                    = null;
    public $status                  = null;
    public $token                   = null;

    public $env                     = array();
    public $profile                 = array();
    public $permission              = array();

    public $anagraph                = null;

    public $username_slug           = null;
    public $avatar                  = null;
    public $display_name            = null;
    public $email                   = null;
    public $tel                     = null;
    public $locale                  = null;
    public $referral                = null;

    public $created_at              = null;
    public $updated_at              = null;
    public $login_at                = null;

    /**
     * @param int $width
     * @param int $height
     * @param string|null $default
     * @return string
     * @throws Exception
     */
    public function getAvatar(int $width = self::AVATAR_WIDTH, int $height = self::AVATAR_HEIGHT, string $default = null) : string
    {
        return Convert::image($this->avatar ?: $default ?: self::AVATAR_DEFAULT, (object) ["width" => $width, "height" => $height]);
    }

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
        // TODO: Implement onCreate() method.
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
     * @param Model $db
     * @param string $recordKey
     */
    protected function onChange($db, string $recordKey): void
    {
        // TODO: Implement onChange() method.
    }

    /**
     * @param Model $db
     * @param string|null $recordKey
     */
    protected function onApply($db, string $recordKey = null): void
    {
        // TODO: Implement onApply() method.
    }

    /**
     * @param Model $db
     */
    protected function onInsert($db): void
    {
        // TODO: Implement onInsert() method.
    }

    /**
     * @param Model $db
     * @param string $recordKey
     */
    protected function onUpdate($db, string $recordKey): void
    {
        // TODO: Implement onUpdate() method.
    }

    /**
     * @param Model $db
     * @param string $recordKey
     */
    protected function onDelete($db, string $recordKey): void
    {
        // TODO: Implement onDelete() method.
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
}
