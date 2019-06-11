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
namespace phpformsframework\libs\storage;

interface DatabaseDriver {
    public static function factory();
    public static function free_all();

    public function connect($Database = null, $Host = null, $User = null, $Password = null, $force = false);
    public function insert($query, $table = null);
    public function update($query, $table = null);
    public function delete($query, $table = null);
    public function execute($query);
    public function query($query);
    public function cmd($query, $name = "count");
    public function multiQuery($queries);
    public function lookup($tabella, $chiave = null, $valorechiave = null, $defaultvalue = null, $nomecampo = null, $tiporestituito = null, $bReturnPlain = false);
    public function nextRecord($obj = null);
    public function numRows($use_found_rows = false);
    public function getRecordset();
    public function getFieldset();
    public function getInsertID($bReturnPlain = false);
    public function getField($Name, $data_type = "Text", $bReturnPlain = false, $return_error = true);
    public function toSql($cDataValue, $data_type = null, $enclose_field = true, $transform_null = null);

}

