<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\EntityManager;
use App\Service\ShipLocationsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ArrivalCommand extends Command
{
    private $locationsService;
    private $logger;

    public function __construct(
        ShipLocationsService $shipLocationsService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->locationsService = $shipLocationsService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('game:worker:arrival')
            ->setDescription('Process all arrivals');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->logger->info(
            '[WORKER] [ARRIVAL] [START]'
        );
        $this->locationsService->processOldestExpired(50);
        $this->logger->notice(
            '[WORKER] [ARRIVAL] [COMPLETE]'
        );
    }
}
