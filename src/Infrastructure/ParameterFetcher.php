<?php
declare(strict_types=1);

namespace App\Infrastructure;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\EcsCredentialProvider;
use Aws\Ssm\SsmClient;
use Generator;
use RuntimeException;

class ParameterFetcher
{
    private const APP_PREFIX = 'planet-cargo';

    private $ssmClient;
    private $env;

    public function __construct(string $env)
    {
        $this->env = $env;

        if (getenv(EcsCredentialProvider::ENV_URI) && $env !== 'build') {
            $provider = CredentialProvider::ecsCredentials();
            $this->ssmClient = new SsmClient([
                'region' => 'eu-west-2',
                'version' => '2014-11-06',
                'credentials' => $provider,
            ]);
        }
    }

    public function load(string $path, string $cacheDir): void
    {
        $version = getenv('APP_VERSION') ?? '';
        $now = new \DateTimeImmutable();

        $cacheFile = $cacheDir . '/param-cache' . $version . '.json';
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile));
            if ($data->expires > $now->getTimestamp()) {
                $this->populate($data->vars);
                return;
            }
        }

        if (!is_readable($path) || is_dir($path)) {
            throw new RuntimeException($path);
        }

        $vars = $this->parse($path);
        if ($this->ssmClient) {
            $results = $this->fetchAll($vars);

            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            file_put_contents($cacheFile, json_encode([
                'expires' => $now->add(new \DateInterval('PT1H30M'))->getTimestamp(),
                'vars' => $results,
            ]));
        } else {
            $results = array_fill_keys($vars, '');
        }

        $this->populate($results);
    }

    private function populate(array $vars): void
    {
        foreach ($vars as $name => $value) {
            $notHttpName = 0 !== strpos($name, 'HTTP_');
            // don't check existence with getenv() because of thread safety issues
            if (isset($_ENV[$name]) || (isset($_SERVER[$name]) && $notHttpName)) {
                continue;
            }

            putenv("$name=$value");
            $_ENV[$name] = $value;
            if ($notHttpName) {
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Parse the .env.dist file for just the variable names
     * This does not use the DotEnv library, as that library should be removed from prod as a devDependency
     */
    private function parse(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new RuntimeException('Could not open ' . $path);
        }
        $envs = [];
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                // it's a comment. move on
                continue;
            }

            $parts = explode('=', $line);
            $envs[] = reset($parts);
        }
        return $envs;
    }

    private function fetchAll(array $vars): array
    {
        $prefix = '/' . self::APP_PREFIX . '/' . $this->env . '/';
        $parameterPaths = array_map(function ($param) use ($prefix) {
            return $prefix . $param;
        }, $vars);

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
