#!/usr/bin/php
<?php
if (php_sapi_name() !== 'cli') {
    exit;
}

require dirname(dirname(dirname(__DIR__))) . '/autoload.php';

$app = new \phpformsframework\cli\Installer();
switch ($argv) {
    case "setup":
        $app->setup();
        break;
    case "dumpautoload":
        echo "indexing configurable and dumpable classes...\n";

        $app->dumpautoload();
        echo "dump-autoload completed!\n";
        break;
    default:
        $app->helper();
}
