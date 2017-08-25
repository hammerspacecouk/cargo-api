<?php
declare(strict_types=1);
namespace App\Command\Setup;

use App\Data\Database\Entity\PlayerStanding;
use App\Data\Database\Entity\Port;
use App\Data\Database\EntityManager;
use App\Data\ID;
use App\Service\PortsService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeClosedPortsCommand extends Command
{
    private $entityManager;
    private $portsService;

    public function __construct(
        PortsService $portsService,
        EntityManager $entityManager
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->portsService = $portsService;
    }

    protected function configure()
    {
        $this
            ->setName('game:setup:make-closed-ports')
            ->setDescription('One off command for ensuring there are 1000 ports')
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Counting the current ports');
        $total = $this->portsService->countAll();
        $need = 1000 - $total;

        $output->writeln('Need ' . $need);

        $output->writeln('Making the ports');

        $progress = new ProgressBar($output, count($need));
        $progress->start();

        for ($i = 1; $i <= $need; $i++) {
            $name = '__CLOSED_PORT__' . $i;
            $port = new Port(
                ID::makeNewID(Port::class),
                $name,
                false
            );
            $this->entityManager->persist($port);

            $progress->advance();
        }

        $progress->finish();

        $this->entityManager->flush();

        $output->writeln('');
        $output->writeln('Done');
    }
}
