<?php
/**
 * VGallery: CMS based on FormsFramework
 * Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage core
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/gpl-3.0.html
 *  @link https://github.com/wolfgan43/vgallery
 */
namespace phpformsframework\libs\international;

use phpformsframework\libs\Mappable;
use stdClass;

/**
 * Class DataAdapter
 * @package phpformsframework\libs\international
 */
class DataAdapter extends Mappable
{
    protected $datetime = array(
        "prototype"     => "DAY/MONTH/YEAR HOUR:MINUTE:SECOND",
        "regexp"        => "/((\d+):(\d+)(:(\d+))*\s+(\d+)[-\/](\d+)[-\/](\d+))|((\d+)[-\/](\d+)[-\/](\d+)\s+(\d+):(\d+)(:(\d+))*)/",
        "day"           => 6,
        "month"         => 7,
        "year"          => 8,
        "hour"          => 2,
        "minute"        => 3,
        "second"        => 5
    );
    protected $date = array(
        "prototype"     => "DAY/MONTH/YEAR",
        "regexp"        => "/(\d+)[-\/\s]*(\d+)[-\/\s]*(\d+)/",
        "day"           => 1,
        "month"         => 2,
        "year"          => 3
    );
    protected $time = array(
        "prototype"     => "HOUR:MINUTE:SECOND",
        "regexp"        => "/(\d+)[:\s]*(\d+)/"
        , "hour"        => 1
        , "minute"      => 2
        , "second"      => 3
    );
    protected $currency = array(
        "prototype"     => "INTEGER.DECIMAL",
        "regexp"        => "/^(\-){0,1}\s*(\d+)(.(\d+)){0,1}$/",
        "strip_chars" => ","
    );
    protected $currency_ext = array(
        "prototype"     => "INTEGER.DECIMAL",
        "regexp"        => "/^(\-){0,1}\s*(\d+)(.(\d+)){0,5}$/",
        "strip_chars" => ","
    );
    protected $number = array(
        "prototype"     => "INTEGER.DECIMAL",
        "regexp"        => "/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\.\\s*(\\d+)){0,1}\\s*$/",
        "strip_chars" => ","
    );
    protected $number_ext = array(
        "prototype"     => "INTEGER.DECIMAL",
        "regexp"        => "/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\.\\s*(\\d+)){0,5}\\s*$/",
        "strip_chars" => ","
    );
    protected $check_time = array(
        "regexp"        => "/\\d{1,2}:\\d{1,2}(:\\d{1,2}){0,1}/"
    );
    protected $check_date = array(
        "regexp"        => "/\\d{1,2}\\/\\d{1,2}\\/\\d{4}/"
    );
    protected $check_datetime = array(
        "regexp"        => "/\\d{1,2}\\/\\d{1,2}\\/\\d{4}\\s*\\d{1,2}:\\d{1,2}(:\\d{1,2}){0,1}/"
    );

    protected $check_currency = array(
        "regexp"        => "/^\\s*\\-{0,1}\\s*\\d{1,3}(\\,{0,1}\\d{3})*\\s*(\\.\\s*\\d{1,2}){0,1}\\s*$/"
    );
    protected $check_currency_ext = array(
        "regexp"        => "/^\\s*\\-{0,1}\\s*\\d{1,3}(\\,{0,1}\\d{3})*\\s*(\\.\\s*\\d{1,5}){0,1}\\s*$/"
    );
    protected $check_number = array(
        "regexp"        => "/^\\s*\\-{0,1}\\s*\\d+\\s*(\\.\\s*\\d+){0,1}\\s*$/"
    );
    protected $check_number_ext = array(
        "regexp"        => "/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\.\\s*(\\d+)){0,5}\\s*$/"
    );
    protected $empty_currency = array(
        "default"        => "0.00"
    );
    protected $empty_date = array(
        "default"        => "00-00-0000"
    );
    protected $empty_datetime = array(
        "default"        => "00-00-0000 00:00:00"
    );

    /**
     * @param string $type
     * @return stdClass
     */
    private function getRule(string $type) : stdClass
    {
        return (object) $this->$type;
    }

