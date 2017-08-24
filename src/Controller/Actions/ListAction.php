<?php
declare(strict_types = 1);
namespace App\Controller\Actions;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ListAction extends AbstractAction
{
    private const ALL_ACTIONS = [
        'move-ship',
        'rename-ship',
        'request-ship-name',
    ];

    // general status and stats of the game as a whole
    public function __invoke(
        LoggerInterface $logger
    ): JsonResponse {
        $logger->debug(__CLASS__);
        $logger->notice('[ACTION] [LIST]');

        $actions = self::ALL_ACTIONS;
        sort($actions);

        return new JsonResponse($actions);
    }
}
