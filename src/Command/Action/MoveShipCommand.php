<?php declare(strict_types=1);

namespace App\Command\Action;

use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MoveShipCommand extends Command
{
    private $shipsService;
    private $logger;

    public function __construct(
        ShipsService $shipsService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->shipsService = $shipsService;
        $this->logger = $logger;
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
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        die('todo - convert to using the controller action');
        $this->logger->debug(__CLASS__);
        $shipId = Uuid::fromString($input->getArgument('shipId'));
        $destinationId = Uuid::fromString($input->getArgument('destinationID'));

        $output->writeln('Will be moving ship ' . (string)$shipId . ' to ' . (string)$destinationId);

        $this->shipsService->moveShipToLocation($shipId, $destinationId);
    }
}
