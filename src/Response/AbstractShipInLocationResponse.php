<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Infrastructure\ApplicationConfig;
use App\Service\AlgorithmService;
use App\Service\AuthenticationService;
use App\Service\ChannelsService;
use App\Service\CratesService;
use App\Service\EffectsService;
use App\Service\EventsService;
use App\Service\PlayerRanksService;
use App\Service\PortsService;
use App\Service\ShipLocationsService;
use App\Service\Ships\ShipHealthService;
use App\Service\Ships\ShipMovementService;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use App\Service\UpgradesService;
use App\Service\UsersService;

abstract class AbstractShipInLocationResponse
{
    /**
     * @var PlayerRanksService
     */
    protected $playerRanksService;
    /**
     * @var CratesService
     */
    protected $cratesService;
    /**
     * @var ApplicationConfig
     */
    protected $applicationConfig;
    /**
     * @var EffectsService
     */
    protected $effectsService;
    /**
     * @var EventsService
     */
    protected $eventsService;
    /**
     * @var ShipsService
     */
    protected $shipsService;
    /**
     * @var ChannelsService
     */
    protected $channelsService;
    /**
     * @var AlgorithmService
     */
    protected $algorithmService;
    /**
     * @var ShipMovementService
     */
    protected $shipMovementService;
    /**
     * @var UsersService
     */
    protected $usersService;
    /**
     * @var ShipNameService
     */
    protected $shipNameService;
    /**
     * @var ShipHealthService
     */
    protected $shipHealthService;
    /**
     * @var UpgradesService
     */
    protected $upgradesService;
    /**
     * @var AuthenticationService
     */
    protected $authenticationService;
    /**
     * @var PortsService
     */
    protected $portsService;
    /**
     * @var ShipLocationsService
     */
    protected $shipLocationsService;

    public function __construct(
        AlgorithmService $algorithmService,
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        ChannelsService $channelsService,
        CratesService $cratesService,
        EffectsService $effectsService,
        EventsService $eventsService,
        PlayerRanksService $playerRanksService,
        PortsService $portsService,
        ShipLocationsService $locationsService,
        ShipMovementService $shipMovementService,
        ShipNameService $shipNameService,
        ShipsService $shipsService,
        ShipHealthService $shipHealthService,
        UpgradesService $upgradesService,
        UsersService $usersService
    ) {
        $this->playerRanksService = $playerRanksService;
        $this->cratesService = $cratesService;
        $this->applicationConfig = $applicationConfig;
        $this->eventsService = $eventsService;
        $this->shipsService = $shipsService;
        $this->channelsService = $channelsService;
        $this->algorithmService = $algorithmService;
        $this->shipMovementService = $shipMovementService;
        $this->usersService = $usersService;
        $this->effectsService = $effectsService;
        $this->shipNameService = $shipNameService;
        $this->shipHealthService = $shipHealthService;
        $this->upgradesService = $upgradesService;
        $this->authenticationService = $authenticationService;
        $this->portsService = $portsService;
        $this->shipLocationsService = $locationsService;
    }

    abstract public function getResponseData(
        User $user,
        Ship $ship,
        ShipLocation $shipInLocation
    ): array;

    protected function getBaseData(
        User $user,
        Ship $ship,
        ShipLocation $shipInLocation
    ): array {
        $rankStatus = $this->playerRanksService->getForUser($user);
        return [
            'tacticalOptions' => $this->effectsService->getUserEffectsForLocation($ship, $user, $shipInLocation),
            'ship' => $ship,
            'status' => $shipInLocation->getStatus(),
            'port' => null,
            'channel' => null,
            'directions' => null,
            'events' => [],
            'playerScore' => $user->getScore(),
            'playerRankStatus' => $rankStatus,
            'hint' => null,
            'renameToken' => $this->shipNameService->getRequestShipNameTransaction(
                $user->getId(),
                $ship->getId(),
            ),
            'health' => [
                $this->shipHealthService->getSmallHealthTransaction($user, $ship),
                $this->shipHealthService->getLargeHealthTransaction($user, $ship),
            ],
        ];
    }
}
