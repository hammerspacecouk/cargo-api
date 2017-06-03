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
    private $portsService;

    public function __construct(
        CratesService $cratesService,
        PortsService $portsService
    ) {
        parent::__construct();
        $this->cratesService = $cratesService;
        $this->portsService = $portsService;
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
                'portId',
                InputArgument::REQUIRED,
                'The port ID to move to'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $crateId = Uuid::fromString($input->getArgument('crateId'));
        $portId = Uuid::fromString($input->getArgument('portId'));

        $output->writeln('Will be moving crate ' . $crateId . ' to port ' . $portId);

        $this->cratesService->moveCrateToPort($crateId, $portId);

        $output->writeln('Done');
    }
}
