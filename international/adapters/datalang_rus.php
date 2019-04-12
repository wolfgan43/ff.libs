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

class datalang_rus implements dataLang {
    private static $format = array(
        "Number" 		=> ""
        , "DateTime" 	=> ""
        , "Time" 		=> ""
        , "Date" 		=> ""
        , "Currency" 	=> ""
    );
    public static function format($key = null) {
        return ($key
            ? self::$format[$key]
            : self::$format
        );
    }

    public static function SetDateTime($oData, $value) {
        preg_match_all("/((\d+):(\d+)(:(\d+))*\s+(\d+)[-\/](\d+)[-\/](\d+))|((\d+)[-\/](\d+)[-\/](\d+)\s+(\d+):(\d+)(:(\d+))*)/", $value, $matches);
        $oData->value_date_day = $matches[6][0] ? $matches[6][0] : $matches[10][0];
        $oData->value_date_month = $matches[7][0] ? $matches[7][0] : $matches[11][0];
        $oData->value_date_year = $matches[8][0] ? $matches[8][0] : $matches[12][0];
        $oData->value_date_hours = $matches[2][0] ? $matches[2][0] : $matches[13][0];
        $oData->value_date_minutes = $matches[3][0] ? $matches[3][0] : $matches[14][0];
        $oData->value_date_seconds = $matches[5][0] ? $matches[5][0] : $matches[16][0];

        self::NormalizeDate($oData);
    }
    public static function SetDate($oData, $value) {
        preg_match_all("/(\d+)[-\/\s]*(\d+)[-\/\s]*(\d+)/", $value, $matches);
        $oData->value_date_day = $matches[1][0];
        $oData->value_date_month = $matches[2][0];
        $oData->value_date_year = $matches[3][0];

        self::NormalizeDate($oData);
    }
    public static function SetTime($oData, $value) {
        preg_match_all("/(\d+)[:\s]*(\d+)/", $value, $matches);
        $oData->value_date_hours = $matches[1][0];
        $oData->value_date_minutes = $matches[2][0];
        $oData->value_date_seconds = $matches[3][0];
    }
    public static function NormalizeDate($oData) {
        if (strlen($oData->value_date_year) == 2) {
            $tmp = substr($oData->value_date_year, 0, 1);
            if (intval($tmp) >= 5) {
                $oData->value_date_year = "19" . $oData->value_date_year;
            } else {
                $oData->value_date_year = "20" . $oData->value_date_year;
            }
        }
    }
    public static function GetDateTime($oData) {
        if ($oData->value_date_year == 0 || $oData->value_date_month == 0 || $oData->value_date_day == 0
            || !strlen($oData->ori_value)) {
            return "";
        } else {
            return sprintf("%02d", intval($oData->value_date_day)) . "/" . sprintf("%02d", intval($oData->value_date_month)) . "/" . sprintf("%04d", intval($oData->value_date_year)) .
                " " . sprintf("%02d", intval($oData->value_date_hours)) . ":" . sprintf("%02d", intval($oData->value_date_minutes)) . ":" . sprintf("%02d", intval($oData->value_date_seconds));
        }
    }
    public static function GetDate($oData) {
        if ($oData->value_date_year == 0 || $oData->value_date_month == 0 || $oData->value_date_day == 0
            || !strlen($oData->ori_value)) {
            return "";
        } else {
            return sprintf("%02d", intval($oData->value_date_day)) . "/" . sprintf("%02d", intval($oData->value_date_month)) . "/" . sprintf("%04d", intval($oData->value_date_year));
        }
    }
    public static function GetTime($oData) {
        return $oData->value_date_hours . ":" . $oData->value_date_minutes /*. ":" . $oData->value_date_seconds*/;
    }
    public static function SetCurrency($oData, $value) {
        $oData->value_text = $value;

        $value = str_replace(",", "", $value);
        preg_match_all("/^(\-){0,1}\s*(\d+)(.(\d+)){0,1}$/", $value, $matches);

        if (strlen($matches[1][0])) {
            $oData->value_sign = true;
        } else {
            $oData->value_sign = false;
        }

        $oData->value_numeric_integer = preg_replace("/[^0-9]+/", "", $matches[2][0]);
        $oData->value_numeric_decimal = preg_replace("/[^0-9]+/", "", $matches[4][0]);
    }
    public static function SetExtCurrency($oData, $value) {
        $oData->value_text = $value;

        $value = str_replace(",", "", $value);
        preg_match_all("/^(\-){0,1}\s*(\d+)(.(\d+)){0,5}$/", $value, $matches);

        if (strlen($matches[1][0])) {
            $oData->value_sign = true;
        } else {
            $oData->value_sign = false;
        }

        $oData->value_numeric_integer = preg_replace("/[^0-9]+/", "", $matches[2][0]);
        $oData->value_numeric_decimal = preg_replace("/[^0-9]+/", "", $matches[4][0]);
    }

