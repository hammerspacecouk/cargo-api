<?php
namespace App\Command\Manual;

use App\Service\ShipsService;
use App\Service\PortsService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MoveShipCommand extends Command
{
    private $shipsService;
    private $portsService;

    public function __construct(
        ShipsService $shipsService,
        PortsService $portsService
    ) {
        parent::__construct();
        $this->shipsService = $shipsService;
        $this->portsService = $portsService;
    }

    protected function configure()
    {
        $this
            ->setName('game:manual:move-ship')
            ->setDescription('Move a ship into a port')
            ->addArgument(
                'shipId',
                InputArgument::REQUIRED,
                'The ship ID to move'
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
        $shipId = Uuid::fromString($input->getArgument('shipId'));
        $portId = Uuid::fromString($input->getArgument('portId'));

        $output->writeln('Will be moving ship ' . $shipId . ' to port ' . $portId);

        $this->shipsService->moveShipToPort($shipId, $portId);

        $output->writeln('Done');
    }
}