    /**
     * @param Data $oData
     */
    private function normalizeDate(Data $oData) : void
    {
        if (strlen($oData->value_date_year) == 2) {
            $tmp = substr($oData->value_date_year, 0, 1);
            if (intval($tmp) >= 5) {
                $oData->value_date_year = "19" . $oData->value_date_year;
            } else {
                $oData->value_date_year = "20" . $oData->value_date_year;
            }
        }
    }

    /**
     * @param Data $oData
     * @param string $rule_name
     * @return string|null
     */
    private function getDateByRule(Data $oData, string $rule_name) : ?string
    {
        if ($oData->value_date_year == 0 || $oData->value_date_month == 0 || $oData->value_date_day == 0
            || !strlen($oData->ori_value)) {
            return null;
        } else {
            $rule                       = $this->getRule($rule_name);

            return str_replace(
                array(
                    "YEAR",
                    "MONTH",
                    "DAY",
                    "HOUR",
                    "MINUTE",
                    "SECOND"
                ),
                array(
                    sprintf("%04d", intval($oData->value_date_year)),
                    sprintf("%02d", intval($oData->value_date_month)),
                    sprintf("%02d", intval($oData->value_date_day)),
                    sprintf("%02d", intval($oData->value_date_hours)),
                    sprintf("%02d", intval($oData->value_date_minutes)),
                    sprintf("%02d", intval($oData->value_date_seconds))
                ),
                $rule->prototype
            );
        }
    }

    /**
     * @param string $type
     * @return string
     */
    public function getFormat(string $type) : string
    {
        return $this->getRule(strtolower($type))->prototype;
    }

    /**
     * @param Data $oData
     * @return string
     */
    public function getDateTime(Data $oData) : string
    {
        return $this->GetDateByRule($oData, "datetime");
    }

    /**
     * @param Data $oData
     * @return string
     */
    public function getDate(Data $oData) : string
    {
        return $this->GetDateByRule($oData, "date");
    }

    /**
     * @param $oData
     * @return string
     */
    public function getTime(Data $oData) : string
    {
        return $oData->value_date_hours . ":" . $oData->value_date_minutes;
    }
    /**
     * @param Data $oData
     * @return string
     */
    public function getExtCurrency(Data $oData) : string
    {
        return self::GetCurrency($oData);
    }

    /**
     * @param Data $oData
     * @return string
     */
    public function getCurrency(Data $oData) : string
    {
        if (!strlen($oData->value_text)) {
            return self::GetEmptyCurrency();
        }

        $sign = "";
        if ($oData->value_sign) {
            $sign = "- ";
        }

        return $sign . number_format($oData->value_numeric_integer + round($oData->value_numeric_decimal / pow(10, strlen($oData->value_numeric_decimal)), 2), 2, ",", ".");
    }

    /**
     * @param Data $oData
     * @return string
     */
    public function getExtNumber(Data $oData) : string
    {
        return $this->GetNumber($oData);
    }

    /**
     * @param Data $oData
     * @return string
     */
    public function getNumber(Data $oData) : string
    {
        if ($oData->value_sign) {
            $sign = -1;
        } else {
            $sign = 1;
        }
        if (intval($oData->value_numeric_decimal) > 0) {
            return number_format($oData->value_numeric_integer * $sign, 0, "", ",") . "." . $oData->value_numeric_decimal;
        } else {
            return $oData->value_numeric_integer * $sign;
        }
    }

    /**
     * @param Data $oData
     * @return string
     */
    public function getTimestamp(Data $oData) : string
    {
        if ($oData->value_date_hours == 0
            && $oData->value_date_minutes == 0
            && $oData->value_date_seconds == 0
            && $oData->value_date_month == 0
            && $oData->value_date_day == 0
            && $oData->value_date_year == 0
        ) {
            return 0;
        } else {
            return mktime(
                $oData->value_date_hours,
                $oData->value_date_minutes,
                $oData->value_date_seconds,
                $oData->value_date_month,
                $oData->value_date_day,
                $oData->value_date_year
            );
        }
    }

