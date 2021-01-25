<?php
namespace phpformsframework\libs;

use Exception;
/**
 * Class Kernel
 * @package phpformsframework\libs
 *
 */
class Kernel
{
    const NAMESPACE                 = null;
    /**
     * @var Kernel
     * @access private
     */
    private static $singleton       = null;
    private static $use_cache       = true;
    /**
     * @var Constant
     */
    public static $Environment      = null;

    /**
     * @var Debug
     */
    private $Debug                   = null;

    /**
     * @return Kernel
     */
    public static function &load(): ?Kernel
    {
        return self::$singleton;
    }

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

        return (!$toArray && $res
            ? $this->array2object($res)
            : $res
        );
    }
    /**
     * Kernel constructor.
     * @param string $environment
     */
    public function __construct(string $environment)
    {
        self::$singleton            = $this;
        self::$Environment          = $environment;

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
        /**
         * @var App $app
         */
        $app                        = static::NAMESPACE . "App";
        $page                       = $app::construct(self::$Environment);

        if (Env::get("REQUEST_SECURITY_LEVEL")) {
            Hook::handle("on_app_run", $this);
        }

        self::useCache(!$page->nocache);

        Config::autoloadRegister(static::NAMESPACE);

        Router::run($page->script_path);
    }

    /**
     * @param array $d
     * @return object|null
     */
    private function array2object(array $d) : ?object
    {
        return (is_array($d)
            ? (object) array_map(__FUNCTION__, $d)
            : null
        );
    }
}
