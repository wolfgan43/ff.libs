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
namespace phpformsframework\libs\util;

use phpformsframework\libs\Kernel;

/**
 * Class Session
 * @package phpformsframework\libs\security
 */
class Cookie
{
    use ServerManager;

    public const EXPIRATION_LONG                                 = 60 * 60 * 24 * 365;

    /**
     * @param string $name
     * @param string $value
     * @param int|null $lifetime
     * @param bool $domainPrimary
     */
    public static function create(string $name, string $value, int $lifetime = null, bool $domainPrimary = false) : void
    {
        $sessionCookie                                          = (object) session_get_cookie_params();
        $domain                                               = (
            $domainPrimary
            ? "." . self::getPrimaryDomain()
            : $sessionCookie->domain
        );

        setcookie($name, $value, $lifetime ?? $sessionCookie->lifetime, $sessionCookie->path, $domain, self::isHTTPS(), Kernel::$Environment::SESSION_COOKIE_HTTPONLY);
        $_COOKIE[$name]                                         = $value;
    }

    /**
     * @param string $name
     */
    public static function destroy(string $name) : void
    {
        $secure                                                 = self::isHTTPS();
        $sessionCookie                                          = (object) session_get_cookie_params();

        setcookie($name, false, $sessionCookie->lifetime, $sessionCookie->path, $sessionCookie->domain, $secure, Kernel::$Environment::SESSION_COOKIE_HTTPONLY);
        setcookie($name, false, $sessionCookie->lifetime, $sessionCookie->path, self::hostname(), $secure, Kernel::$Environment::SESSION_COOKIE_HTTPONLY);
        setcookie($name, false, $sessionCookie->lifetime, $sessionCookie->path, '.' . self::getPrimaryDomain(), $secure, Kernel::$Environment::SESSION_COOKIE_HTTPONLY);

        unset($_COOKIE[$name]);
    }

    /**
     * @param string $name
     * @param bool $destroy
     * @return string|null
     */
    public static function get(string $name, bool $destroy = false) : ?string
    {
        $cookie = $_COOKIE[$name] ?? null;
        if ($destroy) {
            self::destroy($name);
        }

        return $cookie;
    }

    /**
     * @return string|null
     */
    private static function getPrimaryDomain() : ?string
    {
        $regs                                               = array();
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z.]{2,6})$/i', self::hostname(), $regs)) {
            $domain_name                                    = $regs['domain'];
        } else {
            $domain_name                                    = self::hostname();
        }

        return $domain_name;
    }
}
