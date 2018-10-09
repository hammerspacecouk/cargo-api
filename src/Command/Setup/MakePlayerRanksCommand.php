<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function App\Functions\Transforms\csvToArray;

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
            ->setName('game:init:make-player-ranks')
            ->setDescription('One off command for populating player ranks table')
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
        $output->writeln('Making the ranks');

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

            /** @var PlayerRank $entity */
            $entity = $this->entityManager->getPlayerRankRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->name = $data['name'];
                $entity->threshold = $data['threshold'];
                $entity->emblemSvg = $data['emblem'];
            } else {
                $entity = new PlayerRank(
                    $data['name'],
                    $data['emblem'],
                    (int)$data['threshold']
                );
                $entity->id = $id;
            }

            $this->entityManager->persist($entity);

            $progress->advance();
        }

        $progress->finish();

        $this->entityManager->flush();

        $output->writeln('');
        $output->writeln('Done');
    }
}
