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
namespace phpformsframework\libs\security;

use phpformsframework\libs\storage\Media;
use phpformsframework\libs\storage\Model;
use phpformsframework\libs\storage\OrmItem;
use phpformsframework\libs\Exception;

/**
 * Class DataUser
 * @package phpformsframework\libs\dto
 */
class UserData extends OrmItem
{
    protected const COLLECTION      = "access";
    protected const TABLE           = "users";
    protected const JOINS           = [];
    protected const REQUIRED        = [
        "uuid", "username", "email", "tel"
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

    private const MODES             = [
      "crop"                => "x",
      "proportional"        => "-",
    ];

    public $uuid                    = null;
    public $username                = null;
    public $acl                     = null;
    public $role                    = null;
    public $status                  = null;
    public $token                   = null;
    public $model                   = null;

    public $env                     = array();
    public $profile                 = array();
    public $permission              = array();

    public $anagraph                = null;

    public $slug                    = null;
    public $avatar                  = null;
    public $display_name            = null;
    public $email                   = null;
    public $tel                     = null;
    public $locale                  = null;
    public $lang                    = null;

    /**
     * @param string|null $mode
     * @param string $noavatar
     * @return string|null
     * @throws Exception
     */
    public function getAvatar(string $mode = null, string $noavatar = "noavatar") : ?string
    {
        return Media::getUrl($this->avatar ?? $noavatar, $mode);
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
     * @param Model $db
     * @param string $recordKey
     */
    protected function onRead($db, string $recordKey): void
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
}
