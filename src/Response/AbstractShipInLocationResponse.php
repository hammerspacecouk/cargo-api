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
use App\Service\Ships\ShipHealthService;
use App\Service\Ships\ShipMovementService;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use App\Service\UpgradesService;
use App\Service\UsersService;

abstract class AbstractShipInLocationResponse
{
    protected $playerRanksService;
    protected $cratesService;
    protected $applicationConfig;
    protected $effectsService;
    protected $eventsService;
    protected $shipsService;
    protected $channelsService;
    protected $algorithmService;
    protected $shipMovementService;
    protected $usersService;
    protected $shipNameService;
    protected $shipHealthService;
    protected $upgradesService;
    protected $authenticationService;

    public function __construct(
        AlgorithmService $algorithmService,
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        ChannelsService $channelsService,
        CratesService $cratesService,
        EffectsService $effectsService,
        EventsService $eventsService,
        PlayerRanksService $playerRanksService,
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
        $baseData = [
            'ship' => $ship,
            'status' => $shipInLocation->getStatus(),
            'port' => null,
            'channel' => null,
            'directions' => null,
            'purchaseOptions' => null,
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
            'tacticalOptions' => $this->effectsService->getShipDefenceOptions($ship, $user),
        ];
        return $baseData;
    }
}
