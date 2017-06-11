<?php
namespace App\Command\Game;

use App\Domain\ValueObject\Bearing;
use App\Service\PortsService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeChannelCommand extends Command
{
    private $portsService;

    public function __construct(PortsService $portsService)
    {
        parent::__construct();
        $this->portsService = $portsService;
    }

    protected function configure()
    {
        $this
            ->setName('game:make-channel')
            ->setDescription('Creates a new channel')
            ->addArgument(
                'fromPortId',
                InputArgument::REQUIRED,
                'The port the channel starts from'
            )
            ->addArgument(
                'toPortId',
                InputArgument::REQUIRED,
                'The port the channel goes to'
            )
            ->addArgument(
                'bearing',
                InputArgument::REQUIRED,
                'The bearing from the start port (NE, E, SE, SW, W, NW)'
            )
            ->addArgument(
                'distance',
                InputArgument::REQUIRED,
                'The distance unit'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $fromId = Uuid::fromString($input->getArgument('fromPortId'));
        $toId = Uuid::fromString($input->getArgument('toPortId'));
        $bearing = new Bearing($input->getArgument('bearing'));
        $distance = (int) $input->getArgument('distance');

        $output->writeln('Making a new channel');

        $this->portsService->makeChannelBetween(
            $fromId,
            $toId,
            $bearing,
            $distance
        );

        $output->writeln('Done');
    }
}
