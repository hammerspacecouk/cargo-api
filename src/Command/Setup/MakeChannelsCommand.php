<?php
declare(strict_types=1);
namespace App\Command\Setup;

use App\Command\ParseCSVTrait;
use App\Data\Database\Entity\Channel;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\Port;
use App\Data\Database\EntityManager;
use App\Domain\ValueObject\Bearing;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeChannelsCommand extends Command
{
    use ParseCSVTrait;

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('game:setup:make-channels')
            ->setDescription('One off command for populating channel data')
            ->addArgument(
                'inputList',
                InputArgument::REQUIRED,
                'File path of data source (.csv)'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Making the channels between ports');

        $filePath = $input->getArgument('inputList');
        $sourceData = $this->csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($sourceData as $data) {
            if (empty($data['fromPortId'])) {
                $progress->advance();
                continue;
            }

            $id = Uuid::fromString($data['uuid']);
            $fromPort = $this->getPort($data['fromPortId']);
            $toPort = $this->getPort($data['toPortId']);

            if ((string) $fromPort->id === (string) $toPort->id) {
                throw new \InvalidArgumentException('To and From cannot be the same');
            }

            $bearing = Bearing::validate($data['bearing']);

            $distance = (int) $data['distance'];
            $minimumEntryRank = $this->getPlayerRank($data['minimumEntryRankId']);

            /** @var Channel $entity */
            $entity = $this->entityManager->getChannelRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->fromPort = $fromPort;
                $entity->toPort = $toPort;
                $entity->bearing = $bearing;
                $entity->distance = $distance;
                $entity->minimumEntryRank = $minimumEntryRank;
            } else {
                $entity = new Channel(
                    $id,
                    $fromPort,
                    $toPort,
                    $bearing,
                    $distance,
                    $minimumEntryRank
                );
            }

            $this->entityManager->persist($entity);
            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();

        $output->writeln('');
        $output->writeln('Done');
    }

    private function getPort(string $inputString): Port
    {
        $port = $this->entityManager->getPortRepo()->getByID(
            Uuid::fromString($inputString),
            Query::HYDRATE_OBJECT
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
            Query::HYDRATE_OBJECT
        );
        if ($rank) {
            return $rank;
        }
        throw new \InvalidArgumentException('No such rank for Minimum Rank');
    }
}
