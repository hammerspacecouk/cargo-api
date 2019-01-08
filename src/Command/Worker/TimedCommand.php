<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\EntityManager;
use App\Domain\Entity\Channel;
use App\Domain\Entity\ShipInPort;
use App\Domain\ValueObject\Direction;
use App\Infrastructure\DateTimeFactory;
use App\Service\AlgorithmService;
use App\Service\ChannelsService;
use App\Service\CratesService;
use App\Service\ShipLocationsService;
use App\Service\Ships\ShipMovementService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class TimedCommand extends AbstractWorkerCommand
{
    private $cratesService;
    private $shipLocationsService;
    private $channelsService;
    private $shipMovementService;
    private $algorithmService;

    public function __construct(
        AlgorithmService $algorithmService,
        ChannelsService $channelsService,
        CratesService $cratesService,
        ShipLocationsService $shipLocationsService,
        ShipMovementService $shipMovementService,
        DateTimeFactory $dateTimeFactory,
        EntityManager $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct($dateTimeFactory, $entityManager, $logger);
        $this->cratesService = $cratesService;
        $this->shipLocationsService = $shipLocationsService;
        $this->channelsService = $channelsService;
        $this->shipMovementService = $shipMovementService;
        $this->algorithmService = $algorithmService;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('game:worker:timed')
            ->setDescription('Process all actions that happen after a timeout');
    }

    protected function handle(DateTimeImmutable $now): int
    {
        $total = 0;
        // move stagnant probes. been in a port for one hour
        $total += $this->autoMoveShips($now);

        // move hoarded crates back to the port after one hour
        $total += $this->cratesService->restoreHoardedBackToPort($now, self::BATCH_SIZE);

        // todo - if the number of goalCrates is below x% of the user count, make a new one

        return $total;
    }

    private function autoMoveShips($now): int
    {
        // find ships of capacity 0 that have been sitting in a port for a while
        $shipsToMove = $this->shipLocationsService->getStagnantProbes($now, self::BATCH_SIZE);

        if (empty($shipsToMove)) {
            return 0;
        }

        $this->logger->info(\count($shipsToMove) . ' ships to move');

        // for each of them, find all the possible directions they can use
        $count = 0;
        foreach ($shipsToMove as $shipLocation) {
            /** @var ShipInPort $shipLocation */
            $port = $shipLocation->getPort();
            $ship = $shipLocation->getShip();
            $player = $shipLocation->getShip()->getOwner();

            // find all channels for a port, with their bearing and distance
            $channels = $this->channelsService->getAllLinkedToPort($port);

            // make direction objects
            $directions = \array_map(function(Channel $channel) use ($port, $player, $ship) {
                $destination = $channel->getDestination($port);

                return new Direction(
                    $destination,
                    $channel,
                    $player->getRank(),
                    $ship,
                    $this->algorithmService->getJourneyTime(
                        $channel->getDistance(),
                        $ship,
                        $player->getRank(),
                        ),
                    null,
                    $this->shipLocationsService->getLatestVisitTimeForPort($player, $port),
                );
            }, $channels);

            // of the possible directions, find which ones the ship is allowed to travel
            $directions = \array_filter($directions, function(Direction $direction) {
               return $direction->isAllowedToEnter();
            });

            // of the remaining directions, find one which the player has NOT been to before
            /** @var Direction[] $nextOptions */
            $nextOptions = \array_filter($directions, function (Direction $direction) {
                return !$direction->hasVisited();
            });
            $direction = null;
            if (!empty($nextOptions)) {
                $this->logger->info('Moving ship ' . $ship->getName() . ' to a new port');
                $direction = $nextOptions[\array_rand($nextOptions)];
            }

            // if not found, choose the one the player hasn't been to most recently
            if (!$direction) {
                usort($directions, function (Direction $a, Direction $b) {
                    // todo -check this sort is correct
                    return $a->getLastVisitTime() <=> $b->getLastVisitTime();
                });

                $this->logger->info('Moving ship ' . $ship->getName() . ' to a previously visited port');
                $direction = $directions[0];
            }

            $channel = $direction->getChannel();
            $this->shipMovementService->moveShip(
                $ship->getId(),
                $channel->getId(),
                $channel->isReversed($port),
                $direction->getJourneyTimeInterval(),
            );

            $count++;
        }

        return $count;
    }
}
