<?php
namespace App\Command\Action;

use App\Service\ShipsService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MoveShipCommand extends Command
{
    private $shipsService;

    public function __construct(
        ShipsService $shipsService
    ) {
        parent::__construct();
        $this->shipsService = $shipsService;
    }

    protected function configure()
    {
        $this
            ->setName('play:action:move-ship')
            ->setDescription('Move a ship into a port')
            ->addArgument(
                'shipId',
                InputArgument::REQUIRED,
                'The ship ID to move'
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
        $shipId = Uuid::fromString($input->getArgument('shipId'));
        $destinationId = Uuid::fromString($input->getArgument('destinationID'));

        $output->writeln('Will be moving ship ' . $shipId . ' to ' . $destinationId);

        $this->shipsService->moveShipToLocation($shipId, $destinationId);

        $output->writeln('Done');
    }
}
