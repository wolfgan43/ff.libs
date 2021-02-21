<?php
use hcore\Hcore;

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/
require_once("vendor/autoload.php");

/*
|--------------------------------------------------------------------------
| Init
|--------------------------------------------------------------------------
| configuration Server Environment
|
*/
require_once("config.php");

$hcore = new Hcore(Config::class);
$hcore->run();

exit;
