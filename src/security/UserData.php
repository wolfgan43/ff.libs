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
    public $displayName             = null;
    public $email                   = null;
    public $tel                     = null;
    public $locale                  = null;
    public $lang                    = null;

    /**
     * @param int|null $max_width
     * @param int|null $max_height
     * @param string $mode
     * @return string|null
     * @throws Exception
     */
    public function getAvatar(int $max_width = null, int $max_height = null, string $mode = "crop") : ?string
    {
        if ($max_width && $max_height) {
            $mode = (
                $max_width && $max_height
                ? $max_width . self::MODES[$mode] . $max_height
                : null
            );
        }
        return Media::getUrl($this->avatar, $mode, "url");
    }
}
