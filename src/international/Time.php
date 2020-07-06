<?php
namespace phpformsframework\libs\international;

use DateTime;
use DateInterval;
use DateTimeZone;
use Exception;

/**
 * Class Time
 * @package phpformsframework\libs\international
 */
class Time
{
    public const UNIT_DAY       = "D";
    public const UNIT_HOUR      = "H";
    public const UNIT_MINUTE    = "M";
    public const UNIT_SECOND    = "M";

    private const SEP           = ":";

    private const FORMAT        = [
        "ENG"   => "m-d-Y",
        "ITA"   => "d-m-Y",
        "FAL"   => "Y-d-m",
    ];
    private const FORMAT_DEFAULT = "Y-m-d";


    private $time               = 0;
    private $dateTime           = null;



    /**
     * Time constructor.
     * @param int|null $timestamp
     * @throws Exception
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
     * @return int
     */
    public function toSecond() : int
    {
        return $this->dateTime->getTimestamp();
    }

    private function getFormat(string $locale = null) : string
    {
        return (self::FORMAT[$locale ?? Locale::getLang("code")] ?? self::FORMAT_DEFAULT);
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
