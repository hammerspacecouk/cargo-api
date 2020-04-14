<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\Channel;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\Port;
use App\Data\Database\EntityManager;
use App\Domain\ValueObject\Bearing;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Transforms\csvToArray;

class MakeChannelsCommand extends AbstractCommand
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:init:make-channels')
            ->setDescription('One off command for populating channel data')
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
        $output->writeln('Making the channels between ports');

        $filePath = $this->getStringArgument($input, 'inputList');
        $sourceData = csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($sourceData as $data) {
            $this->handleRow($data);
            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();

        $output->writeln('');
        $output->writeln('Done');

        return 0;
    }

    private function handleRow(array $data): void
    {
        if (!isset($data['uuid']) || empty($data['uuid'])) {
            return;
        }
        $id = Uuid::fromString($data['uuid']);
        if (empty($data['fromPortId']) || (bool)($data['ignore'] ?? false)) {
            $entity = $this->entityManager->getChannelRepo()->getByID($id, Query::HYDRATE_OBJECT);
            if ($entity) {
                $this->entityManager->remove($entity);
            }
            return;
        }

        $fromPort = $this->getPort($data['fromPortId']);
        $toPort = $this->getPort($data['toPortId']);

        if ((string)$fromPort->id === (string)$toPort->id) {
            throw new \InvalidArgumentException('To and From cannot be the same');
        }

        $bearing = Bearing::validate($data['bearing']);

        $distance = (int)$data['distance'];
        $minimumStrength = (int)$data['minimumStrength'];
        $minimumEntryRank = $this->getPlayerRank($data['minimumEntryRankId']);

        $this->makeOrUpdateEntity($id, $fromPort, $toPort, $bearing, $distance, $minimumStrength, $minimumEntryRank);
    }

    private function makeOrUpdateEntity(
        UuidInterface $id,
        Port $fromPort,
        Port $toPort,
        string $bearing,
        int $distance,
        int $minimumStrength,
        ?PlayerRank $minimumEntryRank
    ): void {
        /** @var Channel|null $entity */
        $entity = $this->entityManager->getChannelRepo()->getByID($id, Query::HYDRATE_OBJECT);

        if ($entity) {
            $entity->fromPort = $fromPort;
            $entity->toPort = $toPort;
            $entity->bearing = $bearing;
            $entity->distance = $distance;
            $entity->minimumEntryRank = $minimumEntryRank;
            $entity->minimumStrength = $minimumStrength;
        } else {
            $entity = new Channel(
                $fromPort,
                $toPort,
                $bearing,
                $distance,
                $minimumStrength,
                $minimumEntryRank
            );
            $entity->id = $id;
        }
        $entity->uuid = $id->toString();

        $this->entityManager->persist($entity);
    }

    private function getPort(string $inputString): Port
    {
        $port = $this->entityManager->getPortRepo()->getByID(
            Uuid::fromString($inputString),
            Query::HYDRATE_OBJECT,
        );
        if ($port) {
            return $port;
        }
        throw new \InvalidArgumentException('No such port for ID ' . $inputString);
    }

    private function getPlayerRank(string $inputString): ?PlayerRank
    {
        if (empty($inputString)) {
            return null;
        }

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
