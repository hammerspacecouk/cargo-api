<?php
declare(strict_types=1);

namespace App\Command\Worker;

use App\Data\Database\CleanableInterface;
use App\Data\Database\EntityManager;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

use function App\Functions\Classes\whoImplements;

class CleanerCommand extends AbstractWorkerCommand
{
    private $entityManager;

    public function __construct(
        EntityManager $entityManager,
        DateTimeImmutable $currentTime,
        LoggerInterface $logger
    ) {
        parent::__construct($currentTime, $logger);
        $this->entityManager = $entityManager;
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

    protected function handle(DateTimeImmutable $now): int
    {
        $list = whoImplements(CleanableInterface::class, $this->entityManager->getAll());
        $this->logger->info('[CLEANER ACTIVE] ' . \count($list) . ' Cleaners');

        $total = 0;
        foreach ($list as $repo) {
            /** @var CleanableInterface $repo */
            $done = $repo->clean($now);
            $this->logger->notice('[CLEANER CLEANED] ' . \get_class($repo) . ' ' . (string)$done);
            $total += $done;
        }
        return $total;
    }
}
