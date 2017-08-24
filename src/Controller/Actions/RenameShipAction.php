<?php
declare(strict_types = 1);
namespace App\Controller\Actions;

use App\Service\ShipsService;
use App\Service\TokensService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RenameShipAction extends AbstractAction
{
    // general status and stats of the game as a whole
    public function __invoke(
        Request $request,
        TokensService $tokensService,
        ShipsService $shipsService,
        LoggerInterface $logger
    ): JsonResponse {
        $logger->debug(__CLASS__);
        $logger->notice('[ACTION] [RENAME SHIP]');

        $tokenString = $this->getTokenDataFromRequest($request);

        $renameShipToken = $tokensService->parseRenameShipToken($tokenString);
        $shipId = $renameShipToken->getShipId();
        $newName = $renameShipToken->getShipName();

        $shipsService->useRenameShipToken($renameShipToken);
        $logger->info('[SHIP RENAMED] ' . (string) $shipId . ' to ' . $newName);

        return $this->actionResponse(new JsonResponse([
            'status' => 'ok',
            'shipId' => $shipId,
            'newName' => $newName,
        ]));
    }
}
