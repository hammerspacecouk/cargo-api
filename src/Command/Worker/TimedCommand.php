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

    private $cratesService;
    private $shipLocationsService;
    private $channelsService;
    private $shipMovementService;
    private $algorithmService;
    private $dateTimeFactory;
    private $logger;
    /**
     * @var ShipsService
     */
    private ShipsService $shipsService;

    public function __construct(
        AlgorithmService $algorithmService,
        ChannelsService $channelsService,
        CratesService $cratesService,
        ShipsService $shipsService,
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
        $this->shipsService = $shipsService;
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

            // find all channels for a port, with their bearing and distance
            $channels = $this->channelsService->getAllLinkedToPort($port);

            // make direction objects
            $directions = array_map(function (Channel $channel) use ($port, $player, $ship) {
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
            /** @var Direction[] $directions */
            $directions = array_filter($directions, static function (Direction $direction) {
                return $direction->isAllowedToEnter();
            });

            usort($directions, static function (?Direction $a, ?Direction $b) {
                if ($a === $b) {
                    return 0;
                }
                if ($a === null) {
                    return -1;
                }
                if ($b === null) {
                    return 1;
                }
                return $a->getLastVisitTime() <=> $b->getLastVisitTime();
            });

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
}
