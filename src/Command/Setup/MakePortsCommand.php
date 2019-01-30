<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Data\Database\Entity\Port;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function App\Functions\Transforms\csvToArray;

class MakePortsCommand extends Command
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
            ->setName('game:init:make-ports')
            ->setDescription('Creates all the ports from the source data')
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
        $output->writeln('Making or updating the ports');

        $filePath = $input->getArgument('inputList');
        $sourceData = csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($sourceData as $data) {
            $id = Uuid::fromString($data['uuid']);
            $name = $data['name'];
            $isSafeHaven = (bool)$data['isSafeHaven'];
            $isOpen = (bool)$data['isOpen'];
            $isDestination = (bool)$data['isDestination'];

            $cluster = null;
            if ($data['cluster']) {
                $clusterId = Uuid::fromString($data['cluster']);
                $cluster = $this->entityManager->getClusterRepo()->getByID($clusterId, Query::HYDRATE_OBJECT);
            }

            /** @var Port $entity */
            $entity = $this->entityManager->getPortRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->name = $name;
                $entity->cluster = $cluster;
                $entity->isSafeHaven = $isSafeHaven;
                $entity->isOpen = $isOpen;
                $entity->isDestination = $isDestination;
            } else {
                $entity = new Port(
                    $name,
                    $cluster,
                    $isSafeHaven,
                    $isDestination,
                    $isOpen
                );
                $entity->id = $id;
            }
            $entity->uuid = (string)$id;

            $this->entityManager->persist($entity);
            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();

        $output->writeln('');
        $output->writeln('Done');
    }
}
