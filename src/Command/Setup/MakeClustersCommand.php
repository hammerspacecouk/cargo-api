<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\ParseCSVTrait;
use App\Data\Database\Entity\Cluster;
use App\Data\Database\Entity\Port;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeClustersCommand extends Command
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
            ->setName('game:init:make-clusters')
            ->setDescription('Creates the clusters from the source data')
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
        $output->writeln('Making or updating the clusters');

        $filePath = $input->getArgument('inputList');
        $sourceData = $this->csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($sourceData as $data) {
            $id = Uuid::fromString($data['uuid']);
            $name = $data['name'];

            if (empty($name)) {
                $progress->advance();
                continue;
            }

            /** @var Port $entity */
            $entity = $this->entityManager->getClusterRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->name = $name;
            } else {
                $entity = new Cluster(
                    $id,
                    $name
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
}
