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

class Eng extends Adapter {
    public static function SetDateTime($oData, $value) {
        preg_match_all("/((\d+):(\d+)(:(\d+))*\s+(\d+)[-\/](\d+)[-\/](\d+))|((\d+)[-\/](\d+)[-\/](\d+)\s+(\d+):(\d+)(:(\d+))*)/", $value, $matches);
        $oData->value_date_day = $matches[7][0] ? $matches[7][0] : $matches[11][0];
        $oData->value_date_month = $matches[6][0] ? $matches[6][0] : $matches[10][0];
        $oData->value_date_year = $matches[8][0] ? $matches[8][0] : $matches[12][0];
        $oData->value_date_hours = $matches[2][0] ? $matches[2][0] : $matches[13][0];
        $oData->value_date_minutes = $matches[3][0] ? $matches[3][0] : $matches[14][0];
        $oData->value_date_seconds = $matches[5][0] ? $matches[5][0] : $matches[16][0];

        self::NormalizeDate($oData);
    }
    public static function SetDate($oData, $value) {
        preg_match_all("/(\d+)[-\/\s]*(\d+)[-\/\s]*(\d+)/", $value, $matches);
        $oData->value_date_day = $matches[2][0];
        $oData->value_date_month = $matches[1][0];
        $oData->value_date_year = $matches[3][0];

        self::NormalizeDate($oData);
    }
    public static function GetDateTime($oData) {
        if ($oData->value_date_year == 0 || $oData->value_date_month == 0 || $oData->value_date_day == 0
            || !strlen($oData->ori_value)) {
            return "";
        } else {
            return  sprintf("%02d", intval($oData->value_date_month)) . "/" . sprintf("%02d", intval($oData->value_date_day)) . "/" . sprintf("%04d", intval($oData->value_date_year)) .
                " " . sprintf("%02d", intval($oData->value_date_hours)) . ":" . sprintf("%02d", intval($oData->value_date_minutes)) . ":" . sprintf("%02d", intval($oData->value_date_seconds));
        }
    }
    public static function GetDate($oData) {
        if ($oData->value_date_year == 0 || $oData->value_date_month == 0 || $oData->value_date_day == 0
            || !strlen($oData->ori_value)) {
            return "";
        } else {
            return sprintf("%02d", intval($oData->value_date_month)) . "/" . sprintf("%02d", intval($oData->value_date_day)) . "/" . sprintf("%04d", intval($oData->value_date_year));
        }
    }
}