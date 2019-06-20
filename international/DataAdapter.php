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

use phpformsframework\libs\Extendible;

class DataAdapter extends Extendible {
    private $format = array(
        "Number" 		=> ""
        , "DateTime" 	=> ""
        , "Time" 		=> ""
        , "Date" 		=> ""
        , "Currency" 	=> ""
    );

    protected $date_time = array(
        "regexp"        => "/((\d+):(\d+)(:(\d+))*\s+(\d+)[-\/](\d+)[-\/](\d+))|((\d+)[-\/](\d+)[-\/](\d+)\s+(\d+):(\d+)(:(\d+))*)/"
        , "day"         => 6
        , "month"       => 7
        , "year"        => 8
        , "hour"        => 2
        , "minute"      => 3
        , "second"      => 5
    );
    protected $date = array(
        "regexp"        => "/(\d+)[-\/\s]*(\d+)[-\/\s]*(\d+)/"
        , "day"         => 1
        , "month"       => 2
        , "year"        => 3
    );
    protected $time = array(
        "regexp"        => "/(\d+)[:\s]*(\d+)/"
        , "hour"        => 1
        , "minute"      => 2
        , "second"      => 3
    );
    protected $currency = array(
        "regexp"        => "/^(\-){0,1}\s*(\d+)(.(\d+)){0,1}$/"
        , "strip_chars" => ","
    );
    protected $currency_ext = array(
        "regexp"        => "/^(\-){0,1}\s*(\d+)(.(\d+)){0,5}$/"
        , "strip_chars" => ","
    );
    protected $number = array(
        "regexp"        => "/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\.\\s*(\\d+)){0,1}\\s*$/"
        , "strip_chars" => ","
    );
    protected $number_ext = array(
        "regexp"        => "/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\.\\s*(\\d+)){0,5}\\s*$/"
        , "strip_chars" => ","
    );
    protected $check_time = array(
        "regexp"        => "/\\d{1,2}:\\d{1,2}(:\\d{1,2}){0,1}/"
    );
    protected $check_date = array(
        "regexp"        => "/\\d{1,2}\\/\\d{1,2}\\/\\d{4}/"
    );
    protected $check_date_time = array(
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
    protected $empty_date_time = array(
        "default"        => "00-00-0000 00:00:00"
    );
    protected $get_date = array(
        "prototype" => "[DAY/MONTH/YEAR]"
    );
    protected $get_date_time = array(
        "prototype" => "[DAY/MONTH/YEAR HOUR:MINUTE:SECOND]"
    );

    private function getRule($type) {
        return (object) $this->$type;
    }
    public function format($key = null) {
        return ($key
            ? $this->format[$key]
            : $this->format
        );
    }

    public function SetDateTime($oData, $value) {
        $rule                       = $this->getRule("date_time");

        preg_match_all($rule->regexp, $value, $matches);

        $oData->value_date_day      = $matches[$rule->day][0]     ? $matches[$rule->day][0]     : $matches[$rule->day + 4][0];
        $oData->value_date_month    = $matches[$rule->month][0]   ? $matches[$rule->month][0]   : $matches[$rule->month + 4][0];
        $oData->value_date_year     = $matches[$rule->year][0]    ? $matches[$rule->year][0]    : $matches[$rule->year + 4][0];
        $oData->value_date_hours    = $matches[$rule->hour][0]    ? $matches[$rule->hour][0]    : $matches[$rule->hour + 11][0];
        $oData->value_date_minutes  = $matches[$rule->minute][0]  ? $matches[$rule->minute][0]  : $matches[$rule->minute + 11][0];
        $oData->value_date_seconds  = $matches[$rule->second][0]  ? $matches[$rule->second][0]  : $matches[$rule->second + 11][0];

        $this->NormalizeDate($oData);
    }
    public function SetDate($oData, $value) {
        $rule                       = $this->getRule("date");

        preg_match_all($rule->regexp, $value, $matches);

        $oData->value_date_day      = $matches[$rule->day][0];
        $oData->value_date_month    = $matches[$rule->month][0];
        $oData->value_date_year     = $matches[$rule->year][0];

        $this->NormalizeDate($oData);
    }
    public function SetTime($oData, $value) {
        $rule                       = $this->getRule("time");

        preg_match_all($rule->rexexp, $value, $matches);
        $oData->value_date_hours    = $matches[$rule->hour][0];
        $oData->value_date_minutes  = $matches[$rule->minute][0];
        $oData->value_date_seconds  = $matches[$rule->second][0];
    }
    public function NormalizeDate($oData) {
        if (strlen($oData->value_date_year) == 2) {
            $tmp = substr($oData->value_date_year, 0, 1);
            if (intval($tmp) >= 5) {
                $oData->value_date_year = "19" . $oData->value_date_year;
            } else {
                $oData->value_date_year = "20" . $oData->value_date_year;
            }
        }
    }
    public function GetDateTime($oData) {
        if ($oData->value_date_year == 0 || $oData->value_date_month == 0 || $oData->value_date_day == 0
            || !strlen($oData->ori_value)) {
            return "";
        } else {
            $rule                       = $this->getRule("get_date_time");

            return str_replace(array(
                    "YEAR"
                    , "MONTH"
                    , "DAY"
                    , "HOUR"
                    , "MINUTE"
                    , "SECOND"
                )
                , array(
                    sprintf("%04d", intval($oData->value_date_year))
                    , sprintf("%02d", intval($oData->value_date_month))
                    , sprintf("%02d", intval($oData->value_date_day))
                    , sprintf("%02d", intval($oData->value_date_hours))
                    , sprintf("%02d", intval($oData->value_date_minutes))
                    , sprintf("%02d", intval($oData->value_date_seconds))
                )
                , $rule->prototype
            );
        }
    }
    public function GetDate($oData) {
        if ($oData->value_date_year == 0 || $oData->value_date_month == 0 || $oData->value_date_day == 0
            || !strlen($oData->ori_value)) {
            return "";
        } else {
            $rule                       = $this->getRule("get_date");

            return str_replace(array(
                    "YEAR"
                    , "MONTH"
                    , "DAY"
                )
                , array(
                    sprintf("%04d", intval($oData->value_date_year))
                    , sprintf("%02d", intval($oData->value_date_month))
                    , sprintf("%02d", intval($oData->value_date_day))
                )
                , $rule->prototype
            );
        }
    }
    public function GetTime($oData) {
        return $oData->value_date_hours . ":" . $oData->value_date_minutes /*. ":" . $oData->value_date_seconds*/;
    }
    public function SetCurrency($oData, $value) {
        self::SetCurrencyByType($oData, $value, "currency");
    }
    public function SetExtCurrency($oData, $value) {
        self::SetCurrencyByType($oData, $value, "currency_ext");
    }

    private function SetCurrencyByType($oData, $value, $type) {
        $this->SetNumberByType($oData, $value, $type);
    }

    public function GetExtCurrency($oData) {
        return self::GetCurrency($oData);
    }

    public function GetCurrency($oData) {
        if (!strlen($oData->value_text)) {
            return self::GetEmptyCurrency();
        }

        $sign = "";
        if ($oData->value_sign) {
            $sign = "- ";
        }
        if ($oData->format_currency_showdecimals) {
            return $sign . number_format($oData->value_numeric_integer + round($oData->value_numeric_decimal / pow(10, strlen($oData->value_numeric_decimal)), 2), 2, ",", ".");
        } else {
            return $sign . number_format($oData->value_numeric_integer + round($oData->value_numeric_decimal / pow(10, strlen($oData->value_numeric_decimal)), 2), 0, ",", ".");
        }
    }
    public function SetNumber($oData, $value) {
        self::SetNumberByType($oData, $value, "number");
    }
    public function SetExtNumber($oData, $value) {
        self::SetNumberByType($oData, $value, "number_ext");
    }

    private function SetNumberByType($oData, $value, $type) {
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

    public function GetExtNumber($oData) {
        return $this->GetNumber($oData);
    }
    public function GetNumber($oData) {
        if ($oData->value_sign) {
            $sign = -1;
        } else {
            $sign = 1;
        }
        if(intval($oData->value_numeric_decimal) > 0) {
            return number_format($oData->value_numeric_integer * $sign, 0, "", ",") . "." . $oData->value_numeric_decimal;
        } else {
            return $oData->value_numeric_integer * $sign;
        }
    }
    public function GetTimestamp($oData) {
        if($oData->value_date_hours == 0
            && $oData->value_date_minutes == 0
            && $oData->value_date_seconds == 0
            && $oData->value_date_month == 0
            && $oData->value_date_day == 0
            && $oData->value_date_year == 0
        ) {
            return 0;
        } else {
            return mktime($oData->value_date_hours, $oData->value_date_minutes, $oData->value_date_seconds,
                $oData->value_date_month, $oData->value_date_day, $oData->value_date_year);
        }
    }
    public function SetTimestamp($oData, $value) {
        if(is_numeric($value) && $value > 0) {
            $oData->value_date_day = date("d", $value);
            $oData->value_date_month = date("m", $value);
            $oData->value_date_year = date("Y", $value);
            $oData->value_date_hours = date("H", $value);
            $oData->value_date_minutes = date("i", $value);
            $oData->value_date_seconds = date("s", $value);
        }
    }
    public function GetTimeToSec($oData) {
        if(intval($oData->value_date_hours) == 0
            && intval($oData->value_date_minutes) == 0
            && intval($oData->value_date_seconds) == 0
            && intval($oData->value_date_month) == 0
            && intval($oData->value_date_day) == 0
            && intval($oData->value_date_year) == 0
        ) {
            return 0;
        } else {
            if(intval($oData->value_date_month) == 0
                && intval($oData->value_date_year) == 0
            ) {
                return ((intval($oData->value_date_day) * 24 * 60 * 60) + (intval($oData->value_date_hours) * 60 * 60) + (intval($oData->value_date_minutes) * 60) + intval($oData->value_date_seconds));
            } else {
                return gmmktime(intval($oData->value_date_hours), intval($oData->value_date_minutes), intval($oData->value_date_seconds), intval($oData->value_date_month), intval($oData->value_date_day), intval($oData->value_date_year));
            }
        }
    }
    public function SetTimeToSec($oData, $value) {
        if(is_numeric($value) && $value > 0)
        {
            $oData->value_date_day = intval(gmdate("d", $value));
            $oData->value_date_month = intval(gmdate("m", $value));
            $oData->value_date_year = intval(gmdate("Y", $value));
            $oData->value_date_hours = intval(gmdate("H", $value));
            $oData->value_date_minutes = intval(gmdate("i", $value));
            $oData->value_date_seconds = intval(gmdate("s", $value));
        }
    }
    public function CheckTime($raw_value) {
        $rule                               = $this->getRule("check_time");

        return preg_match($rule->regexp, $raw_value) !== false;
    }
    public function CheckDate($raw_value) {
        $rule                               = $this->getRule("check_date");

        return preg_match($rule->regexp, $raw_value) !== false;
    }
    public function CheckDateTime($raw_value) {
        $rule                               = $this->getRule("check_date_time");

        return preg_match($rule->regexp, $raw_value) !== false;
    }
    public function CheckCurrency($raw_value) {
        $rule                               = $this->getRule("check_currency");

        return preg_match($rule->regexp, $raw_value) !== false;
    }
    public function CheckExtCurrency($raw_value) {
        $rule                               = $this->getRule("check_currency_ext");

        return preg_match($rule->regexp, $raw_value) !== false;
    }
    public function CheckNumber($raw_value) {
        $rule                               = $this->getRule("check_number");

        return preg_match($rule->regexp, $raw_value) !== false;
    }
    public function CheckExtNumber($raw_value) {
        $rule                               = $this->getRule("check_number_ext");


        return preg_match($rule->regexp, $raw_value) !== false;
    }
    public function GetEmptyCurrency() {
        $rule                               = $this->getRule("empty_currency");

        return $rule->default;
    }

    public function GetEmptyDate() {
        $rule                               = $this->getRule("empty_date");

        return $rule->default;
    }

    public function GetEmptyDateTime() {
        $rule                               = $this->getRule("empty_date_time");

        return $rule->default;
    }
}