<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Infrastructure\ApplicationConfig;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class StatusAction
{
    // health status of the application itself
    public function __invoke(
        LoggerInterface $logger,
        DateTimeImmutable $applicationTime,
        CacheInterface $cache,
        ApplicationConfig $applicationConfig
    ): JsonResponse {

        $logger->debug(__CLASS__);

        $cacheValue = rand(0, 5000);
        try {
            $key = __CLASS__ . __METHOD__;
            $cache->set($key, $cacheValue, 60);
            $out = $cache->get($key);
            if ($out !== $cacheValue) {
                throw new \Exception('Cache value was corrupted. Expected ' . $cacheValue . ', got: ' . $out);
            }
            $cacheStatus = 'OK';
        } catch (\Throwable $e) {
            $logger->error($e->getMessage());
            $cacheStatus = 'ERROR';
        }

        // todo - check the latest migration (checks database connection is ok)

        return new JsonResponse([
            'status' => 'OK',
            'release' => 'Arctan',
            'appVersion' => $applicationConfig->getVersion(),
            'latestMigration' => 'TODO', // todo
            'appTime' => $applicationTime->format('c'),
            'distanceMutipler' => $applicationConfig->getDistanceMultiplier(),
            'cache' => $cacheStatus,
        ]);
    }
}