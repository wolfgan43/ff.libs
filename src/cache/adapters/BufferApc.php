<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace phpformsframework\libs\cache\adapters;

use function apc_store;
use function apc_fetch;
use function apc_delete;
use function apc_clear_cache;

/**
 * Class MemApc
 * @package phpformsframework\libs\cache\adapters
 */
class BufferApc extends BufferAdapter
{
    /**
     * @param string $name
     * @param string|null $bucket
     * @return mixed
     */
    protected function load(string $name, string $bucket = null)
    {
        $success = null;
        $res = @apc_fetch($bucket . DIRECTORY_SEPARATOR . $name, $success);

        return ($success
            ? $this->getValue($res)
            : null
        );
    }

    /**
     * @param string $name
     * @param mixed $data
     * @param string|null $bucket
     * @return bool
     */
    protected function write(string $name, $data, string $bucket = null): bool
    {
        return @apc_store($bucket . DIRECTORY_SEPARATOR . $name, $this->setValue($data), $this->getTTL());
    }

    /**
     * Cancella una variabile
     * @param String $name il nome dell'elemento
     * @return bool
     */
    public function del(string $name) : bool
    {
        parent::del($name);

        return @apc_delete($this->getBucket() . DIRECTORY_SEPARATOR . $name);
    }

    /**
     * Pulisce la cache
     * Accetta un numero indefinito di parametri che possono essere utilizzati per cancellare i dati basandosi sulle relazioni
     * Se non si specificano le relazioni, verrà cancellata l'intera cache
     */
    public function clear() : void
    {
        parent::clear();

        apc_clear_cache($this->getBucket());
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getInfo(string $name): ?string
    {
        return null;
    }
}
