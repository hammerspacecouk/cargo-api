<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Domain\Entity\Channel;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Direction;
use App\Infrastructure\DateTimeFactory;
use App\Service\AlgorithmService;
use App\Service\ChannelsService;
use App\Service\CratesService;
use App\Service\ShipLocationsService;
use App\Service\Ships\ShipMovementService;
use App\Service\ShipsService;
use DateTimeImmutable;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_filter;
use function array_map;
use function ceil;
use function count;

class TimedCommand extends Command
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private AlgorithmService $algorithmService,
        private ChannelsService $channelsService,
        private CratesService $cratesService,
        private ShipsService $shipsService,
        private ShipLocationsService $shipLocationsService,
        private ShipMovementService $shipMovementService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
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
        $now = DateTimeFactory::now();
        $minute = (int)$now->format('i');
        $this->logger->notice('[WORKER] [TIMED] [STARTUP]');

        // move stagnant probes. been in a port for one hour
        $this->autoMoveShips($now);

        // decrease the health of infected
        if ($minute < 10) {
            $this->shipsService->reduceHealthOfInfected(0.99);
        }
        $this->shipsService->randomOutbreak();

        // move hoarded crates back to the port after one hour
        $this->cratesService->restoreHoardedBackToPort($now, self::BATCH_SIZE);

        $this->cratesService->ensureEnoughGoalCrates();

        $this->cratesService->retrieveLostCrates();

        $this->logger->notice(
            '[WORKER] [TIMED] [SHUTDOWN] ' . ceil((microtime(true) - $start) * 1000) . 'ms'
        );

        return 0;
    }

    private function autoMoveShips(DateTimeImmutable $now): void
    {
        // find probes that have been sitting in a port for a while
        $shipsToMove = $this->shipLocationsService->getStagnantProbes($now, self::BATCH_SIZE);

        if (empty($shipsToMove)) {
            return;
        }

        $this->logger->info(count($shipsToMove) . ' ships to move');

        // for each of them, find all the possible directions they can use
        foreach ($shipsToMove as $shipLocation) {
            if (!$shipLocation instanceof ShipInPort) {
                throw new LogicException('Required ShipInPort but got something else');
            }
            $port = $shipLocation->getPort();
            $ship = $shipLocation->getShip();
            $player = $shipLocation->getShip()->getOwner();

            if ($player->isTrial() && (
                    !$player->getRank()->isTrialRange() ||
                    $player->getRank()->isNearTrialEnd()
                )) {
                // don't auto-move users who's trials have ended
                continue;
            }

            // find all channels for a port, with their bearing and distance
            $channels = $this->channelsService->getAllLinkedToPort($port);

            $directions = $this->getDirections($port, $player, $ship, $channels);

            if (empty($directions)) {
                $this->logger->info('[AUTO_MOVE_SHIP] ' . $ship->getName() . ' NOWHERE TO GO');
                return;
            }

            $direction = $directions[0];

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

    private function getDirections(Port $port, User $player, Ship $ship, array $channels): array
    {
        // make direction objects
        $directions = array_map(function (Channel $channel) use ($port, $player, $ship) {
            $destination = $channel->getDestination($port);
            $origin = $channel->getDestination($destination);

            $allShipsInPort = $this->shipsService->findAllActiveInPort($port);
            $blockadeStrength = null;
            $yourStrength = null;
            $blockadedBy = $origin->getBlockadedBy();
            if ($blockadedBy && $origin->isBlockaded()) {
                // calculate the blockade strength
                $blockadeStrength = $this->strengthForOwner($blockadedBy, $allShipsInPort);
                if (!$blockadedBy->equals($player)) {
                    $yourStrength = $this->strengthForOwner($player, $allShipsInPort);
                }
            }

            return new Direction(
                $destination,
                $channel,
                $player->getRank(),
                $ship,
                false,
                $this->algorithmService->getJourneyTime(
                    $channel->getDistance(),
                    $ship,
                    $player,
                ),
                null,
                $this->shipLocationsService->getLatestVisitTimeForPort($player, $destination),
                [],
                $blockadeStrength,
                $yourStrength
            );
        }, $channels);

        // of the possible directions, find which ones the ship is allowed to travel
        /** @var Direction[] $directions */
        $directions = array_filter($directions, static function (Direction $direction) {
            return $direction->isAllowedToEnter();
        });

        $unvisited = array_filter(
            $directions,
            static fn (Direction $direction) => $direction->getLastVisitTime() === null
        );

        // prefer unvisited, randomise them to split up probes launched together
        if (!empty($unvisited)) {
            shuffle($unvisited);
            return array_values($unvisited);
        }

        // if there's no unvisited. try to avoid where you've just been
        $recent = $this->shipLocationsService->getRecentForShip($ship, 100);
        $options = [];
        foreach ($directions as $direction) {
            $added = false;
            foreach ($recent as $location) {
                if ($location instanceof ShipInPort &&
                    $location->getPort()->equals($direction->getDestinationPort())
                ) {
                    $options[$location->getEntryTime()->getTimestamp()] = $direction;
                    $added = true;
                }
            }
            if (!$added) {
                $options[random_int(1, 1000)] = $direction;
            }
        }

        ksort($options, SORT_NUMERIC);

        if (!empty($options)) {
            return array_values($options);
        }

        shuffle($directions);
        return array_values($directions);
    }

    /**
     * @param User $owner
     * @param Ship[] $allShips
     * @return int
     */
    private function strengthForOwner(User $owner, array $allShips): int
    {
        return array_reduce(
            $allShips,
            static function (int $carry, Ship $ship) use ($owner) {
                if ($ship->getOwner()->equals($owner)) {
                    return $carry + $ship->getStrength();
                }
                return $carry;
            },
            0
        );
    }
}
