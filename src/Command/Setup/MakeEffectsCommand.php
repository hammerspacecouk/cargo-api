<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\Effect;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\EntityManager;
use function App\Functions\DateTimes\jsonDecode;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function App\Functions\Transforms\csvToArray;

class MakeEffectsCommand extends AbstractCommand
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
            ->setName('game:init:make-effects')
            ->setDescription('One off command for populating effects data')
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
        $output->writeln('Making the effects');

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
    }

    private function handleRow(array $data): void
    {
        $id = Uuid::fromString($data['uuid']);
        $type = $data['type'];
        if (empty($type)) {
            return;
        }

        $name = $data['name'];
        $displayGroup = $data['displayGroup'];
        $orderNumber = (int)$data['orderNumber'];
        $description = $data['description'];
        $svg = $data['svg'];
        $purchaseCost = !empty($data['purchaseCost']) ? (int)$data['purchaseCost'] : null;
        $duration = !empty($data['duration']) ? (int)$data['duration'] : null;
        $count = !empty($data['count']) ? (int)$data['count'] : null;
        $value = !empty($data['value']) ? jsonDecode($data['value']) : null;
        $oddsOfWinning = (int)$data['oddsOfWinning'];

        if (!$oddsOfWinning) {
            // this effect isn't ready. ignore it
            return;
        }

        $minimumRank = $this->getPlayerRank($data['minimumRankId']);

        /** @var Effect|null $entity */
        $entity = $this->entityManager->getEffectRepo()->getByID($id, Query::HYDRATE_OBJECT);

        if ($entity) {
            $entity->type = $type;
            $entity->name = $name;
            $entity->displayGroup = $displayGroup;
            $entity->orderNumber = $orderNumber;
            $entity->description = $description;
            $entity->oddsOfWinning = $oddsOfWinning;
            $entity->svg = $svg;
            $entity->minimumRank = $minimumRank;
        } else {
            $entity = new Effect(
                $type,
                $name,
                $displayGroup,
                $orderNumber,
                $description,
                $oddsOfWinning,
                $svg,
                $minimumRank
            );
            $entity->id = $id;
        }
        $entity->uuid = $id->toString();
        $entity->purchaseCost = $purchaseCost;
        $entity->duration = $duration;
        $entity->count = $count;
        $entity->value = $value;

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
