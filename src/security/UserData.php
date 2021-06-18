<?php
namespace phpformsframework\libs\security;

use phpformsframework\libs\storage\Media;
use phpformsframework\libs\storage\Model;
use phpformsframework\libs\storage\OrmItem;
use Exception;

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
     * @return array|null
     */
    protected function onLoad(): ?array
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
