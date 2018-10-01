<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\ShipClass;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function App\Functions\Transforms\csvToArray;

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
            ->setName('game:init:make-ship-classes')
            ->setDescription('One off command for populating ship classes table')
            ->addArgument(
                'inputList',
                InputArgument::REQUIRED,
                'File path of data source (.csv)'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Making the classes');

        $filePath = $input->getArgument('inputList');
        $sourceData = csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($sourceData as $data) {
            if (empty($data['name'])) {
                $progress->advance();
                continue;
            }

            $id = Uuid::fromString($data['uuid']);
            $name = $data['name'];
            $orderNumber = (int)$data['orderNumber'];
            $minimumRank = $this->getPlayerRank($data['minimumRankId']);
            $capacity = (int)$data['capacity'];
            $purchaseCost = (int)$data['purchaseCost'];
            $isStarterShip = (bool)$data['isStarterShip'];
            /** @var ShipClass $entity */
            $entity = $this->entityManager->getShipClassRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->name = $name;
                $entity->orderNumber = $orderNumber;
                $entity->minimumRank = $minimumRank;
                $entity->capacity = $capacity;
                $entity->purchaseCost = $purchaseCost;
                $entity->isStarterShip = $isStarterShip;
            } else {
                $entity = new ShipClass(
                    $name,
                    $orderNumber,
                    $capacity,
                    $isStarterShip,
                    $purchaseCost,
                    $minimumRank
                );
                $entity->id = $id;
            }

            $this->entityManager->persist($entity);
            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();

        $output->writeln('');
        $output->writeln('Done');
    }

    private function getPlayerRank(string $inputString): PlayerRank
    {
        $rank = $this->entityManager->getPlayerRankRepo()->getByID(
            Uuid::fromString($inputString),
            Query::HYDRATE_OBJECT
        );
        if ($rank) {
            return $rank;
        }
        throw new \InvalidArgumentException('No such rank for Minimum Rank');
    }
}
