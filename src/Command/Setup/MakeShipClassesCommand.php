<?php
declare(strict_types=1);
namespace App\Command\Setup;

use App\Data\Database\Entity\ShipClass;
use App\Data\Database\EntityManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeShipClassesCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('game:setup:make-ship-classes')
            ->setDescription('One off command for populating enum table')
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Making the classes');

        foreach ($this->getSourceData() as $data) {
            $output->writeln('Making ' . $data[0]);
            $shipClass = new ShipClass(
                Uuid::fromString($data[0]),
                $data[1],
                $data[2],
                $data[3],
                $data[4],
                $data[5]
            );
            $shipClass->uuid = (string) $shipClass->id;
            $this->entityManager->persist($shipClass);
        }

        $this->entityManager->flush();

        $output->writeln('Done');
    }

    private function getSourceData()
    {
        // todo, move this to XML, JSON, YML or CSV
        return [
            ['c274d46f-5b3b-433c-81a8-ac9f97247699', 'Paddle Boat', 100, 2, true, 20000],
            ['e7b213e8-5b3b-456d-b32a-85ae277a09ee', 'Sail Boat', 200, 3, false, 300],
            ['6cef17a5-5b3b-47df-9027-579172b19498', 'Container Ship', 10000, 50, false, 100000],
        ];
    }
}
