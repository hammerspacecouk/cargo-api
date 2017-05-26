<?php
declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

require __DIR__.'/../vendor/autoload.php';

date_default_timezone_set(@date_default_timezone_get());

$env = 'dev';
$debug = ($env === 'dev');

if ($debug) {
    Debug::enable();
}

$kernel = new App\Kernel($env, $debug);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