    public static function GetExtCurrency($oData) {
        return self::GetCurrency($oData);
    }

    public static function GetCurrency($oData) {
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
    public static function SetNumber($oData, $value) {
        $oData->value_text = $value;

        $value = str_replace(",", "", $value);
        preg_match_all("/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\.\\s*(\\d+)){0,1}\\s*$/", $value, $matches);

        if (strlen($matches[1][0])) {
            $oData->value_sign = true;
        } else {
            $oData->value_sign = false;
        }
        $oData->value_numeric_integer = preg_replace("/[^0-9]+/", "", $matches[2][0]);
        $oData->value_numeric_decimal = preg_replace("/[^0-9]+/", "", $matches[4][0]);

    }
    public static function SetExtNumber($oData, $value) {
        $oData->value_text = $value;

        $value = str_replace(",", "", $value);
        preg_match_all("/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\.\\s*(\\d+)){0,5}\\s*$/", $value, $matches);

        if (strlen($matches[1][0])) {
            $oData->value_sign = true;
        } else {
            $oData->value_sign = false;
        }
        $oData->value_numeric_integer = preg_replace("/[^0-9]+/", "", $matches[2][0]);
        $oData->value_numeric_decimal = preg_replace("/[^0-9]+/", "", $matches[4][0]);
    }

    public static function GetExtNumber($oData) {
        return self::GetNumber($oData);
    }
    public static function GetNumber($oData) {
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
    public static function GetTimestamp($oData) {
        if($oData->value_date_hours == 0
            && $oData->value_date_hours == 0
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
    public static function SetTimestamp($oData, $value) {
        if(is_numeric($value) && $value > 0) {
            $oData->value_date_day = date("d", $value);
            $oData->value_date_month = date("m", $value);
            $oData->value_date_year = date("Y", $value);
            $oData->value_date_hours = date("H", $value);
            $oData->value_date_minutes = date("i", $value);
            $oData->value_date_seconds = date("s", $value);
        }
    }
    public static function GetTimeToSec($oData) {
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
    public static function SetTimeToSec($oData, $value) {
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
    public static function CheckTime($raw_value) {
        if (!preg_match("/\\d{1,2}:\\d{1,2}(:\\d{1,2}){0,1}/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckDate($raw_value) {
        if (!preg_match("/\\d{1,2}\\/\\d{1,2}\\/\\d{4}/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckDateTime($raw_value) {
        if (!preg_match("/\\d{1,2}\\/\\d{1,2}\\/\\d{4}\\s*\\d{1,2}:\\d{1,2}(:\\d{1,2}){0,1}/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckCurrency($raw_value) {
        if (!preg_match("/^\\s*\\-{0,1}\\s*\\d{1,3}(\\,{0,1}\\d{3})*\\s*(\\.\\s*\\d{1,2}){0,1}\\s*$/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckExtCurrency($raw_value) {
        if (!preg_match("/^\\s*\\-{0,1}\\s*\\d{1,3}(\\,{0,1}\\d{3})*\\s*(\\.\\s*\\d{1,5}){0,1}\\s*$/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckNumber($raw_value) {
        if (!preg_match("/^\\s*\\-{0,1}\\s*\\d+\\s*(\\.\\s*\\d+){0,1}\\s*$/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckExtNumber($raw_value) {
        if (!preg_match("/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\.\\s*(\\d+)){0,5}\\s*$/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function GetEmptyCurrency() {
        return "0.00";
    }

    public static function GetEmptyDate() {
        return "0000-00-00";
    }

    public static function GetEmptyDateTime() {
        return "0000-00-00 00:00:00";
    }
}