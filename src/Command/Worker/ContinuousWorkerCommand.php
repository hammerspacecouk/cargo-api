<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\EntityManager;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Monolog\Handler\FingersCrossedHandler;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ContinuousWorkerCommand extends Command
{
    protected const BATCH_SIZE = 100;
    private const MAX_MEMORY_PERCENT = 90;

    protected $logger;
    protected $dateTimeFactory;
    protected $entityManager;

    public function __construct(
        DateTimeFactory $dateTimeFactory,
        EntityManager $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        \gc_enable();

        $workerName = static::class;
        $memoryLimit = \ini_get('memory_limit');
        $memoryLimitBytes = $this->getMemoryLimitBytes((string)$memoryLimit);

        $this->logger->notice("[WORKER] [$workerName] [STARTUP]");
        $this->logger->notice("[MEMORY_LIMIT] $memoryLimit");

        // run forever (unless an exit condition is reached)
        $usedMemory = 0;
        $memoryPercent = 0;
        while ($memoryPercent < self::MAX_MEMORY_PERCENT) {
            // reset the time at the start each loop
            $now = $this->dateTimeFactory->now();

            // handle the batch
            $this->logger->info("[WORKER] [$workerName] [CHECKING] {$now->format(DateTimeFactory::FULL)}");
            $processed = $this->handle($now);
            $this->logger->info("[WORKER] [$workerName] [BATCH] $processed");

            // if the total processed is less than the batch then we need to wait for new items
            if ($processed < static::BATCH_SIZE) {
                sleep(1);
            }

            // cleanup/flush at the end of a loop
            $this->entityManager->clear();
            $this->flushLogger();
            \gc_collect_cycles();

            // check we're good on memory
            $usedMemory = \memory_get_usage();
            $memoryPercent = ($usedMemory / $memoryLimitBytes) * 100;
            $this->logger->info("[MEMORY_USAGE_PERCENT] $memoryPercent");
        }
        $this->logger->notice("[FINAL_MEMORY_USAGE] $usedMemory");
        $this->logger->notice("[FINAL_MEMORY_USAGE_PERCENT] $memoryPercent");
        $this->logger->notice("[WORKER] [$workerName] [SHUTDOWN]");

        return 0;
    }

    private function flushLogger(): void
    {
        if ($this->logger instanceof Logger) {
            $handlers = $this->logger->getHandlers();
            foreach ($handlers as $handler) {
                if ($handler instanceof FingersCrossedHandler) {
                    $handler->close();
                    $handler->clear();
                }
            }
        }
    }

    private function getMemoryLimitBytes(string $memoryLimitString): int
    {
        if (\preg_match('/^(\d+)(.)$/', $memoryLimitString, $matches)) {
            if ($matches[2] === 'M') {
                return (int)($matches[1] * 1024 * 1024);
            }
            if ($matches[2] === 'K') {
                return (int)($matches[1] * 1024);
            }
        }
        throw new \RuntimeException('Could not calculate memory limit');
    }

    abstract protected function handle(DateTimeImmutable $now): int;
}
