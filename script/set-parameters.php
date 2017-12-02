<?php
declare(strict_types=1);

use Aws\Credentials\CredentialProvider;
use Aws\Ssm\SsmClient;

require __DIR__.'/../vendor/autoload.php';

$provider = CredentialProvider::ecsCredentials();

$ssmClient = new SsmClient([
    'region'      => 'eu-west-2',
    'version'     => '2014-11-06',
    'credentials' => $provider,
]);

$result = $ssmClient->getParameters([
    'Names' => ['/planet-cargo/alpha/DB_NAME'],
    'WithDecryption' => true,
]);

var_dump($result);
