<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Infrastructure\DateTimeFactory;
use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractWorkerCommand extends Command
{
    protected const MAX_TTL = ((3 * 60) - 1);
    protected const BATCH_SIZE = 100;

    protected $logger;
    protected $dateTimeFactory;

    public function __construct(
        DateTimeFactory $dateTimeFactory,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $workerName = static::class;
        $this->logger->info("[WORKER] [$workerName] [STARTUP]");
        $calcTimeStart = time(); // use to calculate a seconds offset only
        $diff = 0;

        while ($diff < static::MAX_TTL) {
            $now = $this->dateTimeFactory->now();
            $this->logger->info("[WORKER] [$workerName] [CHECKING] {$now->format(DateTimeFactory::FULL)}");
            $processed = $this->handle($now);
            $this->logger->info("[WORKER] [$workerName] [BATCH] $processed");

            // if the total processed is less than the batch then we need to wait for new items
            if ($processed < static::BATCH_SIZE) {
                sleep(1);
            }
            $diff = (time() - $calcTimeStart);
        }
        $this->logger->notice("[WORKER] [$workerName] [SHUTDOWN]");
    }

    abstract protected function handle(DateTimeImmutable $now): int;
}
