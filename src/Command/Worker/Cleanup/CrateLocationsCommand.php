<?php
declare(strict_types=1);

namespace App\Command\Worker\Cleanup;

use App\Command\Worker\AbstractWorkerCommand;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class CrateLocationsCommand extends AbstractWorkerCommand
{
    protected const BATCH_SIZE = 1000;

    public function __construct(
        DateTimeImmutable $currentTime,
        LoggerInterface $logger
    ) {
        parent::__construct($currentTime, $logger);
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('game:worker:cleanup:crate-locations')
            ->setDescription('Cleanup used crate locations');
    }

    protected function handle(DateTimeImmutable $now): int
    {
        throw new \RuntimeException('Not yet ready'); // todo
    }
}
