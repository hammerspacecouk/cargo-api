<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\Exception\TokenException;
use App\Service\Ships\ShipMovementService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MoveShipAction extends AbstractAction
{
    private $shipMovementService;
    private $usersService;
    private $logger;

    public function __construct(
        ShipMovementService $shipMovementService,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->shipMovementService = $shipMovementService;
        $this->usersService = $usersService;
        $this->logger = $logger;
    }

    // general status and stats of the game as a whole
    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(__CLASS__);

        try {
            $moveShipToken = $this->shipMovementService->parseMoveShipToken(
                $this->getTokenDataFromRequest($request)
            );
        } catch (TokenException $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $newChannelLocation = $this->shipMovementService->useMoveShipToken($moveShipToken);

        // send back the new state of the ship in the channel and the new state of the user
        $data = [
            'port' => null,
            'channel' => $newChannelLocation,
            'directions' => null,
            'players' => null, // todo - get the players in the channel
        ];

        $user = $this->usersService->getById($moveShipToken->getOwnerId());
        $data['playerScore'] = $user->getScore();


        // todo - different response if it is XHR vs Referer
//        $referrer = $request->headers->get('Referer', null);
//        if ($referrer) {
//            // todo - abstract
//            $response = new RedirectResponse((string)$referrer);
//            $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
//            return $response;
//        }
        return $this->actionResponse(new JsonResponse($data));
    }
}
