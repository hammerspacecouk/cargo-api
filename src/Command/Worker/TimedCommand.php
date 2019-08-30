<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Domain\Entity\Channel;
use App\Domain\Entity\ShipInPort;
use App\Domain\ValueObject\Direction;
use App\Infrastructure\DateTimeFactory;
use App\Service\AlgorithmService;
use App\Service\ChannelsService;
use App\Service\CratesService;
use App\Service\ShipLocationsService;
use App\Service\Ships\ShipMovementService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TimedCommand extends Command
{
    private const BATCH_SIZE = 100;

    private $cratesService;
    private $shipLocationsService;
    private $channelsService;
    private $shipMovementService;
    private $algorithmService;
    private $dateTimeFactory;
    private $logger;

    public function __construct(
        AlgorithmService $algorithmService,
        ChannelsService $channelsService,
        CratesService $cratesService,
        ShipLocationsService $shipLocationsService,
        ShipMovementService $shipMovementService,
        DateTimeFactory $dateTimeFactory,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->cratesService = $cratesService;
        $this->shipLocationsService = $shipLocationsService;
        $this->channelsService = $channelsService;
        $this->shipMovementService = $shipMovementService;
        $this->algorithmService = $algorithmService;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('game:worker:timed')
            ->setDescription('Process all actions that happen after a timeout');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $start = microtime(true);
        $now = $this->dateTimeFactory->now();
        $this->logger->notice('[WORKER] [TIMED] [STARTUP]');

        // move stagnant probes. been in a port for one hour
        $this->autoMoveShips($now);

        // move hoarded crates back to the port after one hour
        $this->cratesService->restoreHoardedBackToPort($now, self::BATCH_SIZE);

        // todo - if the number of goalCrates is below x% of the user count, make a new one

        $this->logger->notice(
            '[WORKER] [TIMED] [SHUTDOWN] ' . (string)\ceil((microtime(true) - $start) * 1000) . 'ms'
        );

        return 0;
    }

    private function autoMoveShips($now): void
    {
        // find ships of capacity 0 that have been sitting in a port for a while
        $shipsToMove = $this->shipLocationsService->getStagnantProbes($now, self::BATCH_SIZE);

        if (empty($shipsToMove)) {
            return;
        }

        $this->logger->info(\count($shipsToMove) . ' ships to move');

        // for each of them, find all the possible directions they can use
        foreach ($shipsToMove as $shipLocation) {
            /** @var ShipInPort $shipLocation */
            $port = $shipLocation->getPort();
            $ship = $shipLocation->getShip();
            $player = $shipLocation->getShip()->getOwner();

            // find all channels for a port, with their bearing and distance
            $channels = $this->channelsService->getAllLinkedToPort($port);

            // make direction objects
            $directions = \array_map(function (Channel $channel) use ($port, $player, $ship) {
                $destination = $channel->getDestination($port);

                return new Direction(
                    $destination,
                    $channel,
                    $player->getRank(),
                    $ship,
                    false,
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
            $directions = \array_filter($directions, static function (Direction $direction) {
                return $direction->isAllowedToEnter();
            });

            // of the remaining directions, find one which the player has NOT been to before
            /** @var Direction[] $nextOptions */
            // Use dumb selection for now. todo - navigation computer upgrade that checks hasVisited()
            $nextOptions = $directions;

            $direction = null;
            if (!empty($nextOptions)) {
                $this->logger->info('Moving ship ' . $ship->getName() . ' to a new port');
                $direction = $nextOptions[\array_rand($nextOptions)];
            }

            // if not found, choose the one the player hasn't been to most recently
            if (!$direction) {
                usort($directions, static function (Direction $a, Direction $b) {
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
                0, // auto-moved ships don't earn anything
            );
            $this->logger->info('[AUTO_MOVE_SHIP] ' . $ship->getName());
        }
    }
}
