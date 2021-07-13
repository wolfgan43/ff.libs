<?php
namespace phpformsframework\libs;

use Exception;
use phpformsframework\libs\dto\RequestPage;

/**
 * Class Kernel
 * @package phpformsframework\libs
 *
 */
class Kernel
{
    const NAMESPACE                 = "phpformsframework\\libs\\";

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

        return (!$toArray && $res
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
        Config::autoloadRegister(static::NAMESPACE);

        Request::set(self::$Page)->capture();

        if (Env::get("REQUEST_SECURITY_LEVEL")) {
            Hook::handle("on_app_run", $this);
        }

        self::useCache(!self::$Page->nocache);

        self::$Page->onLoad();

        Router::run(self::$Page->script_path . self::$Page->path_info);
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
