<?php
namespace phpformsframework\cli;

/**
 * Class AppBuilder
 * @package phpformsframework\cli
 */
class App
{
    private const HTACCESS_PATH = "/resources/htaccess";
    private const CONFIG_PATH = "/resources/config";
    private const VIEWS_PATH = "/resources/views";

    public static function setup(array $app_struct, string $disk_path)
    {
        $resource_path = dirname(__DIR__);

        @chmod($disk_path, 0775);

        foreach ($app_struct as $folder) {
            $path = $disk_path.$folder["path"];

            if (strpos($path, "/app") !== false) {
                if (!is_dir($path)) {
                    Utilities::createDir($path, 0777);
                }
            } else {
                Utilities::createDir($path, 0777);
            }
        }

        // Utilities::createDir($disk_path."/app/config", 0777);

        $src_path = $resource_path . self::HTACCESS_PATH . "/.htaccess_template";
        $dest_path = $disk_path."/.htaccess";
        Utilities::copyResource($src_path, $dest_path);
        $htaccessManager = new HtaccessManager($dest_path);
        $htaccessManager->setup();

        $src_path = $resource_path . self::CONFIG_PATH . "/index.php";
        $dest_path = $disk_path."/index.php";
        if (!file_exists($dest_path)) {
            Utilities::copyResource($src_path, $dest_path);
        }

        // Generating APPID
        $appID      = self::uuidv4();
        // Reading App NAME
        $cJson      = file_get_contents($disk_path . "/composer.json");
        $cArr       = json_decode($cJson, true);
        $appName    = str_replace("hcore/", "", $cArr['name']);


        $src_path = $resource_path.self::CONFIG_PATH."/config.php";
        $dest_path = $disk_path . "/config.php";
        if (!file_exists($dest_path)) {
            if (true === Utilities::copyResource($src_path, $dest_path)) {
                self::replace_string_in_file($dest_path, '_APP_ID_', $appID);
                self::replace_string_in_file($dest_path, '_APP_NAME_', $appName);
            }
        }

        // replace placeholders with null
        $content = preg_replace('/(\'@)(?s)(.*?)(\')/', 'null', file_get_contents($dest_path));
        file_put_contents($dest_path, $content);

        $src_path = $resource_path . self::HTACCESS_PATH . "/.htaccess_onlyread";
        if (is_dir($disk_path."/app")) {
            $dest_path = $disk_path . "/app/.htaccess";
            Utilities::copyResource($src_path, $dest_path);
        } else {
            echo $disk_path."/app doesn't exists!\n";
        }


        if (is_dir($disk_path."/cache")) {
            $dest_path = $disk_path . "/cache/.htaccess";
            $cacheHtaccessTemplate = $resource_path . self::HTACCESS_PATH ."/.htaccess_cache";
            Utilities::copyResource($cacheHtaccessTemplate, $dest_path);
        } else {
            echo $disk_path."/cache doesn't exists!\n";
        }

        if (is_dir($disk_path."/uploads")) {
            $dest_path = $disk_path . "/uploads/.htaccess";
            Utilities::copyResource($src_path, $dest_path);
        } else {
            echo $disk_path."/uploads doesn't exists!\n";
        }

        if (is_dir($disk_path."/app/public")) {
            $src_path = $resource_path . self::VIEWS_PATH . "/index.php";
            $dest_path = $disk_path."/app/public/index.php";
            if (!file_exists($dest_path)) {
                Utilities::copyResource($src_path, $dest_path);
            }
        } else {
            echo $disk_path."/app/public doesn't exists!\n";
        }

        if (is_dir($disk_path."/app/conf")) {
            $src_path = $resource_path . self::CONFIG_PATH . "/config.xml";
            $dest_path = $disk_path."/app/conf/config.xml";
            if (!file_exists($dest_path)) {
                Utilities::copyResource($src_path, $dest_path);
            }
        } else {
            echo $disk_path."/app/conf doesn't exists!\n";
        }

        foreach ($app_struct as $folder) {
            $path = $disk_path.$folder["path"];

            $writable = (!empty($folder["writable"])) ? 0775 : 0755;

            if (strpos($path, "/cache") !== false) {
                //print_r("assign ".$path." to ".$apache_user);
                //@chown($path, $apache_user);
                $writable = 0777;
            }

            @chmod($path, $writable);

            echo substr(sprintf('%o', fileperms($path)), -4) . " -> " . $path . "  " . "\n";
        }

    }

    public function makeHtaccess()
    {
    }
    public function makeConfig()
    {
    }
    public function makeDirStruct()
    {
    }

    public function makeDirPermissions()
    {
    }
}
