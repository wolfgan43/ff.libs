<?php
namespace phpformsframework\libs;

/**
 * Class Kernel
 * @package phpformsframework\libs
 */
class Kernel
{
    const NAMESPACE                 = null;

    private static $singleton       = null;
    private static $use_cache       = true;
    /**
     * @var Constant
     */
    public static $Environment      = null;

    /**
     * @var Dir
     */
    public $Dir                     = Dir::class;
    /**
     * @var Debug
     */
    public $Debug                   = Debug::class;
    /**
     * @var Error
     */
    public $Error                   = Error::class;

    /**
     * @var Request
     */
    public $Request                 = Request::class;

    /**
     * @var Response
     */
    public $Response                = Response::class;

    //public $Locale                  = Locale::class;
    //public $Env                     = Env::class;
    //public $Hook                    = Hook::class;
    //public $Model                   = Model::class;
    //public $Resource                = $Resource::class;

    /*
    public $Translator              = null;
    public $Media                   = null;
    public $Filemanager             = null;

    public $Discover                = null;
    public $Router                  = null;

    public $Widget                  = null;




    public $Orm = null;
    public $OrmModel = null;
    public $Cache                   = null;

    public $Firewall = null;
*/

    public static function getInstance($constant = null)
    {
        if (!self::$singleton) {
            self::$singleton        = new static($constant);
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
     * Kernel constructor.
     * @param string $environment
     */
    public function __construct($environment = null)
    {
        if ($environment) {
            Dir::autoload(Constant::DISK_PATH . DIRECTORY_SEPARATOR . str_replace('\\', '/', $environment) . "." . Constant::PHP_EXT);
            $Constant               = static::NAMESPACE . $environment;
        } else {
            $Constant               = static::NAMESPACE . "Constant";
        }

        self::$Environment          = $Constant;


        $this->Debug                = new $this->Debug();
        $this->Error                = new $this->Error();
        $this->Dir                  = new $this->Dir();

        $this->Request              = new $this->Request();
        $this->Response             = new $this->Response();

        $this->useCache(!self::$Environment::DISABLE_CACHE && !isset($_GET["__nocache__"]));

        if (!isset($_SERVER["HTTP_HOST"])) {
            $_SERVER["HTTP_HOST"]   = null;
        }



        Config::load(self::$Environment::CONFIG_DISK_PATHS);
    }

    public function run()
    {
        App::setup(Request::page(), $this);
        Config::autoloadRegister();

        Router::run();
    }
}
