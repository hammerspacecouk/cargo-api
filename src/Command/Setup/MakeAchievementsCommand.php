<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\Achievement;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Transforms\csvToArray;

class MakeAchievementsCommand extends AbstractCommand
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
            ->setName('game:init:make-achievements')
            ->setDescription('One off command for populating achievements data')
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
        $output->writeln('Making the achievements');

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
        if (empty($data['name']) || (bool)($data['ignore'] ?? false)) {
            return;
        }

        $id = Uuid::fromString($data['uuid']);
        $name = $data['name'];
        $description = $data['description'];
        $displayOrder = (int)$data['display_order'];
        $isHidden = (bool)$data['is_hidden'];
        $svg = $data['svg'];

        /** @var Achievement|null $entity */
        $entity = $this->entityManager->getAchievementRepo()->getByID($id, Query::HYDRATE_OBJECT);

        if ($entity) {
            $entity->name = $name;
            $entity->description = $description;
            $entity->displayOrder = $displayOrder;
            $entity->isHidden = $isHidden;
            $entity->svg = $svg;
        } else {
            $entity = new Achievement(
                $name,
                $description,
                $displayOrder,
                $isHidden,
                $svg,
            );
            $entity->id = $id;
        }
        $entity->uuid = $id->toString();

        $this->entityManager->persist($entity);
    }
}
