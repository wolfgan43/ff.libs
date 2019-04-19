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

interface Adapter {
    public static function Format($key);
    public static function SetDateTime($oData, $value);
    public static function SetDate($oData, $value);
    public static function SetTime($oData, $value);
    public static function NormalizeDate($oData);
    public static function GetDateTime($oData);
    public static function GetDate($oData);
    public static function GetTime($oData);
    public static function SetCurrency($oData, $value);
    public static function GetCurrency($oData);
    public static function SetExtCurrency($oData, $value);
    public static function GetExtCurrency($oData);
    public static function SetNumber($oData, $value);
    public static function GetNumber($oData);
    public static function SetExtNumber($oData, $value);
    public static function GetExtNumber($oData);
    public static function GetTimestamp($oData);
    public static function SetTimestamp($oData, $value);
    public static function GetTimeToSec($oData);
    public static function SetTimeToSec($oData, $value);
    public static function CheckTime($raw_value);
    public static function CheckDate($raw_value);
    public static function CheckDateTime($raw_value);
    public static function CheckCurrency($raw_value);
    public static function CheckExtCurrency($raw_value);
    public static function CheckNumber($raw_value);
    public static function CheckExtNumber($raw_value);
    public static function GetEmptyCurrency();
    public static function GetEmptyDate();
    public static function GetEmptyDateTime();
}