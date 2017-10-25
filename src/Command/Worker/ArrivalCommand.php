<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\EntityManager;
use App\Service\ShipLocationsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ArrivalCommand extends AbstractWorkerCommand
{
    protected const SLEEP_TIME = 15;

    private $locationsService;

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

    protected function processSingle(InputInterface $input, OutputInterface $output): void
    {
        $process = $this->locationsService->processOldestExpired();
        if (!$process) {
            throw new NothingToProcessException('Nothing to process');
        }
    }
}
