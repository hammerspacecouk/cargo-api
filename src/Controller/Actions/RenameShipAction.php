<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Service\ShipsService;
use App\Service\TokensService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RenameShipAction extends AbstractAction
{
    // general status and stats of the game as a whole
    public function __invoke(
        Request $request,
        TokensService $tokensService,
        ShipsService $shipsService,
        LoggerInterface $logger
    ): Response {
        $logger->debug(__CLASS__);
        $logger->notice('[ACTION] [RENAME SHIP]');

        $tokenString = $this->getTokenDataFromRequest($request);

        $renameShipToken = $tokensService->parseRenameShipToken($tokenString);
        $shipId = $renameShipToken->getShipId();
        $newName = $renameShipToken->getShipName();

        $shipsService->useRenameShipToken($renameShipToken);
        $logger->info('[SHIP RENAMED] ' . (string)$shipId . ' to ' . $newName);

        // todo - different response if it is XHR vs Referer
        $referrer = $request->headers->get('Referer', null);
        $query = strpos($referrer, '?');
        if ($query) {
            $referrer = substr($referrer, 0, strpos($referrer, '?'));
        }

        if ($referrer) {
            // todo - abstract
            $response = new RedirectResponse($referrer);
            $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
            return $response;
        }

        return $this->actionResponse(new JsonResponse([
            'status' => 'ok',
            'shipId' => $shipId,
            'newName' => $newName,
        ]));
    }
}
