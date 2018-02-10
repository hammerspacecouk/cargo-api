<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Service\Ships\ShipMovementService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MoveShipAction extends AbstractAction
{
    private $shipMovementService;
    private $logger;

    public function __construct(
        ShipMovementService $shipMovementService,
        LoggerInterface $logger
    ) {
        $this->shipMovementService = $shipMovementService;
        $this->logger = $logger;
    }

    // general status and stats of the game as a whole
    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(__CLASS__);

        $tokenString = $this->getTokenDataFromRequest($request);
        $this->shipMovementService->useMoveShipToken($tokenString);

        // todo - different response if it is XHR vs Referer
        $referrer = $request->headers->get('Referer', null);
        if ($referrer) {
            // todo - abstract
            $response = new RedirectResponse((string)$referrer);
            $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
            return $response;
        }

        return $this->actionResponse(new JsonResponse(['status' => 'ok']));
    }
}
