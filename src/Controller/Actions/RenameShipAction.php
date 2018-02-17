<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\Exception\TokenException;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RenameShipAction extends AbstractAction
{
    private $shipsService;
    private $shipNameService;
    private $logger;

    public function __construct(
        ShipsService $shipsService,
        ShipNameService $shipNameService,
        LoggerInterface $logger
    ) {
        $this->shipNameService = $shipNameService;
        $this->shipsService = $shipsService;
        $this->logger = $logger;
    }

    // general status and stats of the game as a whole
    public function __invoke(
        Request $request
    ): Response {
        try {
            $renameShipToken = $this->shipNameService->parseRenameShipToken($this->getTokenDataFromRequest($request));
        } catch (TokenException $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $this->shipNameService->useRenameShipToken($renameShipToken);

        // fetch the updated ship
        $ship = $this->shipsService->getByID($renameShipToken->getShipId());

        // todo - different response if it is XHR vs Referer
//        $referrer = $request->headers->get('Referer', null);
//        $query = strpos($referrer, '?');
//        if ($query) {
//            $referrer = substr($referrer, 0, strpos($referrer, '?'));
//        }

//        if ($referrer) {
//            // todo - abstract
//            $response = new RedirectResponse($referrer);
//            $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
//            return $response;
//        }

        return $this->actionResponse(new JsonResponse([
            'ship' => $ship
        ]));
    }
}
