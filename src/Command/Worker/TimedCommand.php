<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\EntityManager;
use App\Infrastructure\DateTimeFactory;
use App\Service\CratesService;
use App\Service\ShipLocationsService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class TimedCommand extends AbstractWorkerCommand
{
    private $cratesService;
    private $shipLocationsService;

    public function __construct(
        CratesService $cratesService,
        ShipLocationsService $shipLocationsService,
        DateTimeFactory $dateTimeFactory,
        EntityManager $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct($dateTimeFactory, $entityManager, $logger);
        $this->cratesService = $cratesService;
        $this->shipLocationsService = $shipLocationsService;
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
        $total += $this->shipLocationsService->autoMoveShips($now, self::BATCH_SIZE);

        // move hoarded crates back to the port after one hour
        $total += $this->cratesService->restoreHoardedBackToPort($now, self::BATCH_SIZE);

        // todo - if it's been a while since the last goalCrate was made, make a new one

        return $total;
    }
}
