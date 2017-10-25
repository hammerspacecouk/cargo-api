<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\EntityManager;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractWorkerCommand extends Command
{
    protected const SLEEP_TIME = 30;

    protected $logger;
    protected $entityManager;

    public function __construct(EntityManager $entityManager, LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->addOption(
            'loops',
            'l',
            InputArgument::OPTIONAL,
            'Number of loops to run before shutdown'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $numberToRun = $input->getOption('loops');

        // todo - handle the looping of the worker, catching Exception, lease etc

        $loopCount = 1;
        while(true) { // todo - smarter looping
            // ensure the doctrine cache is clear (so each loop is totally independent)
            $this->entityManager->clear();
            $this->logger->info(
                '[WORKER] [' . $this->getWorkerName() . '] [START] ' . $loopCount
            );
            try {
                $this->processSingle($input, $output);
            } catch (NothingToProcessException $e) {
                $this->logger->notice(
                    '[WORKER] [' . $this->getWorkerName() . '] [CAUGHT UP] ' .
                    'Sleeping for ' . static::SLEEP_TIME . ' seconds'
                );
                $this->clearLogger();
                sleep(static::SLEEP_TIME);
            }
            $this->logger->notice(
                '[WORKER] [' . $this->getWorkerName() . '] [COMPLETE] ' . $loopCount
            );
            $this->clearLogger();
            if ($numberToRun && $loopCount >= $numberToRun) {
                break;
            }
            $loopCount++;
        }
    }

    protected function getWorkerName(): string
    {
        return str_replace(__NAMESPACE__ . '\\', '', static::class);
    }

    private function clearLogger()
    {
        // echo out the lines of the logger
        // and clear the fingers crossed data
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

    abstract protected function processSingle(InputInterface $input, OutputInterface $output): void;
}
