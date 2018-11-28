<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\UserAuthenticationTrait;
use App\Controller\Ships\Traits\GetShipTrait;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Service\AuthenticationService;
use App\Service\Ships\ShipHealthService;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ShipEditAction
{
    use UserAuthenticationTrait;
    use GetShipTrait;

    private $authenticationService;
    private $shipsService;
    private $shipHealthService;
    private $shipNameService;
    private $logger;

    public static function getRouteDefinition(): Route
    {
        return new Route('/edit/{uuid}', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        ShipsService $shipsService,
        ShipHealthService $shipHealthService,
        ShipNameService $shipNameService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->shipsService = $shipsService;
        $this->logger = $logger;
        $this->shipHealthService = $shipHealthService;
        $this->shipNameService = $shipNameService;
    }

    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(__CLASS__);
        $user = $this->getUser($request, $this->authenticationService);
        $ship = $this->getShipWithLocation($request, $user);

        $renameToken = $this->shipNameService->getRequestShipNameTransaction(
            $user->getId(),
            $ship->getId()
        );

        // health tokens

        $data = [
            'ship' => $ship,
            'renameToken' => $renameToken,
            'health' => [
                $this->shipHealthService->getSmallHealthTransaction($user, $ship),
                $this->shipHealthService->getLargeHealthTransaction($user, $ship),
            ]
        ];

        return $this->userResponse(new JsonResponse($data), $this->authenticationService);
    }

    private function getShipWithLocation(
        Request $request,
        User $user
    ): Ship {
        $uuid = $this->getIDFromUrl($request);
        $ship = $this->shipsService->getByIDWithLocation($uuid);
        if (!$ship || !$ship->getOwner()->equals($user)) {
            throw new NotFoundHttpException('No such ship');
        }
        return $ship;
    }
}