    /**
     * @param Data $oData
     * @return string
     */
    public function getTimeToSec(Data $oData) : string
    {
        if (intval($oData->value_date_hours) == 0
            && intval($oData->value_date_minutes) == 0
            && intval($oData->value_date_seconds) == 0
            && intval($oData->value_date_month) == 0
            && intval($oData->value_date_day) == 0
            && intval($oData->value_date_year) == 0
        ) {
            return 0;
        } else {
            if (intval($oData->value_date_month) == 0
                && intval($oData->value_date_year) == 0
            ) {
                return ((intval($oData->value_date_day) * 24 * 60 * 60) + (intval($oData->value_date_hours) * 60 * 60) + (intval($oData->value_date_minutes) * 60) + intval($oData->value_date_seconds));
            } else {
                return gmmktime(intval($oData->value_date_hours), intval($oData->value_date_minutes), intval($oData->value_date_seconds), intval($oData->value_date_month), intval($oData->value_date_day), intval($oData->value_date_year));
            }
        }
    }

    /**
     * @param Data $oData
     * @param string $value
     */
    public function setDateTime(Data $oData, string $value) : void
    {
        $rule                       = $this->getRule("datetime");

        preg_match_all($rule->regexp, $value, $matches);

        $oData->value_date_day      = isset($matches[$rule->day][0])     ? $matches[$rule->day][0]     : $matches[$rule->day + 4][0];
        $oData->value_date_month    = isset($matches[$rule->month][0])   ? $matches[$rule->month][0]   : $matches[$rule->month + 4][0];
        $oData->value_date_year     = isset($matches[$rule->year][0])    ? $matches[$rule->year][0]    : $matches[$rule->year + 4][0];
        $oData->value_date_hours    = isset($matches[$rule->hour][0])    ? $matches[$rule->hour][0]    : $matches[$rule->hour + 11][0];
        $oData->value_date_minutes  = isset($matches[$rule->minute][0])  ? $matches[$rule->minute][0]  : $matches[$rule->minute + 11][0];
        $oData->value_date_seconds  = isset($matches[$rule->second][0])  ? $matches[$rule->second][0]  : $matches[$rule->second + 11][0];

        $this->NormalizeDate($oData);
    }

    /**
     * @param Data $oData
     * @param string $value
     */
    public function setDate(Data $oData, string $value) : void
    {
        $rule                       = $this->getRule("date");

        preg_match_all($rule->regexp, $value, $matches);

        $oData->value_date_day      = $matches[$rule->day][0];
        $oData->value_date_month    = $matches[$rule->month][0];
        $oData->value_date_year     = $matches[$rule->year][0];

        $this->NormalizeDate($oData);
    }

    /**
     * @param Data $oData
     * @param string $value
     */
    public function setTime(Data $oData, string $value) : void
    {
        $rule                       = $this->getRule("time");

        preg_match_all($rule->rexexp, $value, $matches);
        $oData->value_date_hours    = $matches[$rule->hour][0];
        $oData->value_date_minutes  = $matches[$rule->minute][0];
        $oData->value_date_seconds  = $matches[$rule->second][0];
    }
    /**
     * @param Data $oData
     * @param string $value
     */
    public function setCurrency(Data $oData, string $value) : void
    {
        self::SetCurrencyByType($oData, $value, "currency");
    }

    /**
     * @param Data $oData
     * @param string $value
     */
    public function setExtCurrency(Data $oData, string $value) : void
    {
        self::SetCurrencyByType($oData, $value, "currency_ext");
    }

    /**
     * @param Data $oData
     * @param string $value
     * @param string $type
     */
    private function setCurrencyByType(Data $oData, string $value, string $type) : void
    {
        $this->SetNumberByType($oData, $value, $type);
    }

    /**
     * @param Data $oData
     * @param string $value
     */
    public function setNumber(Data $oData, string $value) : void
    {
        self::SetNumberByType($oData, $value, "number");
    }

    /**
     * @param Data $oData
     * @param string $value
     */
    public function setExtNumber(Data $oData, string $value) : void
    {
        self::SetNumberByType($oData, $value, "number_ext");
    }

