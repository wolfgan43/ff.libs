<?php
namespace phpformsframework\libs\security;

use Exception;
use stdClass;
/**
 * Trait SecureManager
 * @package phpformsframework\libs\security
 */
trait SecureManager
{
    /**
     * @return bool
     * @throws Exception
     */
    private static function aclVerify() : bool
    {
        if (($page_acl = User::page()->acl)  && ($user = self::getUser())) {
            $user_acl   = explode(",", $user->acl);
            $acls       = explode(",", $page_acl);

            return !empty(array_intersect($acls, $user_acl));
        }
        return true;
    }

    /**
     * @return UserData|null
     * @throws Exception
     */
    private static function getUser() : ?UserData
    {
        return User::get();
    }

    /**
     * @return bool
     * @throws Exception
     */
    private static function issetUser() : bool
    {
        return User::isLogged();
    }

    /**
     * @param stdClass $data
     * @return UserData
     * @throws Exception
     */
    private static function setUser(stdClass $data) : UserData
    {
        return User::set((new UserData())->fillByObject($data));
    }

    private static function cleanUser() : void
    {
        User::clean();
    }
}
