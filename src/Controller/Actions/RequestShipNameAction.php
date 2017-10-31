<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Controller\Security\Traits\UserTokenTrait;
use App\Service\ShipsService;
use App\Service\TokensService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestShipNameAction extends AbstractAction
{
    use UserTokenTrait;

    // general status and stats of the game as a whole
    public function __invoke(
        Request $request,
        TokensService $tokensService,
        ShipsService $shipsService,
        LoggerInterface $logger
    ): Response {
        $logger->debug(__CLASS__);
        $logger->notice('[ACTION] [REQUEST SHIP NAME]');

        $userId = $this->getUserId($request, $tokensService);

        try {
            $shipId = Uuid::fromString($request->get('shipId'));
            $shipName = $shipsService->requestShipName($userId, $shipId);
        } catch (InvalidUuidStringException | InvalidArgumentException $e) {
            throw new BadRequestHttpException('Expected Valid ShipId for this user');
        }

        $actionToken = $tokensService->getRenameShipToken(
            $shipId,
            $shipName
        );

        // todo - different response if it is XHR vs Referer
        $referrer = $request->headers->get('Referer', null);
        $query = strpos($referrer, '?');
        if ($query) {
            $referrer = substr($referrer, 0, strpos($referrer, '?'));
        }
        if ($referrer) {
            // todo - abstract
            $referrer .= '?name=' . $shipName . '&token=' . (string) $actionToken;
            $response = new RedirectResponse($referrer);
            $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
            return $response;
        }

        return $this->userResponse(new JsonResponse([
            'nameOffered' => $shipName,
            'action' => $actionToken,
            'userCredits' => rand(0, 10000), // todo - real user credits
        ]));
    }
}
