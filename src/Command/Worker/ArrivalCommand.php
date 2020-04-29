<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\EntityManager;
use App\Service\ShipLocationsService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class ArrivalCommand extends ContinuousWorkerCommand
{
    private ShipLocationsService $locationsService;

    public function __construct(
        ShipLocationsService $shipLocationsService,
        EntityManager $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct($entityManager, $logger);
        $this->locationsService = $shipLocationsService;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('game:worker:arrival')
            ->setDescription('Process all arrivals');
    }

    protected function handle(DateTimeImmutable $now): int
    {
        return $this->locationsService->processOldestExpired($now, self::BATCH_SIZE);
    }
}
