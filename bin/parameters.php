<?php
declare(strict_types=1);

use App\Kernel;
use App\ParameterFetcher;
use Aws\Credentials\CredentialProvider;
use Aws\Ssm\SsmClient;

require __DIR__ . '/../vendor/autoload.php';

$provider = CredentialProvider::ecsCredentials();
$ssmClient = new SsmClient([
    'region'      => 'eu-west-2',
    'version'     => '2014-11-06',
    'credentials' => $provider,
]);

$env = getenv('APP_ENV') ?: Kernel::ENV_PROD;

$fetcher = new ParameterFetcher($ssmClient);

$parameters = $fetcher->fetchAll($env);

$lines = [];
foreach ($parameters as $key => $value) {
    $lines[] = $key . '=' . $value;
}

echo "# .env\n" . implode("\n", $lines);
