<?php
declare(strict_types=1);

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

require __DIR__.'/../vendor/autoload.php';

date_default_timezone_set('UTC'); // servers should always be UTC

$env = getenv('APP_ENV') ?? Kernel::ENV_DEV;
$debug = ($env === Kernel::ENV_DEV);

if ($debug) {
    Debug::enable();
}

$kernel = new Kernel($env, $debug);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);

// it's over. go home https://www.youtube.com/watch?v=T1XgFsitnQw
exit;
