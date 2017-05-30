<?php
declare(strict_types=1);

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

require __DIR__.'/../vendor/autoload.php';

$env = Kernel::ENV_DEV;
$debug = ($env === Kernel::ENV_DEV);

if ($debug) {
    Debug::enable();
}

$kernel = new Kernel($env, $debug);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
