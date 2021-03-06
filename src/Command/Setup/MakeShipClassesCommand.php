<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\ShipClass;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Transforms\csvToArray;

class MakeShipClassesCommand extends AbstractCommand
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
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
    ): int {
        $output->writeln('Making the classes');

        $filePath = $this->getStringArgument($input, 'inputList');
        $sourceData = csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($sourceData as $data) {
            if (empty($data['name']) || (bool)($data['ignore'] ?? false)) {
                $progress->advance();
                continue;
            }

            $id = Uuid::fromString($data['uuid']);
            $name = $data['name'];
            $description = $data['description'];
            $orderNumber = (int)$data['orderNumber'];
            $strength = (int)$data['strength'];
            $autoNavigate = (bool)$data['autoNavigate'];
            $minimumRank = $this->getPlayerRank($data['minimumRankId']);
            $capacity = (int)$data['capacity'];
            $speedMultiplier = (float)$data['speedMultiplier'];
            $purchaseCost = (int)$data['purchaseCost'];
            $isStarterShip = (bool)$data['isStarterShip'];
            $isDefenceShip = (bool)$data['isDefenceShip'];
            $isHospitalShip = (bool)$data['isHospitalShip'];
            $displayStrength = (int)$data['displayStrength'];
            $displaySpeed = (int)$data['displaySpeed'];
            $displayCapacity = (int)$data['displayCapacity'];
            $svg = trim($data['svg']);
            /** @var ShipClass|null $entity */
            $entity = $this->entityManager->getShipClassRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->name = $name;
                $entity->description = $description;
                $entity->strength = $strength;
                $entity->autoNavigate = $autoNavigate;
                $entity->orderNumber = $orderNumber;
                $entity->minimumRank = $minimumRank;
                $entity->capacity = $capacity;
                $entity->speedMultiplier = $speedMultiplier;
                $entity->purchaseCost = $purchaseCost;
                $entity->isStarterShip = $isStarterShip;
                $entity->isDefenceShip = $isDefenceShip;
                $entity->isHospitalShip = $isHospitalShip;
                $entity->displayCapacity = $displayCapacity;
                $entity->displaySpeed = $displaySpeed;
                $entity->displayStrength = $displayStrength;
                $entity->svg = $svg;
            } else {
                $entity = new ShipClass(
                    $name,
                    $description,
                    $strength,
                    $autoNavigate,
                    $orderNumber,
                    $capacity,
                    $speedMultiplier,
                    $isStarterShip,
                    $isDefenceShip,
                    $isHospitalShip,
                    $purchaseCost,
                    $svg,
                    $displayCapacity,
                    $displaySpeed,
                    $displayStrength,
                    $minimumRank
                );
                $entity->id = $id;
            }
            $entity->uuid = $id->toString();

            $this->entityManager->persist($entity);
            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();

        $output->writeln('');
        $output->writeln('Done');

        return 0;
    }

    private function getPlayerRank(string $inputString): PlayerRank
    {
        $rank = $this->entityManager->getPlayerRankRepo()->getByID(
            Uuid::fromString($inputString),
            Query::HYDRATE_OBJECT,
        );
        if ($rank) {
            return $rank;
        }
        throw new \InvalidArgumentException('No such rank for Minimum Rank');
    }
}
