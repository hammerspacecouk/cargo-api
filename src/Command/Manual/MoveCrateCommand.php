<?php
namespace App\Command\Manual;

use App\Service\CratesService;
use App\Service\PortsService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MoveCrateCommand extends Command
{
    private $cratesService;

    public function __construct(
        CratesService $cratesService
    ) {
        parent::__construct();
        $this->cratesService = $cratesService;
    }

    protected function configure()
    {
        $this
            ->setName('game:manual:move-crate')
            ->setDescription('Move a crate into a port')
            ->addArgument(
                'crateId',
                InputArgument::REQUIRED,
                'The crate ID to move'
            )
            ->addArgument(
                'destinationID',
                InputArgument::REQUIRED,
                'The ID of the destination'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $crateId = Uuid::fromString($input->getArgument('crateId'));
        $destinationId = Uuid::fromString($input->getArgument('destinationID'));

        $output->writeln('Will be moving crate ' . $crateId . ' to ' . $destinationId);

        $this->cratesService->moveCrateToLocation($crateId, $destinationId);

        $output->writeln('Done');
    }
}
