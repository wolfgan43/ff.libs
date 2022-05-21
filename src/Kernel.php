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
namespace ff\libs;

use ff\libs\dto\RequestPage;

/**
 * Class Kernel
 * @package ff\libs
 */
class Kernel
{
    const NAME_SPACE                = __NAMESPACE__ . '\\';

    private const HOOK_ON_BEFORE_RUN= "App::beforeRun";

    private static $use_cache       = true;
    /**
     * @var Constant
     */
    public static $Environment      = null;

    /**
     * @var RequestPage
     */
    public static $Page             = null;

    /**
     * @var Debug
     */
    private $Debug                   = null;

    /**
     * @param bool|null $cache
     * @return bool
     */
    public static function useCache(bool $cache = null) : bool
    {
        if (self::$use_cache && !is_null($cache)) {
            self::$use_cache        = $cache;
        }

        return self::$use_cache;
    }

    /**
     * @param string|null $bucket
     * @param bool $toArray
     * @return array|object|null
     * @todo far ritornare ?array|object quando sara supportato da php
     */
    protected function dirStruct(string $bucket = null, bool $toArray = true)
    {
        $res                        = Config::getDirBucket($bucket);

        return (!$toArray
            ? $this->array2object($res)
            : $res
        );
    }
    /**
     * Kernel constructor.
     * @param string|null $environment
     */
    public function __construct(string $environment = null)
    {
        self::$Environment          = $environment ?? Constant::class;

        ini_set('memory_limit', self::$Environment::MEMORY_LIMIT);

        if (self::$Environment::DEBUG) {
            $this->Debug            = new Debug();
        }

        $this->useCache(!self::$Environment::DISABLE_CACHE && !isset($_GET["__nocache__"]));

        Config::load(self::$Environment::CONFIG_PATHS);
    }

    /**
     * @access private
     * @throws Exception
     */
    public function run()
    {
        $this->autoloadRegister();

        Request::set(self::$Page)->capture();

        Hook::handle(self::HOOK_ON_BEFORE_RUN, $this);

        self::useCache(!self::$Page->nocache);

        Router::run(self::$Page->script_path . self::$Page->path_info);
    }

    /**
     * @throws Exception
     */
    public function autoloadRegister() : void
    {
        Config::autoloadRegister(static::NAME_SPACE);
    }

    /**
     * @param array $d
     * @return object|null
     */
    private function array2object(array $d) : ?object
    {
        return (is_array($d) && !empty($d)
            ? (object) array_map(__FUNCTION__, $d)
            : null
        );
    }
}
