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
namespace ff\libs\international;

use ff\libs\Exception;
use DateTime;
use DateInterval;
use DateTimeZone;

/**
 * Class Time
 * @package ff\libs\international
 */
class Time
{
    public const UNIT_DAY       = "D";
    public const UNIT_HOUR      = "H";
    public const UNIT_MINUTE    = "M";
    public const UNIT_SECOND    = "M";

    private const SEP           = ":";

    private const FORMAT        = [
        "en"        => "m-d-Y",
        "it"        => "d-m-Y",
        "unknown"   => "Y-d-m"
    ];
    private const FORMAT_DEFAULT = "Y-m-d";


    private $time               = 0;
    private $dateTime           = null;


    /**
     * Time constructor.
     * @param int|null $timestamp
     * @param string|null $timezone
     */
    public function __construct(int $timestamp = null, string $timezone = null)
    {
        $this->time = $timestamp ?? time();

        $this->dateTime = new DateTime();
        $this->dateTime->setTimestamp($this->time);
        $this->dateTime->setTimezone(new DateTimeZone($timezone ?? Locale::getTimeZone()));
    }

    /**
     * @param int $interval
     * @param string $unit
     * @throws Exception
     */
    public function add(int $interval, string $unit)
    {
        $this->dateTime->add(new DateInterval("PT" . $interval . $unit));
    }

    /**
     * @param string|null $locale
     * @return string
     */
    public function toDate(string $locale = null) : string
    {
        return $this->dateTime->format($this->getFormat($locale));
    }

    /**
     * @param string $sep
     * @return string
     */
    public function toTime(string $sep = self::SEP) : string
    {
        return  $this->dateTime->format($this->getFormatTime($sep));
    }

    /**
     * @param string $sep
     * @return string
     */
    public function toTimeWithSecond(string $sep = self::SEP) : string
    {
        return  $this->dateTime->format($this->getFormatTime($sep, true));
    }

    /**
     * @param string|null $locale
     * @param string $sep
     * @return string
     */
    public function toDateTime(string $locale = null, string $sep = self::SEP) : string
    {
        return $this->dateTime->format($this->getFormat($locale) . " " . $this->getFormatTime($sep));
    }

    /**
     * @param string|null $locale
     * @param string $sep
     * @return string
     */
    public function toDateTimeWithSecond(string $locale = null, string $sep = self::SEP) : string
    {
        return $this->dateTime->format($this->getFormat($locale) . " " . $this->getFormatTime($sep, true));
    }

    /**
     * @param string $sep
     * @return string
     */
    public function toDateTimeLocal(string $sep = self::SEP) : string
    {
        return $this->dateTime->format($this->getFormat(self::FORMAT_DEFAULT) . "\T" . $this->getFormatTime($sep));
    }

    /**
     * @return int
     */
    public function toSecond() : int
    {
        return $this->dateTime->getTimestamp();
    }

    private function getFormat(string $locale = null) : string
    {
        return (self::FORMAT[$locale ?? Locale::getCodeLang()] ?? self::FORMAT_DEFAULT);
    }

    /**
     * @param string $sep
     * @param bool $second
     * @return string
     */
    private function getFormatTime(string $sep, bool $second = false) : string
    {
        return "H{$sep}i" . ($second ? $this->getFormatSecond($sep) : null);
    }

    /**
     * @param string $sep
     * @return string
     */
    private function getFormatSecond(string $sep) : string
    {
        return "{$sep}s";
    }
}
