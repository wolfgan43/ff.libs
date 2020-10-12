<?php
namespace phpformsframework\libs\security;

use phpformsframework\libs\App;
use phpformsframework\libs\dto\DataResponse;
use Exception;

/**
 * Class User
 * @package phpformsframework\libs\security
 */
class User extends App
{
    private const ERROR_USER_NOT_FOUND                          = "Wrong user or Password";
    private const USER_LABEL                                    = "user";

    private static $session                                     = null;
    /**
     * @var UserData
     */
    private static $user                                        = null;

    /**
     * @return bool
     * @throws Exception
     */
    public static function isLogged() : bool
    {
        return (bool) (self::$user || self::session()->verify());
    }

    /**
     * @return UserData|null
     * @throws Exception
     */
    public static function get() : ?UserData
    {
        if (!self::$user && self::session()->verify()) {
            self::$user                                         = UserData::load(self::session()->get(self::USER_LABEL));
        }

        return self::$user;
    }

    /**
     * @param UserData|null $user
     * @return UserData
     * @throws Exception
     */
    public static function set(UserData $user = null) : UserData
    {
        if (self::session()->verify()) {
            self::session()->set(self::USER_LABEL, $user->toArray());
            self::$user                                         = $user;
        }

        return self::$user;
    }

    public static function clean() : void
    {
        self::$user                                             = null;
    }

    /**
     * @return Session
     */
    private static function session() : Session
    {
        if (!self::$session) {
            self::$session                                      = new Session();
        }

        return self::$session;
    }

    /**
     * @param string $username
     * @param string $secret
     * @param bool|null $permanent
     * @return DataResponse
     * @throws Exception
     */
    public static function login(string $username, string $secret, bool $permanent = null) : DataResponse
    {
        $user                                                   = new UserData(["username" => $username, "password" => $secret]);
        if (!$user->isStored()) {
            self::throwError(401, self::ERROR_USER_NOT_FOUND);
        }

        $response                                               = self::session()->create($permanent, $user->acl);
        self::set($user);

        return $response;
    }

    /**
     * @return DataResponse
     */
    public static function logout() : DataResponse
    {
        self::session()->destroy();
        self::$user                                             = null;

        return new DataResponse();
    }
}
