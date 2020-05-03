<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Infrastructure\ApplicationConfig;
use App\Infrastructure\DateTimeFactory;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Route;

class StatusAction
{
    private CacheInterface $cache;
    private ApplicationConfig $applicationConfig;
    private LoggerInterface $logger;

    public static function getRouteDefinition(): Route
    {
        return new Route('/status', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        CacheInterface $cache,
        ApplicationConfig $applicationConfig,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->applicationConfig = $applicationConfig;
        $this->logger = $logger;
    }

    // health status of the application itself
    public function __invoke(): JsonResponse
    {
        $this->logger->debug(__CLASS__);
        return new JsonResponse([
            'app' => $this->getAppStatus(),
            'request' => $this->getRequestStatus(),
        ]);
    }

    private function getAppStatus(): array
    {
        return [
            'runtime' => true,
            'environment' => $this->applicationConfig->getEnvironment(),
            'cache' => $this->getCacheStatus(),
            'maxScore' => PHP_INT_MAX,
            'release' => 'Alpha',
            'version' => $this->applicationConfig->getVersion(),
            'distanceMultiplier' => $this->applicationConfig->getDistanceMultiplier(),
            'schema' => $this->getMigrationStatus(),
        ];
    }

    private function getRequestStatus(): array
    {
        return [
            'time' => DateTimeFactory::toJson(DateTimeFactory::now()),
            'host' => getenv('HOSTNAME') ?? 'dev',
        ];
    }

    private function getMigrationStatus(): string
    {
        // todo - check the latest migration (checks database connection is ok)
        return 'todo';
    }

    private function getCacheStatus(): bool
    {
        $cacheValue = random_int(0, 5000);
        try {
            $key = __CLASS__ . __METHOD__;
            $this->cache->set($key, $cacheValue, 60);
            $out = $this->cache->get($key);
            if ($out !== $cacheValue) {
                throw new \RuntimeException('Cache value was corrupted. Expected ' . $cacheValue . ', got: ' . $out);
            }
            return true;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }
}