    /**
     * @param Data $oData
     * @param string $value
     * @param string $type
     */
    private function setNumberByType(Data $oData, string $value, string $type) : void
    {
        $rule                       = $this->getRule($type);

        $oData->value_text = $value;

        $value = str_replace($rule->strip_chars, "", $value);
        preg_match_all($rule->regexp, $value, $matches);

        if (strlen($matches[1][0])) {
            $oData->value_sign = true;
        } else {
            $oData->value_sign = false;
        }
        $oData->value_numeric_integer = preg_replace("/[^0-9]+/", "", $matches[2][0]);
        $oData->value_numeric_decimal = preg_replace("/[^0-9]+/", "", $matches[4][0]);
    }

    /**
     * @param Data $oData
     * @param string $value
     */
    public function setTimestamp(Data $oData, string $value) : void
    {
        if (is_numeric($value) && $value > 0) {
            $oData->value_date_day = date("d", $value);
            $oData->value_date_month = date("m", $value);
            $oData->value_date_year = date("Y", $value);
            $oData->value_date_hours = date("H", $value);
            $oData->value_date_minutes = date("i", $value);
            $oData->value_date_seconds = date("s", $value);
        }
    }

    /**
     * @param Data $oData
     * @param string $value
     */
    public function setTimeToSec(Data $oData, string $value) : void
    {
        if (is_numeric($value) && $value > 0) {
            $oData->value_date_day = intval(gmdate("d", $value));
            $oData->value_date_month = intval(gmdate("m", $value));
            $oData->value_date_year = intval(gmdate("Y", $value));
            $oData->value_date_hours = intval(gmdate("H", $value));
            $oData->value_date_minutes = intval(gmdate("i", $value));
            $oData->value_date_seconds = intval(gmdate("s", $value));
        }
    }

    /**
     * @param string $raw_value
     * @return bool
     */
    public function checkTime(string $raw_value) : bool
    {
        $rule                               = $this->getRule("check_time");

        return preg_match($rule->regexp, $raw_value) === 1;
    }

    /**
     * @param string $raw_value
     * @return bool
     */
    public function checkDate(string $raw_value) : bool
    {
        $rule                               = $this->getRule("check_date");

        return preg_match($rule->regexp, $raw_value) === 1;
    }

    /**
     * @param string $raw_value
     * @return bool
     */
    public function checkDateTime(string $raw_value) : bool
    {
        $rule                               = $this->getRule("check_datetime");

        return preg_match($rule->regexp, $raw_value) === 1;
    }

    /**
     * @param string $raw_value
     * @return bool
     */
    public function checkCurrency(string $raw_value) : bool
    {
        $rule                               = $this->getRule("check_currency");

        return preg_match($rule->regexp, $raw_value) === 1;
    }

    /**
     * @param string $raw_value
     * @return bool
     */
    public function checkExtCurrency(string $raw_value) : bool
    {
        $rule                               = $this->getRule("check_currency_ext");

        return preg_match($rule->regexp, $raw_value) === 1;
    }

    /**
     * @param string $raw_value
     * @return bool
     */
    public function checkNumber(string $raw_value) : bool
    {
        $rule                               = $this->getRule("check_number");

        return preg_match($rule->regexp, $raw_value) === 1;
    }

    /**
     * @param string $raw_value
     * @return bool
     */
    public function checkExtNumber(string $raw_value) : bool
    {
        $rule                               = $this->getRule("check_number_ext");


        return preg_match($rule->regexp, $raw_value) === 1;
    }

    /**
     * @return mixed
     */
    public function getEmptyCurrency() : string
    {
        $rule                               = $this->getRule("empty_currency");

        return $rule->default;
    }

    /**
     * @return string
     */
    public function getEmptyDate() : string
    {
        $rule                               = $this->getRule("empty_date");

        return $rule->default;
    }

    /**
     * @return string
     */
    public function getEmptyDateTime() : string
    {
        $rule                               = $this->getRule("empty_datetime");

        return $rule->default;
    }
}
