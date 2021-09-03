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
    private const ERROR_SESSION_HEADER_SENT                     = "Session header already sent";

    private const COOKIE_EXPIRE                                 = 60 * 60 * 24 * 365;

    private const GROUP_LABEL                                   = "group";
    private const SESSION_PREFIX_FILE_LABEL                     = "sess_";

    private static $singleton                                   = [];

    private $session_save_path                                  = null;
    private $session_name                                       = null;
    private $session_id                                         = null;

    private $session_started                                    = false;


    public static function getInstance(string $session_name = null, string $session_path = null) : self
    {
        if (!isset(self::$singleton[$session_name . $session_path])) {
            self::$singleton[$session_name . $session_path]     = new Session($session_name, $session_path);
        }

        return self::$singleton[$session_name . $session_path];
    }

    public function __construct(string $session_name = null, string $session_path = null)
    {
        $this->setSessionName($session_name);
        $this->setSessionPath($session_path);
    }

    /**
     * @param bool|null $permanent
     * @param string|null $acl
     * @return DataResponse
     * @throws Exception
     */
    public function create(bool $permanent = null, string $acl = null) : DataResponse
    {
        if (headers_sent()) {
            throw new Exception(self::ERROR_SESSION_HEADER_SENT, 500);
        }

        $dataResponse                                           = new DataResponse();
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
        $this->destroy();
        $this->sessionStart(true);

        Hook::handle("on_created_session", $this->session_id);

        /*
         * Set Cookie
         */
        $this->cookieCreate($this->session_name, $this->session_id, $permanent);
        if ($acl) {
            $this->cookieCreate(self::GROUP_LABEL, $acl, $permanent);
        }

        $dataResponse->set("session", array(
            "name"      => $this->session_name,
            "id"        => $this->session_id
        ));

        return $dataResponse;
    }
    public function destroy() : void
    {
        @session_unset();
        @session_destroy();

        $this->session_started                                  = false;
        header_remove("Set-Cookie");

        $this->cookieDestroy($this->session_name);
        $this->cookieDestroy(self::GROUP_LABEL);

        Hook::handle("on_destroyed_session");
    }

    /**
     * @param bool $abort
     * @return bool|null
     * @throws Exception
     */
    public function verify(bool $abort = false) : ?bool
    {
        if ($this->session_started) {
            $session_valid                                      = true;
        } elseif ($this->checkSession()) {
            $session_valid                                      = $this->sessionStart();
            if ($abort) {
                session_abort();
            }
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
     * @return array
     */
    public function get(string $name) : array
    {
        return $_SESSION[$name] ?? [];
    }

    /**
     * @param string $name
     * @param array|string|null $value
     * @return Session
     * @todo da tipizzare
     */
    public function set(string $name, $value = null) : self
    {
        $_SESSION[$name] = $value;

        return $this;
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

        $this->session_id                                       = session_id();

        return $this->session_started;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function checkSession() : bool
    {
        if (headers_sent()) {
            throw new Exception(self::ERROR_SESSION_HEADER_SENT, 500);
        }

        $valid_session                                          = !empty($_COOKIE[$this->session_name]) && file_exists(rtrim($this->session_save_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::SESSION_PREFIX_FILE_LABEL . $_COOKIE[$this->session_name]);

        Hook::handle("on_check_session", $valid_session, array("id" => $this->session_id, "name" => $this->session_name, "path" => $this->session_save_path));

        return $valid_session;
    }

    private function setSessionName(string $name = null) : void
    {
        $this->session_name = $name ?? Kernel::$Environment::SESSION_NAME ?? session_name();
        session_name($this->session_name);
    }

    private function setSessionPath(string $path = null) : void
    {
        $this->session_save_path = $path ?? Kernel::$Environment::SESSION_SAVE_PATH ?? session_save_path() ?? sys_get_temp_dir();
        session_save_path($this->session_save_path);
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

        setcookie($name, $value, $lifetime, $sessionCookie->path, $sessionCookie->domain, $this->isHTTPS(), Kernel::$Environment::SESSION_COOKIE_HTTPONLY);
        $_COOKIE[$name]                                         = $value;
    }

    /**
     * @param string $name
     */
    private function cookieDestroy(string $name) : void
    {
        $secure                                                 = $this->isHTTPS();
        $sessionCookie                                          = (object) session_get_cookie_params();

        setcookie($name, false, $sessionCookie->lifetime, $sessionCookie->path, $sessionCookie->domain, $secure, Kernel::$Environment::SESSION_COOKIE_HTTPONLY);
        setcookie($name, false, $sessionCookie->lifetime, $sessionCookie->path, $this->hostname(), $secure, Kernel::$Environment::SESSION_COOKIE_HTTPONLY);
        setcookie($name, false, $sessionCookie->lifetime, $sessionCookie->path, '.' . $this->getPrimaryDomain(), $secure, Kernel::$Environment::SESSION_COOKIE_HTTPONLY);

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
