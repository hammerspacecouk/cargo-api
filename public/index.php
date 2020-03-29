<?php
declare(strict_types=1);

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';
require '../bin/env.php';

header_remove('X-Powered-By');

if (($_SERVER['APP_DEBUG'] ?? ('prod' !== ($_SERVER['APP_ENV'] ?? 'dev')))) {
    umask(0000);
    Debug::enable();
}

$trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false;
if ($trustedProxies) {
    Request::setTrustedProxies(
        explode(',', $trustedProxies),
        (Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST)
    );
}

$trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false;
if ($trustedHosts) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

$kernel = new Kernel(
    $_SERVER['APP_ENV'] ?? 'dev',
    ($_SERVER['APP_DEBUG'] ?? ('prod' !== ($_SERVER['APP_ENV'] ?? 'dev')))
);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
