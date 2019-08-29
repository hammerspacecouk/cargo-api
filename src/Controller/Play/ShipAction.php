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
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ShipAction extends AbstractUserAction
{
    use GetShipTrait;

    private $algorithmService;
    private $shipsService;
    private $shipMovementService;
    private $shipNameService;
    private $channelsService;
    private $playerRanksService;
    private $eventsService;
    private $cratesService;
    private $applicationConfig;
    private $shipInLocationResponse;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play/{uuid}', [
            '_controller' => self::class,
        ], [
            'uuid' => Uuid::VALID_PATTERN,
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
        return $ship;
    }
}
