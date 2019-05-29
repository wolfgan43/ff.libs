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
namespace phpformsframework\libs\international\data;

class Iso9075 extends Adapter {
    public static function SetDateTime($oData, $value) {
        preg_match_all("/(\d+)-(\d+)-(\d+)\s(\d+):(\d+):(\d+)/", $value, $matches);
        $oData->value_date_day = $matches[3][0];
        $oData->value_date_month = $matches[2][0];
        $oData->value_date_year = $matches[1][0];
        $oData->value_date_hours = $matches[4][0];
        $oData->value_date_minutes = $matches[5][0];
        $oData->value_date_seconds = $matches[6][0];

        self::NormalizeDate($oData);
    }
    public static function SetDate($oData, $value) {
        preg_match_all("/(\d+)-(\d+)-(\d+)/", $value, $matches);
        $oData->value_date_day = $matches[3][0];
        $oData->value_date_month = $matches[2][0];
        $oData->value_date_year = $matches[1][0];

        self::NormalizeDate($oData);
    }
    public static function GetDateTime($oData) {
        if ($oData->value_date_year == 0 || $oData->value_date_month == 0 || $oData->value_date_day == 0) {
            return "";
        } else {
            return sprintf("%'04u", $oData->value_date_year) . "-" . sprintf("%'02u", $oData->value_date_month) . "-" . sprintf("%'02u", $oData->value_date_day) . " " .
                sprintf("%'02u", $oData->value_date_hours) . ":" . sprintf("%'02u", $oData->value_date_minutes) . ":" . sprintf("%'02u", $oData->value_date_seconds);
        }
    }
    public static function GetDate($oData) {
        if ($oData->value_date_year == 0 || $oData->value_date_month == 0 || $oData->value_date_day == 0) {
            return "";
        } else {
            return sprintf("%'04u", $oData->value_date_year) . "-" . sprintf("%'02u", $oData->value_date_month) . "-" . sprintf("%'02u", $oData->value_date_day);
        }
    }
    public static function GetTime($oData) {
        return sprintf("%'02u", $oData->value_date_hours) . ":" . sprintf("%'02u", $oData->value_date_minutes) . ":" . sprintf("%'02u", $oData->value_date_seconds);
    }
    public static function CheckDate($raw_value) {
        if (!preg_match("/\\d{1,4}\\-\\d{1,2}\\-\\d{2}/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckDateTime($raw_value) {
        if (!preg_match("/\\d{1,4}\\-\\d{1,2}\\-\\d{2}\\s*\\d{1,2}:\\d{1,2}(:\\d{1,2}){0,1}/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function GetEmptyDate() {
        return "0000-00-00";
    }

    public static function GetEmptyDateTime() {
        return "0000-00-00 00:00:00";
    }
}