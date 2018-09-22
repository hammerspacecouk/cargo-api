<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Data\FlashDataStore;
use App\Service\EventsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class IndexAction
{
    // general status and stats of the game as a whole
    public function __invoke(
        EventsService $eventsService,
        FlashDataStore $flashDataStore,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->debug(__CLASS__);

        return new JsonResponse([
            'status' => 'ok',
            'messages' => $flashDataStore->readMessages(),
            'events' => $eventsService->findAllLatest(),
        ]);
    }
}
