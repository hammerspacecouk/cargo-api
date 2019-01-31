<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Infrastructure\ApplicationConfig;
use App\Service\AlgorithmService;
use App\Service\ChannelsService;
use App\Service\CratesService;
use App\Service\EffectsService;
use App\Service\EventsService;
use App\Service\PlayerRanksService;
use App\Service\Ships\ShipMovementService;
use App\Service\ShipsService;
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

    public function __construct(
        AlgorithmService $algorithmService,
        ApplicationConfig $applicationConfig,
        ChannelsService $channelsService,
        CratesService $cratesService,
        EffectsService $effectsService,
        EventsService $eventsService,
        PlayerRanksService $playerRanksService,
        ShipMovementService $shipMovementService,
        ShipsService $shipsService,
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
            'events' => [],
            'playerScore' => $user->getScore(),
            'playerRankStatus' => $rankStatus,
            'hint' => null,
        ];
        return $baseData;
    }
}
