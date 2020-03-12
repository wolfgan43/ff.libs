<?php
namespace phpformsframework\libs\security;

use phpformsframework\libs\App;

/**
 * Class Acl
 * @package phpformsframework\libs\security
 */
class Acl extends App
{
    /**
     * @return bool
     */
    public static function verify()
    {
        if (($acl = self::configuration()->page->acl)  && ($user = self::getCurrentUser())) {
            $user_acl   = explode(",", $user->acl_profile);
            $acls       = explode(",", $acl);

            return !empty(array_intersect($acls, $user_acl));
        }
        return true;
    }
}
