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

use phpformsframework\libs\App;
use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\Exception;

/**
 * Class User
 * @package phpformsframework\libs\security
 */
class User extends App
{
    private const ERROR_USER_NOT_FOUND                          = "Wrong user or Password";
    private const USER_LABEL                                    = "user";

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
        return (self::$user || self::session()->verify(true));
    }

    /**
     * @return UserData
     * @throws Exception
     */
    public static function get() : UserData
    {
        if (!self::$user && self::session()->verify(true)) {
            self::$user                                         = UserData::load(self::session()->get(self::USER_LABEL));
        }

        return self::$user ?? new UserData();
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
     * @param string $acl_required
     * @return bool
     * @throws Exception
     */
    public static function alcVerify(string $acl_required) : bool
    {
        $user = self::get();
        if ($user->isStored()) {
            $user_acl   = explode(",", $user->acl);
            $acls       = explode(",", $acl_required);

            return !empty(array_intersect($acls, $user_acl));
        }

        return false;
    }

    /**
     * @return Session
     * @throws Exception
     */
    private static function session() : Session
    {
        return Session::getInstance();
    }

    /**
     * @param string $username
     * @param string $secret
     * @return UserData
     * @throws Exception
     * @todo da tipizzare con UserData
     */
    protected static function check(string $username, string $secret)
    {
        $user                                                   = new UserData(["username" => $username, "password" => $secret]);
        if (!$user->isStored()) {
            self::throwError(401, self::ERROR_USER_NOT_FOUND);
        }
        return $user;
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
        $user                                                   = static::check($username, $secret);
        $response                                               = self::session()->create($permanent, $user->acl);

        $user->login_at                                         = time();
        $user->apply();

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
