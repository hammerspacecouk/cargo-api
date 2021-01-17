<?php
declare(strict_types=1);

namespace App\Command\Maintenance;

use App\Service\CratesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCrateCommand extends Command
{
    private CratesService $cratesService;

    public function __construct(CratesService $cratesService)
    {
        parent::__construct();
        $this->cratesService = $cratesService;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:maintenance:make-crates')
            ->setDescription('Creates new crates');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln('Making a new crate');

        $i = 500;
        while ($i > 0) {
            $this->cratesService->makeNew();
            $i--;
        }
        $output->writeln('Done');

        return 0;
    }
}
