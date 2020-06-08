<?php

use Symfony\Component\Dotenv\Dotenv;
use function App\Functions\Json\jsonDecode;

date_default_timezone_set('UTC'); // servers should always be UTC

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
