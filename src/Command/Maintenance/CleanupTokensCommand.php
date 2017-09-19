<?php declare(strict_types=1);

namespace App\Command\Maintenance;

use App\Data\Database\Entity\Token;
use App\Data\Database\EntityManager;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupTokensCommand extends Command
{
    private $entityManager;
    private $now;

    public function __construct(
        EntityManager $entityManager,
        DateTimeImmutable $now
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->now = $now;
    }

    protected function configure()
    {
        $this
            ->setName('game:maintenance:cleanup-tokens')
            ->setDescription('Cleans up the invalid tokens list to remove those we no longer need to check');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Cleaning');

        $this->entityManager->getRepository(Token::class)
            ->removeExpired($this->now);

        $output->writeln('Done');
    }
}
