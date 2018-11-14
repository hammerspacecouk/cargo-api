<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\ValueObject\PlayerRankStatus;
use App\Infrastructure\ApplicationConfig;
use App\Service\AlgorithmService;
use App\Service\ChannelsService;
use App\Service\CratesService;
use App\Service\EventsService;
use App\Service\PlayerRanksService;
use App\Service\Ships\ShipMovementService;
use App\Service\ShipsService;

abstract class AbstractShipInLocationResponse
{
    protected $playerRanksService;
    protected $cratesService;
    protected $applicationConfig;
    protected $eventsService;
    protected $shipsService;
    protected $channelsService;
    protected $algorithmService;
    protected $shipMovementService;

    public function __construct(
        AlgorithmService $algorithmService,
        ApplicationConfig $applicationConfig,
        ChannelsService $channelsService,
        CratesService $cratesService,
        EventsService $eventsService,
        PlayerRanksService $playerRanksService,
        ShipMovementService $shipMovementService,
        ShipsService $shipsService
    ) {
        $this->playerRanksService = $playerRanksService;
        $this->cratesService = $cratesService;
        $this->applicationConfig = $applicationConfig;
        $this->eventsService = $eventsService;
        $this->shipsService = $shipsService;
        $this->channelsService = $channelsService;
        $this->algorithmService = $algorithmService;
        $this->shipMovementService = $shipMovementService;
    }

    public function getResponseData(
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
        ];
        return $this->getResponseDataForLocation($baseData, $user, $ship, $shipInLocation, $rankStatus);
    }

    abstract protected function getResponseDataForLocation(
        array $baseData,
        User $user,
        Ship $ship,
        ShipLocation $shipInLocation,
        PlayerRankStatus $rankStatus
    ): array;
}
