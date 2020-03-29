<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Transforms\csvToArray;

class MakePlayerRanksCommand extends AbstractCommand
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
            ->setName('game:init:make-ranks')
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
    ): int {
        $output->writeln('Making the ranks');

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

            /** @var PlayerRank|null $entity */
            $entity = $this->entityManager->getPlayerRankRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->name = $data['name'];
                $entity->threshold = $data['threshold'];
                $entity->description = $data['description'];
            } else {
                $entity = new PlayerRank(
                    $data['name'],
                    $data['description'],
                    (int)$data['threshold']
                );
                $entity->id = $id;
            }
            $entity->uuid = $id->toString();

            $this->entityManager->persist($entity);

            $progress->advance();
        }

        $progress->finish();

        $this->entityManager->flush();

        $output->writeln('');
        $output->writeln('Done');

        return 0;
    }
}
