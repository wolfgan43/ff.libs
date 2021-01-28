<?php
namespace phpformsframework\libs\security;

use phpformsframework\libs\storage\Media;
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

    protected function onLoad(): void
    {
        // TODO: Implement onLoad() method.
    }

    protected function onCreate(): void
    {
        // TODO: Implement onCreate() method.
    }

    protected function onRead(string $recordKey, string $primaryKey): void
    {
        // TODO: Implement onRead() method.
    }

    protected function onChange(string $recordKey, string $primaryKey): void
    {
        // TODO: Implement onChange() method.
    }

    protected function onApply(): void
    {
        // TODO: Implement onApply() method.
    }

    protected function onInsert(): void
    {
        // TODO: Implement onInsert() method.
    }

    protected function onUpdate(string $recordKey, string $primaryKey): void
    {
        // TODO: Implement onUpdate() method.
    }

    protected function onDelete(string $recordKey, string $primaryKey): void
    {
        // TODO: Implement onDelete() method.
    }
}
