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
namespace phpformsframework\libs\cache\adapters;


use phpformsframework\libs\cache\MemAdapter;
use phpformsframework\libs\Constant;

class MemFs extends MemAdapter
{
	/**
	 * Inserisce un elemento nella cache
	 * Oltre ai parametri indicati, accetta un numero indefinito di chiavi per relazione i valori memorizzati
	 * @param String $name il nome dell'elemento
	 * @param Mixed $value l'elemento
     * @param String $bucket il name space
     * @return bool if storing both value and rel table will success
	 */
	function set($name, $value = null, $bucket = Constant::APPID) {
        return null;
	}

	/**
	 * Recupera un elemento dalla cache
	 * @param String $name il nome dell'elemento
     * @param String $bucket il name space
     * @return Mixed l'elemento
	 */
	function get($name, $bucket = Constant::APPID)
	{
        $res = null;
        return $res;
	}

    /**
     * Cancella una variabile
     * @param String $name il nome dell'elemento
     * @param String $bucket il name space
     * @return bool
     */
    function del($name, $bucket = Constant::APPID)
    {
        return null;
    }


	function clear($bucket = Constant::APPID)
	{
        // global reset

	}
}
