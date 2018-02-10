<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\Exception\TokenException;
use App\Domain\ValueObject\Score;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestShipNameAction extends AbstractAction
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
        $this->logger->debug(__CLASS__);
        $this->logger->notice('[ACTION] [REQUEST SHIP NAME]');

        try {
            $token = $this->shipNameService->parseRequestShipNameToken($request->get('token'));
        } catch (TokenException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $shipName = $this->shipNameService->useRequestShipNameToken($token);

        $actionToken = $this->shipNameService->getRenameShipToken(
            $token->getShipId(),
            $shipName
        );

        // todo - different response if it is XHR vs Referer
//        $referrer = $request->headers->get('Referer', null);
//        $query = strpos($referrer, '?');
//        if ($query) {
//            $referrer = substr($referrer, 0, strpos($referrer, '?'));
//        }
//        if ($referrer) {
//            // todo - abstract
//            $referrer .= '?name=' . $shipName . '&token=' . (string) $actionToken;
//            $response = new RedirectResponse($referrer);
//            $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
//            return $response;
//        }

        // the previous token should not be reusable, so we need to send a new one
        $requestShipNameToken = $this->shipNameService->getRequestShipNameToken(
            $token->getUserId(),
            $token->getShipId()
        );

        return new JsonResponse([
            'nameOffered' => $shipName,
            'action' => $actionToken,
            'requestShipNameToken' => $requestShipNameToken,
            'newScore' => new Score(  // todo - real values
                random_int(0, 10000),
                random_int(-10, 100),
                new \DateTimeImmutable()
            )
        ]);
    }
}
