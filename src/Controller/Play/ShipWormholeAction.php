<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\AbstractUserAction;
use App\Controller\Ships\Traits\GetShipTrait;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\User;
use App\Response\ShipInLocationResponse;
use App\Service\AuthenticationService;
use App\Service\EffectsService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Validator\GenericValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ShipWormholeAction extends AbstractUserAction
{
    use GetShipTrait;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play/{uuid}/wormhole', [
            '_controller' => self::class,
        ], [
            'uuid' => (new GenericValidator())->getPattern(),
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        private ShipsService $shipsService,
        private ShipInLocationResponse $shipInLocationResponse,
        private EffectsService $effectsService,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
    }

    public function invoke(
        Request $request
    ): array {
        $this->logger->debug(__CLASS__);
        $ship = $this->getShipWithLocation($request, $this->user);

        $location = $ship->getLocation();
        $actions = [];
        if ($location instanceof ShipInPort) {
            $actions = $this->effectsService->getUserWormholeActions($this->user, $ship, $location->getPort());
        }

        return [
            'actions' => $actions,
        ];
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
        if ($ship->isDestroyed()) {
            throw new GoneHttpException('Ship destroyed');
        }
        return $ship;
    }
}
