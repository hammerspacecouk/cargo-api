<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ListAction extends AbstractAction
{
    // todo - should this page just be a 400 (missing action)?
    private const ALL_ACTIONS = [
        'move-ship',
        'rename-ship',
        'request-ship-name',
    ];

    private $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    // general status and stats of the game as a whole
    public function __invoke(): JsonResponse
    {
        $this->logger->debug(__CLASS__);
        $this->logger->notice('[ACTION] [LIST]');

        $actions = self::ALL_ACTIONS;
        sort($actions);

        return new JsonResponse($actions);
    }
}
