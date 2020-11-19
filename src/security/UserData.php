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
    protected $dbCollection         = "access";
    protected $dbTable              = "users";
    protected $dbJoins              = [
        "tokens"            => [
            "token"         => "name",
            "expire"
        ]
    ];
    protected $dbRequired           = [
        "uuid", "username", "email", "tel"
    ];
    protected $dbValidator = [
        "uuid"              => "uuid",
        "anagraph"          => "uuid",
        "username"          => "string",
        "email"             => "email",
        "tel"               => "number"
    ];
    protected $dbConversion         = [
        "slug" => "username:toSlug"
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
}
