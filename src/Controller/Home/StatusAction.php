<?php
declare(strict_types=1);

namespace App\Controller\Home;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class StatusAction
{
    // health status of the application itself
    public function __invoke(
        LoggerInterface $logger
    ): JsonResponse {

        $logger->debug(__CLASS__);

        $tag = getenv('Tag') ?: 'dev';

        // todo - some database checks to ensure everything is alright (used for tests)

        return new JsonResponse([
            'status' => 'ok',
            'version' => $tag,
        ]);
    }
}
