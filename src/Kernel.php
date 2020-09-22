<?php
namespace phpformsframework\libs;

use phpformsframework\libs\util\TypesConverter;

/**
 * Class Kernel
 * @package phpformsframework\libs
 *
 */
class Kernel
{
    use TypesConverter;

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
    public $Debug                   = Debug::class;
    /**
     * @var Request
     */
    public $Request                 = Request::class;
    /**
     * @var Response
     */
    public $Response                = Response::class;


    /**
     * @return Kernel
     */
    public static function &load()
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
     * @param string $bucket
     * @param bool $toArray
     * @return array|object|null
     * @todo far ritornare ?array|object quando sara supportato da php
     */
    protected function dirStruct(string $bucket = Config::APP_BASE_NAME, bool $toArray = true)
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

        $this->Debug                = new $this->Debug();
        $this->Request              = new $this->Request();
        $this->Response             = new $this->Response();

        ini_set('memory_limit', self::$Environment::MEMORY_LIMIT);

        $this->useCache(!self::$Environment::DISABLE_CACHE && !isset($_GET["__nocache__"]));

        if (!isset($_SERVER["HTTP_HOST"])) {
            $_SERVER["HTTP_HOST"]   = null;
        }

        Config::load(self::$Environment::CONFIG_DISK_PATHS);
    }

    /**
     * @access private
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

        if ($page->validation && $page->isInvalidURL()) {
            $this->Response->redirect($page->canonicalUrl());
        }

        self::useCache(!$page->nocache);

        Config::autoloadRegister();

        Router::run($page->script_path);
    }
}
