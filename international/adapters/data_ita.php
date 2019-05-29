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

class Ita extends Adapter {
    public static function SetCurrency($oData, $value) {
        $oData->value_text = $value;

        $value = str_replace(".", "", $value);
        preg_match_all("/^(\-){0,1}\s*(\d+)(,(\d+)){0,1}$/", $value, $matches);

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

        $value = str_replace(".", "", $value);
        preg_match_all("/^(\-){0,1}\s*(\d+)(,(\d+)){0,5}$/", $value, $matches);

        if (strlen($matches[1][0])) {
            $oData->value_sign = true;
        } else {
            $oData->value_sign = false;
        }

        $oData->value_numeric_integer = preg_replace("/[^0-9]+/", "", $matches[2][0]);
        $oData->value_numeric_decimal = preg_replace("/[^0-9]+/", "", $matches[4][0]);
    }

    public static function SetNumber($oData, $value) {
        $oData->value_text = $value;

        $value = str_replace(".", "", $value);
        preg_match_all("/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\,\\s*(\\d+)){0,1}\\s*$/", $value, $matches);

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

        $value = str_replace(".", "", $value);
        preg_match_all("/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\,\\s*(\\d+)){0,5}\\s*$/", $value, $matches);

        if (strlen($matches[1][0])) {
            $oData->value_sign = true;
        } else {
            $oData->value_sign = false;
        }
        $oData->value_numeric_integer = preg_replace("/[^0-9]+/", "", $matches[2][0]);
        $oData->value_numeric_decimal = preg_replace("/[^0-9]+/", "", $matches[4][0]);
    }
    public static function CheckCurrency($raw_value) {
        if (!preg_match("/^\\s*\\-{0,1}\\s*\\d{1,3}(\\.{0,1}\\d{3})*\\s*(\\,\\s*\\d{1,2}){0,1}\\s*$/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckExtCurrency($raw_value) {
        if (!preg_match("/^\\s*\\-{0,1}\\s*\\d{1,3}(\\.{0,1}\\d{3})*\\s*(\\,\\s*\\d{1,5}){0,1}\\s*$/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckNumber($raw_value) {
        if (!preg_match("/^\\s*\\-{0,1}\\s*\\d+\\s*(\\,\\s*\\d+){0,1}\\s*$/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function CheckExtNumber($raw_value) {
        if (!preg_match("/^\\s*(\\-){0,1}\\s*(\\d+)\\s*(\\,\\s*(\\d+)){0,5}\\s*$/", $raw_value)) {
            return FALSE;
        } else {
            return true;
        }
    }
    public static function GetEmptyCurrency() {
        return "0,00";
    }
}