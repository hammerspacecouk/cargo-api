<?php
declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use function App\Functions\DateTimes\jsonDecode;

require __DIR__ . '/../vendor/autoload.php';

header_remove('X-Powered-By');

$dotenv = new Dotenv();
if (isset($_SERVER['APP_CONFIG'])) {
    $vars = jsonDecode($_SERVER['APP_CONFIG']);
    unset($_SERVER['APP_CONFIG']);
    $dotenv->populate($vars);
}
if (isset($_SERVER['APP_SECRETS'])) {
    $vars = jsonDecode($_SERVER['APP_SECRETS']);
    unset($_SERVER['APP_SECRETS']);
    $dotenv->populate($vars);
}

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV']) || $_SERVER['APP_ENV'] === 'dev') {
    $dotenv->load(__DIR__ . '/../.env');
}

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
