<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\Achievement;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\RankAchievement;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Transforms\csvToArray;

class MakeRankAchievementsCommand extends AbstractCommand
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
            ->setName('game:init:make-rank-achievements')
            ->setDescription('One off command for populating rank to achievements mapping')
            ->addArgument(
                'inputList',
                InputArgument::REQUIRED,
                'File path of data source (.csv)'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $output->writeln('Making the rank achievements');

        $filePath = $this->getStringArgument($input, 'inputList');
        $sourceData = csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        // delete all current
        $sql = 'DELETE FROM ' . RankAchievement::class;
        $this->entityManager->createQuery($sql)->execute();

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
        if (empty($data['rank_uuid'])) {
            return;
        }

        $rankId = Uuid::fromString($data['rank_uuid']);
        $achievementId = Uuid::fromString($data['achievement_uuid']);

        /** @var Achievement|null $entity */
        $achievementEntity = $this->entityManager->getAchievementRepo()->getByID($achievementId, Query::HYDRATE_OBJECT);

        /** @var PlayerRank|null $entity */
        $rankEntity = $this->entityManager->getPlayerRankRepo()->getByID($rankId, Query::HYDRATE_OBJECT);

        if (!$achievementEntity) {
            throw new \LogicException('No such achievement: ' . $achievementId->toString());
        }
        if (!$rankEntity) {
            throw new \LogicException('No such rank: ' . $rankId->toString());
        }

        $entity = new RankAchievement(
            $rankEntity,
            $achievementEntity,
        );

        $this->entityManager->persist($entity);
    }
}
