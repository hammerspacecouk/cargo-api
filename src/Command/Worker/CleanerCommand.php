<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\CleanableInterface;
use App\Data\Database\EntityManager;
use App\Infrastructure\DateTimeFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Classes\whoImplements;

class CleanerCommand extends Command
{
    private $entityManager;
    private $dateTimeFactory;
    private $logger;

    public function __construct(
        EntityManager $entityManager,
        DateTimeFactory $dateTimeFactory,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('game:worker:cleaner')
            ->setDescription(
                'Finds all EntityRepository classes that implement CleanableInterface and calls their clean() method.'
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $start = microtime(true);
        $now = $this->dateTimeFactory->now();

        $this->logger->notice('[WORKER] [CLEANER] [STARTUP]');

        $list = whoImplements(CleanableInterface::class, $this->entityManager->getAll());
        $this->logger->info('[CLEANER ACTIVE] ' . \count($list) . ' Cleaners');

        foreach ($list as $repo) {
            /** @var CleanableInterface $repo */
            $done = $repo->clean($now);
            $msg = '[CLEANER_CLEANED] ' . \get_class($repo) . ' ' . $done;
            if ($done) {
                // only log if we actually did something
                $this->logger->notice($msg);
            } else {
                $this->logger->info($msg);
            }
        }

        $this->logger->notice(
            '[WORKER] [CLEANER] [SHUTDOWN] ' . (string)\ceil((microtime(true) - $start) * 1000) . 'ms'
        );

        return 0;
    }
}
