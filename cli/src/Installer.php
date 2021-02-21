<?php
namespace phpformsframework\cli;

use hcore\classes\ClassFinder;
use phpformsframework\libs\cache\Buffer;
use phpformsframework\libs\Kernel;
use ReflectionException;

/**
 * Class setup
 * @package phpformsframework\cli
 */
class Installer extends Kernel
{
    public function setup()
    {
        $this->clearCache();

        App::setup($this->dirStruct(), $this::$Environment::DISK_PATH);

        $this->indexClasses();
    }

    /**
     * @throws ReflectionException
     */
    public function dumpautoload() : void
    {
        $this->clearCache();

        $this->indexClasses();
    }


    public function clearCache()
    {
        Buffer::clear();
    }

    /**
     *
     */
    public function createprojecttree() : void
    {
        echo "creating project tree...\n";

        $dirs = $this->dirStruct("app") + $this->dirStruct("cache") + $this->dirStruct("assets");
        sort($dirs);
        $apache = shell_exec("ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1");
        Project::createProjectTree($dirs, $this::$Environment::DISK_PATH, (!empty($apache) ? $apache : "apache"));

        echo "done.\n";
    }

    /**
     * @throws ReflectionException
     */
    private function indexClasses() : void
    {
        $app = new Kernel();

        $ff_path = $app::$Environment::LIBS_FF_PATH;
        $ff_ns = str_replace("/", "\\", $ff_path);

        $classFinder = new ClassFinder($app::$Environment::DISK_PATH);
        $libsClasses = $classFinder->getClassesByNamespace($ff_ns); // retrives all classes for phpformsframework\libs\ namespace

        // obtain classes that implements Configurable interface
        $files_configurable = $classFinder->filterByInterface($libsClasses, "phpformsframework\libs\Configurable");

        // obtain classes that implements Configurable Dumpable
        $files_dumpable = $classFinder->filterByInterface($libsClasses, "phpformsframework\libs\Dumpable");

        // open Config.php in phpformsframework\libs\
        $fileConfig = $app::$Environment::DISK_PATH."/vendor".$ff_path."/src/Config.php";
        $content = file_get_contents($fileConfig);

        // replace (if exists) $class_configurable array in Config.php
        $content = preg_replace(
            '/(?<=private static \$class_configurable)(?s)(.*?)(?=;)/m',
            $content,
            " = " . $this->arrayToStringPhp($files_configurable)
        );


        // replace (if exists) $class_configurable array in Config.php
        $content = preg_replace(
            '/(?<=private static \$class_dumpable)(?s)(.*?)(?=;)/m',
            $content,
            " = " . $this->arrayToStringPhp($files_dumpable)
        );

        // save Config.php
        file_put_contents($fileConfig, $content);
    }

    /**
     * @param array $arr
     * @return string
     */
    private function arrayToStringPhp(array $arr) : string
    {
        $base = "array(\n";
        $keys = array_keys($arr);

        $content = "";
        $i = 0;
        foreach ($keys as $key) {
            $content .= "\t\t\"$key\"" . " => \"" . $arr[$key] . "\",\n";
            $i++;
        }

        if (strlen($content) >= 2) {
            $content = substr($content, 0, -2);
        }
        $base .= $content;
        $base .= "\n\t)";

        return $base;
    }
}