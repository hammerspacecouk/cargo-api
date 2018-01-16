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
    private $tokensService;
    private $shipsService;
    private $logger;

    public function __construct(
        TokensService $tokensService,
        ShipsService $shipsService,
        LoggerInterface $logger
    ) {
        $this->tokensService = $tokensService;
        $this->shipsService = $shipsService;
        $this->logger = $logger;
    }

    // general status and stats of the game as a whole
    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(__CLASS__);
        $this->logger->notice('[ACTION] [REQUEST SHIP NAME]');

        $tokenString = $request->get('token');
        if (!$tokenString) {
            throw new BadRequestHttpException('Missing Token');
        }
        // todo - add the token to the USED list, so it can't be used again.
        try {
            $token = $this->tokensService->parseRequestShipNameToken($tokenString);
            $shipId = $token->getShipId();
            $shipName = $this->shipsService->requestShipName($token->getUserId(), $shipId);
        } catch (InvalidUuidStringException | InvalidArgumentException $e) {
            throw new BadRequestHttpException('Expected Valid ShipId for this user');
        }

        $actionToken = $this->tokensService->getRenameShipToken(
            $shipId,
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

        return new JsonResponse([
            'nameOffered' => $shipName,
            'action' => $actionToken,
            'newScore' => rand(0, 10000), // todo - real user credits
        ]);
    }
}
