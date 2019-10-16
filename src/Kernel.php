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
     * @var dto\RequestPage
     */
    public $configuration           = null;

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
     * @param null|false|string $environment
     * @return Kernel
     */
    public static function &load($environment = null)
    {
        if (!self::$singleton) {
            self::$singleton        = new static($environment);
        }

        return self::$singleton;
    }

    public static function useCache($cache = null)
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
     * @param bool|string $environment
     */
    protected function __construct($environment = false)
    {
        if ($environment) {
            Dir::autoload(Constant::DISK_PATH . DIRECTORY_SEPARATOR . str_replace('\\', '/', $environment) . "." . Constant::PHP_EXT);
            $Constant               = static::NAMESPACE . $environment;
        } else {
            $Constant               = static::NAMESPACE . "Constant";
        }

        self::$Environment          = $Constant;

        $this->Debug                = new $this->Debug();
        $this->Request              = new $this->Request();
        $this->Response             = new $this->Response();

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
        $app::$Environment          =& $this::$Environment;

        $this->configuration        =& Request::pageConfiguration();

        Config::autoloadRegister();

        Router::run();
    }
}
