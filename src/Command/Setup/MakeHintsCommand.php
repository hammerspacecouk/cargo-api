<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Data\Database\Entity\Channel;
use App\Data\Database\Entity\Hint;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\Port;
use App\Data\Database\EntityManager;
use App\Domain\ValueObject\Bearing;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function App\Functions\Transforms\csvToArray;

class MakeHintsCommand extends Command
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
    ) {
        $output->writeln('Making the hints');

        $filePath = $input->getArgument('inputList');
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
    }

    private function handleRow(array $data): void
    {
        $id = Uuid::fromString($data['uuid']);
        $text = $data['text'];

        $minimumRank = $this->getPlayerRank($data['minimumRankId']);

        $this->makeOrUpdateEntity($id, $text, $minimumRank);
    }

    private function makeOrUpdateEntity(
        UuidInterface $id,
        string $text,
        ?PlayerRank $playerRank
    ): void {
        /** @var Hint $entity */
        $entity = $this->entityManager->getChannelRepo()->getByID($id, Query::HYDRATE_OBJECT);

        if ($entity) {
            $entity->text = $text;
            $entity->minimumRank = $playerRank;
        } else {
            $entity = new Hint(
                $text
            );
            $entity->minimumRank = $playerRank;
            $entity->id = $id;
        }

        $this->entityManager->persist($entity);
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
