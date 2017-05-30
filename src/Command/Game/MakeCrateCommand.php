<?php
namespace App\Command\Game;

use App\Data\Database\Entity\Crate;
use App\Service\CratesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCrateCommand extends Command
{
    private $cratesService;

    public function __construct(CratesService $cratesService)
    {
        parent::__construct();
        $this->cratesService = $cratesService;
    }

    protected function configure()
    {
        $this
            ->setName('game:make-crate')
            ->setDescription('Creates a new crate and places it on the map')
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Making a new crate');

        $this->cratesService->makeNew();

        $output->writeln('Done');
    }
}
