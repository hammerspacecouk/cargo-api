<?php
namespace App\Command\Game;

use App\Service\ShipsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeShipCommand extends Command
{
    private $shipsService;

    public function __construct(ShipsService $shipsService)
    {
        parent::__construct();
        $this->shipsService = $shipsService;
    }

    protected function configure()
    {
        $this
            ->setName('game:make-ship')
            ->setDescription('Creates a new ship')
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Making a new ship');

        $this->shipsService->makeNew();

        $output->writeln('Done');
    }
}
