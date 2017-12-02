<?php
declare(strict_types=1);

namespace App;

use Aws\Ssm\SsmClient;
use Generator;

class ParameterFetcher
{
    private const APP_PREFIX = 'planet-cargo';

    private const APP_PARAMETERS = [
        'DB_NAME',
        'DB_PORT',
        'DB_READ_HOST',
        'DB_READ_PASSWORD',
        'DB_READ_USER',
        'DB_WRITE_HOST',
        'DB_WRITE_PASSWORD',
        'DB_WRITE_USER',

        'OAUTH_GOOGLE_CLIENT_ID',
        'OAUTH_GOOGLE_CLIENT_SECRET',

        'TOKEN_ISSUER',
        'TOKEN_PRIVATE_KEY',

        'MAILER_HOST',
        'MAILER_FROM_NAME',
        'MAILER_FROM_ADDRESS',
        'MAILER_USERNAME',
        'MAILER_PASSWORD',
    ];

    private $ssmClient;

    public function __construct(SsmClient $ssmClient)
    {
        $this->ssmClient = $ssmClient;
    }

    public function fetchAll($env = Kernel::ENV_PROD): array
    {
        $prefix = '/' . self::APP_PREFIX . '/' . $env . '/';
        $parameterPaths = array_map(function ($param) use ($prefix) {
            return $prefix . $param;
        }, self::APP_PARAMETERS);

        $results = [];

        foreach (array_chunk($parameterPaths, 10) as $chunk) {
            foreach ($this->fetchParameters($chunk, $prefix) as $key => $value) {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    private function fetchParameters(array $parameterPaths, string $prefix): Generator
    {
        $result = $this->ssmClient->getParameters([
            'Names' => $parameterPaths,
            'WithDecryption' => true,
        ]);

        $paramResults = $result->get('Parameters');
        foreach ($paramResults as $paramData) {
            yield str_replace($prefix, '', $paramData['Name']) => $paramData['Value'];
        }
    }
}