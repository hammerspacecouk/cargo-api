<?php
declare(strict_types=1);

namespace App\Command\Worker\Cleanup;

use App\Command\Worker\AbstractWorkerCommand;
use App\Service\ShipLocationsService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class ShipLocationsCommand extends AbstractWorkerCommand
{
    protected const BATCH_SIZE = 1000;
    private $locationsService;

    public function __construct(
        ShipLocationsService $locationsService,
        DateTimeImmutable $currentTime,
        LoggerInterface $logger
    ) {
        parent::__construct($currentTime, $logger);
        $this->locationsService = $locationsService;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('game:worker:cleanup:ship-locations')
            ->setDescription('Cleanup used auth tokens');
    }

    protected function handle(DateTimeImmutable $now): int
    {
        return $this->locationsService->removeInactive($now);
    }
}
