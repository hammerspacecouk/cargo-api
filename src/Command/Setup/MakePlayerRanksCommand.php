<?php
declare(strict_types=1);
namespace App\Command\Setup;

use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\EntityManager;
use App\Data\ID;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakePlayerRanksCommand extends Command
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
            ->setName('game:setup:make-player-ranks')
            ->setDescription('One off command for populating player ranks table')
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Making the ranks');

        $sourceData = $this->getSourceData();
        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($this->getSourceData() as $data) {
            $shipClass = new PlayerRank(
                Uuid::fromString($data[0]),
                $data[1],
                $data[2],
                $data[3]
            );
            $shipClass->uuid = (string) $shipClass->id;
            $this->entityManager->persist($shipClass);

            $progress->advance();
        }

        $progress->finish();

        $this->entityManager->flush();

        $output->writeln('');
        $output->writeln('Done');
    }

    private function getSourceData()
    {
        // todo, move this to XML, JSON, YML or CSV - and add the real ones
        return [
            [ID::makeNewID(PlayerRank::class), 'Tutorial', 100, 0],
            [ID::makeNewID(PlayerRank::class), 'Rookie', 200, 1],
            [ID::makeNewID(PlayerRank::class), 'Captain', 5000, 20],
            [ID::makeNewID(PlayerRank::class), 'Admiral', 10000, 50],
        ];
    }
}
