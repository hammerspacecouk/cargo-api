<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\AbstractUserAction;
use App\Controller\Ships\Traits\GetShipTrait;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Response\ShipInLocationResponse;
use App\Service\AuthenticationService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Validator\GenericValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ShipAction extends AbstractUserAction
{
    use GetShipTrait;

    private ShipsService $shipsService;
    private ShipInLocationResponse $shipInLocationResponse;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play/{uuid}', [
            '_controller' => self::class,
        ], [
            'uuid' => (new GenericValidator())->getPattern(),
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        ShipsService $shipsService,
        ShipInLocationResponse $shipInLocationResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
        $this->shipsService = $shipsService;
        $this->shipInLocationResponse = $shipInLocationResponse;
    }

    public function invoke(
        Request $request
    ): array {
        $this->logger->debug(__CLASS__);
        $ship = $this->getShipWithLocation($request, $this->user);

        if ($this->user->isTrial() && !$this->user->getRank()->isTrialRange()) {
            throw new HttpException(Response::HTTP_PAYMENT_REQUIRED, 'Full account required');
        }

        return $this->shipInLocationResponse->getResponseData($this->user, $ship);
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
