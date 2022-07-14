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
namespace ff\libs;

use Throwable;

/**
 * Class Exception
 * @package ff\libs
 */
class Exception extends \Exception
{
    private const SEP                                   = ", ";
    private const ERR_                                  = "Err: ";

    private static $errors                              = [];

    /**
     * @param int $status
     * @param string|null $msg
     * @return string|null
     */
    public static function setMessage(int $status, string $msg = null) : ?string
    {
        Debug::set($msg, self::ERR_ . $status);

        return (
            $status > Kernel::$Environment::ERROR_REPORTING
            ? Response::getStatusMessage($status)
            : $msg
        );
    }

    /**
     * @param string $error
     * @param string|null $bucket
     */
    public static function warning(string $error, string $bucket = null) : void
    {
        self::$errors[$bucket][]                        = $error;
    }

    /**
     * @param string $bucket
     * @return string|null
     */
    public static function raise(string $bucket) : ?string
    {
        return (isset(self::$errors[$bucket])
            ? implode(self::SEP, self::$errors[$bucket])
            : null
        );
    }
    /**
     * @return array|null
     */
    public static function dump() : array
    {
        return self::$errors;
    }

    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        Debug::setBackTrace(debug_backtrace());
        
        parent::__construct($message, $code, $previous);
    }
}