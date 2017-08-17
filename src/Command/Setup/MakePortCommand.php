<?php
declare(strict_types=1);
namespace App\Command\Setup;

use App\Data\Database\Entity\Port;
use App\Service\PortsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakePortCommand extends Command
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
            ->setName('game:setup:make-port')
            ->setDescription('Creates a new port and places it on the map')
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Making a new port');

        $this->portsService->makeNew();

        $output->writeln('Done');
    }
}
