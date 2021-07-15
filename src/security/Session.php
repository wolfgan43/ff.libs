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

use phpformsframework\libs\dto\DataResponse;
use phpformsframework\libs\Hook;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\util\ServerManager;
use phpformsframework\libs\Exception;

/**
 * Class Session
 * @package phpformsframework\libs\security
 */
class Session
{
    use ServerManager;

    private const ERROR_SESSION_INVALID                         = "Invalid Session";
    private const ERROR_SESSION_PATH_NOT_WRITABLE               = "Session path not writable or header already sent";

    private const COOKIE_EXPIRE                                 = 60 * 60 * 24 * 365;

    private const GROUP_LABEL                                   = "group";
    private const SESSION_PREFIX_FILE_LABEL                     = "sess_";

    private $session_started                                    = false;

    /**
     * @param bool|null $permanent
     * @param string|null $acl
     * @return DataResponse
     */
    public function create($permanent = null, string $acl = null) : DataResponse
    {
        $dataResponse                                           = new DataResponse();
        if (!$this->sessionPath()) {
            $dataResponse->error(500, self::ERROR_SESSION_PATH_NOT_WRITABLE);
        }

        $this->sessionName();
        $invalid_session                                        = false;
        $permanent                                              = $permanent ?? Kernel::$Environment::SESSION_PERMANENT;
        $domain                                                 = $this->getPrimaryDomain();

        Hook::handle("on_create_session", $invalid_session, array(
            "domain"    => $domain,
            "permanent" => $permanent
        ));
        if ($invalid_session) {
            return $dataResponse->error(401, self::ERROR_SESSION_INVALID);
        }

        /**
         * Purge header and remove old cookie
         */
        $session_id                                         = null;
        if (!headers_sent()) {
            $this->destroy();
            $this->sessionStart(true);
            $session_id                                     = session_id();
        }

        Hook::handle("on_created_session", $session_id);

        /*
         * Set Cookie
         */
        if (!headers_sent()) {
            $this->cookieCreate($this->sessionName(), $session_id, $permanent);
            if ($acl) {
                $this->cookieCreate(self::GROUP_LABEL, $acl, $permanent);
            }
        }

        $dataResponse->set("session", array(
            "name"      => $this->sessionName(),
            "id"        => $this->sessionId()
        ));

        return $dataResponse;
    }
    public function destroy() : void
    {
        @session_unset();
        @session_destroy();

        $this->session_started                                  = false;

        $session_name                                           = $this->sessionName();
        if (!headers_sent()) {
            header_remove("Set-Cookie");

            $this->cookieDestroy($session_name);
            $this->cookieDestroy(self::GROUP_LABEL);
        }

        unset($_COOKIE[$session_name], $_COOKIE[self::GROUP_LABEL]);

        Hook::handle("on_destroyed_session");
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function verify() : ?bool
    {
        if ($this->session_started) {
            $session_valid                                      = true;
        } elseif ($this->checkSession()) {
            $session_valid                                      = $this->sessionStart();
        } else {
            $session_valid                                      = null;
        }

        if ($session_valid === false) {
            $this->destroy();
            throw new Exception(self::ERROR_SESSION_INVALID, 404);
        }

        return $session_valid;
    }

    /**
     * @param string $name
     * @return UserData|string|null
     * @todo da tipizzare
     */
    public function get(string $name)
    {
        return $this->session($name);
    }

    /**
     * @param string $name
     * @param UserData|string|null $value
     * @todo da tipizzare
     */
    public function set(string $name, $value = null) : void
    {
        $this->session($name, $value);
    }

    /**
     * @param bool $regenerate_id
     * @return bool
     */
    private function sessionStart(bool $regenerate_id = false) : bool
    {
        $this->session_started                                  = @session_start();
        if ($regenerate_id) {
            session_regenerate_id(true);
        }

        return $this->session_started;
    }

    /**
     * @param string|null $id
     * @param string|null $path
     * @return bool
     */
    private function checkSession(string $id = null, string $path = null) : bool
    {
        if (!$id) {
            $id                                                 = $this->sessionId();
        }
        if (!$path) {
            $path                                               = $this->sessionPath();
        }

        $valid_session                                          = file_exists(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::SESSION_PREFIX_FILE_LABEL . $id);

        Hook::handle("on_check_session", $valid_session, array("id" => $id, "path" => $path));

        return $valid_session;
    }

    /**
     * @return string|null
     */
    private function sessionId() : ?string
    {
        $session_name                                           = $this->sessionName();

        return (isset($_COOKIE[$session_name])
            ? $_COOKIE[$session_name]
            : null
        );
    }

    /**
     * @return null|string
     */
    private function sessionName() : ?string
    {
        static $isset                                           = null;

        $name                                                   = Kernel::$Environment::SESSION_NAME ?? session_name();
        if ($isset != $name && session_name() != $name) {
            if (!headers_sent()) {
                session_name($name);
            } else {
                return null;
            }
        }
        $isset                                                  = $name;

        return $name;
    }

    /**
     * @return string|null
     */
    private function sessionPath() : ?string
    {
        static $isset                                           = null;


        if (Kernel::$Environment::SESSION_SAVE_PATH) {
            $path                                               = Kernel::$Environment::SESSION_SAVE_PATH;
        } elseif (session_save_path()) {
            $path                                               = session_save_path();
        } else {
            $path                                               = sys_get_temp_dir();
        }

        if ($isset != $path && session_save_path() != $path) {
            if (!headers_sent()) {
                session_save_path($path);
            } else {
                return null;
            }
        }

        $isset                                                   = $path;
        return $path;
    }



    /**
     * @param string $name
     * @param UserData|string|null $value
     * @return UserData|string|null
     * @todo da tipizzare
     */
    private function session(string $name, $value = null)
    {
        if ($name) {
            $ref                                                = &$_SESSION[$name];
        } else {
            $ref                                                = &$_SESSION;
        }

        if ($value) {
            $ref                                                = $value;
        }

        return $ref;
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool $permanent
     */
    private function cookieCreate(string $name, string $value, bool $permanent) : void
    {
        $sessionCookie                                          = (object) session_get_cookie_params();
        $lifetime                                               = (
            $permanent
            ? time() + self::COOKIE_EXPIRE
            : $sessionCookie->lifetime
        );

        setcookie($name, $value, $lifetime, $sessionCookie->path, $sessionCookie->domain, $this->isHTTPS(), true);
        $_COOKIE[$name]                                         = $value;
    }

    /**
     * @param string $name
     */
    private function cookieDestroy(string $name) : void
    {
        $secure                                                 = $this->isHTTPS();
        $sessionCookie                                          = (object) session_get_cookie_params();

        setcookie($name, false, $sessionCookie->lifetime, $sessionCookie->path, $sessionCookie->domain, $secure, true);
        setcookie($name, false, $sessionCookie->lifetime, $sessionCookie->path, $this->hostname(), $secure, true);
        setcookie($name, false, $sessionCookie->lifetime, $sessionCookie->path, '.' . $this->getPrimaryDomain(), $secure, true);

        unset($_COOKIE[$name]);
    }

    /**
     * @return string|null
     */
    private function getPrimaryDomain() : ?string
    {
        $regs                                               = array();
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z.]{2,6})$/i', $this->hostname(), $regs)) {
            $domain_name                                    = $regs['domain'];
        } else {
            $domain_name                                    = $this->hostname();
        }

        return $domain_name;
    }
}
