<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\CleanableInterface;
use App\Data\Database\EntityManager;
use App\Data\Database\EntityRepository\AbstractEntityRepository;
use App\Data\Database\Filters\DeletedItemsFilter;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Classes\whoImplements;

class CleanerCommand extends Command
{
    private const DURATION_TO_KEEP_DELETED = 'P7D';

    private EntityManager $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
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
        $now = DateTimeFactory::now();

        $this->logger->notice('[WORKER] [CLEANER] [STARTUP]');

        // the Cleaner must be able to see deleted items (in order to clean them)
        $this->entityManager->getFilters()->disable(DeletedItemsFilter::FILTER_NAME);

        /** @var AbstractEntityRepository[] $entityRepositories */
        $entityRepositories = $this->entityManager->getAll();
        $deleteOlderThan = $now->sub(new DateInterval(self::DURATION_TO_KEEP_DELETED));
        $this->logger->info('[CLEANER_DELETES] Cleaning rows deleted before ' . $deleteOlderThan->format('c'));

        foreach ($entityRepositories as $entityRepository) {
            $done = $entityRepository->removeDeletedBefore($deleteOlderThan);
            $msg = '[CLEANER_DELETES] ' . \get_class($entityRepository) . ' ' . $done;
            if ($done) {
                // only log if we actually did something
                $this->logger->notice($msg);
            } else {
                $this->logger->info($msg);
            }
        }

        $list = whoImplements(CleanableInterface::class, $entityRepositories);
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
