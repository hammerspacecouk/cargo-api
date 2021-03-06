<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\Hint;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Transforms\csvToArray;

class MakeHintsCommand extends AbstractCommand
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
            ->setName('game:init:make-hints')
            ->setDescription('One off command for populating hints data')
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
        $output->writeln('Making the hints');

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
        if ((bool)($data['ignore'] ?? false)) {
            return;
        }
        $id = Uuid::fromString($data['uuid']);
        $text = $data['text'];

        $minimumRank = $this->getPlayerRank($data['minimumRankId']);

        $this->makeOrUpdateEntity($id, $text, $minimumRank);
    }

    private function makeOrUpdateEntity(
        UuidInterface $id,
        string $text,
        PlayerRank $playerRank
    ): void {
        /** @var Hint|null $entity */
        $entity = $this->entityManager->getHintRepo()->getByID($id, Query::HYDRATE_OBJECT);

        if ($entity) {
            $entity->text = $text;
            $entity->minimumRank = $playerRank;
        } else {
            $entity = new Hint(
                $text,
                $playerRank
            );
            $entity->id = $id;
        }
        $entity->uuid = $id->toString();

        $this->entityManager->persist($entity);
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
